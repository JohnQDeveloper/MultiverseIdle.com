<?php

// Handle queue join
if(isset($_POST['join_queue'])) {
    // Check if already queued
    if($Character->Data['world_boss_queued'] != 1) {
        $Character->Data['world_boss_queued'] = 1;
        $alert_success = t('world_boss.alert.joined');
    } else {
        $alert_danger = t('world_boss.alert.already');
    }
}

// Handle queue leave
if(isset($_POST['leave_queue'])) {
    if($Character->Data['world_boss_queued'] == 1) {
        $Character->Data['world_boss_queued'] = 0;
        $alert_success = t('world_boss.alert.left');
    } else {
        $alert_danger = t('world_boss.alert.not_in_queue');
    }
}

// Handle Avatar of Corruption queue (seasonal + feature flag only)
$is_corruption_season = CORRUPTION_SEASON_ENABLED
    && (CORRUPTION_SEASON_PERPETUAL || ($Character->Data['season_id'] ?? null) !== null);

if ($is_corruption_season && isset($_POST['join_corruption_queue'])) {
    if (($Character->Data['avatar_corruption_queued'] ?? 0) != 1) {
        $Character->Data['avatar_corruption_queued'] = 1;
        $alert_success = t('corruption.avatar.alert.joined');
    } else {
        $alert_danger = t('world_boss.alert.already');
    }
}

if ($is_corruption_season && isset($_POST['leave_corruption_queue'])) {
    if (($Character->Data['avatar_corruption_queued'] ?? 0) == 1) {
        $Character->Data['avatar_corruption_queued'] = 0;
        $alert_success = t('world_boss.alert.left');
    } else {
        $alert_danger = t('world_boss.alert.not_in_queue');
    }
}

// Get queue statistics
$queue_count_result = $DAL->r("SELECT COUNT(*) as count FROM characters WHERE world_boss_queued=1");
$total_in_queue = $queue_count_result[0]['count'] ?? 0;

$avatar_corruption_queue_count = 0;
if ($is_corruption_season) {
    $corruption_queue_sql = CORRUPTION_SEASON_PERPETUAL
        ? "SELECT COUNT(*) as count FROM characters WHERE avatar_corruption_queued=1"
        : "SELECT COUNT(*) as count FROM characters WHERE avatar_corruption_queued=1 AND season_id IS NOT NULL";
    $corruption_queue_result = $DAL->r($corruption_queue_sql);
    $avatar_corruption_queue_count = $corruption_queue_result[0]['count'] ?? 0;
}
