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

    /**
     * @var array<string, mixed> Character data including party_json and worker_json
     */
    public array $Data = [];

    public function __construct()
    {
        global $DAL;
        $this->DAL = $DAL;
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

    public function CharacterExists(int $user_id = 0): bool
    {
        $user_id = $this->getUserId($user_id);

        if ($user_id <= 0) {
            return false;
        }

        $query = "SELECT COUNT(*) as `count` FROM `characters` WHERE `user_id` = :user_id";
        $params = ['user_id' => $user_id];
        $result = $this->DAL->r($query, $params);

        return ($result[0]['count'] > 0);
    }

    public function ActivityCheck(int $user_id = 0): bool
    {
        $user_id = $this->getUserId($user_id);

        if ($user_id <= 0) {
            return false;
        }

        $this->DAL->w("UPDATE `characters` SET `last_seen` = NOW() WHERE `user_id` = :user_id", [
            'user_id' => $user_id
        ]);

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

    public function CreateCharacter(int $user_id = 0, string $name = ""): bool
    {
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

        $party_json = json_encode([
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
        ]);

        $worker_json = json_encode([
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
        ]);

        $query = "INSERT INTO `characters` (
            `user_id`,
            `name`,
            `level`,
            `arena_floor`,
            `gold`,
            `iron`,
            `herbs`,
            `gems`,
            `party_json`,
            `worker_json`,
            `rift_queued`,
            `world_boss_queued`,
            `credits`,
            `subscription_expires`
        ) VALUES (
            :user_id,
            :name,
            :level,
            :arena_floor,
            :gold,
            :iron,
            :herbs,
            :gems,
            :party_json,
            :worker_json,
            NULL,
            NULL,
            0,
            NULL
        )";

        $params = [
            'user_id' => $user_id,
            'name' => $name,
            'level' => self::DEFAULT_LEVEL,
            'arena_floor' => self::DEFAULT_LEVEL,
            'gold' => self::DEFAULT_RESOURCE_AMOUNT,
            'iron' => self::DEFAULT_RESOURCE_AMOUNT,
            'herbs' => self::DEFAULT_RESOURCE_AMOUNT,
            'gems' => self::DEFAULT_RESOURCE_AMOUNT,
            'party_json' => $party_json,
            'worker_json' => $worker_json
        ];

        $this->DAL->w($query, $params);

        $this->LoadByUserId($user_id); // Load after creation to avoid empty class data

        return true;
    }

    public function LoadByUserId(int $user_id = 0): bool
    {
        $user_id = $this->getUserId($user_id);

        if ($user_id <= 0) {
            return false;
        }

        $query = "SELECT * FROM `characters` WHERE `user_id` = :user_id LIMIT 1";
        $params = ['user_id' => $user_id];
        $result = $this->DAL->r($query, $params);

        if (empty($result)) {
            return false;
        }

        $this->Data = $result[0];
        $this->Data['party_json'] = json_decode($this->Data['party_json'], true);
        $this->Data['worker_json'] = json_decode($this->Data['worker_json'], true);

        return true;
    }

    public function Save(): bool
    {
        return $this->SaveByUserId((int)$this->Data['user_id']);
    }

    public function SaveByUserId(int $user_id = 0): bool
    {
        $user_id = $this->getUserId($user_id);

        if ($user_id <= 0) {
            return false;
        }

        $query = "UPDATE `characters` SET
            `name` = :name,
            `level` = :level,
            `arena_floor` = :arena_floor,
            `gold` = :gold,
            `iron` = :iron,
            `herbs` = :herbs,
            `gems` = :gems,
            `party_json` = :party_json,
            `worker_json` = :worker_json,
            `rift_queued` = :rift_queued,
            `world_boss_queued` = :world_boss_queued,
            `world_boss_log` = :world_boss_log,
            `last_save` = NOW(),
            `last_arena_time` = :last_arena_time,
            `last_arena_log` = :last_arena_log,
            `last_rift_time` = :last_rift_time,
            `last_rift_log` = :last_rift_log,
            `highest_rift_level` = :highest_rift_level,
            `last_seen` = :last_seen,
            `credits` = :credits,
            `subscription_expires` = :subscription_expires,
            `last_free_credits_claim` = :last_free_credits_claim
            WHERE `user_id` = :user_id";

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
            'last_arena_time' => $this->Data['last_arena_time'],
            'last_arena_log' => $this->Data['last_arena_log'],
            'last_rift_time' => $this->Data['last_rift_time'] ?? null,
            'last_rift_log' => $this->Data['last_rift_log'] ?? null,
            'highest_rift_level' => $this->Data['highest_rift_level'] ?? 0,
            'user_id' => $user_id,
            'last_seen' => $this->Data['last_seen'],
            'credits' => $this->Data['credits'] ?? 0,
            'subscription_expires' => $this->Data['subscription_expires'] ?? null,
            'last_free_credits_claim' => $this->Data['last_free_credits_claim'] ?? null,
        ];

        $this->DAL->w($query, $params);

        return true;
    }
}
