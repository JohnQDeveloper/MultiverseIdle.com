<?php

    require_once('../config.php');

    // Must interact every 3 days to be marked as active
    $rows = $DAL->r("SELECT user_id  FROM characters WHERE last_save > DATE_SUB(NOW(), INTERVAL 72 HOUR)");

    // Run active users
    foreach($rows as $r) {
        // Run 3 hours of ticks per cron run; should be set to 1 in production with a 10 minute cron
        $number_of_ticks = NUMBER_OF_TICKS_PER_RUN;

        while($number_of_ticks > 0) {
            $number_of_ticks--;
            echo "$number_of_ticks ticks remaining for user_id: ".$r['user_id']."\n";

            $Character = new Character();
            $Character->LoadByUserId($r['user_id']);
            $worker_config = $Character->Data['worker_json'];
            echo "Loaded & running workers for user_id: ".$r['user_id']."\n";
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
            $skill_level = $worker_config['skills'][$resource];
            $num_workers = $worker_config['workers'];
            $speed_upgrades = isset($worker_config['speed_upgrade_percent']) ? $worker_config['speed_upgrade_percent'] : 0;
            $intelligence_upgrades = isset($worker_config['intelligence_upgrade_percent']) ? $worker_config['intelligence_upgrade_percent'] : 0;

            $harvests = 10; // 10 harvests per 10 minute tick basically
            $harvests = round($harvests * (1 + ($speed_upgrades * 0.01)) * (1 + ($skill_level * 0.05)) * $num_workers);
            echo "Gained ".$harvests." $resource\n";
            echo "Gained ".$harvests." skill xp\n";

            # Worker XP Calculation
            if(!isset($Character->Data['worker_json']['skill_xp'])) {
                $Character->Data['worker_json']['skill_xp'] = [
                    "gold" => 0,
                    "iron" => 0,
                    "herbs" => 0,
                    "gems" => 0
                ];
            }
            if(!isset($Character->Data['worker_json']['skill_xp'][$worker_config['resource']])) {
                $Character->Data['worker_json']['skill_xp'][$worker_config['resource']] = 0;
            }
            
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

            # Add Resource
            $Character->Data[$resource] += $harvests;
            $Character->SaveByUserId($r['user_id']);
        }
    }
