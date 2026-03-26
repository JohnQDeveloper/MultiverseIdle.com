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

// Get queue statistics
$queue_count_result = $DAL->r("SELECT COUNT(*) as count FROM characters WHERE world_boss_queued=1");
$total_in_queue = $queue_count_result[0]['count'] ?? 0;
