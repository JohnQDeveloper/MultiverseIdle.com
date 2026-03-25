<?php

    $time_start = microtime(true);
    require_once('../config.php');

    // Must interact every 3 days to be marked as active
    $rows = ActiveUsers();

    // Run active users
    foreach($rows as $r) {
        // Run 8 hours of ticks per cron run; should be set to 1 in production
        $number_of_ticks = NUMBER_OF_MINUTES_PER_RUN;

        while($number_of_ticks > 0) {
            $number_of_ticks--;
            echo "$number_of_ticks ticks remaining for user_id: ".$r['user_id']."\n";

            $Character = new Character();
            $Character->LoadById($r['id']);
            $worker_config = $Character->Data['worker_json'];
            echo "Loaded & running workers for user_id: ".$r['user_id']."\n";

            # Load potion bonuses
            $Potion = new Potion();
            $potion_bonuses = $Potion->GetActivePotionBonuses($Character->Data['id']);
            echo "Loaded potion bonuses for user_id: ".$r['user_id']."\n";
            #print_r($worker_config);
            /*
            Array
            (
                [skills] => Array
                    (
                        [gems] => 1
                        [gold] => 1
                        [iron] => 1
                        [herbs] => 1
                    )

                [workers] => 1
                [resource] => gold
                [speed_upgrades] => 1
                [intelligence_upgrades] => 1
            )
            */

            $resource = $worker_config['resource'];
            $skill_level = (int)($worker_config['skills'][$resource] ?? 1);
            $num_workers = $worker_config['workers'];

            # Ensure skill_xp is initialized for all resources
            if (!isset($Character->Data['worker_json']['skill_xp'])) {
                $Character->Data['worker_json']['skill_xp'] = ['gold' => 0, 'iron' => 0, 'herbs' => 0, 'gems' => 0];
            }
            foreach (['gold', 'iron', 'herbs', 'gems'] as $res) {
                if (!isset($Character->Data['worker_json']['skill_xp'][$res])) {
                    $Character->Data['worker_json']['skill_xp'][$res] = 0;
                }
            }
            $speed_upgrades = $worker_config['speed_upgrades'] ?? 0;
            $intelligence_upgrades = $worker_config['intelligence_upgrades'] ?? 0;

            # Get the specific potion bonus for this resource type
            # Note: the potion system uses 'herb' (singular) for the herbs resource
            $potion_resource_key = ($resource === 'herbs') ? 'herb' : $resource;
            $potion_bonus_key = $potion_resource_key . '_worker_yield';
            $potion_bonus = isset($potion_bonuses[$potion_bonus_key]) ? $potion_bonuses[$potion_bonus_key] : 0;

            $harvests = 10; // 10 harvests per 1 minute tick basically
            $harvests_without_potion = worker_yield($harvests, $speed_upgrades, $skill_level, $num_workers, 0);
            $harvests_with_potion = worker_yield($harvests, $speed_upgrades, $skill_level, $num_workers, $potion_bonus);

            echo display_worker_yield_formula(10, $speed_upgrades, $skill_level, $num_workers, $potion_bonus);
            if ($potion_bonus > 0) {
                echo "Gained ".$harvests_with_potion." $resource (base: ".$harvests_without_potion.", +".$potion_bonus."% potion bonus: +".($harvests_with_potion - $harvests_without_potion).")\n";
            } else {
                echo "Gained ".$harvests_with_potion." $resource\n";
            }
            echo "Gained ".$harvests_with_potion." skill xp\n";

            $harvests = $harvests_with_potion;

            # Worker XP Calculation
            $Character->Data['worker_json']['skill_xp'][$worker_config['resource']] += $harvests;
            $xp = $Character->Data['worker_json']['skill_xp'][$worker_config['resource']];
            $xp_needed = 100 * (pow($skill_level,2));
            echo "Current XP: ".$xp." / ".$xp_needed."\n";

            if($xp >= $xp_needed) {
                // Level Up!
                $Character->Data['worker_json']['skills'][$worker_config['resource']] += 1;
                $Character->Data['worker_json']['skill_xp'][$worker_config['resource']] = $xp - $xp_needed;
                echo "Worker skill ".$worker_config['resource']." leveled up to ".$Character->Data['worker_json']['skills'][$worker_config['resource']]."\n";
            }

            print_r($Character->Data['worker_json']);

            # Add Resource (minus guild tax)
            $Character->Data[$resource] += $harvests;
            $worker_tax = collectGuildTax($r['user_id'], $Character->Data['season_id'] ?? null, $resource, (int)$harvests);
            if ($worker_tax > 0) {
                $Character->Data[$resource] -= $worker_tax;
                echo "Guild tax: $worker_tax $resource sent to guild bank\n";
            }
            $Character->SaveByUserId($r['user_id']);
        }
    }

    $time_end = microtime(true);
    $execution_time = ($time_end - $time_start);
    echo "Workers script execution time: ".$execution_time." seconds\n";
