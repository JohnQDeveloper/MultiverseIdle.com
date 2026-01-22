<?php

    class Potion {
        private $DAL;
        public $Data;

        public function __construct() {
            global $DAL;
            $this->DAL = $DAL;
        }

        function CreatePotion($name, $prefix, $suffix, $level) {
            $this->DAL->w("INSERT INTO potions SET created_at=NOW(), name=:name, prefix=:prefix, suffix=:suffix, level=:level, owner_id=:owner_id", [
                ':name' => $name,
                ':prefix' => $prefix,
                ':suffix' => $suffix,
                ':level' => $level,
                ':owner_id' => $_SESSION['auth_user_id'] ?? null
            ]);

            // return new potion ID
            return $this->DAL->last_insert_id();
        }

        function LoadPotionByID($potion_id) {
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
                    'level' => $potion_record['level'],
                    'owner_id' => $potion_record['owner_id'],
                    'created_at' => $potion_record['created_at']
                ];
                return true;
            } else {
                return false;
            }
        }

        function GetAllPotionsByOwner($owner_id) {
            $potion_records = $this->DAL->r("SELECT * FROM potions WHERE owner_id=:owner_id ORDER BY created_at DESC", [
                ':owner_id' => $owner_id
            ]);

            $potions = [];
            if ($potion_records) {
                foreach ($potion_records as $record) {
                    $potions[] = [
                        'id' => $record['id'],
                        'name' => $record['name'],
                        'prefix' => $record['prefix'],
                        'suffix' => $record['suffix'],
                        'level' => $record['level'],
                        'owner_id' => $record['owner_id'],
                        'created_at' => $record['created_at']
                    ];
                }
            }
            return $potions;
        }

        function DestroyPotion($potion_id, $owner_id) {
            $this->DAL->w("DELETE FROM potions WHERE id=:id AND owner_id=:owner_id", [
                ':id' => $potion_id,
                ':owner_id' => $owner_id
            ]);
            return $this->DAL->rows_affected() > 0;
        }

        function VerifyOwnership($potion_id, $owner_id) {
            $potion_record = $this->DAL->r("SELECT id FROM potions WHERE id=:id AND owner_id=:owner_id", [
                ':id' => $potion_id,
                ':owner_id' => $owner_id
            ]);
            return !empty($potion_record);
        }

        function UsePotion($potion_id, $character_id, $owner_id) {
            # First verify ownership
            if (!$this->VerifyOwnership($potion_id, $owner_id)) {
                return false;
            }

            # Calculate expiration time (24 hours from now)
            $expire_time = date('Y-m-d H:i:s', strtotime('+24 hours'));

            # Update character with active potion
            $this->DAL->w("UPDATE characters SET active_potion_id=:potion_id, potion_expire_time=:expire_time WHERE id=:character_id AND user_id=:user_id", [
                ':potion_id' => $potion_id,
                ':expire_time' => $expire_time,
                ':character_id' => $character_id,
                ':user_id' => $owner_id
            ]);

            return $this->DAL->rows_affected() > 0;
        }

        function GetActivePotion($character_id) {
            # Get character's active potion info
            $character_records = $this->DAL->r("SELECT active_potion_id, potion_expire_time FROM characters WHERE id=:character_id", [
                ':character_id' => $character_id
            ]);

            if (!$character_records || empty($character_records)) {
                return null;
            }

            $character_record = $character_records[0];

            if (!$character_record['active_potion_id']) {
                return null;
            }

            # Check if potion has expired
            $expire_time = strtotime($character_record['potion_expire_time']);
            if ($expire_time < time()) {
                # Potion expired, clear it
                $this->DAL->w("UPDATE characters SET active_potion_id=NULL, potion_expire_time=NULL WHERE id=:character_id", [
                    ':character_id' => $character_id
                ]);
                return null;
            }

            # Load the potion details
            $this->LoadPotionByID($character_record['active_potion_id']);
            if ($this->Data) {
                $this->Data['expire_time'] = $character_record['potion_expire_time'];
                return $this->Data;
            }

            return null;
        }

        function GetActivePotionBonuses($character_id) {
            # Get active potion
            $active_potion = $this->GetActivePotion($character_id);

            if (!$active_potion) {
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

            # Potion affix definitions
            $potion_prefix_definitions = [
                'herb_worker_yield' => ['per_level' => 1],
                'gold_worker_yield' => ['per_level' => 1],
                'iron_worker_yield' => ['per_level' => 1],
                'gems_worker_yield' => ['per_level' => 1],
                'arena_resource_drops' => ['per_level' => 1],
                'rift_drops' => ['per_level' => 1],
            ];

            $potion_suffix_definitions = [
                'arena_xp' => ['per_level' => 1],
                'arena_stat_gains' => ['per_level' => 1],
                'rift_xp' => ['per_level' => 1],
                'rift_stat_gains' => ['per_level' => 1],
                'world_boss_xp' => ['per_level' => 100],
            ];

            # Calculate bonuses
            $bonuses = [
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

            # Apply prefix bonus
            if (isset($potion_prefix_definitions[$active_potion['prefix']])) {
                $bonuses[$active_potion['prefix']] = $active_potion['level'] * $potion_prefix_definitions[$active_potion['prefix']]['per_level'];
            }

            # Apply suffix bonus
            if (isset($potion_suffix_definitions[$active_potion['suffix']])) {
                $bonuses[$active_potion['suffix']] = $active_potion['level'] * $potion_suffix_definitions[$active_potion['suffix']]['per_level'];
            }

            return $bonuses;
        }

    }
