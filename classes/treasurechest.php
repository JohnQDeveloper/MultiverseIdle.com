<?php

declare(strict_types=1);

class TreasureChest
{
    public const ENTRY_TYPE_CHEST = 'chest';
    public const ENTRY_TYPE_WYRDSTONE_NODE = 'wyrdstone_node';

    /** @var array<string, int> Multiplier X for each chest size (reward = 5000 * X) */
    public const CHEST_MULTIPLIERS = [
        'small'  => 1,
        'medium' => 2,
        'large'  => 3,
    ];

    /** @var array<string, int> Total number of PvP battles granted by each chest size */
    public const CHEST_BATTLES = [
        'small'  => 1,
        'medium' => 2,
        'large'  => 3,
    ];

    public const REWARD_BASE    = 5000;
    public const QUEUE_MAX      = 4;
    public const QUEUE_MAX_SUB  = 24;

    /** @var array<string, string> Maps wyrdstone node labels to shared queue sizes */
    public const WYRDSTONE_NODE_STORAGE_SIZES = [
        'minor'  => 'small',
        'medium' => 'medium',
        'large'  => 'large',
    ];

    /** @var array<string, int> Lucky wyrdstone rewards by node size */
    public const WYRDSTONE_NODE_REWARDS = [
        'small'  => 1,
        'medium' => 3,
        'large'  => 5,
    ];

    /**
     * Queue a new chest entry for the given user.
     */
    public function QueueChest(string $size, int $user_id, int $queue_position, ?int $season_id): bool
    {
        return $this->queueEntry($size, $user_id, $queue_position, $season_id, self::ENTRY_TYPE_CHEST);
    }

    public function QueueWyrdstoneNode(string $size, int $user_id, int $queue_position, ?int $season_id): bool
    {
        $storage_size = self::WYRDSTONE_NODE_STORAGE_SIZES[$size] ?? null;

        if ($storage_size === null) {
            return false;
        }

        return $this->queueEntry($storage_size, $user_id, $queue_position, $season_id, self::ENTRY_TYPE_WYRDSTONE_NODE);
    }

    /**
     * Queue a new PvP reward entry for the given user.
     */
    private function queueEntry(
        string $size,
        int $user_id,
        int $queue_position,
        ?int $season_id,
        string $entry_type
    ): bool
    {
        global $DAL;

        $queued = $DAL->w(
            "INSERT INTO treasure_chests (owner_id, chest_size, queue_position, season_id)
             VALUES (:owner_id, :chest_size, :queue_position, :season_id)",
            [
                ':owner_id'       => $user_id,
                ':chest_size'     => $size,
                ':queue_position' => $queue_position,
                ':season_id'      => $season_id,
            ]
        );

        if (!$queued) {
            return false;
        }

        $entry_id = (int)$DAL->last_insert_id();
        if ($entry_id <= 0) {
            return false;
        }

        $this->setQueueEntryType($entry_id, $entry_type);

        return true;
    }

    /**
     * Get all queued chests for a user, ordered by position ascending.
     *
     * @return array<int, array<string, mixed>>
     */
    public function GetQueuedChestsByOwner(int $user_id): array
    {
        global $DAL;
        $result = $DAL->r(
            "SELECT * FROM treasure_chests WHERE owner_id = :owner_id ORDER BY queue_position ASC",
            [':owner_id' => $user_id]
        );

        if (empty($result)) {
            return [];
        }

        foreach ($result as &$entry) {
            $entry['queue_type'] = $this->getQueueEntryType((int)$entry['id']);
        }
        unset($entry);

        return $result;
    }

    /**
     * Remove a chest from the queue by ID (ownership verified) and reorder the remainder.
     */
    public function RemoveChest(int $chest_id, int $user_id): bool
    {
        global $DAL;

        $record = $DAL->r(
            "SELECT queue_position FROM treasure_chests WHERE id = :id AND owner_id = :owner_id",
            [':id' => $chest_id, ':owner_id' => $user_id]
        );

        if (empty($record)) {
            return false;
        }

        $removed_position = (int)$record[0]['queue_position'];

        $DAL->w(
            "DELETE FROM treasure_chests WHERE id = :id AND owner_id = :owner_id",
            [':id' => $chest_id, ':owner_id' => $user_id]
        );

        if ($DAL->rows_affected() === 0) {
            return false;
        }

        $this->clearQueueEntryType($chest_id);

        // Shift all later positions down by one
        $DAL->w(
            "UPDATE treasure_chests SET queue_position = queue_position - 1
             WHERE owner_id = :owner_id AND queue_position > :removed_position",
            [':owner_id' => $user_id, ':removed_position' => $removed_position]
        );

        return true;
    }

    public function GetQueueEntryDisplaySize(array $entry): string
    {
        $storage_size = (string)($entry['chest_size'] ?? 'small');
        $queue_type = (string)($entry['queue_type'] ?? self::ENTRY_TYPE_CHEST);

        if ($queue_type !== self::ENTRY_TYPE_WYRDSTONE_NODE) {
            return $storage_size;
        }

        return array_search($storage_size, self::WYRDSTONE_NODE_STORAGE_SIZES, true) ?: $storage_size;
    }

    private function getQueueEntryType(int $entry_id): string
    {
        global $redis;

        $stored_type = $redis->get($this->getQueueEntryTypeKey($entry_id));

        if ($stored_type === self::ENTRY_TYPE_WYRDSTONE_NODE) {
            return self::ENTRY_TYPE_WYRDSTONE_NODE;
        }

        return self::ENTRY_TYPE_CHEST;
    }

    private function setQueueEntryType(int $entry_id, string $entry_type): void
    {
        global $redis;

        if ($entry_type === self::ENTRY_TYPE_CHEST) {
            $redis->del($this->getQueueEntryTypeKey($entry_id));
            return;
        }

        $redis->set($this->getQueueEntryTypeKey($entry_id), $entry_type);
    }

    private function clearQueueEntryType(int $entry_id): void
    {
        global $redis;
        $redis->del($this->getQueueEntryTypeKey($entry_id));
    }

    private function getQueueEntryTypeKey(int $entry_id): string
    {
        return 'pvp_queue_entry_type:' . $entry_id;
    }

    /**
     * Find a PvP opponent whose arena_floor is within +/-15% of the given floor.
     * The opponent must also have a chest queued at position 1.
     * Returns decoded character data (party_json already decoded), or null if no match.
     *
     * @return array<string, mixed>|null
     */
    public function FindOpponent(int $arena_floor, int $exclude_user_id, ?int $season_id): ?array
    {
        global $DAL;

        $min_floor = (int)floor($arena_floor * 0.85);
        $max_floor = (int)ceil($arena_floor * 1.15);

        $result = $DAL->r(
            "SELECT c.* FROM characters c
             INNER JOIN treasure_chests tc ON tc.owner_id = c.user_id
             WHERE c.user_id != :exclude_user_id
               AND c.arena_floor BETWEEN :min_floor AND :max_floor
               AND tc.queue_position = 1
               AND c.season_id <=> :season_id
             ORDER BY RAND()
             LIMIT 1",
            [
                ':exclude_user_id' => $exclude_user_id,
                ':min_floor'       => $min_floor,
                ':max_floor'       => $max_floor,
                ':season_id'       => $season_id,
            ]
        );

        if (empty($result)) {
            return null;
        }

        $char = $result[0];
        $char['party_json'] = json_decode((string)$char['party_json'], true);
        return $char;
    }
}
