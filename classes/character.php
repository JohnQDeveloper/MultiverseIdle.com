<?php

declare(strict_types=1);

class Character
{
    // Class constants for magic numbers
    private const DEFAULT_STAT_VALUE = 10;
    private const DEFAULT_LEVEL = 1;
    private const DEFAULT_RESOURCE_AMOUNT = 1;
    private const XP_MULTIPLIER = 50;

    private object $DAL;
    private bool $isGuest = false;

    /**
     * @var array<string, mixed> Character data including party_json, worker_json, and inventory_json
     */
    public array $Data = [];

    public function __construct()
    {
        global $DAL;
        $this->DAL = $DAL;
    }

    public function IsGuest(): bool
    {
        return $this->isGuest;
    }

    /**
     * Get user ID from parameter or session
     */
    private function getUserId(int $user_id = 0): int
    {
        if ($user_id === 0 && isset($_SESSION['auth_user_id'])) {
            return (int)$_SESSION['auth_user_id'];
        }
        return $user_id;
    }

    private function isGuestSession(): bool
    {
        return isset($_SESSION['guest_mode']) && $_SESSION['guest_mode'] === true;
    }

    /**
     * @return array<string, int>
     */
    private function getDefaultSpecialResources(): array
    {
        $special_resources = [
            'lucky_wyrdstone' => 0,
            'corruption_orbs' => 0,
        ];

        foreach (Gear::getEssenceInventoryKeys() as $inventory_key) {
            $special_resources[$inventory_key] = 0;
        }

        return $special_resources;
    }

    private function buildDefaultCharacterData(string $name): array
    {
        $party_json = [
            "members" => [
                "frontline" => [
                    "class" => "strength",
                    "xp" => 0,
                    "level" => self::DEFAULT_LEVEL,
                    "strength" => self::DEFAULT_STAT_VALUE,
                    "dexterity" => self::DEFAULT_STAT_VALUE,
                    "health" => self::DEFAULT_STAT_VALUE,
                    "wisdom" => self::DEFAULT_STAT_VALUE,
                    "gear" => [],
                    "skills" => ["Flaming Blades", "Antimage"],
                ],
                "backline" => [
                    "class" => "wisdom",
                    "xp" => 0,
                    "level" => self::DEFAULT_LEVEL,
                    "strength" => self::DEFAULT_STAT_VALUE,
                    "dexterity" => self::DEFAULT_STAT_VALUE,
                    "health" => self::DEFAULT_STAT_VALUE,
                    "wisdom" => self::DEFAULT_STAT_VALUE,
                    "gear" => [],
                    "skills" => ["Healing Rain", "Firestorm"],
                ]
            ]
        ];

        $worker_json = [
            "resource" => "gold",
            "workers" => self::DEFAULT_LEVEL,
            "intelligence_upgrades" => self::DEFAULT_LEVEL,
            "speed_upgrades" => self::DEFAULT_LEVEL,
            "skills" => [
                "gold" => self::DEFAULT_LEVEL,
                "iron" => self::DEFAULT_LEVEL,
                "herbs" => self::DEFAULT_LEVEL,
                "gems" => self::DEFAULT_LEVEL
            ]
        ];

        $inventory_json = [
            "special_resources" => $this->getDefaultSpecialResources(),
        ];

        return [
            'id' => 0,
            'user_id' => 0,
            'season_id' => null,
            'name' => $name,
            'level' => self::DEFAULT_LEVEL,
            'arena_floor' => self::DEFAULT_LEVEL,
            'gold' => self::DEFAULT_RESOURCE_AMOUNT,
            'iron' => self::DEFAULT_RESOURCE_AMOUNT,
            'herbs' => self::DEFAULT_RESOURCE_AMOUNT,
            'gems' => self::DEFAULT_RESOURCE_AMOUNT,
            'party_json' => $party_json,
            'worker_json' => $worker_json,
            'inventory_json' => $inventory_json,
            'rift_queued' => null,
            'world_boss_queued' => null,
            'world_boss_log' => null,
            'avatar_corruption_queued' => 0,
            'avatar_corruption_log' => null,
            'credits' => 0,
            'subscription_expires' => null,
            'vip_expires' => null,
            'last_seen' => date('Y-m-d H:i:s'),
            'last_save' => date('Y-m-d H:i:s'),
            'last_arena_time' => null,
            'last_arena_log' => null,
            'last_rift_time' => null,
            'last_rift_log' => null,
            'highest_rift_level' => 0,
            'last_pvp_time' => null,
            'last_pvp_log' => null,
            'last_free_credits_claim' => null,
            'active_potion_id' => null,
            'potion_expire_time' => null,
        ];
    }

    private function initGuestCharacter(string $name = 'Guest'): bool
    {
        $this->Data = $this->buildDefaultCharacterData($name);
        $this->normalizeCharacterData();
        $_SESSION['guest_character'] = $this->Data;
        $this->isGuest = true;
        return true;
    }

    private function loadFromGuestSession(): bool
    {
        if (empty($_SESSION['guest_character'])) {
            return false;
        }
        $this->Data = $_SESSION['guest_character'];
        $this->Data['id'] = $this->Data['id'] ?? 0;
        $this->normalizeCharacterData();
        $this->isGuest = true;
        return true;
    }

    private function saveToGuestSession(): bool
    {
        if (empty($this->Data)) {
            return false;
        }
        $_SESSION['guest_character'] = $this->Data;
        return true;
    }

    /**
     * Migrate guest session character data into a newly registered user's DB record.
     * Should be called immediately after $auth->register() succeeds.
     */
    public function MigrateGuestToUser(int $user_id, string $name): bool
    {
        if (empty($this->Data)) {
            return false;
        }

        $has_inventory_json = $this->hasInventoryJsonColumn();
        $columns = [
            '`user_id`',
            '`name`',
            '`level`',
            '`arena_floor`',
            '`gold`',
            '`iron`',
            '`herbs`',
            '`gems`',
            '`party_json`',
            '`worker_json`',
        ];
        $values = [
            ':user_id',
            ':name',
            ':level',
            ':arena_floor',
            ':gold',
            ':iron',
            ':herbs',
            ':gems',
            ':party_json',
            ':worker_json',
        ];

        if ($has_inventory_json) {
            $columns[] = '`inventory_json`';
            $values[] = ':inventory_json';
        }

        $columns[] = '`rift_queued`';
        $columns[] = '`world_boss_queued`';
        $columns[] = '`credits`';
        $columns[] = '`subscription_expires`';
        $columns[] = '`vip_expires`';

        $values[] = 'NULL';
        $values[] = 'NULL';
        $values[] = '0';
        $values[] = 'NULL';
        $values[] = 'NULL';

        $query = "INSERT INTO `characters` (" . implode(', ', $columns) . ")
        VALUES (" . implode(', ', $values) . ")";

        $params = [
            'user_id' => $user_id,
            'name' => $name,
            'level' => max(1, (int)$this->Data['level']),
            'arena_floor' => max(1, (int)$this->Data['arena_floor']),
            'gold' => max(0, (int)$this->Data['gold']),
            'iron' => max(0, (int)$this->Data['iron']),
            'herbs' => max(0, (int)$this->Data['herbs']),
            'gems' => max(0, (int)$this->Data['gems']),
            'party_json' => json_encode($this->Data['party_json']),
            'worker_json' => json_encode($this->Data['worker_json']),
        ];

        if ($has_inventory_json) {
            $params['inventory_json'] = json_encode($this->Data['inventory_json']);
        }

        $this->DAL->w($query, $params);

        unset($_SESSION['guest_mode'], $_SESSION['guest_character']);
        $this->isGuest = false;

        return true;
    }

    public function CharacterExists(int $user_id = 0, ?int $season_id = null): bool
    {
        if ($this->isGuestSession()) {
            return isset($_SESSION['guest_character']) && !empty($_SESSION['guest_character']);
        }

        $user_id = $this->getUserId($user_id);

        if ($user_id <= 0) {
            return false;
        }

        $query = "SELECT COUNT(*) as `count` FROM `characters` WHERE `user_id` = :user_id AND `season_id` <=> :season_id";
        $result = $this->DAL->r($query, ['user_id' => $user_id, 'season_id' => $season_id]);

        return ($result[0]['count'] > 0);
    }

    public function ActivityCheck(int $user_id = 0): bool
    {
        if ($this->isGuestSession()) {
            return true;
        }

        if (empty($this->Data['id'])) {
            return false;
        }

        $this->DAL->w(
            "UPDATE `characters` SET `last_seen` = NOW() WHERE `id` = :id",
            ['id' => (int)$this->Data['id']]
        );

        return true;
    }

    public function IncrementPartyXP(int $xp_amount, int $user_id = 0): bool
    {
        $user_id = $this->getUserId($user_id);

        if ($user_id <= 0) {
            return false;
        }

        $this->Data['party_json']['members']['frontline']['xp'] += $xp_amount;
        $this->Data['party_json']['members']['backline']['xp'] += $xp_amount;

        $level = (int)$this->Data['party_json']['members']['frontline']['level'];
        $xp_required = self::XP_MULTIPLIER * $level * ($level + 1);

        if ($this->Data['party_json']['members']['frontline']['xp'] >= $xp_required) {
            // Level Up both members
            $this->Data['party_json']['members']['frontline']['level'] += 1;
            $this->Data['party_json']['members']['backline']['level'] += 1;

            // Subtract required XP
            $this->Data['party_json']['members']['frontline']['xp'] -= $xp_required;
            $this->Data['party_json']['members']['backline']['xp'] -= $xp_required;
        }

        return true;
    }

    public function CreateCharacter(int $user_id = 0, string $name = "", ?int $season_id = null): bool
    {
        if ($this->isGuestSession()) {
            return $this->initGuestCharacter($name ?: 'Guest');
        }

        $user_id = $this->getUserId($user_id);

        if ($user_id <= 0) {
            return false;
        }

        if ($name === "" && isset($_SESSION['auth_username'])) {
            $name = (string)$_SESSION['auth_username'];
        }

        if ($name === "") {
            return false;
        }

        $defaults = $this->buildDefaultCharacterData($name);
        $party_json = json_encode($defaults['party_json']);
        $worker_json = json_encode($defaults['worker_json']);
        $has_inventory_json = $this->hasInventoryJsonColumn();

        $columns = [
            '`user_id`',
            '`season_id`',
            '`name`',
            '`level`',
            '`arena_floor`',
            '`gold`',
            '`iron`',
            '`herbs`',
            '`gems`',
            '`party_json`',
            '`worker_json`',
        ];
        $values = [
            ':user_id',
            ':season_id',
            ':name',
            ':level',
            ':arena_floor',
            ':gold',
            ':iron',
            ':herbs',
            ':gems',
            ':party_json',
            ':worker_json',
        ];

        if ($has_inventory_json) {
            $columns[] = '`inventory_json`';
            $values[] = ':inventory_json';
        }

        $columns[] = '`rift_queued`';
        $columns[] = '`world_boss_queued`';
        $columns[] = '`credits`';
        $columns[] = '`subscription_expires`';
        $columns[] = '`vip_expires`';

        $values[] = 'NULL';
        $values[] = 'NULL';
        $values[] = '0';
        $values[] = 'NULL';
        $values[] = 'NULL';

        $query = "INSERT INTO `characters` (" . implode(', ', $columns) . ")
        VALUES (" . implode(', ', $values) . ")";

        $params = [
            'user_id' => $user_id,
            'season_id' => $season_id,
            'name' => $name,
            'level' => self::DEFAULT_LEVEL,
            'arena_floor' => self::DEFAULT_LEVEL,
            'gold' => self::DEFAULT_RESOURCE_AMOUNT,
            'iron' => self::DEFAULT_RESOURCE_AMOUNT,
            'herbs' => self::DEFAULT_RESOURCE_AMOUNT,
            'gems' => self::DEFAULT_RESOURCE_AMOUNT,
            'party_json' => $party_json,
            'worker_json' => $worker_json,
        ];

        if ($has_inventory_json) {
            $params['inventory_json'] = json_encode($defaults['inventory_json']);
        }

        $this->DAL->w($query, $params);

        $this->LoadByUserId($user_id, $season_id); // Load after creation to avoid empty class data

        return true;
    }

    public function LoadByUserId(int $user_id = 0, ?int $season_id = null): bool
    {
        if ($this->isGuestSession()) {
            return $this->loadFromGuestSession();
        }

        $user_id = $this->getUserId($user_id);

        if ($user_id <= 0) {
            return false;
        }

        $query = "SELECT * FROM `characters` WHERE `user_id` = :user_id AND `season_id` <=> :season_id LIMIT 1";
        $result = $this->DAL->r($query, ['user_id' => $user_id, 'season_id' => $season_id]);

        if (empty($result)) {
            return false;
        }

        $this->Data = $result[0];
        $this->Data['party_json'] = json_decode($this->Data['party_json'], true);
        $this->Data['worker_json'] = json_decode($this->Data['worker_json'], true);
        $this->Data['inventory_json'] = isset($this->Data['inventory_json'])
            ? json_decode((string)$this->Data['inventory_json'], true)
            : [];
        $this->normalizeCharacterData();

        return true;
    }

    public function LoadById(int $character_id): bool
    {
        $query = "SELECT * FROM `characters` WHERE `id` = :id LIMIT 1";
        $result = $this->DAL->r($query, ['id' => $character_id]);

        if (empty($result)) {
            return false;
        }

        $this->Data = $result[0];
        $this->Data['party_json'] = json_decode($this->Data['party_json'], true);
        $this->Data['worker_json'] = json_decode($this->Data['worker_json'], true);
        $this->Data['inventory_json'] = isset($this->Data['inventory_json'])
            ? json_decode((string)$this->Data['inventory_json'], true)
            : [];
        $this->normalizeCharacterData();

        return true;
    }

    public function Save(): bool
    {
        return $this->SaveByUserId((int)$this->Data['user_id']);
    }

    /**
     * Saves the currently loaded character by its database ID.
     * The $user_id parameter is retained for API compatibility but the save
     * targets the specific character record via its primary key.
     */
    public function SaveByUserId(int $user_id = 0): bool
    {
        if ($this->isGuestSession()) {
            return $this->saveToGuestSession();
        }

        if (empty($this->Data['id'])) {
            return false;
        }

        $has_inventory_json = $this->hasInventoryJsonColumn();
        $set_clauses = [
            '`name` = :name',
            '`level` = :level',
            '`arena_floor` = :arena_floor',
            '`gold` = :gold',
            '`iron` = :iron',
            '`herbs` = :herbs',
            '`gems` = :gems',
            '`party_json` = :party_json',
            '`worker_json` = :worker_json',
        ];

        if ($has_inventory_json) {
            $set_clauses[] = '`inventory_json` = :inventory_json';
        }

        $set_clauses = array_merge($set_clauses, [
            '`rift_queued` = :rift_queued',
            '`world_boss_queued` = :world_boss_queued',
            '`world_boss_log` = :world_boss_log',
            '`avatar_corruption_queued` = :avatar_corruption_queued',
            '`avatar_corruption_log` = :avatar_corruption_log',
            '`last_save` = NOW()',
            '`last_arena_time` = :last_arena_time',
            '`last_arena_log` = :last_arena_log',
            '`last_rift_time` = :last_rift_time',
            '`last_rift_log` = :last_rift_log',
            '`highest_rift_level` = :highest_rift_level',
            '`last_seen` = :last_seen',
            '`credits` = :credits',
            '`subscription_expires` = :subscription_expires',
            '`vip_expires` = :vip_expires',
            '`last_free_credits_claim` = :last_free_credits_claim',
            '`last_pvp_time` = :last_pvp_time',
            '`last_pvp_log` = :last_pvp_log',
        ]);

        $query = "UPDATE `characters` SET
            " . implode(",\n            ", $set_clauses) . "
            WHERE `id` = :id";

        $params = [
            'name' => $this->Data['name'],
            'level' => $this->Data['level'],
            'arena_floor' => $this->Data['arena_floor'],
            'gold' => $this->Data['gold'],
            'iron' => $this->Data['iron'],
            'herbs' => $this->Data['herbs'],
            'gems' => $this->Data['gems'],
            'party_json' => json_encode($this->Data['party_json']),
            'worker_json' => json_encode($this->Data['worker_json']),
            'rift_queued' => $this->Data['rift_queued'],
            'world_boss_queued' => $this->Data['world_boss_queued'],
            'world_boss_log' => $this->Data['world_boss_log'] ?? '',
            'avatar_corruption_queued' => $this->Data['avatar_corruption_queued'] ?? 0,
            'avatar_corruption_log' => $this->Data['avatar_corruption_log'] ?? null,
            'last_arena_time' => $this->Data['last_arena_time'],
            'last_arena_log' => $this->Data['last_arena_log'],
            'last_rift_time' => $this->Data['last_rift_time'] ?? null,
            'last_rift_log' => $this->Data['last_rift_log'] ?? null,
            'highest_rift_level' => $this->Data['highest_rift_level'] ?? 0,
            'last_seen' => $this->Data['last_seen'],
            'credits' => $this->Data['credits'] ?? 0,
            'subscription_expires' => $this->Data['subscription_expires'] ?? null,
            'vip_expires' => $this->Data['vip_expires'] ?? null,
            'last_free_credits_claim' => $this->Data['last_free_credits_claim'] ?? null,
            'last_pvp_time' => $this->Data['last_pvp_time'] ?? null,
            'last_pvp_log'  => $this->Data['last_pvp_log'] ?? null,
            'id' => (int)$this->Data['id'],
        ];

        if ($has_inventory_json) {
            $params['inventory_json'] = json_encode($this->Data['inventory_json']);
        }

        $this->DAL->w($query, $params);

        return true;
    }

    /**
     * Merges a season character's stats and commodity resources into the user's
     * perpetual character. Called by the season-end cron when a season closes.
     */
    public function MergeSeasonToPerpetual(int $user_id): bool
    {
        if (empty($this->Data) || $this->Data['season_id'] === null) {
            return false;
        }

        $Perpetual = new Character();
        if (!$Perpetual->LoadByUserId($user_id, null)) {
            return false;
        }

        // Add party member stats
        foreach (['frontline', 'backline'] as $slot) {
            foreach (['strength', 'dexterity', 'health', 'wisdom'] as $stat) {
                $gain = (int)($this->Data['party_json']['members'][$slot][$stat] ?? 0);
                if ($gain > 0) {
                    $Perpetual->Data['party_json']['members'][$slot][$stat] += $gain;
                }
            }
        }

        // Add commodity resources
        foreach (['gold', 'iron', 'herbs', 'gems'] as $resource) {
            $Perpetual->Data[$resource] = ((int)$Perpetual->Data[$resource]) + ((int)($this->Data[$resource] ?? 0));
        }

        $special_resources = ['lucky_wyrdstone', 'corruption_orbs'];
        $special_resources = array_merge($special_resources, array_values(Gear::getEssenceInventoryKeys()));

        foreach ($special_resources as $special) {
            $Perpetual->Data['inventory_json']['special_resources'][$special] =
                ((int)($Perpetual->Data['inventory_json']['special_resources'][$special] ?? 0))
                + ((int)($this->Data['inventory_json']['special_resources'][$special] ?? 0));
        }

        $Perpetual->Data['last_seen'] = date('Y-m-d H:i:s');
        return $Perpetual->SaveByUserId($user_id);
    }

    private function normalizeCharacterData(): void
    {
        if (!isset($this->Data['worker_json']) || !is_array($this->Data['worker_json'])) {
            $this->Data['worker_json'] = [];
        }

        if (!isset($this->Data['inventory_json']) || !is_array($this->Data['inventory_json'])) {
            $this->Data['inventory_json'] = [];
        }

        if (!isset($this->Data['inventory_json']['special_resources']) || !is_array($this->Data['inventory_json']['special_resources'])) {
            $this->Data['inventory_json']['special_resources'] = [];
        }

        $legacy_lucky_wyrdstone = (int)($this->Data['worker_json']['special_resources']['lucky_wyrdstone'] ?? 0);
        $this->Data['inventory_json']['special_resources']['lucky_wyrdstone'] =
            (int)($this->Data['inventory_json']['special_resources']['lucky_wyrdstone'] ?? 0) + $legacy_lucky_wyrdstone;

        if (isset($this->Data['worker_json']['special_resources']) && is_array($this->Data['worker_json']['special_resources'])) {
            unset($this->Data['worker_json']['special_resources']['lucky_wyrdstone']);
            if ($this->Data['worker_json']['special_resources'] === []) {
                unset($this->Data['worker_json']['special_resources']);
            }
        }

        if (!isset($this->Data['inventory_json']['special_resources']['corruption_orbs'])) {
            $this->Data['inventory_json']['special_resources']['corruption_orbs'] = 0;
        }

        foreach (Gear::getEssenceInventoryKeys() as $inventory_key) {
            if (!isset($this->Data['inventory_json']['special_resources'][$inventory_key])) {
                $this->Data['inventory_json']['special_resources'][$inventory_key] = 0;
            }
        }

        // Ensure equipped_skill_gems is initialized for both party members
        foreach (['frontline', 'backline'] as $position) {
            if (!isset($this->Data['party_json']['members'][$position]['equipped_skill_gems'])) {
                $this->Data['party_json']['members'][$position]['equipped_skill_gems'] = [0, 0];
            }
        }
    }

    private function hasInventoryJsonColumn(): bool
    {
        static $has_inventory_json = null;

        if ($has_inventory_json !== null) {
            return $has_inventory_json;
        }

        $result = $this->DAL->r(
            "SELECT 1
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'characters'
               AND COLUMN_NAME = 'inventory_json'
             LIMIT 1"
        );

        $has_inventory_json = !empty($result);
        return $has_inventory_json;
    }
}
