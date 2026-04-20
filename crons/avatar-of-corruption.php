<?php

declare(strict_types=1);

$time_start = microtime(true);
require_once('../config.php');

/**
 * Avatar of Corruption Cron — Corruption Season only
 *
 * Runs once per day at midnight. Only executes when CORRUPTION_SEASON_ENABLED is true.
 * Processes all seasonal characters queued for the Avatar of Corruption battle.
 * Rewards: Corruption Orbs based on party level (1 orb per CORRUPTION_ORBS_PER_LEVELS combined levels).
 */

if (!CORRUPTION_SEASON_ENABLED) {
    echo "Avatar of Corruption cron skipped — CORRUPTION_SEASON_ENABLED is false.\n";
    return;
}

$current_hour   = (int) date('G');
$current_minute = (int) date('i');
if (($current_hour !== 0) || ($current_minute > 3)) {
    echo "Avatar of Corruption cron skipped — not midnight (current time: {$current_hour}:{$current_minute}).\n";
    return;
}

$today_key = 'avatar_corruption_ran_' . date('Y-m-d');
if ($redis->exists($today_key)) {
    echo "Avatar of Corruption cron already ran today, skipping.\n";
    return;
}

$season_filter = CORRUPTION_SEASON_PERPETUAL ? '' : 'AND c.season_id IS NOT NULL';
$queued_characters = $DAL->r(
    "SELECT c.id, c.user_id, c.party_json, c.season_id
     FROM characters c
     WHERE c.avatar_corruption_queued = 1
     {$season_filter}"
);

if (empty($queued_characters)) {
    echo "No players in Avatar of Corruption queue, skipping.\n";
    $redis->setex($today_key, 86400, '1');
    return;
}

$total_participants = count($queued_characters);
echo "Avatar of Corruption starting with {$total_participants} participants.\n";

$total_damage      = 0;
$participant_data  = [];

// First pass: simulate damage for each participant
foreach ($queued_characters as $queued_char) {
    $party_config = is_string($queued_char['party_json'])
        ? json_decode($queued_char['party_json'], true)
        : $queued_char['party_json'];

    $Battle      = new Battle();
    $battle_result = $Battle->WorldBossBattle($party_config);
    $damage_dealt  = $battle_result['total_damage'];
    $total_damage += $damage_dealt;

    // Sum of all party member levels owned by this character
    $frontline_level = (int)($party_config['members']['frontline']['level'] ?? 1);
    $backline_level  = (int)($party_config['members']['backline']['level'] ?? 1);
    $total_party_levels = $frontline_level + $backline_level;

    // 1 orb per CORRUPTION_ORBS_PER_LEVELS combined levels, minimum 1
    $orbs_earned = max(1, (int)floor($total_party_levels / CORRUPTION_ORBS_PER_LEVELS));

    $participant_data[] = [
        'character_id'      => (int)$queued_char['id'],
        'user_id'           => (int)$queued_char['user_id'],
        'season_id'         => (int)$queued_char['season_id'],
        'damage'            => $damage_dealt,
        'total_party_levels' => $total_party_levels,
        'orbs_earned'       => $orbs_earned,
    ];

    echo "Character " . $queued_char['id'] . " dealt {$damage_dealt} damage "
        . "(party levels: {$total_party_levels}, orbs: {$orbs_earned}).\n";
}

echo "Total damage dealt to Avatar of Corruption: {$total_damage}\n";

// Sort by damage descending for ranking
usort($participant_data, fn($a, $b) => $b['damage'] <=> $a['damage']);

// Second pass: award orbs and log
$rank = 1;
foreach ($participant_data as $participant) {
    $damage_percent = $total_damage > 0
        ? round(($participant['damage'] / $total_damage) * 100, 2)
        : 0.0;

    $RewardCharacter = new Character();
    $RewardCharacter->LoadById($participant['character_id']);

    // Award corruption orbs into inventory_json
    $current_orbs = (int)(
        $RewardCharacter->Data['inventory_json']['special_resources']['corruption_orbs'] ?? 0
    );
    $RewardCharacter->Data['inventory_json']['special_resources']['corruption_orbs'] =
        $current_orbs + $participant['orbs_earned'];

    // Append to log
    if (empty($RewardCharacter->Data['avatar_corruption_log'])) {
        $RewardCharacter->Data['avatar_corruption_log'] = '';
    }
    $RewardCharacter->Data['avatar_corruption_log'] .= date('Y-m-d H:i:s')
        . " - Rank #{$rank}: Dealt " . number_format($participant['damage'])
        . " damage ({$damage_percent}%), earned " . $participant['orbs_earned']
        . " Corruption Orb(s) (party levels: " . $participant['total_party_levels'] . ").\n";

    $RewardCharacter->Save();

    echo "  Rank #{$rank}: Awarded " . $participant['orbs_earned']
        . " orb(s) to Character " . $participant['character_id'] . ".\n";

    $rank++;
}

// Clear the queue
$DAL->w("UPDATE characters SET avatar_corruption_queued = 0 WHERE avatar_corruption_queued = 1");
$rows_cleared = $DAL->rows_affected();
echo "Cleared {$rows_cleared} characters from Avatar of Corruption queue.\n";

$redis->setex($today_key, 86400, '1');

$time_end = microtime(true);
echo "Avatar of Corruption cron completed in " . ($time_end - $time_start) . " seconds.\n";
