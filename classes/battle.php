<?php

declare(strict_types=1);

class Battle
{
    // Helper function to calculate gear bonuses for a party member
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
            $resistance_percent = min($resistance_percent, 75);

            if ($resistance_percent > 0) {
                $damage -= (int)floor($damage * $resistance_percent / 100);
            }
            return max($damage, 1); // Minimum 1 damage
        }

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

                $ArenaLog .= "Monster stats are $monster_strength STR, $monster_dexterity DEX, " .
                    "$monster_health HEALTH, $monster_wisdom WIS. Monster ability: $monster_ability. <BR />\n";

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
          $party_config['members']['frontline']['health'] * 5;
          $party_config['members']['backline']['current_health'] =
          $party_config['members']['backline']['health'] * 5;

          $monster_config['members']['frontline']['current_health'] =
          $monster_config['members']['frontline']['health'] * 5;
          $monster_config['members']['backline']['current_health'] =
          $monster_config['members']['backline']['health'] * 5;

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
                if ($echo_log)
                    echo "Finished with party @ ".$party_config['members']['frontline']['current_health'].", ".
                $party_config['members']['backline']['current_health']."\n";
                $running = false;
            }

            if ($monster_config['members']['frontline']['current_health'] <= 0 &&
            $monster_config['members']['backline']['current_health'] <= 0) {
                if ($echo_log)
                    echo "Finished with monsters @ ".$monster_config['members']['frontline']['current_health'].", ".
                    $monster_config['members']['backline']['current_health']."\n";

                $running = false;
            }

               // Player turn

                /// frontliner attacks
                if ($party_config['members']['frontline']['current_health'] <= 0) {
                    // dead skip turn
                    #$return_me_log[] = "Party Frontliner is down and cannot attack.";
                }
                else {
                    $target = 'frontline';
                    if ($monster_config['members']['frontline']['current_health'] <= 0) {
                        $target = 'backline';
                    }

                    $dex = (int)$party_config['members']['frontline']['dexterity'];
                    // Check for Hypothermia debuff on attacker
                    if (isset($status_effects['party']['frontline']['Hypothermia']) && $status_effects['party']['frontline']['Hypothermia'] > 0) {
                        $dex = (int)floor($dex * 0.8); // 20% dexterity reduction
                    }
                    $str = (int)$party_config['members']['frontline']['strength'];
                    $wis = (int)$party_config['members']['frontline']['wisdom'];
                    $target_dex = (int)$monster_config['members'][$target]['dexterity'];
                    // Check for Hypothermia debuff on target
                    if (isset($status_effects['monster'][$target]['Hypothermia']) && $status_effects['monster'][$target]['Hypothermia'] > 0) {
                        $target_dex = (int)floor($target_dex * 0.8); // 20% dexterity reduction
                    }
                    $hit_chance = $dex / ($dex + $target_dex);
                    if (rand(0, 100) / 100 <= $hit_chance) {
                        // Hit
                        $damage = (int)$str;
                        $damage_type = "";

                        // Check for Flaming Blades buff
                        if (isset($status_effects['party']['frontline']['FlamingBlades']) && $status_effects['party']['frontline']['FlamingBlades'] > 0) {
                            $damage_type = " fire";
                            // Check if target has Scorched for bonus fire damage
                            if (isset($status_effects['monster'][$target]['Scorched']) && $status_effects['monster'][$target]['Scorched'] > 0) {
                                $damage = (int)floor($damage * 1.2);
                            }
                            // 50% chance to apply Scorched
                            if (rand(0, 100) / 100 <= 0.5) {
                                $status_effects['monster'][$target]['Scorched'] = 3;
                                $return_me_log[] = "Party Frontliner's flaming attack scorches $target!";
                            }
                        }

                        // Check for Frost Blades buff
                        if (isset($status_effects['party']['frontline']['FrostBlades']) && $status_effects['party']['frontline']['FrostBlades'] > 0) {
                            $damage_type = " cold";
                            // 50% chance to apply Hypothermia
                            if (rand(0, 100) / 100 <= 0.5) {
                                $status_effects['monster'][$target]['Hypothermia'] = 3;
                                $return_me_log[] = "Party Frontliner's frost attack chills $target with Hypothermia!";
                            }
                        }

                        // Check for Antimage buff
                        if (isset($status_effects['party']['frontline']['Antimage']) && $status_effects['party']['frontline']['Antimage'] > 0) {
                            // 50% chance to apply Antimagic
                            if (rand(0, 100) / 100 <= 0.5) {
                                $status_effects['monster'][$target]['Antimagic'] = 3;
                                $return_me_log[] = "Party Frontliner's attack applies Antimagic to $target!";
                            }
                        }

                        // Apply damage bonus from gear
                        $damage = $this->applyDamageBonus($damage, $damage_type, $gear_bonuses['party']['frontline']);
                        // Apply resistance from target's gear
                        $damage = $this->applyResistance($damage, $damage_type, $gear_bonuses['monster'][$target]);

                        $monster_config['members'][$target]['current_health'] -= $damage;
                        $return_me_log[] = "Party Frontliner hits $target for $damage$damage_type damage.";
                    } else {
                        // Miss
                        $return_me_log[] = "Party Frontliner misses $target.";
                    }

                    // Ability Runs
                    $wis = (int)$party_config['members']['frontline']['wisdom'];
                    // Check for Antimagic debuff on caster
                    if (isset($status_effects['party']['frontline']['Antimagic']) && $status_effects['party']['frontline']['Antimagic'] > 0) {
                        $wis = (int)floor($wis * 0.8); // 20% wisdom reduction
                    }

                    // Pick a random living enemy for wisdom check
                    $living_enemies = [];
                    if ($monster_config['members']['frontline']['current_health'] > 0) {
                        $living_enemies[] = 'frontline';
                    }
                    if ($monster_config['members']['backline']['current_health'] > 0) {
                        $living_enemies[] = 'backline';
                    }
                    try {
                        $random_enemy = $living_enemies[array_rand($living_enemies)];
                    } catch (ValueError $e) {
                        // No living enemies to target
                        continue;
                    }
                    $enemy_wis = (int)$monster_config['members'][$random_enemy]['wisdom'];

                    // Healing Rain chance: Caster Wisdom / (Caster Wisdom + Random Living Enemy Wisdom)
                    $ability_chance = $wis / ($wis + $enemy_wis);

                    if (rand(0, 100) / 100 <= $ability_chance) {
                        if (in_array('Healing Rain', $party_config['members']['frontline']['skills'])) {
                            // Use Healing Rain - heals all living party members by 20% of Wisdom
                            $heal_amount = (int)floor($wis * 0.2);
                            $frontline_max_health = $party_config['members']['frontline']['health'] * 5;
                            $backline_max_health = $party_config['members']['backline']['health'] * 5;

                            if ($party_config['members']['frontline']['current_health'] > 0) {
                                $party_config['members']['frontline']['current_health'] += $heal_amount;
                                if ($party_config['members']['frontline']['current_health'] > $frontline_max_health) {
                                    $party_config['members']['frontline']['current_health'] = $frontline_max_health;
                                }
                            }

                            if ($party_config['members']['backline']['current_health'] > 0) {
                                $party_config['members']['backline']['current_health'] += $heal_amount;
                                if ($party_config['members']['backline']['current_health'] > $backline_max_health) {
                                    $party_config['members']['backline']['current_health'] = $backline_max_health;
                                }
                            }

                            $return_me_log[] = "Party Frontliner casts Healing Rain, healing all allies for $heal_amount.";
                        }
                        if (in_array('Greater Heal', $party_config['members']['frontline']['skills'])) {
                            // Use Greater Heal - heals the lowest health living party member by 35% of Wisdom
                            $heal_amount = (int)floor($wis * 0.35);
                            $frontline_max_health = $party_config['members']['frontline']['health'] * 5;
                            $backline_max_health = $party_config['members']['backline']['health'] * 5;

                            // Find lowest health living ally
                            $heal_target = null;
                            $lowest_health = $party_config['members']['frontline']['current_health'] >
                                $party_config['members']['backline']['current_health']
                                ? $party_config['members']['backline']['current_health']
                                : $party_config['members']['frontline']['current_health'];

                            if ($party_config['members']['frontline']['current_health'] > 0 &&
                               $party_config['members']['frontline']['current_health'] <= $lowest_health) {
                                $lowest_health = $party_config['members']['frontline']['current_health'];
                                $heal_target = 'frontline';
                            }
                            if ($party_config['members']['backline']['current_health'] > 0 &&
                               $party_config['members']['backline']['current_health'] < $lowest_health) {
                                $lowest_health = $party_config['members']['backline']['current_health'];
                                $heal_target = 'backline';
                            }

                            if ($heal_target !== null) {
                                $max_health = ($heal_target === 'frontline') ? $frontline_max_health : $backline_max_health;
                                $party_config['members'][$heal_target]['current_health'] += $heal_amount;
                                if ($party_config['members'][$heal_target]['current_health'] > $max_health) {
                                    $party_config['members'][$heal_target]['current_health'] = $max_health;
                                }
                                $return_me_log[] = "Party Frontliner casts Greater Heal on $heal_target for $heal_amount.";
                            }
                        }
                        if (in_array('Firestorm', $party_config['members']['frontline']['skills'])) {
                            // Use Firestorm - applies Scorched then deals 20% of Wisdom as fire damage to all enemies
                            $base_damage = (int)floor($wis * 0.2);

                            // Apply Scorched and deal damage to frontline enemy
                            if ($monster_config['members']['frontline']['current_health'] > 0) {
                                $status_effects['monster']['frontline']['Scorched'] = 3;
                                $damage = (int)$base_damage;
                                if (isset($status_effects['monster']['frontline']['Scorched']) && $status_effects['monster']['frontline']['Scorched'] > 0) {
                                    $damage = (int)floor($damage * 1.2); // 20% more fire damage
                                }
                                // Apply gear bonuses and resistances
                                $damage = $this->applyDamageBonus($damage, ' fire', $gear_bonuses['party']['frontline']);
                                $damage = $this->applyResistance($damage, ' fire', $gear_bonuses['monster']['frontline']);
                                $monster_config['members']['frontline']['current_health'] -= $damage;
                                $return_me_log[] = "Party Frontliner casts Firestorm, scorching and hitting Monster Frontline for $damage fire damage.";
                            }

                            // Apply Scorched and deal damage to backline enemy
                            if ($monster_config['members']['backline']['current_health'] > 0) {
                                $status_effects['monster']['backline']['Scorched'] = 3;
                                $damage = (int)$base_damage;
                                if (isset($status_effects['monster']['backline']['Scorched']) && $status_effects['monster']['backline']['Scorched'] > 0) {
                                    $damage = (int)floor($damage * 1.2); // 20% more fire damage
                                }
                                // Apply gear bonuses and resistances
                                $damage = $this->applyDamageBonus($damage, ' fire', $gear_bonuses['party']['frontline']);
                                $damage = $this->applyResistance($damage, ' fire', $gear_bonuses['monster']['backline']);
                                $monster_config['members']['backline']['current_health'] -= $damage;
                                $return_me_log[] = "Party Frontliner casts Firestorm, scorching and hitting Monster Backline for $damage fire damage.";
                            }
                        }
                        if (in_array('Blizzard', $party_config['members']['frontline']['skills'])) {
                            // Use Blizzard - deals 20% of Wisdom as cold damage and applies Hypothermia to all enemies
                            $base_damage = (int)floor($wis * 0.2);

                            // Apply Hypothermia and deal damage to frontline enemy
                            if ($monster_config['members']['frontline']['current_health'] > 0) {
                                $status_effects['monster']['frontline']['Hypothermia'] = 3;
                                $damage = (int)$base_damage;
                                // Apply gear bonuses and resistances
                                $damage = $this->applyDamageBonus($damage, ' cold', $gear_bonuses['party']['frontline']);
                                $damage = $this->applyResistance($damage, ' cold', $gear_bonuses['monster']['frontline']);
                                $monster_config['members']['frontline']['current_health'] -= $damage;
                                $return_me_log[] = "Party Frontliner casts Blizzard, chilling and hitting Monster Frontline for $damage cold damage.";
                            }

                            // Apply Hypothermia and deal damage to backline enemy
                            if ($monster_config['members']['backline']['current_health'] > 0) {
                                $status_effects['monster']['backline']['Hypothermia'] = 3;
                                $damage = (int)$base_damage;
                                // Apply gear bonuses and resistances
                                $damage = $this->applyDamageBonus($damage, ' cold', $gear_bonuses['party']['frontline']);
                                $damage = $this->applyResistance($damage, ' cold', $gear_bonuses['monster']['backline']);
                                $monster_config['members']['backline']['current_health'] -= $damage;
                                $return_me_log[] = "Party Frontliner casts Blizzard, chilling and hitting Monster Backline for $damage cold damage.";
                            }
                        }
                        if (in_array('Flaming Blades', $party_config['members']['frontline']['skills'])) {
                            // Use Flaming Blades - switches basic attacks to fire damage for 3 rounds
                            $status_effects['party']['frontline']['FlamingBlades'] = 3;
                            $return_me_log[] = "Party Frontliner activates Flaming Blades!";
                        }
                        if (in_array('Antimage', $party_config['members']['frontline']['skills'])) {
                            // Use Antimage - basic attacks apply Antimagic debuff 50% of the time for 3 rounds
                            $status_effects['party']['frontline']['Antimage'] = 3;
                            $return_me_log[] = "Party Frontliner activates Antimage!";
                        }
                        if (in_array('Frost Blades', $party_config['members']['frontline']['skills'])) {
                            // Use Frost Blades - switches basic attacks to cold damage for 3 rounds
                            $status_effects['party']['frontline']['FrostBlades'] = 3;
                            $return_me_log[] = "Party Frontliner activates Frost Blades!";
                        }
                    }
                }

                /// backliner attacks or uses skills
                if ($party_config['members']['backline']['current_health'] <= 0) {
                    // dead skip turn
                    #$return_me_log[] = "Party Backliner is down and cannot attack.";
                }
                else {
                    //Attack runs
                    $target = 'frontline';
                    if ($monster_config['members']['frontline']['current_health'] <= 0) {
                        $target = 'backline';
                    }

                    $dex = (int)$party_config['members']['backline']['dexterity'];
                    // Check for Hypothermia debuff on attacker
                    if (isset($status_effects['party']['backline']['Hypothermia']) && $status_effects['party']['backline']['Hypothermia'] > 0) {
                        $dex = (int)floor($dex * 0.8); // 20% dexterity reduction
                    }
                    $str = (int)$party_config['members']['backline']['strength'];
                    $wis = (int)$party_config['members']['backline']['wisdom'];
                    $target_dex = (int)$monster_config['members'][$target]['dexterity'];
                    // Check for Hypothermia debuff on target
                    if (isset($status_effects['monster'][$target]['Hypothermia']) && $status_effects['monster'][$target]['Hypothermia'] > 0) {
                        $target_dex = (int)floor($target_dex * 0.8); // 20% dexterity reduction
                    }
                    $hit_chance = $dex / ($dex + $target_dex);
                    if (rand(0, 100) / 100 <= $hit_chance) {
                        // Hit
                        $damage = (int)$str;
                        $damage_type = "";

                        // Check for Flaming Blades buff
                        if (isset($status_effects['party']['backline']['FlamingBlades']) && $status_effects['party']['backline']['FlamingBlades'] > 0) {
                            $damage_type = " fire";
                            // Check if target has Scorched for bonus fire damage
                            if (isset($status_effects['monster'][$target]['Scorched']) && $status_effects['monster'][$target]['Scorched'] > 0) {
                                $damage = (int)floor($damage * 1.2);
                            }
                            // 50% chance to apply Scorched
                            if (rand(0, 100) / 100 <= 0.5) {
                                $status_effects['monster'][$target]['Scorched'] = 3;
                                $return_me_log[] = "Party Backliner's flaming attack scorches $target!";
                            }
                        }

                        // Check for Frost Blades buff
                        if (isset($status_effects['party']['backline']['FrostBlades']) && $status_effects['party']['backline']['FrostBlades'] > 0) {
                            $damage_type = " cold";
                            // 50% chance to apply Hypothermia
                            if (rand(0, 100) / 100 <= 0.5) {
                                $status_effects['monster'][$target]['Hypothermia'] = 3;
                                $return_me_log[] = "Party Backliner's frost attack chills $target with Hypothermia!";
                            }
                        }

                        // Check for Antimage buff
                        if (isset($status_effects['party']['backline']['Antimage']) && $status_effects['party']['backline']['Antimage'] > 0) {
                            // 50% chance to apply Antimagic
                            if (rand(0, 100) / 100 <= 0.5) {
                                $status_effects['monster'][$target]['Antimagic'] = 3;
                                $return_me_log[] = "Party Backliner's attack applies Antimagic to $target!";
                            }
                        }

                        // Apply damage bonus from gear
                        $damage = $this->applyDamageBonus($damage, $damage_type, $gear_bonuses['party']['backline']);
                        // Apply resistance from target's gear
                        $damage = $this->applyResistance($damage, $damage_type, $gear_bonuses['monster'][$target]);

                        $monster_config['members'][$target]['current_health'] -= $damage;
                        $return_me_log[] = "Party Backliner hits $target for $damage$damage_type damage.";
                    } else {
                        // Miss
                        $return_me_log[] = "Party Backliner misses $target.";
                    }

                    // Ability Runs
                    $wis = (int)$party_config['members']['backline']['wisdom'];
                    // Check for Antimagic debuff on caster
                    if (isset($status_effects['party']['backline']['Antimagic']) && $status_effects['party']['backline']['Antimagic'] > 0) {
                        $wis = (int)floor($wis * 0.8); // 20% wisdom reduction
                    }

                    // Pick a random living enemy for wisdom check
                    $living_enemies = [];
                    if ($monster_config['members']['frontline']['current_health'] > 0) {
                        $living_enemies[] = 'frontline';
                    }
                    if ($monster_config['members']['backline']['current_health'] > 0) {
                        $living_enemies[] = 'backline';
                    }
                    try {
                        $random_enemy = $living_enemies[array_rand($living_enemies)];
                    } catch (ValueError $e) {
                        // No living enemies to target
                        continue;
                    }
                    $enemy_wis = (int)$monster_config['members'][$random_enemy]['wisdom'];

                    // Healing Rain chance: Caster Wisdom / (Caster Wisdom + Random Living Enemy Wisdom)
                    $ability_chance = $wis / ($wis + $enemy_wis);

                    if (rand(0, 100) / 100 <= $ability_chance) {
                        if (in_array('Healing Rain', $party_config['members']['backline']['skills'])) {
                            // Use Healing Rain - heals all living party members by 20% of Wisdom
                            $heal_amount = (int)floor($wis * 0.2);
                            $frontline_max_health = $party_config['members']['frontline']['health'] * 5;
                            $backline_max_health = $party_config['members']['backline']['health'] * 5;

                            if ($party_config['members']['frontline']['current_health'] > 0) {
                                $party_config['members']['frontline']['current_health'] += $heal_amount;
                                if ($party_config['members']['frontline']['current_health'] > $frontline_max_health) {
                                    $party_config['members']['frontline']['current_health'] = $frontline_max_health;
                                }
                            }

                            if ($party_config['members']['backline']['current_health'] > 0) {
                                $party_config['members']['backline']['current_health'] += $heal_amount;
                                if ($party_config['members']['backline']['current_health'] > $backline_max_health) {
                                    $party_config['members']['backline']['current_health'] = $backline_max_health;
                                }
                            }

                            $return_me_log[] = "Party Backliner casts Healing Rain, healing all allies for $heal_amount.";
                        }
                        if (in_array('Greater Heal', $party_config['members']['backline']['skills'])) {
                            // Use Greater Heal - heals the lowest health living party member by 35% of Wisdom
                            $heal_amount = (int)floor($wis * 0.35);
                            $frontline_max_health = $party_config['members']['frontline']['health'] * 5;
                            $backline_max_health = $party_config['members']['backline']['health'] * 5;

                            // Find lowest health living ally
                            $heal_target = null;
                            $lowest_health = $party_config['members']['frontline']['current_health'] >
                                $party_config['members']['backline']['current_health']
                                ? $party_config['members']['backline']['current_health']
                                : $party_config['members']['frontline']['current_health'];

                            if ($party_config['members']['frontline']['current_health'] > 0 &&
                               $party_config['members']['frontline']['current_health'] <= $lowest_health) {
                                $lowest_health = $party_config['members']['frontline']['current_health'];
                                $heal_target = 'frontline';
                            }
                            if ($party_config['members']['backline']['current_health'] > 0 &&
                               $party_config['members']['backline']['current_health'] < $lowest_health) {
                                $lowest_health = $party_config['members']['backline']['current_health'];
                                $heal_target = 'backline';
                            }

                            if ($heal_target !== null) {
                                $max_health = ($heal_target === 'frontline') ? $frontline_max_health : $backline_max_health;
                                $party_config['members'][$heal_target]['current_health'] += $heal_amount;
                                if ($party_config['members'][$heal_target]['current_health'] > $max_health) {
                                    $party_config['members'][$heal_target]['current_health'] = $max_health;
                                }
                                $return_me_log[] = "Party Backliner casts Greater Heal on $heal_target for $heal_amount.";
                            }
                        }
                        if (in_array('Firestorm', $party_config['members']['backline']['skills'])) {
                            // Use Firestorm - applies Scorched then deals 20% of Wisdom as fire damage to all enemies
                            $base_damage = (int)floor($wis * 0.2);

                            // Apply Scorched and deal damage to frontline enemy
                            if ($monster_config['members']['frontline']['current_health'] > 0) {
                                $status_effects['monster']['frontline']['Scorched'] = 3;
                                $damage = (int)$base_damage;
                                if (isset($status_effects['monster']['frontline']['Scorched']) && $status_effects['monster']['frontline']['Scorched'] > 0) {
                                    $damage = (int)floor($damage * 1.2); // 20% more fire damage
                                }
                                // Apply gear bonuses and resistances
                                $damage = $this->applyDamageBonus($damage, ' fire', $gear_bonuses['party']['backline']);
                                $damage = $this->applyResistance($damage, ' fire', $gear_bonuses['monster']['frontline']);
                                $monster_config['members']['frontline']['current_health'] -= $damage;
                                $return_me_log[] = "Party Backliner casts Firestorm, scorching and hitting Monster Frontline for $damage fire damage.";
                            }

                            // Apply Scorched and deal damage to backline enemy
                            if ($monster_config['members']['backline']['current_health'] > 0) {
                                $status_effects['monster']['backline']['Scorched'] = 3;
                                $damage = (int)$base_damage;
                                if (isset($status_effects['monster']['backline']['Scorched']) && $status_effects['monster']['backline']['Scorched'] > 0) {
                                    $damage = (int)floor($damage * 1.2); // 20% more fire damage
                                }
                                // Apply gear bonuses and resistances
                                $damage = $this->applyDamageBonus($damage, ' fire', $gear_bonuses['party']['backline']);
                                $damage = $this->applyResistance($damage, ' fire', $gear_bonuses['monster']['backline']);
                                $monster_config['members']['backline']['current_health'] -= $damage;
                                $return_me_log[] = "Party Backliner casts Firestorm, scorching and hitting Monster Backline for $damage fire damage.";
                            }
                        }
                        if (in_array('Blizzard', $party_config['members']['backline']['skills'])) {
                            // Use Blizzard - deals 20% of Wisdom as cold damage and applies Hypothermia to all enemies
                            $base_damage = (int)floor($wis * 0.2);

                            // Apply Hypothermia and deal damage to frontline enemy
                            if ($monster_config['members']['frontline']['current_health'] > 0) {
                                $status_effects['monster']['frontline']['Hypothermia'] = 3;
                                $damage = (int)$base_damage;
                                // Apply gear bonuses and resistances
                                $damage = $this->applyDamageBonus($damage, ' cold', $gear_bonuses['party']['backline']);
                                $damage = $this->applyResistance($damage, ' cold', $gear_bonuses['monster']['frontline']);
                                $monster_config['members']['frontline']['current_health'] -= $damage;
                                $return_me_log[] = "Party Backliner casts Blizzard, chilling and hitting Monster Frontline for $damage cold damage.";
                            }

                            // Apply Hypothermia and deal damage to backline enemy
                            if ($monster_config['members']['backline']['current_health'] > 0) {
                                $status_effects['monster']['backline']['Hypothermia'] = 3;
                                $damage = (int)$base_damage;
                                // Apply gear bonuses and resistances
                                $damage = $this->applyDamageBonus($damage, ' cold', $gear_bonuses['party']['backline']);
                                $damage = $this->applyResistance($damage, ' cold', $gear_bonuses['monster']['backline']);
                                $monster_config['members']['backline']['current_health'] -= $damage;
                                $return_me_log[] = "Party Backliner casts Blizzard, chilling and hitting Monster Backline for $damage cold damage.";
                            }
                        }
                        if (in_array('Flaming Blades', $party_config['members']['backline']['skills'])) {
                            // Use Flaming Blades - switches basic attacks to fire damage for 3 rounds
                            $status_effects['party']['backline']['FlamingBlades'] = 3;
                            $return_me_log[] = "Party Backliner activates Flaming Blades!";
                        }
                        if (in_array('Antimage', $party_config['members']['backline']['skills'])) {
                            // Use Antimage - basic attacks apply Antimagic debuff 50% of the time for 3 rounds
                            $status_effects['party']['backline']['Antimage'] = 3;
                            $return_me_log[] = "Party Backliner activates Antimage!";
                        }
                        if (in_array('Frost Blades', $party_config['members']['backline']['skills'])) {
                            // Use Frost Blades - switches basic attacks to cold damage for 3 rounds
                            $status_effects['party']['backline']['FrostBlades'] = 3;
                            $return_me_log[] = "Party Backliner activates Frost Blades!";
                        }
                    }
                }

               // Monster turn

                /// frontliner attacks
                if ($monster_config['members']['frontline']['current_health'] <= 0) {
                    // dead skip turn
                    #$return_me_log[] = "Monster Frontliner is down and cannot attack.";
                }
                else {
                    $target = 'frontline';
                    if ($party_config['members']['frontline']['current_health'] <= 0) {
                        $target = 'backline';
                    }

                    $dex = (int)$monster_config['members']['frontline']['dexterity'];
                    // Check for Hypothermia debuff on attacker
                    if (isset($status_effects['monster']['frontline']['Hypothermia']) && $status_effects['monster']['frontline']['Hypothermia'] > 0) {
                        $dex = (int)floor($dex * 0.8); // 20% dexterity reduction
                    }
                    $str = (int)$monster_config['members']['frontline']['strength'];
                    $wis = (int)$monster_config['members']['frontline']['wisdom'];
                    $target_dex = (int)$party_config['members'][$target]['dexterity'];
                    // Check for Hypothermia debuff on target
                    if (isset($status_effects['party'][$target]['Hypothermia']) && $status_effects['party'][$target]['Hypothermia'] > 0) {
                        $target_dex = (int)floor($target_dex * 0.8); // 20% dexterity reduction
                    }
                    $hit_chance = $dex / ($dex + $target_dex);
                    if (rand(0, 100) / 100 <= $hit_chance) {
                        // Hit
                        $damage = (int)$str;
                        $damage_type = "";

                        // Check for Flaming Blades buff
                        if (isset($status_effects['monster']['frontline']['FlamingBlades']) && $status_effects['monster']['frontline']['FlamingBlades'] > 0) {
                            $damage_type = " fire";
                            // Check if target has Scorched for bonus fire damage
                            if (isset($status_effects['party'][$target]['Scorched']) && $status_effects['party'][$target]['Scorched'] > 0) {
                                $damage = (int)floor($damage * 1.2);
                            }
                            // 50% chance to apply Scorched
                            if (rand(0, 100) / 100 <= 0.5) {
                                $status_effects['party'][$target]['Scorched'] = 3;
                                $return_me_log[] = "Monster Frontliner's flaming attack scorches $target!";
                            }
                        }

                        // Check for Frost Blades buff
                        if (isset($status_effects['monster']['frontline']['FrostBlades']) && $status_effects['monster']['frontline']['FrostBlades'] > 0) {
                            $damage_type = " cold";
                            // 50% chance to apply Hypothermia
                            if (rand(0, 100) / 100 <= 0.5) {
                                $status_effects['party'][$target]['Hypothermia'] = 3;
                                $return_me_log[] = "Monster Frontliner's frost attack chills $target with Hypothermia!";
                            }
                        }

                        // Check for Antimage buff
                        if (isset($status_effects['monster']['frontline']['Antimage']) && $status_effects['monster']['frontline']['Antimage'] > 0) {
                            // 50% chance to apply Antimagic
                            if (rand(0, 100) / 100 <= 0.5) {
                                $status_effects['party'][$target]['Antimagic'] = 3;
                                $return_me_log[] = "Monster Frontliner's attack applies Antimagic to $target!";
                            }
                        }

                        // Apply damage bonus from gear
                        $damage = $this->applyDamageBonus($damage, $damage_type, $gear_bonuses['monster']['frontline']);
                        // Apply resistance from target's gear
                        $damage = $this->applyResistance($damage, $damage_type, $gear_bonuses['party'][$target]);

                        $party_config['members'][$target]['current_health'] -= $damage;
                        $return_me_log[] = "Monster Frontliner hits $target for $damage$damage_type damage.";
                    } else {
                        // Miss
                        $return_me_log[] = "Monster Frontliner misses $target.";
                    }

                    // Ability Runs
                    $wis = (int)$monster_config['members']['frontline']['wisdom'];
                    // Check for Antimagic debuff on caster
                    if (isset($status_effects['monster']['frontline']['Antimagic']) && $status_effects['monster']['frontline']['Antimagic'] > 0) {
                        $wis = (int)floor($wis * 0.8); // 20% wisdom reduction
                    }

                    // Pick a random living enemy for wisdom check
                    $living_enemies = [];
                    if ($party_config['members']['frontline']['current_health'] > 0) {
                        $living_enemies[] = 'frontline';
                    }
                    if ($party_config['members']['backline']['current_health'] > 0) {
                        $living_enemies[] = 'backline';
                    }
                    try {
                        $random_enemy = $living_enemies[array_rand($living_enemies)];
                    } catch (ValueError $e) {
                        // No living enemies to target
                        continue;
                    }
                    $enemy_wis = (int)$party_config['members'][$random_enemy]['wisdom'];

                    // Healing Rain chance: Caster Wisdom / (Caster Wisdom + Random Living Enemy Wisdom)
                    $ability_chance = $wis / ($wis + $enemy_wis);

                    if (rand(0, 100) / 100 <= $ability_chance) {
                        if (in_array('Healing Rain', $monster_config['members']['frontline']['skills'])) {
                            // Use Healing Rain - heals all living monster members by 20% of Wisdom
                            $heal_amount = (int)floor($wis * 0.2);
                            $frontline_max_health = $monster_config['members']['frontline']['health'] * 5;
                            $backline_max_health = $monster_config['members']['backline']['health'] * 5;

                            if ($monster_config['members']['frontline']['current_health'] > 0) {
                                $monster_config['members']['frontline']['current_health'] += $heal_amount;
                                if ($monster_config['members']['frontline']['current_health'] > $frontline_max_health) {
                                    $monster_config['members']['frontline']['current_health'] = $frontline_max_health;
                                }
                            }

                            if ($monster_config['members']['backline']['current_health'] > 0) {
                                $monster_config['members']['backline']['current_health'] += $heal_amount;
                                if ($monster_config['members']['backline']['current_health'] > $backline_max_health) {
                                    $monster_config['members']['backline']['current_health'] = $backline_max_health;
                                }
                            }

                            $return_me_log[] = "Monster Frontliner casts Healing Rain, healing all allies for $heal_amount.";
                        }
                        if (in_array('Greater Heal', $monster_config['members']['frontline']['skills'])) {
                            // Use Greater Heal - heals the lowest health living monster member by 35% of Wisdom
                            $heal_amount = (int)floor($wis * 0.35);
                            $frontline_max_health = $monster_config['members']['frontline']['health'] * 5;
                            $backline_max_health = $monster_config['members']['backline']['health'] * 5;

                            // Find lowest health living ally
                            $heal_target = null;
                            $lowest_health =
                            $monster_config['members']['frontline']['current_health'] >
                            $monster_config['members']['backline']['current_health'] ?
                            $monster_config['members']['backline']['current_health']
                            : $monster_config['members']['frontline']['current_health'];

                            if ($monster_config['members']['frontline']['current_health'] > 0 &&
                               $monster_config['members']['frontline']['current_health'] < $lowest_health) {
                                $lowest_health = $monster_config['members']['frontline']['current_health'];
                                $heal_target = 'frontline';
                            }
                            if ($monster_config['members']['backline']['current_health'] > 0 &&
                               $monster_config['members']['backline']['current_health'] < $lowest_health) {
                                $lowest_health = $monster_config['members']['backline']['current_health'];
                                $heal_target = 'backline';
                            }

                            if ($heal_target !== null) {
                                $max_health = ($heal_target === 'frontline') ? $frontline_max_health : $backline_max_health;
                                $monster_config['members'][$heal_target]['current_health'] += $heal_amount;
                                if ($monster_config['members'][$heal_target]['current_health'] > $max_health) {
                                    $monster_config['members'][$heal_target]['current_health'] = $max_health;
                                }
                                $return_me_log[] = "Monster Frontliner casts Greater Heal on $heal_target for $heal_amount.";
                            }
                        }
                        if (in_array('Firestorm', $monster_config['members']['frontline']['skills'])) {
                            // Use Firestorm - applies Scorched then deals 20% of Wisdom as fire damage to all enemies
                            $base_damage = (int)floor($wis * 0.2);

                            // Apply Scorched and deal damage to frontline enemy
                            if ($party_config['members']['frontline']['current_health'] > 0) {
                                $status_effects['party']['frontline']['Scorched'] = 3;
                                $damage = (int)$base_damage;
                                if (isset($status_effects['party']['frontline']['Scorched']) && $status_effects['party']['frontline']['Scorched'] > 0) {
                                    $damage = (int)floor($damage * 1.2); // 20% more fire damage
                                }
                                // Apply gear bonuses and resistances
                                $damage = $this->applyDamageBonus($damage, ' fire', $gear_bonuses['monster']['frontline']);
                                $damage = $this->applyResistance($damage, ' fire', $gear_bonuses['party']['frontline']);
                                $party_config['members']['frontline']['current_health'] -= $damage;
                                $return_me_log[] = "Monster Frontliner casts Firestorm, scorching and hitting Party Frontline for $damage fire damage.";
                            }

                            // Apply Scorched and deal damage to backline enemy
                            if ($party_config['members']['backline']['current_health'] > 0) {
                                $status_effects['party']['backline']['Scorched'] = 3;
                                $damage = (int)$base_damage;
                                if (isset($status_effects['party']['backline']['Scorched']) && $status_effects['party']['backline']['Scorched'] > 0) {
                                    $damage = (int)floor($damage * 1.2); // 20% more fire damage
                                }
                                // Apply gear bonuses and resistances
                                $damage = $this->applyDamageBonus($damage, ' fire', $gear_bonuses['monster']['frontline']);
                                $damage = $this->applyResistance($damage, ' fire', $gear_bonuses['party']['backline']);
                                $party_config['members']['backline']['current_health'] -= $damage;
                                $return_me_log[] = "Monster Frontliner casts Firestorm, scorching and hitting Party Backline for $damage fire damage.";
                            }
                        }
                        if (in_array('Blizzard', $monster_config['members']['frontline']['skills'])) {
                            // Use Blizzard - deals 20% of Wisdom as cold damage and applies Hypothermia to all enemies
                            $base_damage = (int)floor($wis * 0.2);

                            // Apply Hypothermia and deal damage to frontline enemy
                            if ($party_config['members']['frontline']['current_health'] > 0) {
                                $status_effects['party']['frontline']['Hypothermia'] = 3;
                                $damage = (int)$base_damage;
                                // Apply gear bonuses and resistances
                                $damage = $this->applyDamageBonus($damage, ' cold', $gear_bonuses['monster']['frontline']);
                                $damage = $this->applyResistance($damage, ' cold', $gear_bonuses['party']['frontline']);
                                $party_config['members']['frontline']['current_health'] -= $damage;
                                $return_me_log[] = "Monster Frontliner casts Blizzard, chilling and hitting Party Frontline for $damage cold damage.";
                            }

                            // Apply Hypothermia and deal damage to backline enemy
                            if ($party_config['members']['backline']['current_health'] > 0) {
                                $status_effects['party']['backline']['Hypothermia'] = 3;
                                $damage = (int)$base_damage;
                                // Apply gear bonuses and resistances
                                $damage = $this->applyDamageBonus($damage, ' cold', $gear_bonuses['monster']['frontline']);
                                $damage = $this->applyResistance($damage, ' cold', $gear_bonuses['party']['backline']);
                                $party_config['members']['backline']['current_health'] -= $damage;
                                $return_me_log[] = "Monster Frontliner casts Blizzard, chilling and hitting Party Backline for $damage cold damage.";
                            }
                        }
                        if (in_array('Flaming Blades', $monster_config['members']['frontline']['skills'])) {
                            // Use Flaming Blades - switches basic attacks to fire damage for 3 rounds
                            $status_effects['monster']['frontline']['FlamingBlades'] = 3;
                            $return_me_log[] = "Monster Frontliner activates Flaming Blades!";
                        }
                        if (in_array('Antimage', $monster_config['members']['frontline']['skills'])) {
                            // Use Antimage - basic attacks apply Antimagic debuff 50% of the time for 3 rounds
                            $status_effects['monster']['frontline']['Antimage'] = 3;
                            $return_me_log[] = "Monster Frontliner activates Antimage!";
                        }
                        if (in_array('Frost Blades', $monster_config['members']['frontline']['skills'])) {
                            // Use Frost Blades - switches basic attacks to cold damage for 3 rounds
                            $status_effects['monster']['frontline']['FrostBlades'] = 3;
                            $return_me_log[] = "Monster Frontliner activates Frost Blades!";
                        }
                    }
                }

                /// backliner attacks
                if ($monster_config['members']['backline']['current_health'] <= 0) {
                    // dead skip turn
                    #$return_me_log[] = "Monster Backliner is down and cannot attack.";
                }
                else {
                    $target = 'frontline';
                    if ($party_config['members']['frontline']['current_health'] <= 0) {
                        $target = 'backline';
                    }
                    $dex = (int)$monster_config['members']['backline']['dexterity'];
                    // Check for Hypothermia debuff on attacker
                    if (isset($status_effects['monster']['backline']['Hypothermia']) && $status_effects['monster']['backline']['Hypothermia'] > 0) {
                        $dex = (int)floor($dex * 0.8); // 20% dexterity reduction
                    }
                    $str = (int)$monster_config['members']['backline']['strength'];
                    $wis = (int)$monster_config['members']['backline']['wisdom'];
                    $target_dex = (int)$party_config['members'][$target]['dexterity'];
                    // Check for Hypothermia debuff on target
                    if (isset($status_effects['party'][$target]['Hypothermia']) && $status_effects['party'][$target]['Hypothermia'] > 0) {
                        $target_dex = (int)floor($target_dex * 0.8); // 20% dexterity reduction
                    }
                    $hit_chance = $dex / ($dex + $target_dex);
                    if (rand(0, 100) / 100 <= $hit_chance) {
                        // Hit
                        $damage = (int)$str;
                        $damage_type = "";

                        // Check for Flaming Blades buff
                        if (isset($status_effects['monster']['backline']['FlamingBlades']) && $status_effects['monster']['backline']['FlamingBlades'] > 0) {
                            $damage_type = " fire";
                            // Check if target has Scorched for bonus fire damage
                            if (isset($status_effects['party'][$target]['Scorched']) && $status_effects['party'][$target]['Scorched'] > 0) {
                                $damage = (int)floor($damage * 1.2);
                            }
                            // 50% chance to apply Scorched
                            if (rand(0, 100) / 100 <= 0.5) {
                                $status_effects['party'][$target]['Scorched'] = 3;
                                $return_me_log[] = "Monster Backliner's flaming attack scorches $target!";
                            }
                        }

                        // Check for Frost Blades buff
                        if (isset($status_effects['monster']['backline']['FrostBlades']) && $status_effects['monster']['backline']['FrostBlades'] > 0) {
                            $damage_type = " cold";
                            // 50% chance to apply Hypothermia
                            if (rand(0, 100) / 100 <= 0.5) {
                                $status_effects['party'][$target]['Hypothermia'] = 3;
                                $return_me_log[] = "Monster Backliner's frost attack chills $target with Hypothermia!";
                            }
                        }

                        // Check for Antimage buff
                        if (isset($status_effects['monster']['backline']['Antimage']) && $status_effects['monster']['backline']['Antimage'] > 0) {
                            // 50% chance to apply Antimagic
                            if (rand(0, 100) / 100 <= 0.5) {
                                $status_effects['party'][$target]['Antimagic'] = 3;
                                $return_me_log[] = "Monster Backliner's attack applies Antimagic to $target!";
                            }
                        }

                        // Apply damage bonus from gear
                        $damage = $this->applyDamageBonus($damage, $damage_type, $gear_bonuses['monster']['backline']);
                        // Apply resistance from target's gear
                        $damage = $this->applyResistance($damage, $damage_type, $gear_bonuses['party'][$target]);

                        $party_config['members'][$target]['current_health'] -= $damage;
                        $return_me_log[] = "Monster Backliner hits $target for $damage$damage_type damage.";
                    } else {
                        // Miss
                        $return_me_log[] = "Monster Backliner misses $target.";
                    }

                    // Ability Runs
                    $wis = (int)$monster_config['members']['backline']['wisdom'];
                    // Check for Antimagic debuff on caster
                    if (isset($status_effects['monster']['backline']['Antimagic']) && $status_effects['monster']['backline']['Antimagic'] > 0) {
                        $wis = (int)floor($wis * 0.8); // 20% wisdom reduction
                    }

                    // Pick a random living enemy for wisdom check
                    $living_enemies = [];
                    if ($party_config['members']['frontline']['current_health'] > 0) {
                        $living_enemies[] = 'frontline';
                    }
                    if ($party_config['members']['backline']['current_health'] > 0) {
                        $living_enemies[] = 'backline';
                    }
                    try {
                        $random_enemy = $living_enemies[array_rand($living_enemies)];
                    } catch (ValueError $e) {
                        // No living enemies to target
                        continue;
                    }
                    $enemy_wis = (int)$party_config['members'][$random_enemy]['wisdom'];

                    // Healing Rain chance: Caster Wisdom / (Caster Wisdom + Random Living Enemy Wisdom)
                    $ability_chance = $wis / ($wis + $enemy_wis);

                    if (rand(0, 100) / 100 <= $ability_chance) {
                        if (in_array('Healing Rain', $monster_config['members']['backline']['skills'])) {
                            // Use Healing Rain - heals all living monster members by 20% of Wisdom
                            $heal_amount = (int)floor($wis * 0.2);
                            $frontline_max_health = $monster_config['members']['frontline']['health'] * 5;
                            $backline_max_health = $monster_config['members']['backline']['health'] * 5;

                            if ($monster_config['members']['frontline']['current_health'] > 0) {
                                $monster_config['members']['frontline']['current_health'] += $heal_amount;
                                if ($monster_config['members']['frontline']['current_health'] > $frontline_max_health) {
                                    $monster_config['members']['frontline']['current_health'] = $frontline_max_health;
                                }
                            }

                            if ($monster_config['members']['backline']['current_health'] > 0) {
                                $monster_config['members']['backline']['current_health'] += $heal_amount;
                                if ($monster_config['members']['backline']['current_health'] > $backline_max_health) {
                                    $monster_config['members']['backline']['current_health'] = $backline_max_health;
                                }
                            }

                            $return_me_log[] = "Monster Backliner casts Healing Rain, healing all allies for $heal_amount.";
                        }
                        if (in_array('Greater Heal', $monster_config['members']['backline']['skills'])) {
                            // Use Greater Heal - heals the lowest health living monster member by 35% of Wisdom
                            $heal_amount = (int)floor($wis * 0.35);
                            $frontline_max_health = $monster_config['members']['frontline']['health'] * 5;
                            $backline_max_health = $monster_config['members']['backline']['health'] * 5;

                            // Find lowest health living ally
                            $heal_target = null;
                            $lowest_health = $monster_config['members']['frontline']['current_health'] >
                                $monster_config['members']['backline']['current_health']
                                ? $monster_config['members']['backline']['current_health']
                                : $monster_config['members']['frontline']['current_health'];

                            if ($monster_config['members']['frontline']['current_health'] > 0 &&
                               $monster_config['members']['frontline']['current_health'] < $lowest_health) {
                                $lowest_health = $monster_config['members']['frontline']['current_health'];
                                $heal_target = 'frontline';
                            }
                            if ($monster_config['members']['backline']['current_health'] > 0 &&
                               $monster_config['members']['backline']['current_health'] < $lowest_health) {
                                $lowest_health = $monster_config['members']['backline']['current_health'];
                                $heal_target = 'backline';
                            }

                            if ($heal_target !== null) {
                                $max_health = ($heal_target === 'frontline') ? $frontline_max_health : $backline_max_health;
                                $monster_config['members'][$heal_target]['current_health'] += $heal_amount;
                                if ($monster_config['members'][$heal_target]['current_health'] > $max_health) {
                                    $monster_config['members'][$heal_target]['current_health'] = $max_health;
                                }
                                $return_me_log[] = "Monster Backliner casts Greater Heal on $heal_target for $heal_amount.";
                            }
                        }
                        if (in_array('Firestorm', $monster_config['members']['backline']['skills'])) {
                            // Use Firestorm - applies Scorched then deals 20% of Wisdom as fire damage to all enemies
                            $base_damage = (int)floor($wis * 0.2);

                            // Apply Scorched and deal damage to frontline enemy
                            if ($party_config['members']['frontline']['current_health'] > 0) {
                                $status_effects['party']['frontline']['Scorched'] = 3;
                                $damage = (int)$base_damage;
                                if (isset($status_effects['party']['frontline']['Scorched']) && $status_effects['party']['frontline']['Scorched'] > 0) {
                                    $damage = (int)floor($damage * 1.2); // 20% more fire damage
                                }
                                // Apply gear bonuses and resistances
                                $damage = $this->applyDamageBonus($damage, ' fire', $gear_bonuses['monster']['backline']);
                                $damage = $this->applyResistance($damage, ' fire', $gear_bonuses['party']['frontline']);
                                $party_config['members']['frontline']['current_health'] -= $damage;
                                $return_me_log[] = "Monster Backliner casts Firestorm, scorching and hitting Party Frontline for $damage fire damage.";
                            }

                            // Apply Scorched and deal damage to backline enemy
                            if ($party_config['members']['backline']['current_health'] > 0) {
                                $status_effects['party']['backline']['Scorched'] = 3;
                                $damage = (int)$base_damage;
                                if (isset($status_effects['party']['backline']['Scorched']) && $status_effects['party']['backline']['Scorched'] > 0) {
                                    $damage = (int)floor($damage * 1.2); // 20% more fire damage
                                }
                                // Apply gear bonuses and resistances
                                $damage = $this->applyDamageBonus($damage, ' fire', $gear_bonuses['monster']['backline']);
                                $damage = $this->applyResistance($damage, ' fire', $gear_bonuses['party']['backline']);
                                $party_config['members']['backline']['current_health'] -= $damage;
                                $return_me_log[] = "Monster Backliner casts Firestorm, scorching and hitting Party Backline for $damage fire damage.";
                            }
                        }
                        if (in_array('Blizzard', $monster_config['members']['backline']['skills'])) {
                            // Use Blizzard - deals 20% of Wisdom as cold damage and applies Hypothermia to all enemies
                            $base_damage = (int)floor($wis * 0.2);

                            // Apply Hypothermia and deal damage to frontline enemy
                            if ($party_config['members']['frontline']['current_health'] > 0) {
                                $status_effects['party']['frontline']['Hypothermia'] = 3;
                                $damage = (int)$base_damage;
                                // Apply gear bonuses and resistances
                                $damage = $this->applyDamageBonus($damage, ' cold', $gear_bonuses['monster']['backline']);
                                $damage = $this->applyResistance($damage, ' cold', $gear_bonuses['party']['frontline']);
                                $party_config['members']['frontline']['current_health'] -= $damage;
                                $return_me_log[] = "Monster Backliner casts Blizzard, chilling and hitting Party Frontline for $damage cold damage.";
                            }

                            // Apply Hypothermia and deal damage to backline enemy
                            if ($party_config['members']['backline']['current_health'] > 0) {
                                $status_effects['party']['backline']['Hypothermia'] = 3;
                                $damage = (int)$base_damage;
                                // Apply gear bonuses and resistances
                                $damage = $this->applyDamageBonus($damage, ' cold', $gear_bonuses['monster']['backline']);
                                $damage = $this->applyResistance($damage, ' cold', $gear_bonuses['party']['backline']);
                                $party_config['members']['backline']['current_health'] -= $damage;
                                $return_me_log[] = "Monster Backliner casts Blizzard, chilling and hitting Party Backline for $damage cold damage.";
                            }
                        }
                        if (in_array('Flaming Blades', $monster_config['members']['backline']['skills'])) {
                            // Use Flaming Blades - switches basic attacks to fire damage for 3 rounds
                            $status_effects['monster']['backline']['FlamingBlades'] = 3;
                            $return_me_log[] = "Monster Backliner activates Flaming Blades!";
                        }
                        if (in_array('Antimage', $monster_config['members']['backline']['skills'])) {
                            // Use Antimage - basic attacks apply Antimagic debuff 50% of the time for 3 rounds
                            $status_effects['monster']['backline']['Antimage'] = 3;
                            $return_me_log[] = "Monster Backliner activates Antimage!";
                        }
                        if (in_array('Frost Blades', $monster_config['members']['backline']['skills'])) {
                            // Use Frost Blades - switches basic attacks to cold damage for 3 rounds
                            $status_effects['monster']['backline']['FrostBlades'] = 3;
                            $return_me_log[] = "Monster Backliner activates Frost Blades!";
                        }
                    }
                }

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
    }
