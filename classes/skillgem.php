<?php

declare(strict_types=1);

class SkillGem
{
    private object $DAL;

    /**
     * @var array<string, mixed> Skill gem data including id, name, skill_name, bonus_type, tier, owner_id
     */
    public array $Data = [];

    public function __construct()
    {
        global $DAL;
        $this->DAL = $DAL;
    }

    /**
     * Get skill gem bonus type definitions
     *
     * @return array<string, array<string, string|float>>
     */
    public static function getBonusTypeDefinitions(): array
    {
        return [
            'wisdom_cast' => [
                'name'        => 'Attunement',
                'description' => '+0.5% Effective Wisdom per Tier (cast more reliably)',
                'per_tier'    => 0.5,
                'unit'        => '%',
                'gem_prefix'  => 'Attuned',
            ],
            'effect' => [
                'name'        => 'Empowerment',
                'description' => '+1% Effect (healing or damage) per Tier',
                'per_tier'    => 1.0,
                'unit'        => '%',
                'gem_prefix'  => 'Empowering',
            ],
            'echo' => [
                'name'        => 'Resonance',
                'description' => '+1% Chance to Echo (Double Cast) per Tier',
                'per_tier'    => 1.0,
                'unit'        => '%',
                'gem_prefix'  => 'Resonant',
            ],
            'triple_cast' => [
                'name'        => 'Instability',
                'description' => '+0.5% Chance to Triple Cast per Tier',
                'per_tier'    => 0.5,
                'unit'        => '%',
                'gem_prefix'  => 'Unstable',
            ],
        ];
    }

    /**
     * Create a new skill gem item
     *
     * @param string $name Display name
     * @param array<string, mixed> $details Gem details (skill_name, bonus_type, tier, party_level_at_craft)
     * @return string|false New gem ID or false on failure
     */
    public function CreateItem(string $name, array $details): string|false
    {
        $this->DAL->w(
            "INSERT INTO skill_gems SET created_at=NOW(), name=:name, skill_name=:skill_name, details=:details, market_price=0, owner_id=:owner_id",
            [
                ':name'       => $name,
                ':skill_name' => $details['skill_name'],
                ':details'    => json_encode($details),
                ':owner_id'   => $_SESSION['auth_user_id'] ?? null,
            ]
        );

        return $this->DAL->last_insert_id();
    }

    /**
     * Load a skill gem by ID
     *
     * @param int $gem_id Skill gem ID
     * @return bool True if loaded successfully
     */
    public function LoadItemByGemID(int $gem_id): bool
    {
        $record = $this->DAL->r("SELECT * FROM skill_gems WHERE id=:id", [':id' => $gem_id]);

        if ($record && !empty($record)) {
            $row = $record[0];
            $this->Data = json_decode($row['details'], true);
            $this->Data['id']           = $row['id'];
            $this->Data['name']         = $row['name'];
            $this->Data['skill_name']   = $row['skill_name'];
            $this->Data['market_price'] = $row['market_price'];
            $this->Data['owner_id']     = $row['owner_id'];
            $this->Data['favorite']     = $row['favorite'] ?? 0;
            return true;
        }

        return false;
    }

    /**
     * Get all skill gems owned by a user
     *
     * @param int $owner_id Owner user ID
     * @return array<int, array<string, mixed>>
     */
    public function GetAllItemsByOwner(int $owner_id): array
    {
        $records = $this->DAL->r(
            "SELECT * FROM skill_gems WHERE owner_id=:owner_id ORDER BY favorite DESC, created_at DESC",
            [':owner_id' => $owner_id]
        );

        $items = [];
        if ($records) {
            foreach ($records as $row) {
                $item                 = json_decode($row['details'], true);
                $item['id']           = $row['id'];
                $item['name']         = $row['name'];
                $item['skill_name']   = $row['skill_name'];
                $item['market_price'] = $row['market_price'];
                $item['owner_id']     = $row['owner_id'];
                $item['favorite']     = $row['favorite'] ?? 0;
                $item['created_at']   = $row['created_at'];
                $items[]              = $item;
            }
        }

        return $items;
    }

    /**
     * Get favorite skill gems owned by a user, grouped by skill name
     *
     * @param int $owner_id Owner user ID
     * @return array<int, array<string, mixed>>
     */
    public function GetFavoriteItemsByOwner(int $owner_id): array
    {
        $records = $this->DAL->r(
            "SELECT * FROM skill_gems WHERE owner_id=:owner_id AND favorite=1 ORDER BY created_at DESC",
            [':owner_id' => $owner_id]
        );

        $items = [];
        if ($records) {
            foreach ($records as $row) {
                $item                 = json_decode($row['details'], true);
                $item['id']           = $row['id'];
                $item['name']         = $row['name'];
                $item['skill_name']   = $row['skill_name'];
                $item['market_price'] = $row['market_price'];
                $item['owner_id']     = $row['owner_id'];
                $items[]              = $item;
            }
        }

        return $items;
    }

    /**
     * Verify ownership of a skill gem
     *
     * @param int $gem_id   Skill gem ID
     * @param int $owner_id User ID to verify
     * @return bool True if owned
     */
    public function VerifyOwnership(int $gem_id, int $owner_id): bool
    {
        $record = $this->DAL->r("SELECT id FROM skill_gems WHERE id=:id AND owner_id=:owner_id", [
            ':id'       => $gem_id,
            ':owner_id' => $owner_id,
        ]);
        return !empty($record);
    }

    /**
     * Destroy a skill gem
     *
     * @param int $gem_id   Skill gem ID
     * @param int $owner_id Owner user ID (security check)
     * @return bool True if destroyed
     */
    public function DestroyItem(int $gem_id, int $owner_id): bool
    {
        $this->DAL->w("DELETE FROM skill_gems WHERE id=:id AND owner_id=:owner_id", [
            ':id'       => $gem_id,
            ':owner_id' => $owner_id,
        ]);
        return $this->DAL->rows_affected() > 0;
    }

    /**
     * Toggle favorite status
     *
     * @param int $gem_id   Skill gem ID
     * @param int $owner_id Owner user ID (security check)
     * @return bool True if toggled
     */
    public function ToggleFavorite(int $gem_id, int $owner_id): bool
    {
        $this->DAL->w(
            "UPDATE skill_gems SET favorite = NOT COALESCE(favorite, 0) WHERE id=:id AND owner_id=:owner_id",
            [':id' => $gem_id, ':owner_id' => $owner_id]
        );
        return $this->DAL->rows_affected() > 0;
    }
}
