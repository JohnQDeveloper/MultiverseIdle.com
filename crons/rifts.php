<?php

declare(strict_types=1);

$time_start = microtime(true);
require_once('../config.php');

/**
 * Rift Cron
 *
 * This cron runs once per hour to process queued rift delves.
 * Players must win all 10 consecutive battles to earn rewards.
 */

// Check if we should run (only once per hour, at the top of the hour)
$current_minute = (int) date('i');
if ($current_minute >= 5 && $current_minute <= 8) { // Allow 5 minute buffer for cron timing
    echo "Rifts cron skipped - not top of hour (current minute: $current_minute)\n";
    return;
}

// Check if already ran this hour using Redis to prevent duplicate runs
$current_hour_key = 'rifts_ran_' . date('Y-m-d_H');
if ($redis->exists($current_hour_key)) {
    echo "Rifts cron already ran this hour, skipping.\n";
    return;
}

// Mark this hour's rift processing as started
$redis->setex($current_hour_key, 3600, '1'); // expires in 1 hour

// Process rifts for active users
$row = ActiveUsers();

foreach ($row as $r) {
    echo "Processing rifts for user_id: " . $r['user_id'] . "\n";

    $Character = new Character();
    $Character->LoadById($r['id']);

    $RiftStone = new RiftStone();
    $queued_rifts = $RiftStone->GetQueuedRiftsByOwner($r['user_id']);

    if (empty($queued_rifts)) {
        echo "No queued rifts for user_id: " . $r['user_id'] . "\n";
        continue;
    }

    // Process the first rift in queue (position 1)
    $current_rift = null;
    foreach ($queued_rifts as $rift) {
        if ($rift['queue_position'] == 1) {
            $current_rift = $rift;
            break;
        }
    }

    if ($current_rift === null) {
        echo "No rift at position 1 for user_id: " . $r['user_id'] . "\n";
        continue;
    }

    echo "Processing rift ID: " . $current_rift['id'] . " (Level " . $current_rift['level'] . ")\n";

    // Load potion bonuses
    $Potion = new Potion();
    $potion_bonuses = $Potion->GetActivePotionBonuses($Character->Data['id']);

    // Load rift stone definitions
    $rift_stone_implicit_definitions = RiftStone::getImplicitDefinitions();
    $rift_stone_affix_definitions = RiftStone::getAffixDefinitions();

    // Initialize rift battle log
    $rift_log = "";
    $rift_log .= "<h3>Rift Delve: " . htmlspecialchars($current_rift['name']) . "</h3>\n";
    $rift_log .= "<p><b>Rift Level:</b> " . $current_rift['level'] . "</p>\n";
    $rift_log .= "<p><b>Reward:</b> " . htmlspecialchars($rift_stone_implicit_definitions[$current_rift['implicit']]['description']) . "</p>\n";
    $rift_log .= "<p><b>Difficulty Affixes:</b> ";
    $affix_descriptions = [];
    foreach ($current_rift['affixes'] as $affix_key) {
        $affix_descriptions[] = htmlspecialchars($rift_stone_affix_definitions[$affix_key]['description']);
    }
    $rift_log .= implode(', ', $affix_descriptions) . "</p>\n";
    $rift_log .= "<hr />\n";

    // Track battle outcomes
    $battles_won = 0;
    $all_battles_won = true;
    $total_battles = 10;

    // Run 10 consecutive battles
    for ($battle_num = 1; $battle_num <= $total_battles; $battle_num++) {
        echo "  Battle $battle_num of $total_battles\n";
        $rift_log .= "<h4>Battle $battle_num/$total_battles</h4>\n";

        // Generate monster stats based on rift level
        $monster_strength = calculate_monster_attribute($current_rift['level']);
        $monster_dexterity = calculate_monster_attribute($current_rift['level']);
        $monster_health = calculate_monster_attribute($current_rift['level']);
        $monster_wisdom = calculate_monster_attribute($current_rift['level']);

        // Apply rift stone affixes to monsters
        $monster_skills = [];
        foreach ($current_rift['affixes'] as $affix_key) {
            switch ($affix_key) {
                case 'monster_skill':
                    // Add a random skill to the monster
                    $random_skill = SKILL_GEMS[array_rand(SKILL_GEMS)]['Name'];
                    $monster_skills[] = $random_skill;
                    break;
                case 'monster_damage':
                case 'monster_strength':
                    // +20% strength
                    $monster_strength = (int)floor($monster_strength * 1.2);
                    break;
                case 'monster_dexterity':
                    // +20% dexterity
                    $monster_dexterity = (int)floor($monster_dexterity * 1.2);
                    break;
                case 'monster_health':
                    // +20% health
                    $monster_health = (int)floor($monster_health * 1.2);
                    break;
                case 'monster_wisdom':
                    // +20% wisdom
                    $monster_wisdom = (int)floor($monster_wisdom * 1.2);
                    break;
            }
        }

        // Ensure monster has at least 2 skills
        if (count($monster_skills) < 2) {
            $monster_skills[] = SKILL_GEMS[array_rand(SKILL_GEMS)]['Name'];
        }
        if (count($monster_skills) < 2) {
            $monster_skills[] = SKILL_GEMS[array_rand(SKILL_GEMS)]['Name'];
        }

        $rift_log .= "<p>Monster Stats: $monster_strength STR, $monster_dexterity DEX, $monster_health HP, $monster_wisdom WIS</p>\n";
        $rift_log .= "<p>Monster Skills: " . implode(', ', $monster_skills) . "</p>\n";

        // Simulate battle
        $Battle = new Battle();
        $party_config = $Character->Data['party_json'];

        $battle_result = $Battle->Battle(
            // Player party
            $party_config,
            // Monster party
            [
                "members" => [
                    "frontline" => [
                        "class" => "warrior",
                        "level" => $current_rift['level'],
                        "strength" => $monster_strength,
                        "dexterity" => $monster_dexterity,
                        "health" => $monster_health,
                        "wisdom" => $monster_wisdom,
                        "gear" => [],
                        "skills" => $monster_skills,
                    ],
                    "backline" => [
                        "class" => "warrior",
                        "level" => $current_rift['level'],
                        "strength" => $monster_strength,
                        "dexterity" => $monster_dexterity,
                        "health" => $monster_health,
                        "wisdom" => $monster_wisdom,
                        "gear" => [],
                        "skills" => $monster_skills,
                    ]
                ]
            ],
            false // Don't echo log
        );

        if ($battle_result['player_won']) {
            $battles_won++;
            $rift_log .= "<span class='success'>Victory! ($battles_won/$total_battles wins so far)</span><BR />\n";
            echo "  Battle $battle_num: VICTORY\n";
        } else {
            $rift_log .= "<span class='danger'>Defeat! Rift Delve failed at battle $battle_num.</span><BR />\n";
            echo "  Battle $battle_num: DEFEAT - Rift failed!\n";
        }

        // Add complete battle log
        if (!empty($battle_result['log'])) {
            $rift_log .= "<details><summary>Battle Log</summary>\n";
            $rift_log .= implode("<BR />\n", $battle_result['log']);
            $rift_log .= "</details>\n";
        }
        $rift_log .= "<hr />\n";

        if ($battle_result['player_won']) {
            // Heal party to full for next battle
            $Character->Data['party_json']['members']['frontline']['current_health'] =
                $Character->Data['party_json']['members']['frontline']['health'] * 5;
            $Character->Data['party_json']['members']['backline']['current_health'] =
                $Character->Data['party_json']['members']['backline']['health'] * 5;
        } else {
            $all_battles_won = false;
            break; // Stop processing battles if one is lost
        }
    }

    // Award rewards if all battles were won
    if ($all_battles_won) {
        echo "All 10 battles won! Awarding rewards.\n";
        $rift_log .= "<h3><span class='success'>RIFT DELVE COMPLETED! All 10 battles won!</span></h3>\n";

        // Update highest rift level if this is a new record
        $current_highest = $Character->Data['highest_rift_level'] ?? 0;
        if ($current_rift['level'] > $current_highest) {
            $Character->Data['highest_rift_level'] = $current_rift['level'];
            echo "New highest rift level record: " . $current_rift['level'] . "\n";
        }

        // Calculate base rewards (equivalent to 30 arena floors)
        $base_gold = $current_rift['level'] * 30;
        $base_xp = $current_rift['level'] * 30 * 10; // 300 * level
        $base_resource = $current_rift['level'] * 30;
        $base_stat_gain = 30; // 30 stat points per character (60 total for both)

        // Apply implicit bonus (+60% for all)
        $implicit_multiplier = 1.6; // +60% = 1.6x

        // Apply potion bonuses
        $rift_xp_bonus = isset($potion_bonuses['rift_xp']) ? $potion_bonuses['rift_xp'] : 0;
        $rift_resource_bonus = isset($potion_bonuses['rift_drops']) ? $potion_bonuses['rift_drops'] : 0;
        $rift_stat_bonus = isset($potion_bonuses['rift_stat_gains']) ? $potion_bonuses['rift_stat_gains'] : 0;

        $stats = ['strength', 'dexterity', 'health', 'wisdom'];

        switch ($current_rift['implicit']) {
            case 'gold':
                // Award gold
                $gold_award = (int)round($base_gold * $implicit_multiplier * (1 + $rift_resource_bonus / 100));
                $Character->Data['gold'] += $gold_award;
                $rift_log .= "<span class='success'>Earned $gold_award gold!</span><BR />\n";
                break;

            case 'xp':
                // Award XP
                $xp_award = (int)round($base_xp * $implicit_multiplier * (1 + $rift_xp_bonus / 100));
                $Character->IncrementPartyXP($xp_award, $r['user_id']);
                $rift_log .= "<span class='success'>Both party members gained $xp_award XP!</span><BR />\n";
                break;

            case 'resource_drop':
                // Award random resource
                $bonus_resources = ['iron', 'herbs', 'gems'];
                $bonus_resource = $bonus_resources[array_rand($bonus_resources)];
                $resource_award = (int)round($base_resource * $implicit_multiplier * (1 + $rift_resource_bonus / 100));
                $Character->Data[$bonus_resource] += $resource_award;
                $rift_log .= "<span class='success'>Earned $resource_award $bonus_resource!</span><BR />\n";
                break;

            case 'stat_gains':
                // Award stat gains to both party members
                $frontline_stat = $stats[array_rand($stats)];
                $backline_stat = $stats[array_rand($stats)];

                // Apply class preference
                if (rand(1, 2) == 1) {
                    $frontline_stat = $Character->Data['party_json']['members']['frontline']['class'];
                }
                if (rand(1, 2) == 1) {
                    $backline_stat = $Character->Data['party_json']['members']['backline']['class'];
                }

                $frontline_stat_gain = (int)round($base_stat_gain * $implicit_multiplier);
                $backline_stat_gain = (int)round($base_stat_gain * $implicit_multiplier);

                // Apply potion bonus (percentage chance for +1)
                if ($rift_stat_bonus > 0 && rand(1, 100) <= $rift_stat_bonus) {
                    $frontline_stat_gain++;
                }
                if ($rift_stat_bonus > 0 && rand(1, 100) <= $rift_stat_bonus) {
                    $backline_stat_gain++;
                }

                $Character->Data['party_json']['members']['frontline'][$frontline_stat] += $frontline_stat_gain;
                $Character->Data['party_json']['members']['backline'][$backline_stat] += $backline_stat_gain;

                $rift_log .= "<span class='success'>Frontline gained +$frontline_stat_gain $frontline_stat!</span><BR />\n";
                $rift_log .= "<span class='success'>Backline gained +$backline_stat_gain $backline_stat!</span><BR />\n";
                break;
        }
    } else {
        echo "Rift failed - no rewards awarded.\n";
        $rift_log .= "<h3><span class='danger'>RIFT DELVE FAILED</span></h3>\n";
        $rift_log .= "<p>You won $battles_won out of $total_battles battles.</p>\n";
        $rift_log .= "<p><span class='danger'>No rewards - you must win all 10 battles to earn rewards.</span></p>\n";
    }

    // Remove from queue and reorder remaining rifts
    echo "Removing rift ID " . $current_rift['id'] . " from queue and reordering...\n";
    $RiftStone->RemoveFromQueue((int)$current_rift['id'], $r['user_id']);

    // Delete the rift stone (consumed regardless of outcome)
    echo "Deleting rift stone ID: " . $current_rift['id'] . "\n";
    $RiftStone->DestroyRiftStone((int)$current_rift['id'], $r['user_id']);

    // Save rift log to character
    $Character->Data['last_rift_time'] = date('Y-m-d H:i:s');
    $Character->Data['last_rift_log'] = $rift_log;
    $Character->SaveByUserId($r['user_id']);

    echo "Rift processing completed for user_id: " . $r['user_id'] . "\n\n";
}

$time_end = microtime(true);
$execution_time = ($time_end - $time_start);
echo "Rifts cron execution time: " . $execution_time . " seconds\n";
