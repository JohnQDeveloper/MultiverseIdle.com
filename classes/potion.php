<?php

declare(strict_types=1);

class Potion
{
    private const POTION_DURATION_HOURS = 24;

    private object $DAL;

    /**
     * @var array<string, mixed>|null Potion data including id, name, prefix, suffix, level, owner_id
     */
    public ?array $Data = null;

    public function __construct()
    {
        global $DAL;
        $this->DAL = $DAL;
    }

    /**
     * Get default bonus structure with all bonuses set to zero
     *
     * @return array<string, int>
     */
    private function getDefaultBonuses(): array
    {
        return [
            'herb_worker_yield' => 0,
            'gold_worker_yield' => 0,
            'iron_worker_yield' => 0,
            'gems_worker_yield' => 0,
            'arena_resource_drops' => 0,
            'rift_drops' => 0,
            'arena_xp' => 0,
            'arena_stat_gains' => 0,
            'rift_xp' => 0,
            'rift_stat_gains' => 0,
            'world_boss_xp' => 0,
        ];
    }

    /**
     * Get potion prefix definitions
     *
     * @return array<string, array<string, int>>
     */
    private function getPrefixDefinitions(): array
    {
        return [
            'herb_worker_yield' => ['per_level' => 1],
            'gold_worker_yield' => ['per_level' => 1],
            'iron_worker_yield' => ['per_level' => 1],
            'gems_worker_yield' => ['per_level' => 1],
            'arena_resource_drops' => ['per_level' => 1],
            'rift_drops' => ['per_level' => 1],
        ];
    }

    /**
     * Get potion suffix definitions
     *
     * @return array<string, array<string, int>>
     */
    private function getSuffixDefinitions(): array
    {
        return [
            'arena_xp' => ['per_level' => 1],
            'arena_stat_gains' => ['per_level' => 1],
            'rift_xp' => ['per_level' => 1],
            'rift_stat_gains' => ['per_level' => 1],
            'world_boss_xp' => ['per_level' => 25],
        ];
    }

    /**
     * Create a new potion
     *
     * @param string $name Potion name
     * @param string $prefix Potion prefix (bonus type)
     * @param string $suffix Potion suffix (bonus type)
     * @param int $level Potion level (determines bonus strength)
     * @return string|false New potion ID or false on failure
     */
    public function CreatePotion(string $name, string $prefix, string $suffix, int $level): string|false
    {
        $this->DAL->w(
            "INSERT INTO potions SET created_at=NOW(), name=:name, prefix=:prefix, suffix=:suffix, level=:level, owner_id=:owner_id",
            [
                ':name' => $name,
                ':prefix' => $prefix,
                ':suffix' => $suffix,
                ':level' => $level,
                ':owner_id' => $_SESSION['auth_user_id'] ?? null
            ]
        );

        return $this->DAL->last_insert_id();
    }

    /**
     * Load potion by ID
     *
     * @param int $potion_id Potion ID
     * @return bool True if potion loaded successfully, false otherwise
     */
    public function LoadPotionByID(int $potion_id): bool
    {
        $potion_records = $this->DAL->r("SELECT * FROM potions WHERE id=:id", [
            ':id' => $potion_id
        ]);

        if ($potion_records && !empty($potion_records)) {
            $potion_record = $potion_records[0];
            $this->Data = [
                'id' => $potion_record['id'],
                'name' => $potion_record['name'],
                'prefix' => $potion_record['prefix'],
                'suffix' => $potion_record['suffix'],
                'level' => (int)$potion_record['level'],
                'owner_id' => $potion_record['owner_id'],
                'created_at' => $potion_record['created_at']
            ];
            return true;
        }

        return false;
    }

    /**
     * Get all potions owned by a user
     *
     * @param int $owner_id User ID of the owner
     * @return array<int, array<string, mixed>> Array of potions
     */
    public function GetAllPotionsByOwner(int $owner_id): array
    {
        $potion_records = $this->DAL->r(
            "SELECT * FROM potions WHERE owner_id=:owner_id ORDER BY created_at DESC",
            [':owner_id' => $owner_id]
        );

        $potions = [];
        if ($potion_records) {
            foreach ($potion_records as $record) {
                $potions[] = [
                    'id' => $record['id'],
                    'name' => $record['name'],
                    'prefix' => $record['prefix'],
                    'suffix' => $record['suffix'],
                    'level' => (int)$record['level'],
                    'owner_id' => $record['owner_id'],
                    'created_at' => $record['created_at']
                ];
            }
        }
        return $potions;
    }

    /**
     * Delete a potion
     *
     * @param int $potion_id Potion ID
     * @param int $owner_id Owner user ID (for security check)
     * @return bool True if potion was deleted, false otherwise
     */
    public function DestroyPotion(int $potion_id, int $owner_id): bool
    {
        $this->DAL->w("DELETE FROM potions WHERE id=:id AND owner_id=:owner_id", [
            ':id' => $potion_id,
            ':owner_id' => $owner_id
        ]);
        return $this->DAL->rows_affected() > 0;
    }

    /**
     * Verify that a user owns a specific potion
     *
     * @param int $potion_id Potion ID
     * @param int $owner_id User ID to verify ownership
     * @return bool True if user owns the potion, false otherwise
     */
    public function VerifyOwnership(int $potion_id, int $owner_id): bool
    {
        $potion_record = $this->DAL->r("SELECT id FROM potions WHERE id=:id AND owner_id=:owner_id", [
            ':id' => $potion_id,
            ':owner_id' => $owner_id
        ]);
        return !empty($potion_record);
    }

    /**
     * Use a potion on a character (activates it for 24 hours)
     *
     * @param int $potion_id Potion ID
     * @param int $character_id Character ID
     * @param int $owner_id Owner user ID (for security check)
     * @return bool True if potion was activated, false otherwise
     */
    public function UsePotion(int $potion_id, int $character_id, int $owner_id): bool
    {
        // First verify ownership
        if (!$this->VerifyOwnership($potion_id, $owner_id)) {
            return false;
        }

        // Calculate expiration time
        $expire_time = date('Y-m-d H:i:s', strtotime('+' . self::POTION_DURATION_HOURS . ' hours'));

        // Update character with active potion
        $this->DAL->w(
            "UPDATE characters SET active_potion_id=:potion_id, potion_expire_time=:expire_time WHERE id=:character_id AND user_id=:user_id",
            [
                ':potion_id' => $potion_id,
                ':expire_time' => $expire_time,
                ':character_id' => $character_id,
                ':user_id' => $owner_id
            ]
        );

        return $this->DAL->rows_affected() > 0;
    }

    /**
     * Get active potion for a character
     *
     * @param int $character_id Character ID
     * @return array<string, mixed>|null Active potion data or null if no active potion
     */
    public function GetActivePotion(int $character_id): ?array
    {
        // Get character's active potion info
        $character_records = $this->DAL->r(
            "SELECT active_potion_id, potion_expire_time FROM characters WHERE id=:character_id",
            [':character_id' => $character_id]
        );

        if (!$character_records || empty($character_records)) {
            return null;
        }

        $character_record = $character_records[0];

        if (!$character_record['active_potion_id']) {
            return null;
        }

        // Check if potion has expired
        $expire_time = strtotime($character_record['potion_expire_time']);
        if ($expire_time < time()) {
            // Potion expired, clear it
            $this->DAL->w(
                "UPDATE characters SET active_potion_id=NULL, potion_expire_time=NULL WHERE id=:character_id",
                [':character_id' => $character_id]
            );
            return null;
        }

        // Load the potion details
        $this->LoadPotionByID((int)$character_record['active_potion_id']);
        if ($this->Data) {
            $this->Data['expire_time'] = $character_record['potion_expire_time'];
            return $this->Data;
        }

        return null;
    }

    /**
     * Get active potion bonuses for a character
     *
     * @param int $character_id Character ID
     * @return array<string, int> Bonus values for all bonus types
     */
    public function GetActivePotionBonuses(int $character_id): array
    {
        // Get active potion
        $active_potion = $this->GetActivePotion($character_id);

        // Return default bonuses if no active potion
        if (!$active_potion) {
            return $this->getDefaultBonuses();
        }

        // Get definitions
        $prefix_definitions = $this->getPrefixDefinitions();
        $suffix_definitions = $this->getSuffixDefinitions();

        // Calculate bonuses
        $bonuses = $this->getDefaultBonuses();

        // Apply prefix bonus
        if (isset($prefix_definitions[$active_potion['prefix']])) {
            $bonuses[$active_potion['prefix']] = $active_potion['level'] * $prefix_definitions[$active_potion['prefix']]['per_level'];
        }

        // Apply suffix bonus
        if (isset($suffix_definitions[$active_potion['suffix']])) {
            $bonuses[$active_potion['suffix']] = $active_potion['level'] * $suffix_definitions[$active_potion['suffix']]['per_level'];
        }

        return $bonuses;
    }
}
