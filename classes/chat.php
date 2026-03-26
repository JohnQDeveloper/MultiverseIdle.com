<?php

declare(strict_types=1);

class Chat
{
    private const MAX_MESSAGES = 200;
    private const RATE_LIMIT_MAX = 5;
    private const RATE_LIMIT_WINDOW = 10; // seconds
    private const MAX_MSG_LENGTH = 500;
    private const PUBLIC_CHANNELS = ['global', 'help'];

    private Redis $redis;
    private DAL $dal;

    public function __construct()
    {
        global $DAL, $redis;
        $this->dal = $DAL;
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
    public function getMessages(string $channel, int $sinceId = 0, int $limit = 50, int $viewerUserId = 0): array
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

        $messages = array_values(array_filter(array_map(
            fn(string $json) => json_decode($json, true),
            $raw
        )));

        if ($viewerUserId <= 0) {
            return $messages;
        }

        $ignoredUserIds = $this->getIgnoredUserIds($viewerUserId);
        if ($ignoredUserIds === []) {
            return $messages;
        }

        return array_values(array_filter(
            $messages,
            static fn(array $message): bool => !in_array((int)($message['user_id'] ?? 0), $ignoredUserIds, true)
        ));
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
        $muteInfo = $this->getActiveMuteRow($userId);
        if ($muteInfo === null) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, string>|null
     */
    public function getMuteInfo(int $userId): ?array
    {
        $muteInfo = $this->getActiveMuteRow($userId);
        if ($muteInfo === null) {
            return null;
        }

        return [
            'until' => $muteInfo['expires_at'] === null ? '-1' : (string)strtotime($muteInfo['expires_at']),
            'reason' => (string)$muteInfo['reason'],
            'muted_by' => (string)$muteInfo['muted_by'],
        ];
    }

    /**
     * Mute a user. Pass $seconds = -1 for a permanent mute.
     */
    public function muteUser(int $userId, int $seconds, string $reason, int $mutedBy): bool
    {
        $expiresAt = $seconds === -1
            ? null
            : date('Y-m-d H:i:s', time() + $seconds);

        $result = $this->dal->w(
            'INSERT INTO chat_mutes (user_id, muted_by, reason, expires_at)
             VALUES (:user_id, :muted_by, :reason, :expires_at)
             ON DUPLICATE KEY UPDATE
                muted_by = VALUES(muted_by),
                reason = VALUES(reason),
                expires_at = VALUES(expires_at),
                updated_at = CURRENT_TIMESTAMP',
            [
                ':user_id' => $userId,
                ':muted_by' => $mutedBy,
                ':reason' => $reason,
                ':expires_at' => $expiresAt,
            ]
        );

        if (!$result) {
            return false;
        }

        return $this->getActiveMuteRow($userId) !== null;
    }

    public function unmuteUser(int $userId): bool
    {
        $result = $this->dal->w(
            'DELETE FROM chat_mutes WHERE user_id = :user_id',
            [':user_id' => $userId]
        );

        if (!$result) {
            return false;
        }

        return $this->getActiveMuteRow($userId) === null;
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
        $rows = $this->dal->r(
            'SELECT user_id
             FROM chat_moderators
             WHERE user_id = :user_id
             LIMIT 1',
            [':user_id' => $userId]
        );

        if (!empty($rows)) {
            return true;
        }

        return $this->isSiteAdmin($userId);
    }

    public function promoteModerator(int $userId, int $promotedBy = 0): bool
    {
        $result = $this->dal->w(
            'INSERT INTO chat_moderators (user_id, promoted_by)
             VALUES (:user_id, :promoted_by)
             ON DUPLICATE KEY UPDATE promoted_by = VALUES(promoted_by)',
            [
                ':user_id' => $userId,
                ':promoted_by' => $promotedBy > 0 ? $promotedBy : null,
            ]
        );

        if (!$result) {
            return false;
        }

        return $this->hasModeratorRecord($userId);
    }

    public function demoteModerator(int $userId): bool
    {
        $result = $this->dal->w(
            'DELETE FROM chat_moderators WHERE user_id = :user_id',
            [':user_id' => $userId]
        );

        if (!$result) {
            return false;
        }

        return !$this->hasModeratorRecord($userId);
    }

    /**
     * @return int[]
     */
    public function getModeratorIds(): array
    {
        $rows = $this->dal->r('SELECT user_id FROM chat_moderators');
        if (empty($rows)) {
            return [];
        }

        return array_map(
            static fn(array $row): int => (int)$row['user_id'],
            $rows
        );
    }

    // -------------------------------------------------------------------------
    // Site admin check (read-only DB query)
    // -------------------------------------------------------------------------

    private function isSiteAdmin(int $userId): bool
    {
        $rows = $this->dal->r(
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

    private function hasModeratorRecord(int $userId): bool
    {
        $rows = $this->dal->r(
            'SELECT user_id
             FROM chat_moderators
             WHERE user_id = :user_id
             LIMIT 1',
            [':user_id' => $userId]
        );

        return !empty($rows);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getActiveMuteRow(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        $rows = $this->dal->r(
            'SELECT user_id, muted_by, reason, expires_at
             FROM chat_mutes
             WHERE user_id = :user_id
             LIMIT 1',
            [':user_id' => $userId]
        );

        if (empty($rows)) {
            return null;
        }

        $muteInfo = $rows[0];
        $expiresAt = $muteInfo['expires_at'];
        if ($expiresAt !== null && strtotime((string)$expiresAt) <= time()) {
            $this->unmuteUser($userId);
            return null;
        }

        return $muteInfo;
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

        $ignoredUserIds = $this->getIgnoredUserIds($userId);
        $result = [];
        foreach ($partners as $partnerId) {
            $pid  = (int)$partnerId;
            if (in_array($pid, $ignoredUserIds, true)) {
                continue;
            }

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
    // Ignore system
    // -------------------------------------------------------------------------

    public function ignoreUser(int $userId, int $ignoredUserId): bool
    {
        if ($userId <= 0 || $ignoredUserId <= 0 || $userId === $ignoredUserId) {
            return false;
        }

        $result = $this->dal->w(
            'INSERT INTO chat_ignores (user_id, ignored_user_id)
             VALUES (:user_id, :ignored_user_id)
             ON DUPLICATE KEY UPDATE created_at = created_at',
            [
                ':user_id' => $userId,
                ':ignored_user_id' => $ignoredUserId,
            ]
        );

        if (!$result) {
            return false;
        }

        return $this->isIgnoringUser($userId, $ignoredUserId);
    }

    public function unignoreUser(int $userId, int $ignoredUserId): bool
    {
        if ($userId <= 0 || $ignoredUserId <= 0) {
            return false;
        }

        $result = $this->dal->w(
            'DELETE FROM chat_ignores
             WHERE user_id = :user_id
               AND ignored_user_id = :ignored_user_id',
            [
                ':user_id' => $userId,
                ':ignored_user_id' => $ignoredUserId,
            ]
        );

        if (!$result) {
            return false;
        }

        return !$this->isIgnoringUser($userId, $ignoredUserId);
    }

    public function isIgnoringUser(int $userId, int $ignoredUserId): bool
    {
        if ($userId <= 0 || $ignoredUserId <= 0) {
            return false;
        }

        $rows = $this->dal->r(
            'SELECT ignored_user_id
             FROM chat_ignores
             WHERE user_id = :user_id
               AND ignored_user_id = :ignored_user_id
             LIMIT 1',
            [
                ':user_id' => $userId,
                ':ignored_user_id' => $ignoredUserId,
            ]
        );

        return !empty($rows);
    }

    /**
     * @return int[]
     */
    public function getIgnoredUserIds(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $rows = $this->dal->r(
            'SELECT ignored_user_id
             FROM chat_ignores
             WHERE user_id = :user_id
             ORDER BY ignored_user_id ASC',
            [':user_id' => $userId]
        );

        if (empty($rows)) {
            return [];
        }

        return array_map(
            static fn(array $row): int => (int)$row['ignored_user_id'],
            $rows
        );
    }

    /**
     * @return array<int, array{id: int, username: string}>
     */
    public function getIgnoredUsers(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $rows = $this->dal->r(
            'SELECT users.id, users.username
             FROM chat_ignores
             INNER JOIN users ON users.id = chat_ignores.ignored_user_id
             WHERE chat_ignores.user_id = :user_id
             ORDER BY users.username ASC',
            [':user_id' => $userId]
        );

        if (empty($rows)) {
            return [];
        }

        return array_map(
            static fn(array $row): array => [
                'id' => (int)$row['id'],
                'username' => (string)$row['username'],
            ],
            $rows
        );
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
