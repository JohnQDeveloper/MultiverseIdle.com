<?php

declare(strict_types=1);

class Battle
{
    // Battle constants
    private const HEALTH_MULTIPLIER = 5;
    private const SCORCHED_DAMAGE_BONUS = 1.2;
    private const HYPOTHERMIA_REDUCTION = 0.8;
    private const ANTIMAGIC_REDUCTION = 0.8;
    private const RESISTANCE_CAP = 75;
    private const MINIMUM_DAMAGE = 1;
    private const HEALING_RAIN_PERCENT = 0.2;
    private const GREATER_HEAL_PERCENT = 0.35;
    private const AOE_SPELL_PERCENT = 0.2;
    private const STATUS_EFFECT_DURATION = 3;
    private const STATUS_EFFECT_CHANCE = 0.5;

    /**
     * Calculates all bonuses from equipped weapon and armor
     *
     * @param int $equipped_weapon Gear ID of weapon (0 if none equipped)
     * @param int $equipped_armor Gear ID of armor (0 if none equipped)
     * @return array<string, int> Bonus values for stats, damage types, and resistances
     */
    private function calculateGearBonuses(int $equipped_weapon, int $equipped_armor): array
    {
            $gear = new Gear();
            $bonuses = [
                // Flat stat bonuses
                'strength' => 0,
                'health' => 0,
                'dexterity' => 0,
                'wisdom' => 0,
                // Percent stat bonuses (from base_bonuses)
                'strength_percent' => 0,
                'health_percent' => 0,
                'dexterity_percent' => 0,
                'wisdom_percent' => 0,
                'resistances_percent' => 0, // All resistances from base_bonuses
                // Damage bonuses (percent)
                'physical_damage' => 0,
                'cold_damage' => 0,
                'fire_damage' => 0,
                // Resistance bonuses (percent)
                'physical_resistance' => 0,
                'cold_resistance' => 0,
                'fire_resistance' => 0,
            ];

            // Process weapon
            if ($equipped_weapon > 0 && $gear->LoadItemByGearID($equipped_weapon)) {
                $this->applyGearBonuses($bonuses, $gear->Data);
            }

            // Process armor
            if ($equipped_armor > 0 && $gear->LoadItemByGearID($equipped_armor)) {
                $this->applyGearBonuses($bonuses, $gear->Data);
            }

            return $bonuses;
        }

        // Helper to apply a single gear item's bonuses
        private function applyGearBonuses(array &$bonuses, array $gearData): void
        {
            // Apply base bonuses (these are percentage bonuses to stats)
            if (!empty($gearData['base_bonuses'])) {
                foreach ($gearData['base_bonuses'] as $stat => $value) {
                    if ($stat === 'resistances') {
                        $bonuses['resistances_percent'] += $value;
                    } elseif (isset($bonuses[$stat . '_percent'])) {
                        $bonuses[$stat . '_percent'] += $value;
                    }
                }
            }

            // Apply affixes
            if (!empty($gearData['affixes'])) {
                foreach ($gearData['affixes'] as $affix) {
                    $key = $affix['key'];
                    $value = $affix['value'];
                    $type = $affix['type'];

                    // Flat stat bonuses
                    if ($type === 'flat' && isset($bonuses[$key])) {
                        $bonuses[$key] += $value;
                    }
                    // Percent bonuses (damage and resistance)
                    elseif ($type === 'percent' && isset($bonuses[$key])) {
                        $bonuses[$key] += $value;
                    }
                }
            }
        }

        // Apply gear bonuses to party config stats
        private function applyGearToStats(array &$member, array $bonuses): void
        {
            // Apply percent bonuses from base_bonuses first (multiply base stat)
            if ($bonuses['strength_percent'] > 0) {
                $member['strength'] += (int)floor($member['strength'] * $bonuses['strength_percent'] / 100);
            }
            if ($bonuses['health_percent'] > 0) {
                $member['health'] += (int)floor($member['health'] * $bonuses['health_percent'] / 100);
            }
            if ($bonuses['dexterity_percent'] > 0) {
                $member['dexterity'] += (int)floor($member['dexterity'] * $bonuses['dexterity_percent'] / 100);
            }
            if ($bonuses['wisdom_percent'] > 0) {
                $member['wisdom'] += (int)floor($member['wisdom'] * $bonuses['wisdom_percent'] / 100);
            }

            // Apply flat stat bonuses from affixes
            $member['strength'] += $bonuses['strength'];
            $member['health'] += $bonuses['health'];
            $member['dexterity'] += $bonuses['dexterity'];
            $member['wisdom'] += $bonuses['wisdom'];
        }

        // Apply damage bonus based on damage type
        private function applyDamageBonus(int $damage, string $damage_type, array $bonuses): int
        {
            $bonus_percent = 0;
            if ($damage_type === '' || $damage_type === ' physical') {
                $bonus_percent = $bonuses['physical_damage'];
            } elseif ($damage_type === ' fire') {
                $bonus_percent = $bonuses['fire_damage'];
            } elseif ($damage_type === ' cold') {
                $bonus_percent = $bonuses['cold_damage'];
            }

            if ($bonus_percent > 0) {
                $damage += (int)floor($damage * $bonus_percent / 100);
            }
            return $damage;
        }

        // Apply resistance reduction based on damage type
        private function applyResistance(int $damage, string $damage_type, array $bonuses): int
        {
            $resistance_percent = 0;
            if ($damage_type === '' || $damage_type === ' physical') {
                $resistance_percent = $bonuses['physical_resistance'];
            } elseif ($damage_type === ' fire') {
                $resistance_percent = $bonuses['fire_resistance'];
            } elseif ($damage_type === ' cold') {
                $resistance_percent = $bonuses['cold_resistance'];
            }

            // Apply resistances_percent bonus (from plate armor) to all resistances
            $resistance_percent += $bonuses['resistances_percent'];

            // Cap resistance at 75%
            $resistance_percent = min($resistance_percent, self::RESISTANCE_CAP);

            if ($resistance_percent > 0) {
                $damage -= (int)floor($damage * $resistance_percent / 100);
            }
            return max($damage, self::MINIMUM_DAMAGE); // Minimum 1 damage
        }

        /**
         * Performs a basic attack from one combatant to another
         *
         * @param array<string, mixed> $attacker_config Config of the attacking side
         * @param array<string, mixed> $target_config Config of the target side
         * @param array<string, array<string, array<string, int>>> $status_effects Status effects tracker
         * @param array<string, array<string, array<string, int>>> $gear_bonuses Gear bonuses for both sides
         * @param string $attacker_side 'party' or 'monster'
         * @param string $attacker_position 'frontline' or 'backline'
         * @param string $target_side 'party' or 'monster'
         * @param string $target_position 'frontline' or 'backline'
         * @return array{hit: bool, damage: int, log: string}
         */
        private function performBasicAttack(
            array &$attacker_config,
            array &$target_config,
            array &$status_effects,
            array $gear_bonuses,
            string $attacker_side,
            string $attacker_position,
            string $target_side,
            string $target_position
        ): array
        {
            // Load stats
            $dex = (int)$attacker_config['members'][$attacker_position]['dexterity'];
            $str = (int)$attacker_config['members'][$attacker_position]['strength'];
            $target_dex = (int)$target_config['members'][$target_position]['dexterity'];

            // Apply Hypothermia debuff to attacker
            if (isset($status_effects[$attacker_side][$attacker_position]['Hypothermia']) &&
                $status_effects[$attacker_side][$attacker_position]['Hypothermia'] > 0) {
                $dex = (int)floor($dex * self::HYPOTHERMIA_REDUCTION);
            }

            // Apply Hypothermia debuff to target
            if (isset($status_effects[$target_side][$target_position]['Hypothermia']) &&
                $status_effects[$target_side][$target_position]['Hypothermia'] > 0) {
                $target_dex = (int)floor($target_dex * self::HYPOTHERMIA_REDUCTION);
            }

            // Calculate hit chance
            $hit_chance = $dex / ($dex + $target_dex);

            // Check if attack hits
            if (rand(0, 100) / 100 <= $hit_chance) {
                // Hit
                $damage = (int)$str;
                $damage_type = "";

                // Check for Flaming Blades buff
                if (isset($status_effects[$attacker_side][$attacker_position]['FlamingBlades']) &&
                    $status_effects[$attacker_side][$attacker_position]['FlamingBlades'] > 0) {
                    $damage_type = " fire";
                    // Check if target has Scorched for bonus fire damage
                    if (isset($status_effects[$target_side][$target_position]['Scorched']) &&
                        $status_effects[$target_side][$target_position]['Scorched'] > 0) {
                        $damage = (int)floor($damage * self::SCORCHED_DAMAGE_BONUS);
                    }
                    // 50% chance to apply Scorched
                    if (rand(0, 100) / 100 <= self::STATUS_EFFECT_CHANCE) {
                        $status_effects[$target_side][$target_position]['Scorched'] = self::STATUS_EFFECT_DURATION;
                    }
                }

                // Check for Frost Blades buff
                if (isset($status_effects[$attacker_side][$attacker_position]['FrostBlades']) &&
                    $status_effects[$attacker_side][$attacker_position]['FrostBlades'] > 0) {
                    $damage_type = " cold";
                    // 50% chance to apply Hypothermia
                    if (rand(0, 100) / 100 <= self::STATUS_EFFECT_CHANCE) {
                        $status_effects[$target_side][$target_position]['Hypothermia'] = self::STATUS_EFFECT_DURATION;
                    }
                }

                // Check for Antimage buff
                if (isset($status_effects[$attacker_side][$attacker_position]['Antimage']) &&
                    $status_effects[$attacker_side][$attacker_position]['Antimage'] > 0) {
                    // 50% chance to apply Antimagic
                    if (rand(0, 100) / 100 <= self::STATUS_EFFECT_CHANCE) {
                        $status_effects[$target_side][$target_position]['Antimagic'] = self::STATUS_EFFECT_DURATION;
                    }
                }

                // Apply damage bonus from gear
                $damage = $this->applyDamageBonus($damage, $damage_type, $gear_bonuses[$attacker_side][$attacker_position]);
                // Apply resistance from target's gear
                $damage = $this->applyResistance($damage, $damage_type, $gear_bonuses[$target_side][$target_position]);

                // Apply damage
                $target_config['members'][$target_position]['current_health'] -= $damage;
                // Cap health at 0 minimum
                if ($target_config['members'][$target_position]['current_health'] < 0) {
                    $target_config['members'][$target_position]['current_health'] = 0;
                }

                $attacker_name = ucfirst($attacker_side) . " " . ucfirst($attacker_position);
                return [
                    'hit' => true,
                    'damage' => $damage,
                    'log' => "$attacker_name hits $target_position for $damage$damage_type damage."
                ];
            } else {
                // Miss
                $attacker_name = ucfirst($attacker_side) . " " . ucfirst($attacker_position);
                return [
                    'hit' => false,
                    'damage' => 0,
                    'log' => "$attacker_name misses $target_position."
                ];
            }
        }

        /**
         * Executes abilities for a combatant
         *
         * @param array<string, mixed> $caster_config Config of the caster's side
         * @param array<string, mixed> $enemy_config Config of the enemy side
         * @param array<string, array<string, array<string, int>>> $status_effects Status effects tracker
         * @param array<string, array<string, array<string, int>>> $gear_bonuses Gear bonuses for both sides
         * @param string $caster_side 'party' or 'monster'
         * @param string $caster_position 'frontline' or 'backline'
         * @param string $enemy_side 'party' or 'monster'
         * @return array<string>
         */
        private function executeAbilities(
            array &$caster_config,
            array &$enemy_config,
            array &$status_effects,
            array $gear_bonuses,
            string $caster_side,
            string $caster_position,
            string $enemy_side
        ): array
        {
            $logs = [];
            $wis = (int)$caster_config['members'][$caster_position]['wisdom'];

            // Check for Antimagic debuff on caster
            if (isset($status_effects[$caster_side][$caster_position]['Antimagic']) &&
                $status_effects[$caster_side][$caster_position]['Antimagic'] > 0) {
                $wis = (int)floor($wis * self::ANTIMAGIC_REDUCTION);
            }

            // Pick a random living enemy for wisdom check
            $living_enemies = [];
            if ($enemy_config['members']['frontline']['current_health'] > 0) {
                $living_enemies[] = 'frontline';
            }
            if ($enemy_config['members']['backline']['current_health'] > 0) {
                $living_enemies[] = 'backline';
            }

            if (empty($living_enemies)) {
                return $logs;
            }

            $random_enemy = $living_enemies[array_rand($living_enemies)];
            $enemy_wis = (int)$enemy_config['members'][$random_enemy]['wisdom'];

            // Calculate ability chance
            $ability_chance = $wis / ($wis + $enemy_wis);

            if (rand(0, 100) / 100 <= $ability_chance) {
                $skills = $caster_config['members'][$caster_position]['skills'];
                $caster_name = ucfirst($caster_side) . " " . ucfirst($caster_position);

                // Healing Rain
                if (in_array('Healing Rain', $skills)) {
                    $heal_amount = (int)floor($wis * self::HEALING_RAIN_PERCENT);
                    $frontline_max_health = $caster_config['members']['frontline']['health'] * self::HEALTH_MULTIPLIER;
                    $backline_max_health = $caster_config['members']['backline']['health'] * self::HEALTH_MULTIPLIER;

                    if ($caster_config['members']['frontline']['current_health'] > 0) {
                        $caster_config['members']['frontline']['current_health'] += $heal_amount;
                        if ($caster_config['members']['frontline']['current_health'] > $frontline_max_health) {
                            $caster_config['members']['frontline']['current_health'] = $frontline_max_health;
                        }
                    }

                    if ($caster_config['members']['backline']['current_health'] > 0) {
                        $caster_config['members']['backline']['current_health'] += $heal_amount;
                        if ($caster_config['members']['backline']['current_health'] > $backline_max_health) {
                            $caster_config['members']['backline']['current_health'] = $backline_max_health;
                        }
                    }

                    $logs[] = "$caster_name casts Healing Rain, healing all allies for $heal_amount.";
                }

                // Greater Heal
                if (in_array('Greater Heal', $skills)) {
                    $heal_amount = (int)floor($wis * self::GREATER_HEAL_PERCENT);
                    $frontline_max_health = $caster_config['members']['frontline']['health'] * self::HEALTH_MULTIPLIER;
                    $backline_max_health = $caster_config['members']['backline']['health'] * self::HEALTH_MULTIPLIER;

                    // Find lowest health living ally
                    $heal_target = null;
                    $lowest_health = $caster_config['members']['frontline']['current_health'] >
                        $caster_config['members']['backline']['current_health']
                        ? $caster_config['members']['backline']['current_health']
                        : $caster_config['members']['frontline']['current_health'];

                    if ($caster_config['members']['frontline']['current_health'] > 0 &&
                        $caster_config['members']['frontline']['current_health'] <= $lowest_health) {
                        $lowest_health = $caster_config['members']['frontline']['current_health'];
                        $heal_target = 'frontline';
                    }
                    if ($caster_config['members']['backline']['current_health'] > 0 &&
                        $caster_config['members']['backline']['current_health'] < $lowest_health) {
                        $lowest_health = $caster_config['members']['backline']['current_health'];
                        $heal_target = 'backline';
                    }

                    if ($heal_target !== null) {
                        $max_health = ($heal_target === 'frontline') ? $frontline_max_health : $backline_max_health;
                        $caster_config['members'][$heal_target]['current_health'] += $heal_amount;
                        if ($caster_config['members'][$heal_target]['current_health'] > $max_health) {
                            $caster_config['members'][$heal_target]['current_health'] = $max_health;
                        }
                        $logs[] = "$caster_name casts Greater Heal on $heal_target for $heal_amount.";
                    }
                }

                // Firestorm
                if (in_array('Firestorm', $skills)) {
                    $base_damage = (int)floor($wis * self::AOE_SPELL_PERCENT);

                    // Apply Scorched and deal damage to frontline enemy
                    if ($enemy_config['members']['frontline']['current_health'] > 0) {
                        $status_effects[$enemy_side]['frontline']['Scorched'] = self::STATUS_EFFECT_DURATION;
                        $damage = (int)$base_damage;
                        if ($status_effects[$enemy_side]['frontline']['Scorched'] > 0) {
                            $damage = (int)floor($damage * self::SCORCHED_DAMAGE_BONUS);
                        }
                        // Apply gear bonuses and resistances
                        $damage = $this->applyDamageBonus($damage, ' fire', $gear_bonuses[$caster_side][$caster_position]);
                        $damage = $this->applyResistance($damage, ' fire', $gear_bonuses[$enemy_side]['frontline']);
                        $enemy_config['members']['frontline']['current_health'] -= $damage;
                        // Cap health at 0 minimum
                        if ($enemy_config['members']['frontline']['current_health'] < 0) {
                            $enemy_config['members']['frontline']['current_health'] = 0;
                        }
                        $enemy_name = ucfirst($enemy_side) . " Frontline";
                        $logs[] = "$caster_name casts Firestorm, scorching and hitting $enemy_name for $damage fire damage.";
                    }

                    // Apply Scorched and deal damage to backline enemy
                    if ($enemy_config['members']['backline']['current_health'] > 0) {
                        $status_effects[$enemy_side]['backline']['Scorched'] = self::STATUS_EFFECT_DURATION;
                        $damage = (int)$base_damage;
                        if ($status_effects[$enemy_side]['backline']['Scorched'] > 0) {
                            $damage = (int)floor($damage * self::SCORCHED_DAMAGE_BONUS);
                        }
                        // Apply gear bonuses and resistances
                        $damage = $this->applyDamageBonus($damage, ' fire', $gear_bonuses[$caster_side][$caster_position]);
                        $damage = $this->applyResistance($damage, ' fire', $gear_bonuses[$enemy_side]['backline']);
                        $enemy_config['members']['backline']['current_health'] -= $damage;
                        // Cap health at 0 minimum
                        if ($enemy_config['members']['backline']['current_health'] < 0) {
                            $enemy_config['members']['backline']['current_health'] = 0;
                        }
                        $enemy_name = ucfirst($enemy_side) . " Backline";
                        $logs[] = "$caster_name casts Firestorm, scorching and hitting $enemy_name for $damage fire damage.";
                    }
                }

                // Blizzard
                if (in_array('Blizzard', $skills)) {
                    $base_damage = (int)floor($wis * self::AOE_SPELL_PERCENT);

                    // Apply Hypothermia and deal damage to frontline enemy
                    if ($enemy_config['members']['frontline']['current_health'] > 0) {
                        $status_effects[$enemy_side]['frontline']['Hypothermia'] = self::STATUS_EFFECT_DURATION;
                        $damage = (int)$base_damage;
                        // Apply gear bonuses and resistances
                        $damage = $this->applyDamageBonus($damage, ' cold', $gear_bonuses[$caster_side][$caster_position]);
                        $damage = $this->applyResistance($damage, ' cold', $gear_bonuses[$enemy_side]['frontline']);
                        $enemy_config['members']['frontline']['current_health'] -= $damage;
                        // Cap health at 0 minimum
                        if ($enemy_config['members']['frontline']['current_health'] < 0) {
                            $enemy_config['members']['frontline']['current_health'] = 0;
                        }
                        $enemy_name = ucfirst($enemy_side) . " Frontline";
                        $logs[] = "$caster_name casts Blizzard, chilling and hitting $enemy_name for $damage cold damage.";
                    }

                    // Apply Hypothermia and deal damage to backline enemy
                    if ($enemy_config['members']['backline']['current_health'] > 0) {
                        $status_effects[$enemy_side]['backline']['Hypothermia'] = self::STATUS_EFFECT_DURATION;
                        $damage = (int)$base_damage;
                        // Apply gear bonuses and resistances
                        $damage = $this->applyDamageBonus($damage, ' cold', $gear_bonuses[$caster_side][$caster_position]);
                        $damage = $this->applyResistance($damage, ' cold', $gear_bonuses[$enemy_side]['backline']);
                        $enemy_config['members']['backline']['current_health'] -= $damage;
                        // Cap health at 0 minimum
                        if ($enemy_config['members']['backline']['current_health'] < 0) {
                            $enemy_config['members']['backline']['current_health'] = 0;
                        }
                        $enemy_name = ucfirst($enemy_side) . " Backline";
                        $logs[] = "$caster_name casts Blizzard, chilling and hitting $enemy_name for $damage cold damage.";
                    }
                }

                // Flaming Blades
                if (in_array('Flaming Blades', $skills)) {
                    $status_effects[$caster_side][$caster_position]['FlamingBlades'] = self::STATUS_EFFECT_DURATION;
                    $logs[] = "$caster_name activates Flaming Blades!";
                }

                // Antimage
                if (in_array('Antimage', $skills)) {
                    $status_effects[$caster_side][$caster_position]['Antimage'] = self::STATUS_EFFECT_DURATION;
                    $logs[] = "$caster_name activates Antimage!";
                }

                // Frost Blades
                if (in_array('Frost Blades', $skills)) {
                    $status_effects[$caster_side][$caster_position]['FrostBlades'] = self::STATUS_EFFECT_DURATION;
                    $logs[] = "$caster_name activates Frost Blades!";
                }
            }

            return $logs;
        }

        /**
         * Executes a complete turn for one combatant (attack + abilities)
         *
         * @param array<string, mixed> $party_config Party configuration
         * @param array<string, mixed> $monster_config Monster configuration
         * @param array<string, array<string, array<string, int>>> $status_effects Status effects tracker
         * @param array<string, array<string, array<string, int>>> $gear_bonuses Gear bonuses for both sides
         * @param string $attacker_side 'party' or 'monster'
         * @param string $attacker_position 'frontline' or 'backline'
         * @return array<string>
         */
        private function executeCombatantTurn(
            array &$party_config,
            array &$monster_config,
            array &$status_effects,
            array $gear_bonuses,
            string $attacker_side,
            string $attacker_position
        ): array
        {
            $logs = [];

            // Determine which configs to use based on attacker side
            if ($attacker_side === 'party') {
                $attacker_config = &$party_config;
                $enemy_config = &$monster_config;
                $enemy_side = 'monster';
            } else {
                $attacker_config = &$monster_config;
                $enemy_config = &$party_config;
                $enemy_side = 'party';
            }

            // Check if attacker is alive
            if ($attacker_config['members'][$attacker_position]['current_health'] <= 0) {
                return $logs;
            }

            // Determine target (prioritize frontline, fall back to backline)
            $target_position = 'frontline';
            if ($enemy_config['members']['frontline']['current_health'] <= 0) {
                $target_position = 'backline';
            }

            // Perform basic attack
            $attack_result = $this->performBasicAttack(
                $attacker_config,
                $enemy_config,
                $status_effects,
                $gear_bonuses,
                $attacker_side,
                $attacker_position,
                $enemy_side,
                $target_position
            );
            $logs[] = $attack_result['log'];

            // Execute abilities
            $ability_logs = $this->executeAbilities(
                $attacker_config,
                $enemy_config,
                $status_effects,
                $gear_bonuses,
                $attacker_side,
                $attacker_position,
                $enemy_side
            );
            $logs = array_merge($logs, $ability_logs);

            return $logs;
        }

        /**
         * Simulates 1000 arena battles and returns win/loss statistics
         *
         * @param object $Character Character instance with Data property containing party_json
         * @param int $arena_floor Floor level for monster stat calculation
         * @return array{won: int, lost: int, total: int} Battle outcome statistics
         */
        public function SimulateArenaFloor(object $Character, int $arena_floor): array
        {
            $won = 0;
            $lost = 0;
            $total = 1000;

            // Testing loop
            while ($total > 0) {
                $party_config = $Character->Data['party_json'];

                $monster_strength = calculate_monster_attribute($arena_floor);
                $monster_dexterity = calculate_monster_attribute($arena_floor);
                $monster_health = calculate_monster_attribute($arena_floor);
                $monster_wisdom = calculate_monster_attribute($arena_floor);
                $monster_ability = SKILL_GEMS[array_rand(SKILL_GEMS)]['Name'];

                // Simulate Battle
                $Battle = new Battle();
                $battle_result = $Battle->Battle(
                //player party
                $party_config,
                //monster party
                [
                    "members" => [
                        "frontline" => [
                            "class" => "warrior",
                            "level" => $arena_floor,
                            "strength" => $monster_strength,
                            "dexterity" => $monster_dexterity,
                            "health" => $monster_health,
                            "wisdom" => $monster_wisdom,
                            "gear" => [],
                            "skills" => [$monster_ability, $monster_ability],
                        ],
                        "backline" => [
                            "class" => "warrior",
                            "level" => $arena_floor,
                            "strength" => $monster_strength,
                            "dexterity" => $monster_dexterity,
                            "health" => $monster_health,
                            "wisdom" => $monster_wisdom,
                            "gear" => [],
                            "skills" => [$monster_ability, $monster_ability],
                        ]
                    ]
                ], false);


                if ($battle_result['player_won']) {
                    $won++;
                } else {
                    $lost++;
                }
                $total--;
            }

            return ["won" => $won, "lost" => $lost, "total" => $won+$lost];
        }
        /**
         * Simulates a battle between party and monster configurations
         *
         * @param array{members: array{frontline: array<string, mixed>, backline: array<string, mixed>}} $party_config
         * @param array{members: array{frontline: array<string, mixed>, backline: array<string, mixed>}} $monster_config
         * @param bool $echo_log Whether to echo battle progress to output
         * @return array{player_won: bool, log: array<string>} Battle result with win status and combat log
         */
        public function Battle(array $party_config, array $monster_config, bool $echo_log = true): array
        {
          $return_me_log = [];

          // Calculate and apply gear bonuses for party members
          $gear_bonuses = [
              'party' => [
                  'frontline' => $this->calculateGearBonuses(
                      $party_config['members']['frontline']['equipped_weapon'] ?? 0,
                      $party_config['members']['frontline']['equipped_armor'] ?? 0
                  ),
                  'backline' => $this->calculateGearBonuses(
                      $party_config['members']['backline']['equipped_weapon'] ?? 0,
                      $party_config['members']['backline']['equipped_armor'] ?? 0
                  )
              ],
              'monster' => [
                  'frontline' => $this->calculateGearBonuses(0, 0), // Monsters have no gear
                  'backline' => $this->calculateGearBonuses(0, 0)
              ]
          ];

          // Apply stat bonuses from gear to party members
          $this->applyGearToStats($party_config['members']['frontline'], $gear_bonuses['party']['frontline']);
          $this->applyGearToStats($party_config['members']['backline'], $gear_bonuses['party']['backline']);

        // Actual health is 5x normal for battle purposes
          $party_config['members']['frontline']['current_health'] =
          $party_config['members']['frontline']['health'] * self::HEALTH_MULTIPLIER;
          $party_config['members']['backline']['current_health'] =
          $party_config['members']['backline']['health'] * self::HEALTH_MULTIPLIER;

          $monster_config['members']['frontline']['current_health'] =
          $monster_config['members']['frontline']['health'] * self::HEALTH_MULTIPLIER;
          $monster_config['members']['backline']['current_health'] =
          $monster_config['members']['backline']['health'] * self::HEALTH_MULTIPLIER;

          // Initialize status effects tracking
          $status_effects = [
              'party' => [
                  'frontline' => [],
                  'backline' => []
              ],
              'monster' => [
                  'frontline' => [],
                  'backline' => []
              ]
          ];

          $running = true;
           while ($running) {

            if ($party_config['members']['frontline']['current_health'] <= 0 &&
            $party_config['members']['backline']['current_health'] <= 0) {
                if ($echo_log) {
                    echo "Finished with party @ ".$party_config['members']['frontline']['current_health'].", ".
                        $party_config['members']['backline']['current_health']."\n";
                }
                $running = false;
            }

            if ($monster_config['members']['frontline']['current_health'] <= 0 &&
            $monster_config['members']['backline']['current_health'] <= 0) {
                if ($echo_log) {
                    echo "Finished with monsters @ ".$monster_config['members']['frontline']['current_health'].", ".
                        $monster_config['members']['backline']['current_health']."\n";
                }
                $running = false;
            }

               // Player turn
                $return_me_log = array_merge(
                    $return_me_log,
                    $this->executeCombatantTurn($party_config, $monster_config, $status_effects, $gear_bonuses, 'party', 'frontline')
                );
                $return_me_log = array_merge(
                    $return_me_log,
                    $this->executeCombatantTurn($party_config, $monster_config, $status_effects, $gear_bonuses, 'party', 'backline')
                );

               // Monster turn
                $return_me_log = array_merge(
                    $return_me_log,
                    $this->executeCombatantTurn($party_config, $monster_config, $status_effects, $gear_bonuses, 'monster', 'frontline')
                );
                $return_me_log = array_merge(
                    $return_me_log,
                    $this->executeCombatantTurn($party_config, $monster_config, $status_effects, $gear_bonuses, 'monster', 'backline')
                );

                // Tick down status effects at end of round
                foreach (['party', 'monster'] as $side) {
                    foreach (['frontline', 'backline'] as $position) {
                        foreach ($status_effects[$side][$position] as $effect => $duration) {
                            $status_effects[$side][$position][$effect]--;
                            if ($status_effects[$side][$position][$effect] <= 0) {
                                unset($status_effects[$side][$position][$effect]);
                            }
                        }
                    }
                }

           }

           $return_me_log[] = "Battle ended.";
           $return_me_log[] = "Party Frontline Health: ".$party_config['members']['frontline']['current_health'];
           $return_me_log[] = "Party Backline Health: ".$party_config['members']['backline']['current_health'];
           $return_me_log[] = "Monster Frontline Health: ".$monster_config['members']['frontline']['current_health'];
           $return_me_log[] = "Monster Backline Health: ".$monster_config['members']['backline']['current_health'];

           return [
            'player_won' =>
            ($party_config['members']['frontline']['current_health'] > 0 ||
            $party_config['members']['backline']['current_health'] > 0) ? true : false,

            'log' => $return_me_log
           ];
        }

        /**
         * Simulates a world boss battle for 100 rounds, tracking total damage dealt
         *
         * The world boss is a damage sponge that doesn't attack back.
         * Players deal damage for 100 rounds and the total is recorded.
         *
         * @param array{members: array{frontline: array<string, mixed>, backline: array<string, mixed>}} $party_config
         * @return array{total_damage: int, log: array<string>} Total damage dealt and combat log
         */
        public function WorldBossBattle(array $party_config): array
        {
            $total_damage = 0;
            $return_me_log = [];
            $rounds = 100;

            // Calculate and apply gear bonuses for party members
            $gear_bonuses = [
                'party' => [
                    'frontline' => $this->calculateGearBonuses(
                        $party_config['members']['frontline']['equipped_weapon'] ?? 0,
                        $party_config['members']['frontline']['equipped_armor'] ?? 0
                    ),
                    'backline' => $this->calculateGearBonuses(
                        $party_config['members']['backline']['equipped_weapon'] ?? 0,
                        $party_config['members']['backline']['equipped_armor'] ?? 0
                    )
                ],
                'monster' => [
                    'frontline' => $this->calculateGearBonuses(0, 0),
                    'backline' => $this->calculateGearBonuses(0, 0)
                ]
            ];

            // Apply stat bonuses from gear to party members
            $this->applyGearToStats($party_config['members']['frontline'], $gear_bonuses['party']['frontline']);
            $this->applyGearToStats($party_config['members']['backline'], $gear_bonuses['party']['backline']);

            // Set party health
            $party_config['members']['frontline']['current_health'] =
                $party_config['members']['frontline']['health'] * self::HEALTH_MULTIPLIER;
            $party_config['members']['backline']['current_health'] =
                $party_config['members']['backline']['health'] * self::HEALTH_MULTIPLIER;

            // World boss is a damage sponge - infinite health, no attacks
            $world_boss_health = PHP_INT_MAX;

            // Initialize status effects tracking
            $status_effects = [
                'party' => [
                    'frontline' => [],
                    'backline' => []
                ],
                'monster' => [
                    'frontline' => [],
                    'backline' => []
                ]
            ];

            for ($round = 1; $round <= $rounds; $round++) {
                $round_damage = 0;

                // Frontline attack
                if ($party_config['members']['frontline']['current_health'] > 0) {
                    $damage = $this->calculateWorldBossAttackDamage(
                        $party_config['members']['frontline'],
                        $status_effects,
                        $gear_bonuses['party']['frontline'],
                        'frontline'
                    );
                    $round_damage += $damage;
                }

                // Backline attack
                if ($party_config['members']['backline']['current_health'] > 0) {
                    $damage = $this->calculateWorldBossAttackDamage(
                        $party_config['members']['backline'],
                        $status_effects,
                        $gear_bonuses['party']['backline'],
                        'backline'
                    );
                    $round_damage += $damage;
                }

                // Execute ability damage (wisdom-based)
                $ability_damage = $this->calculateWorldBossAbilityDamage(
                    $party_config,
                    $status_effects,
                    $gear_bonuses
                );
                $round_damage += $ability_damage;

                $total_damage += $round_damage;

                // Tick down status effects at end of round
                foreach (['party', 'monster'] as $side) {
                    foreach (['frontline', 'backline'] as $position) {
                        foreach ($status_effects[$side][$position] as $effect => $duration) {
                            $status_effects[$side][$position][$effect]--;
                            if ($status_effects[$side][$position][$effect] <= 0) {
                                unset($status_effects[$side][$position][$effect]);
                            }
                        }
                    }
                }
            }

            $return_me_log[] = "World Boss battle completed after $rounds rounds.";
            $return_me_log[] = "Total damage dealt: $total_damage";

            return [
                'total_damage' => $total_damage,
                'log' => $return_me_log
            ];
        }

        /**
         * Calculates damage for a single attack against the world boss
         *
         * @param array<string, mixed> $attacker Party member config
         * @param array<string, array<string, array<string, int>>> $status_effects Status effects tracker
         * @param array<string, int> $gear_bonuses Attacker's gear bonuses
         * @param string $position 'frontline' or 'backline'
         * @return int Damage dealt
         */
        private function calculateWorldBossAttackDamage(
            array $attacker,
            array &$status_effects,
            array $gear_bonuses,
            string $position
        ): int {
            $dex = (int)$attacker['dexterity'];
            $str = (int)$attacker['strength'];

            // Apply Hypothermia debuff
            if (isset($status_effects['party'][$position]['Hypothermia']) &&
                $status_effects['party'][$position]['Hypothermia'] > 0) {
                $dex = (int)floor($dex * self::HYPOTHERMIA_REDUCTION);
            }

            // World boss has 0 dexterity, so hit chance is essentially 100%
            // But we still roll for some variance
            $hit_chance = 0.9; // 90% base hit rate against world boss

            if (rand(0, 100) / 100 <= $hit_chance) {
                $damage = $str;
                $damage_type = '';

                // Check for Flaming Blades buff
                if (isset($status_effects['party'][$position]['FlamingBlades']) &&
                    $status_effects['party'][$position]['FlamingBlades'] > 0) {
                    $damage_type = ' fire';
                    // Bonus fire damage (world boss is always scorched from firestorms)
                    $damage = (int)floor($damage * self::SCORCHED_DAMAGE_BONUS);
                }

                // Check for Frost Blades buff
                if (isset($status_effects['party'][$position]['FrostBlades']) &&
                    $status_effects['party'][$position]['FrostBlades'] > 0) {
                    $damage_type = ' cold';
                }

                // Apply damage bonus from gear
                $damage = $this->applyDamageBonus($damage, $damage_type, $gear_bonuses);

                return max($damage, self::MINIMUM_DAMAGE);
            }

            return 0;
        }

        /**
         * Calculates ability damage against the world boss
         *
         * @param array<string, mixed> $party_config Party configuration
         * @param array<string, array<string, array<string, int>>> $status_effects Status effects tracker
         * @param array<string, array<string, array<string, int>>> $gear_bonuses Gear bonuses
         * @return int Total ability damage dealt
         */
        private function calculateWorldBossAbilityDamage(
            array &$party_config,
            array &$status_effects,
            array $gear_bonuses
        ): int {
            $total_ability_damage = 0;

            foreach (['frontline', 'backline'] as $position) {
                if ($party_config['members'][$position]['current_health'] <= 0) {
                    continue;
                }

                $wis = (int)$party_config['members'][$position]['wisdom'];

                // Apply Antimagic debuff
                if (isset($status_effects['party'][$position]['Antimagic']) &&
                    $status_effects['party'][$position]['Antimagic'] > 0) {
                    $wis = (int)floor($wis * self::ANTIMAGIC_REDUCTION);
                }

                // 70% ability success rate against world boss (low wisdom)
                $ability_chance = 0.7;

                if (rand(0, 100) / 100 <= $ability_chance) {
                    $skills = $party_config['members'][$position]['skills'] ?? [];

                    // Firestorm damage
                    if (in_array('Firestorm', $skills)) {
                        $damage = (int)floor($wis * self::AOE_SPELL_PERCENT);
                        $damage = (int)floor($damage * self::SCORCHED_DAMAGE_BONUS);
                        $damage = $this->applyDamageBonus($damage, ' fire', $gear_bonuses['party'][$position]);
                        // Double damage for hitting both frontline and backline of boss
                        $total_ability_damage += $damage * 2;
                    }

                    // Blizzard damage
                    if (in_array('Blizzard', $skills)) {
                        $damage = (int)floor($wis * self::AOE_SPELL_PERCENT);
                        $damage = $this->applyDamageBonus($damage, ' cold', $gear_bonuses['party'][$position]);
                        // Double damage for hitting both frontline and backline of boss
                        $total_ability_damage += $damage * 2;
                    }

                    // Activate buff abilities
                    if (in_array('Flaming Blades', $skills)) {
                        $status_effects['party'][$position]['FlamingBlades'] = self::STATUS_EFFECT_DURATION;
                    }
                    if (in_array('Frost Blades', $skills)) {
                        $status_effects['party'][$position]['FrostBlades'] = self::STATUS_EFFECT_DURATION;
                    }
                    if (in_array('Antimage', $skills)) {
                        $status_effects['party'][$position]['Antimage'] = self::STATUS_EFFECT_DURATION;
                    }
                }
            }

            return $total_ability_damage;
        }
    }
