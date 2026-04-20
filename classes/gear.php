<?php

declare(strict_types=1);

class Gear
{
    private object $DAL;

    /**
     * @var array<string, mixed> Gear item data including id, name, market_price, owner_id, and details
     */
    public array $Data = [];

    public function __construct()
    {
        global $DAL;
        $this->DAL = $DAL;
    }

    /**
     * Get gear affix definitions
     *
     * @return array<string, array<string, int|string>>
     */
    public static function getAffixDefinitions(): array
    {
        return [
            'strength' => ['name' => 'Strength', 'per_level' => 20, 'type' => 'flat'],
            'health' => ['name' => 'Health', 'per_level' => 20, 'type' => 'flat'],
            'dexterity' => ['name' => 'Dexterity', 'per_level' => 20, 'type' => 'flat'],
            'wisdom' => ['name' => 'Wisdom', 'per_level' => 20, 'type' => 'flat'],
            'cold_damage' => ['name' => 'Increased Cold Damage', 'per_level' => 2, 'type' => 'percent'],
            'fire_damage' => ['name' => 'Increased Fire Damage', 'per_level' => 2, 'type' => 'percent'],
            'physical_damage' => ['name' => 'Increased Physical Damage', 'per_level' => 1, 'type' => 'percent'],
            'physical_resistance' => ['name' => 'Physical Resistance', 'per_level' => 1, 'type' => 'percent'],
            'cold_resistance' => ['name' => 'Cold Resistance', 'per_level' => 3, 'type' => 'percent'],
            'fire_resistance' => ['name' => 'Fire Resistance', 'per_level' => 3, 'type' => 'percent'],
        ];
    }

    /**
     * Get gear item type definitions
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getItemTypeDefinitions(): array
    {
        return [
            'weapon' => ['name' => 'Weapon', 'slot' => 'weapon', 'bonuses' => ['strength' => 15, 'health' => 15]],
            'wand' => ['name' => 'Wand', 'slot' => 'weapon', 'bonuses' => ['wisdom' => 30]],
            'plate' => ['name' => 'Plate', 'slot' => 'armor', 'bonuses' => ['health' => 15, 'resistances' => 15]],
            'robe' => ['name' => 'Robe', 'slot' => 'armor', 'bonuses' => ['dexterity' => 15, 'wisdom' => 15]],
        ];
    }

    /**
     * Create a new gear item
     *
     * @param string $name Item name
     * @param array<string, mixed> $gear_details Item details including slot, affixes, base_bonuses, etc.
     * @return string|false New gear ID or false on failure
     */
    public function CreateItem(string $name, array $gear_details): string|false
    {
        $this->DAL->w(
            "INSERT INTO gear SET created_at=NOW(), name=:name, details=:details, market_price=0, owner_id=:owner_id",
            [
                ':name' => $name,
                ':details' => json_encode($gear_details),
                ':owner_id' => $_SESSION['auth_user_id'] ?? null
            ]
        );

        return $this->DAL->last_insert_id();
    }

    /**
     * Load gear item by ID
     *
     * @param int $gear_id Gear item ID
     * @return bool True if item loaded successfully, false otherwise
     */
    public function LoadItemByGearID(int $gear_id): bool
    {
        $gear_record = $this->DAL->r("SELECT * FROM gear WHERE id=:id", [
            ':id' => $gear_id
        ]);

        if ($gear_record && !empty($gear_record)) {
            $record = $gear_record[0];
            $this->Data = json_decode($record['details'], true);
            $this->Data['id'] = $record['id'];
            $this->Data['market_price'] = $record['market_price'];
            $this->Data['owner_id'] = $record['owner_id'];
            return true;
        }

        return false;
    }

    /**
     * Get all gear items owned by a user
     *
     * @param int $owner_id User ID of the owner
     * @return array<int, array<string, mixed>> Array of gear items
     */
    public function GetAllItemsByOwner(int $owner_id): array
    {
        $gear_records = $this->DAL->r(
            "SELECT * FROM gear WHERE owner_id=:owner_id ORDER BY favorite DESC, created_at DESC",
            [':owner_id' => $owner_id]
        );

        $items = [];
        if ($gear_records) {
            foreach ($gear_records as $record) {
                $item = json_decode($record['details'], true);
                $item['id'] = $record['id'];
                $item['name'] = $record['name'];
                $item['market_price'] = $record['market_price'];
                $item['owner_id'] = $record['owner_id'];
                $item['favorite'] = $record['favorite'] ?? 0;
                $item['created_at'] = $record['created_at'];
                $items[] = $item;
            }
        }
        return $items;
    }

    /**
     * Delete a gear item
     *
     * @param int $gear_id Gear item ID
     * @param int $owner_id Owner user ID (for security check)
     * @return bool True if item was deleted, false otherwise
     */
    public function DestroyItem(int $gear_id, int $owner_id): bool
    {
        $this->DAL->w("DELETE FROM gear WHERE id=:id AND owner_id=:owner_id", [
            ':id' => $gear_id,
            ':owner_id' => $owner_id
        ]);
        return $this->DAL->rows_affected() > 0;
    }

    /**
     * Toggle favorite status of a gear item
     *
     * @param int $gear_id Gear item ID
     * @param int $owner_id Owner user ID (for security check)
     * @return bool True if favorite was toggled, false otherwise
     */
    public function ToggleFavorite(int $gear_id, int $owner_id): bool
    {
        $this->DAL->w(
            "UPDATE gear SET favorite = NOT COALESCE(favorite, 0) WHERE id=:id AND owner_id=:owner_id",
            [
                ':id' => $gear_id,
                ':owner_id' => $owner_id
            ]
        );
        return $this->DAL->rows_affected() > 0;
    }

    /**
     * Get favorite gear items by owner and slot
     *
     * @param int $owner_id User ID of the owner
     * @param string $slot Gear slot (e.g., 'weapon', 'armor')
     * @return array<int, array<string, mixed>> Array of favorite gear items for the specified slot
     */
    public function GetFavoriteItemsByOwnerAndSlot(int $owner_id, string $slot): array
    {
        $gear_records = $this->DAL->r(
            "SELECT * FROM gear WHERE owner_id=:owner_id AND favorite=1 ORDER BY created_at DESC",
            [':owner_id' => $owner_id]
        );

        $items = [];
        if ($gear_records) {
            foreach ($gear_records as $record) {
                $item = json_decode($record['details'], true);
                if (isset($item['slot']) && $item['slot'] === $slot) {
                    $item['id'] = $record['id'];
                    $item['name'] = $record['name'];
                    $item['market_price'] = $record['market_price'];
                    $item['owner_id'] = $record['owner_id'];
                    $items[] = $item;
                }
            }
        }
        return $items;
    }

    /**
     * Verify that a user owns a specific gear item
     *
     * @param int $gear_id Gear item ID
     * @param int $owner_id User ID to verify ownership
     * @return bool True if user owns the item, false otherwise
     */
    public function VerifyOwnership(int $gear_id, int $owner_id): bool
    {
        $gear_record = $this->DAL->r("SELECT id FROM gear WHERE id=:id AND owner_id=:owner_id", [
            ':id' => $gear_id,
            ':owner_id' => $owner_id
        ]);
        return !empty($gear_record);
    }

    /**
     * Apply a Corruption Orb to a gear item.
     *
     * Rolls a random modifier uniformly between CORRUPTION_ORB_MIN and CORRUPTION_ORB_MAX
     * in 1% increments and stores it in the item's details JSON.
     *
     * @return float|false The rolled modifier (e.g. 1.12 or 0.87) on success, false if already corrupted or not owned
     */
    public function ApplyCorruptionOrb(int $gear_id, int $owner_id): float|false
    {
        $gear_record = $this->DAL->r(
            "SELECT id, details FROM gear WHERE id=:id AND owner_id=:owner_id",
            [':id' => $gear_id, ':owner_id' => $owner_id]
        );

        if (empty($gear_record)) {
            return false;
        }

        $details = json_decode($gear_record[0]['details'], true);

        if (isset($details['corruption_modifier'])) {
            return false; // already corrupted
        }

        $min_int = (int)round(CORRUPTION_ORB_MIN * 100);
        $max_int = (int)round(CORRUPTION_ORB_MAX * 100);
        $modifier = round(random_int($min_int, $max_int) / 100, 2);

        $details['corruption_modifier'] = $modifier;

        $this->DAL->w(
            "UPDATE gear SET details=:details WHERE id=:id AND owner_id=:owner_id",
            [':details' => json_encode($details), ':id' => $gear_id, ':owner_id' => $owner_id]
        );

        return $this->DAL->rows_affected() > 0 ? $modifier : false;
    }

    /**
     * Get the party level required to equip a gear item
     *
     * @param int $gear_id Gear item ID
     * @return int The party_level_at_craft value, or 0 if not found/not set
     */
    public function GetItemLevelByID(int $gear_id): int
    {
        $gear_record = $this->DAL->r("SELECT details FROM gear WHERE id=:id", [':id' => $gear_id]);
        if (!$gear_record || empty($gear_record)) {
            return 0;
        }
        $details = json_decode($gear_record[0]['details'], true);
        return (int)($details['party_level_at_craft'] ?? 0);
    }
}
