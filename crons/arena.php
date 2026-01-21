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
            $Character->LoadByUserId($r['user_id']);
            $party_config = $Character->Data['party_json'];

            $arena_floor = $Character->Data['arena_floor'];

            echo "Loaded & running party for user_id: ".$r['user_id']."\n";

            $monster_strength = 10 * $arena_floor;
            $monster_dexterity = 10 * $arena_floor;
            $monster_health = 10 * $arena_floor;
            $monster_wisdom = 10 * $arena_floor;
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
            ]);

            if($battle_result['player_won']) {
                $ArenaLog .= "<span class='success'>You won the arena battle on floor $arena_floor!</span><BR />\n";

                // Both characters gain 1 point in a random stat
                $stats = ['strength', 'dexterity', 'health', 'wisdom'];

                $frontline_stat = $stats[array_rand($stats)];
                $frontline_stat_lucky = $stats[array_rand($stats)];

                // Do the lucky stat check for a 2nd chance to roll the rclass stat
                if($Character->Data['party_json']['members']['frontline']['class'] == $frontline_stat_lucky) {
                    $frontline_stat = $Character->Data['party_json']['members']['frontline']['class'];
                }

                $Character->Data['party_json']['members']['frontline'][$frontline_stat]++;
                $ArenaLog = "<span class='success'>Frontline gained +1 $frontline_stat!</span><BR />\n$ArenaLog";

                $backline_stat = $stats[array_rand($stats)];
                $backline_stat_lucky = $stats[array_rand($stats)];
                // Do the lucky stat check for a 2nd chance to roll the rclass stat
                if($Character->Data['party_json']['members']['backline']['class'] == $backline_stat_lucky) {
                    $backline_stat = $Character->Data['party_json']['members']['backline']['class'];
                }

                $Character->Data['party_json']['members']['backline'][$backline_stat]++;
                $ArenaLog = "<span class='success'>Backline gained +1 $backline_stat!</span><BR />\n$ArenaLog";

                // Award gold equal to arena floor
                $Character->Data['gold'] += $arena_floor;
                $ArenaLog = "<span class='success'>You gained $arena_floor gold!</span><BR />\n$ArenaLog";

                // Award bonus resource equal to arena floor
                $bonus_resources = ['iron', 'herbs', 'gems'];
                $bonus_resource = $bonus_resources[array_rand($bonus_resources)];
                $Character->Data[$bonus_resource] += $arena_floor;
                $ArenaLog = "<span class='success'>You gained $arena_floor $bonus_resource!</span><BR />\n$ArenaLog";

                // Award XP equal to arena floor * 10
                $xp_award = $arena_floor * 10;
                $Character->Data['party_json']['members']['frontline']['xp'] += $xp_award;
                $Character->Data['party_json']['members']['backline']['xp'] += $xp_award;
                $ArenaLog = "<span class='success'>Both party members gained $xp_award XP!</span><BR />\n$ArenaLog";

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
