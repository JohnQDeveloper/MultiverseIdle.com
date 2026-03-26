<?php

declare(strict_types=1);

$time_start = microtime(true);
require_once('../config.php');

/**
 * World Boss Cron
 *
 * This cron runs once per day at midnight to process the world boss battle.
 * It checks if there are players in the queue, runs the battle, and clears the queue.
 */

// Check if we should run (only once per day at midnight)
$current_hour = (int) date('G');
$current_minute = (int) date('i'); # Only run at 00:00 to 00:03, slight buffer for cron timing
if (($current_hour !== 0) || ($current_minute > 3)) {
    echo "World Boss cron skipped - not midnight (current hour: $current_hour:$current_minute)\n";
    return;
}

// Check if already ran today using Redis to prevent duplicate runs
$today_key = 'world_boss_ran_' . date('Y-m-d');
if ($redis->exists($today_key)) {
    echo "World Boss cron already ran today, skipping.\n";
    return;
}

// Get all characters in the world boss queue
$queued_characters = $DAL->r("SELECT c.id, c.user_id, c.party_json FROM characters c WHERE c.world_boss_queued = 1");

if (empty($queued_characters)) {
    echo "No players in World Boss queue, skipping.\n";
    // Mark as ran even if no players to prevent repeated checks
    $redis->setex($today_key, 86400, '1'); // expires in 24 hours
    return;
}

$total_participants = count($queued_characters);
echo "World Boss starting with $total_participants participants.\n";

$total_world_boss_damage = 0;
$participant_damage = []; // Track damage per character

// First pass: Calculate damage for all participants
foreach ($queued_characters as $queued_char) {
    echo "Processing World Boss for character_id: " . $queued_char['id'] . "\n";

    // Load character and run battle
    $Character = new Character();
    $Character->LoadById((int)$queued_char['id']);


    // Decode party_json if it's a string
    $party_config = is_string($queued_char['party_json'])
        ? json_decode($queued_char['party_json'], true)
        : $queued_char['party_json'];

    // Simulate battle against world boss (100 rounds of damage)
    $Battle = new Battle();
    $battle_result = $Battle->WorldBossBattle($party_config);

    $damage_dealt = $battle_result['total_damage'];
    $total_world_boss_damage += $damage_dealt;

    // Store damage for ranking calculation
    $participant_damage[] = [
        'character_id' => (int)$queued_char['id'],
        'user_id' => (int)$queued_char['user_id'],
        'damage' => $damage_dealt,
    ];

    echo "Character " . $queued_char['id'] . " dealt $damage_dealt damage to World Boss.\n";
}

echo "Total damage dealt to World Boss: $total_world_boss_damage\n";

// Second pass: Calculate rankings based on percentage of total damage
if ($total_world_boss_damage > 0) {
    // Sort by damage descending
    usort($participant_damage, function ($a, $b) {
        return $b['damage'] <=> $a['damage'];
    });

    // Calculate percentages and assign ranks
    $rank = 1;
    foreach ($participant_damage as &$participant) {
        $participant['rank'] = $rank;
        $participant['damage_percent'] = round(($participant['damage'] / $total_world_boss_damage) * 100, 2);

        echo "Rank #$rank: Character " . $participant['character_id'] .
             " - " . $participant['damage'] . " damage (" . $participant['damage_percent'] . "%)\n";

        // Award rewards: 10% of damage dealt as gold and XP
        $gold_reward = (int) floor($participant['damage'] * 0.10);
        $xp_reward = (int) floor($participant['damage'] * 0.10);

        // Load character to award rewards
        $RewardCharacter = new Character();
        $RewardCharacter->LoadById($participant['character_id']);

        // Load potion bonuses
        $Potion = new Potion();
        $potion_bonuses = $Potion->GetActivePotionBonuses((int)$RewardCharacter->Data['user_id']);

        // Load guild building bonuses
        $guild_building_bonuses = getGuildBuildingBonuses($participant['user_id'], $RewardCharacter->Data['season_id'] ?? null);

        // Apply market building bonus to gold reward before awarding
        if ($guild_building_bonuses['market'] > 0) {
            $gold_reward = (int)round($gold_reward * (1 + $guild_building_bonuses['market'] / 100));
        }

        // Award gold (minus guild tax)
        $RewardCharacter->Data['gold'] += $gold_reward;
        $gold_tax = collectGuildTax($participant['user_id'], $RewardCharacter->Data['season_id'] ?? null, 'gold', $gold_reward);
        if ($gold_tax > 0) {
            $RewardCharacter->Data['gold'] -= $gold_tax;
        }

        // Apply world boss XP potion bonus + tavern building bonus (percentage increase)
        $world_boss_xp_bonus = ($potion_bonuses['world_boss_xp'] ?? 0) + $guild_building_bonuses['tavern'];
        if ($world_boss_xp_bonus > 0) {
            $xp_reward += (int) floor($xp_reward * ($world_boss_xp_bonus / 100));
        }

        // Award XP to party members
        $RewardCharacter->IncrementPartyXP($xp_reward, $participant['user_id']);

        // Initialize world_boss_log if null
        if (empty($RewardCharacter->Data['world_boss_log'])) {
            $RewardCharacter->Data['world_boss_log'] = '';
        }

        $net_gold = $gold_reward - ($gold_tax ?? 0);
        $RewardCharacter->Data['world_boss_log'] .= date('Y-m-d H:i:s') . " - Rank #$rank: Dealt " . number_format($participant['damage']) .
            " damage (" . $participant['damage_percent'] . "%), awarded " . number_format($net_gold) . " gold" .
            (($gold_tax ?? 0) > 0 ? " (-" . number_format($gold_tax) . " guild tax)" : "") .
            " and " . number_format($xp_reward) . " XP.\n";

        echo "  Awarded $gold_reward gold and $xp_reward XP to Character " . $participant['character_id'] . "\n";

        // Save character
        $RewardCharacter->Save();




        $rank++;
    }
    unset($participant); // Break reference
}

// TODO: Log results to character's log

// Increment guild quest progress for world boss top 50%
if ($total_world_boss_damage > 0 && count($participant_damage) >= 2) {
    $top50_cutoff = (int) ceil(count($participant_damage) / 2);
    // $participant_damage is already sorted by damage descending with rank assigned
    $guild_top50_counts = [];
    foreach ($participant_damage as $participant) {
        if ($participant['rank'] <= $top50_cutoff) {
            // Look up this user's guild
            $GuildLookup = new Guild();
            $wb_guild_id = $GuildLookup->GetUserGuildId($participant['user_id']);
            if ($wb_guild_id !== null) {
                $guild_top50_counts[$wb_guild_id] = ($guild_top50_counts[$wb_guild_id] ?? 0) + 1;
            }
        }
    }
    foreach ($guild_top50_counts as $wb_guild_id => $count) {
        $GuildQuestsWb = new GuildQuests();
        // Season ID: world boss runs globally without season filtering; use null
        $GuildQuestsWb->setSeasonId(null);
        $GuildQuestsWb->IncrementProgress($wb_guild_id, 'world_boss_top50', $count);
        echo "Guild $wb_guild_id had $count member(s) in top 50% of World Boss.\n";
    }
}

// Clear the queue - remove all participants after battle completes
$DAL->w("UPDATE characters SET world_boss_queued = 0 WHERE world_boss_queued = 1");
$rows_cleared = $DAL->rows_affected();
echo "Cleared $rows_cleared characters from World Boss queue.\n";

// Mark this day's world boss as completed
$redis->setex($today_key, 86400, '1'); // expires in 24 hours

$time_end = microtime(true);
$execution_time = ($time_end - $time_start);
echo "World Boss cron completed in $execution_time seconds.\n";
