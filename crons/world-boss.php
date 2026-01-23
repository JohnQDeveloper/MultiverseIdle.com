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
if (($current_hour !== 0) && ($current_minute > 3)) {
    echo "World Boss cron skipped - not midnight (current hour: $current_hour)\n";
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

// TODO: Generate world boss stats based on number of participants or scaling factor
// $world_boss_level = calculate_world_boss_level($total_participants);
// $world_boss_strength = calculate_world_boss_attribute($world_boss_level);
// etc.

// TODO: Process battle for each participant
foreach ($queued_characters as $queued_char) {
    echo "Processing World Boss for character_id: " . $queued_char['id'] . "\n";

    // TODO: Load character and run battle
    // $Character = new Character();
    // $Character->LoadById($queued_char['id']);

    // TODO: Load potion bonuses
    // $Potion = new Potion();
    // $potion_bonuses = $Potion->GetActivePotionBonuses($queued_char['id']);

    // TODO: Simulate battle against world boss
    // $Battle = new Battle();
    // $battle_result = $Battle->Battle($queued_char['party_json'], $world_boss_party);

    // TODO: Award rewards based on participation/damage dealt
    // if ($battle_result['player_won']) { ... }

    // TODO: Log results
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
