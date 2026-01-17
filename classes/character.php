<?php

class Character {
    private $DAL;
    public $Data;

    public function __construct() {
        global $DAL;
        $this->DAL = $DAL;
    }

    public function CharacterExists($user_id = "") {
        // Load Session Defaults
        if($user_id == "") {
            $user_id = $_SESSION['auth_user_id'];
        }

        $query = "SELECT COUNT(*) as `count` FROM `characters` WHERE `user_id` = :user_id";
        $params = ['user_id' => $user_id];
        $result = $this->DAL->r($query, $params);

        return ($result[0]['count'] > 0);
    }

    public function ActivityCheck($user_id = "") {
        // Load Session Defaults
        if($user_id == "") {
            $user_id = $_SESSION['auth_user_id'];
        }

        $this->DAL->w("UPDATE `characters` SET `last_save` = NOW() WHERE `user_id` = :user_id", [
            'user_id' => $user_id
        ]);

        return true;
    }

    public function CreateCharacter($user_id = "", $name = "") {
        // Load Session Defaults
        if($user_id == "") {
            $user_id = $_SESSION['auth_user_id'];
        }

        if($name == "") {
            $name = $_SESSION['auth_username'];
        }

        $party_json = json_encode([
            "members" => [
                "frontline" => [
                    "class" => "warrior",
                    "level" => 1,
                    "strength" => 10,
                    "dexterity" => 10,
                    "health" => 10,
                    "wisdom" => 10,
                    "gear" => [],
                    "skills" => ["Flaming Blades", "Antimage"],
                ],
                "backline" => [
                    "class" => "healer",
                    "level" => 1,
                    "strength" => 10,
                    "dexterity" => 10,
                    "health" => 10,
                    "wisdom" => 10,
                    "gear" => [],
                    "skills" => ["Healing Rain", "Firestorm"],
                ]
            ]
        ]);

        $worker_json = json_encode([
            "resource" => "gold",
            "workers" => 1,
            "intelligence_upgrades" => 1,
            "speed_upgrades" => 1,
            "skills" => [
                "gold" => 1,
                "iron" => 1,
                "herbs" => 1,
                "gems" => 1
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
            `world_boss_queued`
        ) VALUES (
            :user_id,
            :name,
            1,
            1,
            1,
            1,
            1,
            1,
            :party_json,
            :worker_json,
            NULL,
            NULL
        )";

        $params = [
            'user_id' => $user_id,
            'name' => $name,
            'party_json' => $party_json,
            'worker_json' => $worker_json
        ];

        $this->DAL->w($query, $params);

        $this->LoadByUserId($user_id); // Load after creation to avoid empty class data

        return true;
    }

    public function LoadByUserId($user_id = "") {
        // Load Session Defaults
        if($user_id == "") {
            $user_id = $_SESSION['auth_user_id'];
        }

        $query = "SELECT * FROM `characters` WHERE `user_id` = :user_id LIMIT 1";
        $params = ['user_id' => $user_id];
        $result = $this->DAL->r($query, $params);

        if(empty($result)) {
            return false;
        }

        $this->Data = $result[0];
        $this->Data['party_json'] = json_decode($this->Data['party_json'], true);
        $this->Data['worker_json'] = json_decode($this->Data['worker_json'], true);

        return true;
    }

    public function SaveByUserId($user_id = "") {
         // Load Session Defaults
        if($user_id == "") {
            $user_id = $_SESSION['auth_user_id'];
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
            `last_save` = NOW()
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
            'user_id' => $user_id
        ];

        $this->DAL->w($query, $params);

        return true;
    }

}
