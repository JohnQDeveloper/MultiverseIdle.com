<?php

declare(strict_types=1);

class Chat
{
    private const MAX_MESSAGES     = 200;
    private const RATE_LIMIT_MAX   = 5;
    private const RATE_LIMIT_WINDOW = 10; // seconds
    private const MAX_MSG_LENGTH   = 500;
    private const PUBLIC_CHANNELS  = ['global', 'help'];

    private Redis $redis;

    public function __construct()
    {
        global $redis;
        $this->redis = $redis;
    }

    // -------------------------------------------------------------------------
    // Messaging
    // -------------------------------------------------------------------------

    /**
     * Send a message to a channel.
     * Returns the stored message array on success, false on failure.
     *
     * @return array<string, mixed>|false
     */
    public function sendMessage(string $channel, int $userId, string $username, string $message): array|false
    {
        if (!$this->isValidChannel($channel)) {
            return false;
        }

        if ($this->isMuted($userId)) {
            return false;
        }

        if ($this->isRateLimited($userId)) {
            return false;
        }

        $message = trim($message);
        $len = mb_strlen($message);
        if ($len < 1 || $len > self::MAX_MSG_LENGTH) {
            return false;
        }

        $messageId = (int)$this->redis->incr("chat:seq:{$channel}");
        $isMod     = $this->isModerator($userId);

        $payload = [
            'id'        => $messageId,
            'user_id'   => $userId,
            'username'  => $username,
            'message'   => $message,
            'timestamp' => time(),
            'is_mod'    => $isMod,
        ];

        $key = "chat:messages:{$channel}";
        $this->redis->zAdd($key, [], $messageId, json_encode($payload));

        // Keep only the newest MAX_MESSAGES entries
        $this->redis->zRemRangeByRank($key, 0, -(self::MAX_MESSAGES + 1));

        $this->incrementRateLimit($userId);

        return $payload;
    }

    /**
     * Fetch messages newer than $sinceId (0 = fetch the last $limit messages).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMessages(string $channel, int $sinceId = 0, int $limit = 50): array
    {
        if (!$this->isValidChannel($channel)) {
            return [];
        }

        $key    = "chat:messages:{$channel}";
        $min    = $sinceId > 0 ? "({$sinceId}" : '-inf';
        $raw    = $this->redis->zRangeByScore($key, $min, '+inf', ['limit' => [0, $limit]]);

        if (empty($raw)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn(string $json) => json_decode($json, true),
            $raw
        )));
    }

    // -------------------------------------------------------------------------
    // Channel validation
    // -------------------------------------------------------------------------

    public function isValidChannel(string $channel): bool
    {
        if (in_array($channel, self::PUBLIC_CHANNELS, true)) {
            return true;
        }
        if ((bool)preg_match('/^guild:\d+$/', $channel)) {
            return true;
        }
        return $this->isDMChannel($channel);
    }

    public function isDMChannel(string $channel): bool
    {
        return (bool)preg_match('/^dm:\d+:\d+$/', $channel);
    }

    /**
     * Verify the requesting user is a member of the guild the channel belongs to.
     */
    public function validateGuildAccess(string $channel, int $userId): bool
    {
        if (!preg_match('/^guild:(\d+)$/', $channel, $matches)) {
            return false;
        }
        $guildId  = (int)$matches[1];
        $Guild    = new Guild();
        $userGuildId = $Guild->GetUserGuildId($userId);
        return $userGuildId === $guildId;
    }

    // -------------------------------------------------------------------------
    // Mute system
    // -------------------------------------------------------------------------

    public function isMuted(int $userId): bool
    {
        $data = $this->redis->hGetAll("chat:mute:{$userId}");
        if (empty($data)) {
            return false;
        }
        $until = (int)($data['until'] ?? 0);
        if ($until === -1) {
            return true; // permanent mute
        }
        return time() < $until;
    }

    /**
     * @return array<string, string>|null
     */
    public function getMuteInfo(int $userId): ?array
    {
        $data = $this->redis->hGetAll("chat:mute:{$userId}");
        return empty($data) ? null : $data;
    }

    /**
     * Mute a user. Pass $seconds = -1 for a permanent mute.
     */
    public function muteUser(int $userId, int $seconds, string $reason, int $mutedBy): void
    {
        $key   = "chat:mute:{$userId}";
        $until = ($seconds === -1) ? -1 : time() + $seconds;

        $this->redis->hMSet($key, [
            'until'    => (string)$until,
            'reason'   => $reason,
            'muted_by' => (string)$mutedBy,
        ]);

        if ($seconds !== -1) {
            $this->redis->expire($key, $seconds);
        } else {
            // Remove TTL so the key persists
            $this->redis->persist($key);
        }
    }

    public function unmuteUser(int $userId): void
    {
        $this->redis->del("chat:mute:{$userId}");
    }

    // -------------------------------------------------------------------------
    // Moderator system
    // -------------------------------------------------------------------------

    /**
     * Returns true if the user is a chat moderator OR a site admin (roles_mask > 0).
     */
    public function isModerator(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }
        if ((bool)$this->redis->sIsMember('chat:moderators', (string)$userId)) {
            return true;
        }
        return $this->isSiteAdmin($userId);
    }

    public function promoteModerator(int $userId): void
    {
        $this->redis->sAdd('chat:moderators', (string)$userId);
    }

    public function demoteModerator(int $userId): void
    {
        $this->redis->sRem('chat:moderators', (string)$userId);
    }

    /**
     * @return int[]
     */
    public function getModeratorIds(): array
    {
        $members = $this->redis->sMembers('chat:moderators');
        return array_map('intval', $members ?: []);
    }

    // -------------------------------------------------------------------------
    // Site admin check (read-only DB query)
    // -------------------------------------------------------------------------

    private function isSiteAdmin(int $userId): bool
    {
        global $DAL;
        $rows = $DAL->r(
            'SELECT roles_mask FROM users WHERE id = :id LIMIT 1',
            [':id' => $userId]
        );
        if (empty($rows)) {
            return false;
        }
        return (int)$rows[0]['roles_mask'] > 0;
    }

    // -------------------------------------------------------------------------
    // Direct messages
    // -------------------------------------------------------------------------

    /**
     * Returns the normalised DM channel string for two users.
     * Uses min/max so both directions share the same key.
     */
    public static function getDMChannelId(int $userId1, int $userId2): string
    {
        $a = min($userId1, $userId2);
        $b = max($userId1, $userId2);
        return "dm:{$a}:{$b}";
    }

    /**
     * Verify the requesting user is one of the two participants.
     */
    public function validateDMAccess(string $channel, int $userId): bool
    {
        if (!preg_match('/^dm:(\d+):(\d+)$/', $channel, $m)) {
            return false;
        }
        return (int)$m[1] === $userId || (int)$m[2] === $userId;
    }

    /**
     * Send a direct message. Bypasses mute checks; uses shared rate limit.
     *
     * @return array<string, mixed>|false
     */
    public function sendDM(string $channel, int $fromUserId, string $fromUsername, int $toUserId, string $message): array|false
    {
        if (!$this->isDMChannel($channel) || !$this->validateDMAccess($channel, $fromUserId)) {
            return false;
        }

        if ($this->isMuted($fromUserId)) {
            return false;
        }

        if ($this->isRateLimited($fromUserId)) {
            return false;
        }

        $message = trim($message);
        $len = mb_strlen($message);
        if ($len < 1 || $len > self::MAX_MSG_LENGTH) {
            return false;
        }

        $messageId = (int)$this->redis->incr("chat:seq:{$channel}");
        $isMod     = $this->isModerator($fromUserId);

        $payload = [
            'id'        => $messageId,
            'user_id'   => $fromUserId,
            'username'  => $fromUsername,
            'message'   => $message,
            'timestamp' => time(),
            'is_mod'    => $isMod,
        ];

        $key = "chat:messages:{$channel}";
        $this->redis->zAdd($key, [], $messageId, json_encode($payload));
        $this->redis->zRemRangeByRank($key, 0, -(self::MAX_MESSAGES + 1));

        // Update conversation indexes so both parties see the thread
        $ts = time();
        $this->redis->zAdd("dm:conversations:{$fromUserId}", [], $ts, (string)$toUserId);
        $this->redis->zAdd("dm:conversations:{$toUserId}", [], $ts, (string)$fromUserId);

        $this->incrementRateLimit($fromUserId);

        return $payload;
    }

    /**
     * Return up to 20 recent DM conversation partners for a user.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDMConversations(int $userId): array
    {
        global $DAL;

        $partners = $this->redis->zRevRangeByScore(
            "dm:conversations:{$userId}",
            '+inf',
            '-inf',
            ['limit' => [0, 20]]
        );

        if (empty($partners)) {
            return [];
        }

        $result = [];
        foreach ($partners as $partnerId) {
            $pid  = (int)$partnerId;
            $ts   = (int)$this->redis->zScore("dm:conversations:{$userId}", $partnerId);
            $rows = $DAL->r('SELECT username FROM users WHERE id = :id LIMIT 1', [':id' => $pid]);
            $username = !empty($rows) ? $rows[0]['username'] : "User #{$pid}";

            $result[] = [
                'user_id'  => $pid,
                'username' => $username,
                'last_ts'  => $ts,
            ];
        }

        return $result;
    }

    /**
     * Find a user by exact username (case-sensitive).
     *
     * @return array{id: int, username: string}|null
     */
    public function findUserByUsername(string $username): ?array
    {
        global $DAL;

        $rows = $DAL->r(
            'SELECT id, username FROM users WHERE username = :username LIMIT 1',
            [':username' => $username]
        );

        if (empty($rows)) {
            return null;
        }

        return ['id' => (int)$rows[0]['id'], 'username' => $rows[0]['username']];
    }

    // -------------------------------------------------------------------------
    // Rate limiting
    // -------------------------------------------------------------------------

    private function isRateLimited(int $userId): bool
    {
        return (int)$this->redis->get("chat:ratelimit:{$userId}") >= self::RATE_LIMIT_MAX;
    }

    private function incrementRateLimit(int $userId): void
    {
        $key   = "chat:ratelimit:{$userId}";
        $count = $this->redis->incr($key);
        if ($count === 1) {
            $this->redis->expire($key, self::RATE_LIMIT_WINDOW);
        }
    }
}
