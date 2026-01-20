<?php

    class Battle {

        public function SimulateArenaFloor($Character, $floor) {
            $won = 0;
            $lost = 0;
            $total = 1000;

            // Testing loop
            while($total > 0) {
                $party_config = $Character->Data['party_json'];

                $arena_floor = $Character->Data['arena_floor'];

                $monster_strength = 10 * $arena_floor;
                $monster_dexterity = 10 * $arena_floor;
                $monster_health = 10 * $arena_floor;
                $monster_wisdom = 10 * $arena_floor;
                $monster_ability = SKILL_GEMS[array_rand(SKILL_GEMS)]['Name'];

                $ArenaLog .= "Monster stats are $monster_strength STR, $monster_dexterity DEX,
                $monster_health HEALTH, $monster_wisdom WIS. Monster ability: $monster_ability. <BR />\n";

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


                if($battle_result['player_won']) {
                    $won++;
                } else {
                    $lost++;
                }
                $total--;
            }

            return ["won" => $won, "lost" => $lost, "total" => $won+$lost];
        }
        public function Battle($party_config, $monster_config, $echo_log = true) {
          $return_me_log = [];
        // Actual health is 5x normal for battle purposes
          $party_config['members']['frontline']['current_health'] =
          $party_config['members']['frontline']['health'] * 5;
          $party_config['members']['backline']['current_health'] =
          $party_config['members']['backline']['health'] * 5;;

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
           while($running) {

            if ($party_config['members']['frontline']['current_health'] <= 0 &&
            $party_config['members']['backline']['current_health'] <= 0) {
                if($echo_log)
                    echo "Finished with party @ ".$party_config['members']['frontline']['current_health'].", ".
                $party_config['members']['backline']['current_health']."\n";
                $running = false;
            }

            if ($monster_config['members']['frontline']['current_health'] <= 0 &&
            $monster_config['members']['backline']['current_health'] <= 0) {
                if($echo_log)
                    echo "Finished with monsters @ ".$monster_config['members']['frontline']['current_health'].", ".
                    $monster_config['members']['backline']['current_health']."\n";

                $running = false;
            }

               // Player turn

                /// frontliner attacks
                if($party_config['members']['frontline']['current_health'] <= 0) {
                    // dead skip turn
                    #$return_me_log[] = "Party Frontliner is down and cannot attack.";
                }
                else {
                    $target = 'frontline';
                    if($monster_config['members']['frontline']['current_health'] <= 0) {
                        $target = 'backline';
                    }

                    $dex = $party_config['members']['frontline']['dexterity'];
                    $str = $party_config['members']['frontline']['strength'];
                    $wis = $party_config['members']['frontline']['wisdom'];
                    $hit_chance = $dex / ($dex + $monster_config['members'][$target]['dexterity']);
                    if(rand(0, 100) / 100 <= $hit_chance) {
                        // Hit
                        $monster_config['members'][$target]['current_health'] -= $str;
                        $return_me_log[] = "Party Frontliner hits $target for $str damage.";
                    } else {
                        // Miss
                        $return_me_log[] = "Party Frontliner misses $target.";
                    }

                    // Ability Runs
                    $wis = $party_config['members']['frontline']['wisdom'];

                    // Pick a random living enemy for wisdom check
                    $living_enemies = [];
                    if($monster_config['members']['frontline']['current_health'] > 0) {
                        $living_enemies[] = 'frontline';
                    }
                    if($monster_config['members']['backline']['current_health'] > 0) {
                        $living_enemies[] = 'backline';
                    }
                    try {
                        $random_enemy = $living_enemies[array_rand($living_enemies)];
                    } catch (ValueError $e) {
                        // No living enemies to target
                        continue;
                    }
                    $enemy_wis = $monster_config['members'][$random_enemy]['wisdom'];

                    // Healing Rain chance: Caster Wisdom / (Caster Wisdom + Random Living Enemy Wisdom)
                    $ability_chance = $wis / ($wis + $enemy_wis);

                    if(rand(0, 100) / 100 <= $ability_chance) {
                        if(in_array('Healing Rain', $party_config['members']['frontline']['skills'])) {
                            // Use Healing Rain - heals all living party members by 20% of Wisdom
                            $heal_amount = floor($wis * 0.2);
                            $frontline_max_health = $party_config['members']['frontline']['health'] * 5;
                            $backline_max_health = $party_config['members']['backline']['health'] * 5;

                            if($party_config['members']['frontline']['current_health'] > 0) {
                                $party_config['members']['frontline']['current_health'] += $heal_amount;
                                if($party_config['members']['frontline']['current_health'] > $frontline_max_health) {
                                    $party_config['members']['frontline']['current_health'] = $frontline_max_health;
                                }
                            }

                            if($party_config['members']['backline']['current_health'] > 0) {
                                $party_config['members']['backline']['current_health'] += $heal_amount;
                                if($party_config['members']['backline']['current_health'] > $backline_max_health) {
                                    $party_config['members']['backline']['current_health'] = $backline_max_health;
                                }
                            }

                            $return_me_log[] = "Party Frontliner casts Healing Rain, healing all allies for $heal_amount.";
                        }
                        if(in_array('Greater Heal', $party_config['members']['frontline']['skills'])) {
                            // Use Greater Heal - heals the lowest health living party member by 35% of Wisdom
                            $heal_amount = floor($wis * 0.35);
                            $frontline_max_health = $party_config['members']['frontline']['health'] * 5;
                            $backline_max_health = $party_config['members']['backline']['health'] * 5;

                            // Find lowest health living ally
                            $heal_target = null;
                            $lowest_health =
                            $party_config['members']['frontline']['current_health'] >
                            $party_config['members']['backline']['current_health'] ?
                            $party_config['members']['backline']['current_health']
                            : $party_config['members']['frontline']['current_health'];

                            if($party_config['members']['frontline']['current_health'] > 0 &&
                               $party_config['members']['frontline']['current_health'] <= $lowest_health) {
                                $lowest_health = $party_config['members']['frontline']['current_health'];
                                $heal_target = 'frontline';
                            }
                            if($party_config['members']['backline']['current_health'] > 0 &&
                               $party_config['members']['backline']['current_health'] < $lowest_health) {
                                $lowest_health = $party_config['members']['backline']['current_health'];
                                $heal_target = 'backline';
                            }

                            if($heal_target !== null) {
                                $max_health = ($heal_target === 'frontline') ? $frontline_max_health : $backline_max_health;
                                $party_config['members'][$heal_target]['current_health'] += $heal_amount;
                                if($party_config['members'][$heal_target]['current_health'] > $max_health) {
                                    $party_config['members'][$heal_target]['current_health'] = $max_health;
                                }
                                $return_me_log[] = "Party Frontliner casts Greater Heal on $heal_target for $heal_amount.";
                            }
                        }
                        if(in_array('Firestorm', $party_config['members']['frontline']['skills'])) {
                            // Use Firestorm - applies Scorched then deals 20% of Wisdom as fire damage to all enemies
                            $base_damage = floor($wis * 0.2);

                            // Apply Scorched and deal damage to frontline enemy
                            if($monster_config['members']['frontline']['current_health'] > 0) {
                                $status_effects['monster']['frontline']['Scorched'] = 3;
                                $damage = $base_damage;
                                if(isset($status_effects['monster']['frontline']['Scorched']) && $status_effects['monster']['frontline']['Scorched'] > 0) {
                                    $damage = floor($damage * 1.2); // 20% more fire damage
                                }
                                $monster_config['members']['frontline']['current_health'] -= $damage;
                                $return_me_log[] = "Party Frontliner casts Firestorm, scorching and hitting Monster Frontline for $damage fire damage.";
                            }

                            // Apply Scorched and deal damage to backline enemy
                            if($monster_config['members']['backline']['current_health'] > 0) {
                                $status_effects['monster']['backline']['Scorched'] = 3;
                                $damage = $base_damage;
                                if(isset($status_effects['monster']['backline']['Scorched']) && $status_effects['monster']['backline']['Scorched'] > 0) {
                                    $damage = floor($damage * 1.2); // 20% more fire damage
                                }
                                $monster_config['members']['backline']['current_health'] -= $damage;
                                $return_me_log[] = "Party Frontliner casts Firestorm, scorching and hitting Monster Backline for $damage fire damage.";
                            }
                        }
                    }
                }

                /// backliner attacks or uses skills
                if($party_config['members']['backline']['current_health'] <= 0) {
                    // dead skip turn
                    #$return_me_log[] = "Party Backliner is down and cannot attack.";
                }
                else {
                    //Attack runs
                    $target = 'frontline';
                    if($monster_config['members']['frontline']['current_health'] <= 0) {
                        $target = 'backline';
                    }

                    $dex = $party_config['members']['backline']['dexterity'];
                    $str = $party_config['members']['backline']['strength'];
                    $wis = $party_config['members']['backline']['wisdom'];
                    $hit_chance = $dex / ($dex + $monster_config['members'][$target]['dexterity']);
                    if(rand(0, 100) / 100 <= $hit_chance) {
                        // Hit
                        $monster_config['members'][$target]['current_health'] -= $str;
                        $return_me_log[] = "Party Backliner hits $target for $str damage.";
                    } else {
                        // Miss
                        $return_me_log[] = "Party Backliner misses $target.";
                    }

                    // Ability Runs
                    $wis = $party_config['members']['backline']['wisdom'];

                    // Pick a random living enemy for wisdom check
                    $living_enemies = [];
                    if($monster_config['members']['frontline']['current_health'] > 0) {
                        $living_enemies[] = 'frontline';
                    }
                    if($monster_config['members']['backline']['current_health'] > 0) {
                        $living_enemies[] = 'backline';
                    }
                    try {
                        $random_enemy = $living_enemies[array_rand($living_enemies)];
                    } catch (ValueError $e) {
                        // No living enemies to target
                        continue;
                    }
                    $enemy_wis = $monster_config['members'][$random_enemy]['wisdom'];

                    // Healing Rain chance: Caster Wisdom / (Caster Wisdom + Random Living Enemy Wisdom)
                    $ability_chance = $wis / ($wis + $enemy_wis);

                    if(rand(0, 100) / 100 <= $ability_chance) {
                        if(in_array('Healing Rain', $party_config['members']['backline']['skills'])) {
                            // Use Healing Rain - heals all living party members by 20% of Wisdom
                            $heal_amount = floor($wis * 0.2);
                            $frontline_max_health = $party_config['members']['frontline']['health'] * 5;
                            $backline_max_health = $party_config['members']['backline']['health'] * 5;

                            if($party_config['members']['frontline']['current_health'] > 0) {
                                $party_config['members']['frontline']['current_health'] += $heal_amount;
                                if($party_config['members']['frontline']['current_health'] > $frontline_max_health) {
                                    $party_config['members']['frontline']['current_health'] = $frontline_max_health;
                                }
                            }

                            if($party_config['members']['backline']['current_health'] > 0) {
                                $party_config['members']['backline']['current_health'] += $heal_amount;
                                if($party_config['members']['backline']['current_health'] > $backline_max_health) {
                                    $party_config['members']['backline']['current_health'] = $backline_max_health;
                                }
                            }

                            $return_me_log[] = "Party Backliner casts Healing Rain, healing all allies for $heal_amount.";
                        }
                        if(in_array('Greater Heal', $party_config['members']['backline']['skills'])) {
                            // Use Greater Heal - heals the lowest health living party member by 35% of Wisdom
                            $heal_amount = floor($wis * 0.35);
                            $frontline_max_health = $party_config['members']['frontline']['health'] * 5;
                            $backline_max_health = $party_config['members']['backline']['health'] * 5;

                            // Find lowest health living ally
                            $heal_target = null;
                            $lowest_health =
                            $party_config['members']['frontline']['current_health'] >
                            $party_config['members']['backline']['current_health'] ?
                            $party_config['members']['backline']['current_health']
                            : $party_config['members']['frontline']['current_health'];

                            if($party_config['members']['frontline']['current_health'] > 0 &&
                               $party_config['members']['frontline']['current_health'] <= $lowest_health) {
                                $lowest_health = $party_config['members']['frontline']['current_health'];
                                $heal_target = 'frontline';
                            }
                            if($party_config['members']['backline']['current_health'] > 0 &&
                               $party_config['members']['backline']['current_health'] < $lowest_health) {
                                $lowest_health = $party_config['members']['backline']['current_health'];
                                $heal_target = 'backline';
                            }

                            if($heal_target !== null) {
                                $max_health = ($heal_target === 'frontline') ? $frontline_max_health : $backline_max_health;
                                $party_config['members'][$heal_target]['current_health'] += $heal_amount;
                                if($party_config['members'][$heal_target]['current_health'] > $max_health) {
                                    $party_config['members'][$heal_target]['current_health'] = $max_health;
                                }
                                $return_me_log[] = "Party Backliner casts Greater Heal on $heal_target for $heal_amount.";
                            }
                        }
                        if(in_array('Firestorm', $party_config['members']['backline']['skills'])) {
                            // Use Firestorm - applies Scorched then deals 20% of Wisdom as fire damage to all enemies
                            $base_damage = floor($wis * 0.2);

                            // Apply Scorched and deal damage to frontline enemy
                            if($monster_config['members']['frontline']['current_health'] > 0) {
                                $status_effects['monster']['frontline']['Scorched'] = 3;
                                $damage = $base_damage;
                                if(isset($status_effects['monster']['frontline']['Scorched']) && $status_effects['monster']['frontline']['Scorched'] > 0) {
                                    $damage = floor($damage * 1.2); // 20% more fire damage
                                }
                                $monster_config['members']['frontline']['current_health'] -= $damage;
                                $return_me_log[] = "Party Backliner casts Firestorm, scorching and hitting Monster Frontline for $damage fire damage.";
                            }

                            // Apply Scorched and deal damage to backline enemy
                            if($monster_config['members']['backline']['current_health'] > 0) {
                                $status_effects['monster']['backline']['Scorched'] = 3;
                                $damage = $base_damage;
                                if(isset($status_effects['monster']['backline']['Scorched']) && $status_effects['monster']['backline']['Scorched'] > 0) {
                                    $damage = floor($damage * 1.2); // 20% more fire damage
                                }
                                $monster_config['members']['backline']['current_health'] -= $damage;
                                $return_me_log[] = "Party Backliner casts Firestorm, scorching and hitting Monster Backline for $damage fire damage.";
                            }
                        }
                    }
                }

               // Monster turn

                /// frontliner attacks
                if($monster_config['members']['frontline']['current_health'] <= 0) {
                    // dead skip turn
                    #$return_me_log[] = "Monster Frontliner is down and cannot attack.";
                }
                else {
                    $target = 'frontline';
                    if($party_config['members']['frontline']['current_health'] <= 0) {
                        $target = 'backline';
                    }

                    $dex = $monster_config['members']['frontline']['dexterity'];
                    $str = $monster_config['members']['frontline']['strength'];
                    $wis = $monster_config['members']['frontline']['wisdom'];
                    $hit_chance = $dex / ($dex + $party_config['members'][$target]['dexterity']);
                    if(rand(0, 100) / 100 <= $hit_chance) {
                        // Hit
                        $party_config['members'][$target]['current_health'] -= $str;
                        $return_me_log[] = "Monster Frontliner hits $target for $str damage.";
                    } else {
                        // Miss
                        $return_me_log[] = "Monster Frontliner misses $target.";
                    }

                    // Ability Runs
                    $wis = $monster_config['members']['frontline']['wisdom'];

                    // Pick a random living enemy for wisdom check
                    $living_enemies = [];
                    if($party_config['members']['frontline']['current_health'] > 0) {
                        $living_enemies[] = 'frontline';
                    }
                    if($party_config['members']['backline']['current_health'] > 0) {
                        $living_enemies[] = 'backline';
                    }
                    try {
                        $random_enemy = $living_enemies[array_rand($living_enemies)];
                    } catch (ValueError $e) {
                        // No living enemies to target
                        continue;
                    }
                    $enemy_wis = $party_config['members'][$random_enemy]['wisdom'];

                    // Healing Rain chance: Caster Wisdom / (Caster Wisdom + Random Living Enemy Wisdom)
                    $ability_chance = $wis / ($wis + $enemy_wis);

                    if(rand(0, 100) / 100 <= $ability_chance) {
                        if(in_array('Healing Rain', $monster_config['members']['frontline']['skills'])) {
                            // Use Healing Rain - heals all living monster members by 20% of Wisdom
                            $heal_amount = floor($wis * 0.2);
                            $frontline_max_health = $monster_config['members']['frontline']['health'] * 5;
                            $backline_max_health = $monster_config['members']['backline']['health'] * 5;

                            if($monster_config['members']['frontline']['current_health'] > 0) {
                                $monster_config['members']['frontline']['current_health'] += $heal_amount;
                                if($monster_config['members']['frontline']['current_health'] > $frontline_max_health) {
                                    $monster_config['members']['frontline']['current_health'] = $frontline_max_health;
                                }
                            }

                            if($monster_config['members']['backline']['current_health'] > 0) {
                                $monster_config['members']['backline']['current_health'] += $heal_amount;
                                if($monster_config['members']['backline']['current_health'] > $backline_max_health) {
                                    $monster_config['members']['backline']['current_health'] = $backline_max_health;
                                }
                            }

                            $return_me_log[] = "Monster Frontliner casts Healing Rain, healing all allies for $heal_amount.";
                        }
                        if(in_array('Greater Heal', $monster_config['members']['frontline']['skills'])) {
                            // Use Greater Heal - heals the lowest health living monster member by 35% of Wisdom
                            $heal_amount = floor($wis * 0.35);
                            $frontline_max_health = $monster_config['members']['frontline']['health'] * 5;
                            $backline_max_health = $monster_config['members']['backline']['health'] * 5;

                            // Find lowest health living ally
                            $heal_target = null;
                            $lowest_health =
                            $monster_config['members']['frontline']['current_health'] >
                            $monster_config['members']['backline']['current_health'] ?
                            $monster_config['members']['backline']['current_health']
                            : $monster_config['members']['frontline']['current_health'];

                            if($monster_config['members']['frontline']['current_health'] > 0 &&
                               $monster_config['members']['frontline']['current_health'] < $lowest_health) {
                                $lowest_health = $monster_config['members']['frontline']['current_health'];
                                $heal_target = 'frontline';
                            }
                            if($monster_config['members']['backline']['current_health'] > 0 &&
                               $monster_config['members']['backline']['current_health'] < $lowest_health) {
                                $lowest_health = $monster_config['members']['backline']['current_health'];
                                $heal_target = 'backline';
                            }

                            if($heal_target !== null) {
                                $max_health = ($heal_target === 'frontline') ? $frontline_max_health : $backline_max_health;
                                $monster_config['members'][$heal_target]['current_health'] += $heal_amount;
                                if($monster_config['members'][$heal_target]['current_health'] > $max_health) {
                                    $monster_config['members'][$heal_target]['current_health'] = $max_health;
                                }
                                $return_me_log[] = "Monster Frontliner casts Greater Heal on $heal_target for $heal_amount.";
                            }
                        }
                        if(in_array('Firestorm', $monster_config['members']['frontline']['skills'])) {
                            // Use Firestorm - applies Scorched then deals 20% of Wisdom as fire damage to all enemies
                            $base_damage = floor($wis * 0.2);

                            // Apply Scorched and deal damage to frontline enemy
                            if($party_config['members']['frontline']['current_health'] > 0) {
                                $status_effects['party']['frontline']['Scorched'] = 3;
                                $damage = $base_damage;
                                if(isset($status_effects['party']['frontline']['Scorched']) && $status_effects['party']['frontline']['Scorched'] > 0) {
                                    $damage = floor($damage * 1.2); // 20% more fire damage
                                }
                                $party_config['members']['frontline']['current_health'] -= $damage;
                                $return_me_log[] = "Monster Frontliner casts Firestorm, scorching and hitting Party Frontline for $damage fire damage.";
                            }

                            // Apply Scorched and deal damage to backline enemy
                            if($party_config['members']['backline']['current_health'] > 0) {
                                $status_effects['party']['backline']['Scorched'] = 3;
                                $damage = $base_damage;
                                if(isset($status_effects['party']['backline']['Scorched']) && $status_effects['party']['backline']['Scorched'] > 0) {
                                    $damage = floor($damage * 1.2); // 20% more fire damage
                                }
                                $party_config['members']['backline']['current_health'] -= $damage;
                                $return_me_log[] = "Monster Frontliner casts Firestorm, scorching and hitting Party Backline for $damage fire damage.";
                            }
                        }
                    }
                }

                /// backliner attacks
                if($monster_config['members']['backline']['current_health'] <= 0) {
                    // dead skip turn
                    #$return_me_log[] = "Monster Backliner is down and cannot attack.";
                }
                else {
                    $target = 'frontline';
                    if($party_config['members']['frontline']['current_health'] <= 0) {
                        $target = 'backline';
                    }
                    $dex = $monster_config['members']['backline']['dexterity'];
                    $str = $monster_config['members']['backline']['strength'];
                    $wis = $monster_config['members']['backline']['wisdom'];
                    $hit_chance = $dex / ($dex + $party_config['members'][$target]['dexterity']);
                    if(rand(0, 100) / 100 <= $hit_chance) {
                        // Hit
                        $party_config['members'][$target]['current_health'] -= $str;
                        $return_me_log[] = "Monster Backliner hits $target for $str damage.";
                    } else {
                        // Miss
                        $return_me_log[] = "Monster Backliner misses $target.";
                    }

                    // Ability Runs
                    $wis = $monster_config['members']['backline']['wisdom'];

                    // Pick a random living enemy for wisdom check
                    $living_enemies = [];
                    if($party_config['members']['frontline']['current_health'] > 0) {
                        $living_enemies[] = 'frontline';
                    }
                    if($party_config['members']['backline']['current_health'] > 0) {
                        $living_enemies[] = 'backline';
                    }
                    try {
                        $random_enemy = $living_enemies[array_rand($living_enemies)];
                    } catch (ValueError $e) {
                        // No living enemies to target
                        continue;
                    }
                    $enemy_wis = $party_config['members'][$random_enemy]['wisdom'];

                    // Healing Rain chance: Caster Wisdom / (Caster Wisdom + Random Living Enemy Wisdom)
                    $ability_chance = $wis / ($wis + $enemy_wis);

                    if(rand(0, 100) / 100 <= $ability_chance) {
                        if(in_array('Healing Rain', $monster_config['members']['backline']['skills'])) {
                            // Use Healing Rain - heals all living monster members by 20% of Wisdom
                            $heal_amount = floor($wis * 0.2);
                            $frontline_max_health = $monster_config['members']['frontline']['health'] * 5;
                            $backline_max_health = $monster_config['members']['backline']['health'] * 5;

                            if($monster_config['members']['frontline']['current_health'] > 0) {
                                $monster_config['members']['frontline']['current_health'] += $heal_amount;
                                if($monster_config['members']['frontline']['current_health'] > $frontline_max_health) {
                                    $monster_config['members']['frontline']['current_health'] = $frontline_max_health;
                                }
                            }

                            if($monster_config['members']['backline']['current_health'] > 0) {
                                $monster_config['members']['backline']['current_health'] += $heal_amount;
                                if($monster_config['members']['backline']['current_health'] > $backline_max_health) {
                                    $monster_config['members']['backline']['current_health'] = $backline_max_health;
                                }
                            }

                            $return_me_log[] = "Monster Backliner casts Healing Rain, healing all allies for $heal_amount.";
                        }
                        if(in_array('Greater Heal', $monster_config['members']['backline']['skills'])) {
                            // Use Greater Heal - heals the lowest health living monster member by 35% of Wisdom
                            $heal_amount = floor($wis * 0.35);
                            $frontline_max_health = $monster_config['members']['frontline']['health'] * 5;
                            $backline_max_health = $monster_config['members']['backline']['health'] * 5;

                            // Find lowest health living ally
                            $heal_target = null;
                            $lowest_health = $monster_config['members']['frontline']['current_health'] >
                            $monster_config['members']['backline']['current_health'] ?
                            $monster_config['members']['backline']['current_health']
                            : $monster_config['members']['frontline']['current_health'];

                            if($monster_config['members']['frontline']['current_health'] > 0 &&
                               $monster_config['members']['frontline']['current_health'] < $lowest_health) {
                                $lowest_health = $monster_config['members']['frontline']['current_health'];
                                $heal_target = 'frontline';
                            }
                            if($monster_config['members']['backline']['current_health'] > 0 &&
                               $monster_config['members']['backline']['current_health'] < $lowest_health) {
                                $lowest_health = $monster_config['members']['backline']['current_health'];
                                $heal_target = 'backline';
                            }

                            if($heal_target !== null) {
                                $max_health = ($heal_target === 'frontline') ? $frontline_max_health : $backline_max_health;
                                $monster_config['members'][$heal_target]['current_health'] += $heal_amount;
                                if($monster_config['members'][$heal_target]['current_health'] > $max_health) {
                                    $monster_config['members'][$heal_target]['current_health'] = $max_health;
                                }
                                $return_me_log[] = "Monster Backliner casts Greater Heal on $heal_target for $heal_amount.";
                            }
                        }
                        if(in_array('Firestorm', $monster_config['members']['backline']['skills'])) {
                            // Use Firestorm - applies Scorched then deals 20% of Wisdom as fire damage to all enemies
                            $base_damage = floor($wis * 0.2);

                            // Apply Scorched and deal damage to frontline enemy
                            if($party_config['members']['frontline']['current_health'] > 0) {
                                $status_effects['party']['frontline']['Scorched'] = 3;
                                $damage = $base_damage;
                                if(isset($status_effects['party']['frontline']['Scorched']) && $status_effects['party']['frontline']['Scorched'] > 0) {
                                    $damage = floor($damage * 1.2); // 20% more fire damage
                                }
                                $party_config['members']['frontline']['current_health'] -= $damage;
                                $return_me_log[] = "Monster Backliner casts Firestorm, scorching and hitting Party Frontline for $damage fire damage.";
                            }

                            // Apply Scorched and deal damage to backline enemy
                            if($party_config['members']['backline']['current_health'] > 0) {
                                $status_effects['party']['backline']['Scorched'] = 3;
                                $damage = $base_damage;
                                if(isset($status_effects['party']['backline']['Scorched']) && $status_effects['party']['backline']['Scorched'] > 0) {
                                    $damage = floor($damage * 1.2); // 20% more fire damage
                                }
                                $party_config['members']['backline']['current_health'] -= $damage;
                                $return_me_log[] = "Monster Backliner casts Firestorm, scorching and hitting Party Backline for $damage fire damage.";
                            }
                        }
                    }
                }

                // Tick down status effects at end of round
                foreach(['party', 'monster'] as $side) {
                    foreach(['frontline', 'backline'] as $position) {
                        foreach($status_effects[$side][$position] as $effect => $duration) {
                            $status_effects[$side][$position][$effect]--;
                            if($status_effects[$side][$position][$effect] <= 0) {
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
