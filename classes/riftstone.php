<?php

declare(strict_types=1);

class RiftStone
{
    private object $DAL;

    /**
     * @var array<string, mixed> Rift stone data including id, name, implicit, affixes, level, owner_id
     */
    public array $Data = [];

    public function __construct()
    {
        global $DAL;
        $this->DAL = $DAL;
    }

    /**
     * Get rift stone implicit definitions (player selects one)
     *
     * @return array<string, array<string, int|string>>
     */
    public static function getImplicitDefinitions(): array
    {
        return [
            'gold' => ['name' => 'Gold Bonus', 'bonus' => 60, 'description' => '+60% Gold compared to an Arena Floor'],
            'xp' => ['name' => 'XP Bonus', 'bonus' => 60, 'description' => '+60% XP compared to an Arena Floor'],
            'resource_drop' => ['name' => 'Resource Drop Bonus', 'bonus' => 60, 'description' => '+60% Random Resource Drop compared to an Arena Floor'],
            'stat_gains' => ['name' => 'Stat Gains Bonus', 'bonus' => 60, 'description' => '+60% Stat Gains compared to an Arena Floor'],
        ];
    }

    /**
     * Get rift stone random affix definitions (3 random selected, can repeat)
     *
     * @return array<string, array<string, int|string>>
     */
    public static function getAffixDefinitions(): array
    {
        return [
            'monster_skill' => ['name' => 'Random Monster Skill', 'bonus' => 1, 'description' => '+1 Random Monster Skill', 'category' => 'difficulty'],
            'monster_damage' => ['name' => 'Monster Damage', 'bonus' => 20, 'description' => '+20% Monster Damage', 'category' => 'difficulty'],
            'monster_strength' => ['name' => 'Monster Strength', 'bonus' => 20, 'description' => '+20% Monster Strength', 'category' => 'difficulty'],
            'monster_dexterity' => ['name' => 'Monster Dexterity', 'bonus' => 20, 'description' => '+20% Monster Dexterity', 'category' => 'difficulty'],
            'monster_health' => ['name' => 'Monster Health', 'bonus' => 20, 'description' => '+20% Monster Health', 'category' => 'difficulty'],
            'monster_wisdom' => ['name' => 'Monster Wisdom', 'bonus' => 20, 'description' => '+20% Monster Wisdom', 'category' => 'difficulty'],
            ESSENCE_RIFT_AFFIX_KEY => [
                'name' => 'Essence Drop Chance',
                'bonus' => ESSENCE_RIFT_EXTRA_DROP_CHANCE_PER_AFFIX,
                'description' => '+' . ESSENCE_RIFT_EXTRA_DROP_CHANCE_PER_AFFIX . '% chance to find 1 additional random essence on completion',
                'category' => 'reward',
            ],
        ];
    }

    public static function isRewardAffix(string $affix_key): bool
    {
        $definitions = self::getAffixDefinitions();

        return ($definitions[$affix_key]['category'] ?? 'difficulty') === 'reward';
    }

    /**
     * Create a new rift stone
     *
     * @param array<string, mixed> $rift_details Details including name, implicit, affixes, level
     * @return string|false New rift stone ID or false on failure
     */
    public function CreateRiftStone(array $rift_details): string|false
    {
        $this->DAL->w(
            "INSERT INTO rifts SET created_at=NOW(), details=:details, queue_position=NULL, market_price=0, owner_id=:owner_id",
            [
                ':details' => json_encode($rift_details),
                ':owner_id' => $_SESSION['auth_user_id'] ?? null
            ]
        );

        return $this->DAL->last_insert_id();
    }

    /**
     * Load rift stone by ID
     *
     * @param int $rift_stone_id Rift stone ID
     * @return bool True if rift stone loaded successfully, false otherwise
     */
    public function LoadRiftStoneByID(int $rift_stone_id): bool
    {
        $rift_stone_records = $this->DAL->r("SELECT * FROM rifts WHERE id=:id", [
            ':id' => $rift_stone_id
        ]);

        if ($rift_stone_records && !empty($rift_stone_records)) {
            $record = $rift_stone_records[0];
            $this->Data = json_decode($record['details'], true);
            $this->Data['id'] = $record['id'];
            $this->Data['owner_id'] = $record['owner_id'];
            $this->Data['queue_position'] = $record['queue_position'];
            $this->Data['market_price'] = $record['market_price'];
            return true;
        }

        return false;
    }

    /**
     * Get all rift stones owned by a user
     *
     * @param int $owner_id User ID of the owner
     * @return array<int, array<string, mixed>> Array of rift stones
     */
    public function GetAllRiftStonesByOwner(int $owner_id): array
    {
        $rift_stone_records = $this->DAL->r(
            "SELECT * FROM rifts WHERE owner_id=:owner_id ORDER BY created_at DESC",
            [':owner_id' => $owner_id]
        );

        $rift_stones = [];
        if ($rift_stone_records) {
            foreach ($rift_stone_records as $record) {
                $rift_stone = json_decode($record['details'], true);
                $rift_stone['id'] = $record['id'];
                $rift_stone['owner_id'] = $record['owner_id'];
                $rift_stone['queue_position'] = $record['queue_position'];
                $rift_stone['market_price'] = $record['market_price'];
                $rift_stone['created_at'] = $record['created_at'];
                $rift_stones[] = $rift_stone;
            }
        }
        return $rift_stones;
    }

    /**
     * Delete a rift stone
     *
     * @param int $rift_stone_id Rift stone ID
     * @param int $owner_id Owner user ID (for security check)
     * @return bool True if rift stone was deleted, false otherwise
     */
    public function DestroyRiftStone(int $rift_stone_id, int $owner_id): bool
    {
        $this->DAL->w("DELETE FROM rifts WHERE id=:id AND owner_id=:owner_id", [
            ':id' => $rift_stone_id,
            ':owner_id' => $owner_id
        ]);
        return $this->DAL->rows_affected() > 0;
    }

    /**
     * Verify that a user owns a specific rift stone
     *
     * @param int $rift_stone_id Rift stone ID
     * @param int $owner_id User ID to verify ownership
     * @return bool True if user owns the rift stone, false otherwise
     */
    public function VerifyOwnership(int $rift_stone_id, int $owner_id): bool
    {
        $rift_stone_record = $this->DAL->r("SELECT id FROM rifts WHERE id=:id AND owner_id=:owner_id", [
            ':id' => $rift_stone_id,
            ':owner_id' => $owner_id
        ]);
        return !empty($rift_stone_record);
    }

    /**
     * Get available (not queued) rift stones owned by a user
     *
     * @param int $owner_id User ID of the owner
     * @return array<int, array<string, mixed>> Array of available rift stones
     */
    public function GetAvailableRiftStonesByOwner(int $owner_id): array
    {
        $rift_stone_records = $this->DAL->r(
            "SELECT * FROM rifts WHERE owner_id=:owner_id AND queue_position IS NULL ORDER BY created_at DESC",
            [':owner_id' => $owner_id]
        );

        $rift_stones = [];
        if ($rift_stone_records) {
            foreach ($rift_stone_records as $record) {
                $rift_stone = json_decode($record['details'], true);
                $rift_stone['id'] = $record['id'];
                $rift_stone['owner_id'] = $record['owner_id'];
                $rift_stone['queue_position'] = $record['queue_position'];
                $rift_stone['market_price'] = $record['market_price'];
                $rift_stone['created_at'] = $record['created_at'];
                $rift_stones[] = $rift_stone;
            }
        }
        return $rift_stones;
    }

    /**
     * Get queued rift stones owned by a user
     *
     * @param int $owner_id User ID of the owner
     * @return array<int, array<string, mixed>> Array of queued rift stones ordered by position
     */
    public function GetQueuedRiftsByOwner(int $owner_id): array
    {
        $rift_stone_records = $this->DAL->r(
            "SELECT * FROM rifts WHERE owner_id=:owner_id AND queue_position IS NOT NULL ORDER BY queue_position ASC",
            [':owner_id' => $owner_id]
        );

        $rift_stones = [];
        if ($rift_stone_records) {
            foreach ($rift_stone_records as $record) {
                $rift_stone = json_decode($record['details'], true);
                $rift_stone['id'] = $record['id'];
                $rift_stone['owner_id'] = $record['owner_id'];
                $rift_stone['queue_position'] = $record['queue_position'];
                $rift_stone['market_price'] = $record['market_price'];
                $rift_stone['created_at'] = $record['created_at'];
                $rift_stones[] = $rift_stone;
            }
        }
        return $rift_stones;
    }

    /**
     * Queue a rift stone for running
     *
     * @param int $rift_stone_id Rift stone ID
     * @param int $position Queue position (1-6)
     * @param int $owner_id Owner user ID (for security check)
     * @return bool True if rift was queued, false otherwise
     */
    public function QueueRift(int $rift_stone_id, int $position, int $owner_id): bool
    {
        $this->DAL->w(
            "UPDATE rifts SET queue_position=:position WHERE id=:id AND owner_id=:owner_id",
            [
                ':position' => $position,
                ':id' => $rift_stone_id,
                ':owner_id' => $owner_id
            ]
        );
        return $this->DAL->rows_affected() > 0;
    }

    /**
     * Remove a rift stone from queue and reorder remaining rifts
     *
     * @param int $rift_stone_id Rift stone ID
     * @param int $owner_id Owner user ID (for security check)
     * @return bool True if rift was removed from queue, false otherwise
     */
    public function RemoveFromQueue(int $rift_stone_id, int $owner_id): bool
    {
        # Get the current position of the rift being removed
        $rift_record = $this->DAL->r(
            "SELECT queue_position FROM rifts WHERE id=:id AND owner_id=:owner_id",
            [':id' => $rift_stone_id, ':owner_id' => $owner_id]
        );

        if (empty($rift_record) || $rift_record[0]['queue_position'] === null) {
            return false;
        }

        $removed_position = (int)$rift_record[0]['queue_position'];

        # Remove from queue
        $this->DAL->w(
            "UPDATE rifts SET queue_position=NULL WHERE id=:id AND owner_id=:owner_id",
            [':id' => $rift_stone_id, ':owner_id' => $owner_id]
        );

        # Reorder remaining rifts (decrement position for all rifts after the removed one)
        $this->DAL->w(
            "UPDATE rifts SET queue_position = queue_position - 1 WHERE owner_id=:owner_id AND queue_position > :removed_position",
            [':owner_id' => $owner_id, ':removed_position' => $removed_position]
        );

        return true;
    }
}
