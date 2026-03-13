<?php

    $time_start = microtime(true);
    require_once('../config.php');

    // Must interact every 3 days to be marked as active
    $row = ActiveUsers();

    foreach($row as $r) {
        $number_of_ticks = NUMBER_OF_MINUTES_PER_RUN;

        while($number_of_ticks > 0) {
            $number_of_ticks--;
            echo "$number_of_ticks ticks remaining for user_id: ".$r['user_id']."\n";
            $ArenaLog = "";
            $Character = new Character();
            $Character->LoadById($r['id']);
            $party_config = $Character->Data['party_json'];

            $arena_floor = $Character->Data['arena_floor'];

            # Load potion bonuses
            $Potion = new Potion();
            $potion_bonuses = $Potion->GetActivePotionBonuses($Character->Data['id']);

            echo "Loaded & running party for user_id: ".$r['user_id']."\n";

            $monster_strength = calculate_monster_attribute($arena_floor);
            $monster_dexterity = calculate_monster_attribute($arena_floor);
            $monster_health = calculate_monster_attribute($arena_floor);
            $monster_wisdom = calculate_monster_attribute($arena_floor);
            $monster_ability = SKILL_GEMS[array_rand(SKILL_GEMS)]['Name'];
            echo "Generated monsters for floor $arena_floor\n";
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
                        "class" => "strength",
                        "level" => $arena_floor,
                        "strength" => $monster_strength,
                        "dexterity" => $monster_dexterity,
                        "health" => $monster_health,
                        "wisdom" => $monster_wisdom,
                        "gear" => [],
                        "skills" => [$monster_ability, $monster_ability],
                    ],
                    "backline" => [
                        "class" => "strength",
                        "level" => $arena_floor,
                        "strength" => $monster_strength,
                        "dexterity" => $monster_dexterity,
                        "health" => $monster_health,
                        "wisdom" => $monster_wisdom,
                        "gear" => [],
                        "skills" => [$monster_ability, $monster_ability],
                    ]
                ]
            ]);

            if($battle_result['player_won']) {
                $ArenaLog .= "<span class='success'>You won the arena battle on floor $arena_floor!</span><BR />\n";

                // Both characters gain 1 point in a random stat
                $stats = ['strength', 'dexterity', 'health', 'wisdom'];

                # Get arena stat gain bonus (percentage chance for +1 additional stat)
                $arena_stat_bonus = isset($potion_bonuses['arena_stat_gains']) ? $potion_bonuses['arena_stat_gains'] : 0;

                $frontline_stat = $stats[array_rand($stats)];
                $frontline_stat_lucky = $stats[array_rand($stats)];

                // Do the lucky stat check for a 2nd chance to roll the rclass stat
                if($Character->Data['party_json']['members']['frontline']['class'] == $frontline_stat_lucky) {
                    $frontline_stat = $Character->Data['party_json']['members']['frontline']['class'];
                }

                $frontline_stat_gain = 1;
                # Roll for bonus stat from potion (percentage chance)
                if ($arena_stat_bonus > 0 && rand(1, 100) <= $arena_stat_bonus) {
                    $frontline_stat_gain++;
                }

                $Character->Data['party_json']['members']['frontline'][$frontline_stat] += $frontline_stat_gain;
                if ($frontline_stat_gain > 1) {
                    $ArenaLog = "<span class='success'>Frontline gained +$frontline_stat_gain $frontline_stat (+1 bonus from potion)!</span><BR />\n$ArenaLog";
                } else {
                    $ArenaLog = "<span class='success'>Frontline gained +$frontline_stat_gain $frontline_stat!</span><BR />\n$ArenaLog";
                }

                $backline_stat = $stats[array_rand($stats)];
                $backline_stat_lucky = $stats[array_rand($stats)];
                // Do the lucky stat check for a 2nd chance to roll the rclass stat
                if($Character->Data['party_json']['members']['backline']['class'] == $backline_stat_lucky) {
                    $backline_stat = $Character->Data['party_json']['members']['backline']['class'];
                }

                $backline_stat_gain = 1;
                # Roll for bonus stat from potion (percentage chance)
                while($arena_stat_bonus > 0 && rand(1, 100) <= $arena_stat_bonus) {
                    $backline_stat_gain++;
                    $arena_stat_bonus -= 100; // Only allow 1 bonus per 100% chance
                }

                $Character->Data['party_json']['members']['backline'][$backline_stat] += $backline_stat_gain;
                if ($backline_stat_gain > 1) {
                    $ArenaLog = "<span class='success'>Backline gained +$backline_stat_gain $backline_stat (+".($backline_stat_gain-1)." bonus from potion)!</span><BR />\n$ArenaLog";
                } else {
                    $ArenaLog = "<span class='success'>Backline gained +$backline_stat_gain $backline_stat!</span><BR />\n$ArenaLog";
                }

                // Award gold equal to arena floor (with potion bonus)
                $arena_resource_bonus = isset($potion_bonuses['arena_resource_drops']) ? $potion_bonuses['arena_resource_drops'] : 0;
                $base_gold = $arena_floor;
                $gold_multiplier = 1 + ($arena_resource_bonus / 100);
                $gold_award = round($base_gold * $gold_multiplier);

                $Character->Data['gold'] += $gold_award;
                if ($arena_resource_bonus > 0) {
                    $ArenaLog = "<span class='success'>You gained $gold_award gold (base: $base_gold, +".$arena_resource_bonus."% potion bonus: +".($gold_award - $base_gold).")!</span><BR />\n$ArenaLog";
                } else {
                    $ArenaLog = "<span class='success'>You gained $gold_award gold!</span><BR />\n$ArenaLog";
                }

                // Award bonus resource equal to arena floor (with potion bonus)
                $bonus_resources = ['iron', 'herbs', 'gems'];
                $bonus_resource = $bonus_resources[array_rand($bonus_resources)];
                $base_resource = $arena_floor;
                $resource_award = round($base_resource * $gold_multiplier); // Same multiplier as gold

                $Character->Data[$bonus_resource] += $resource_award;
                if ($arena_resource_bonus > 0) {
                    $ArenaLog = "<span class='success'>You gained $resource_award $bonus_resource (base: $base_resource, +".$arena_resource_bonus."% potion bonus: +".($resource_award - $base_resource).")!</span><BR />\n$ArenaLog";
                } else {
                    $ArenaLog = "<span class='success'>You gained $resource_award $bonus_resource!</span><BR />\n$ArenaLog";
                }

                // Award XP equal to arena floor * 10 (with potion bonus)
                $arena_xp_bonus = isset($potion_bonuses['arena_xp']) ? $potion_bonuses['arena_xp'] : 0;
                $base_xp = $arena_floor * 10;
                $xp_multiplier = 1 + ($arena_xp_bonus / 100);
                $xp_award = round($base_xp * $xp_multiplier);

                $Character->IncrementPartyXP($xp_award);
                if ($arena_xp_bonus > 0) {
                    $ArenaLog = "<span class='success'>Both party members gained $xp_award XP (base: $base_xp, +".$arena_xp_bonus."% potion bonus: +".($xp_award - $base_xp).")!</span><BR />\n$ArenaLog";
                } else {
                    $ArenaLog = "<span class='success'>Both party members gained $xp_award XP!</span><BR />\n$ArenaLog";
                }

            } else {
                $ArenaLog .= "<span class='danger'>You lost the arena battle on floor $arena_floor.</span><BR />\n";
            }

            $ArenaLog .= implode("<BR />\n", $battle_result['log'])."\n";
            $Character->Data['last_arena_time'] = date('Y-m-d H:i:s');
            $Character->Data['last_arena_log'] = $ArenaLog;
            $Character->SaveByUserId($r['user_id']);
            echo "Saved arena results for user_id: ".$r['user_id']."\n";
        }
    }

    $time_end = microtime(true);
    $execution_time = ($time_end - $time_start);
    echo "Arena script execution time: ".$execution_time." seconds\n";
