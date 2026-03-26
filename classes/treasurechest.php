<?php

declare(strict_types=1);

class TreasureChest
{
    /** @var array<string, int> Multiplier X for each chest size (reward = 5000 * X) */
    public const CHEST_MULTIPLIERS = [
        'small'  => 1,
        'medium' => 2,
        'large'  => 3,
    ];

    public const REWARD_BASE    = 5000;
    public const QUEUE_MAX      = 4;
    public const QUEUE_MAX_SUB  = 24;

    /**
     * Queue a new chest entry for the given user.
     */
    public function QueueChest(string $size, int $user_id, int $queue_position, ?int $season_id): bool
    {
        global $DAL;
        return $DAL->w(
            "INSERT INTO treasure_chests (owner_id, chest_size, queue_position, season_id)
             VALUES (:owner_id, :chest_size, :queue_position, :season_id)",
            [
                ':owner_id'       => $user_id,
                ':chest_size'     => $size,
                ':queue_position' => $queue_position,
                ':season_id'      => $season_id,
            ]
        );
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
        return $result ?: [];
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

        // Shift all later positions down by one
        $DAL->w(
            "UPDATE treasure_chests SET queue_position = queue_position - 1
             WHERE owner_id = :owner_id AND queue_position > :removed_position",
            [':owner_id' => $user_id, ':removed_position' => $removed_position]
        );

        return true;
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
