<?php

// Handle queue join
if(isset($_POST['join_queue'])) {
    // Check if already queued
    if($Character->Data['world_boss_queued'] != 1) {
        $Character->Data['world_boss_queued'] = 1;
        $alert_success = 'You have joined the World Boss queue!';
    } else {
        $alert_danger = 'You are already in the queue.';
    }
}

// Handle queue leave
if(isset($_POST['leave_queue'])) {
    if($Character->Data['world_boss_queued'] == 1) {
        $Character->Data['world_boss_queued'] = 0;
        $alert_success = 'You have left the World Boss queue.';
    } else {
        $alert_danger = 'You are not in the queue.';
    }
}

// Get queue statistics
$queue_count_result = $DAL->r("SELECT COUNT(*) as count FROM characters WHERE world_boss_queued=1");
$total_in_queue = $queue_count_result[0]['count'] ?? 0;
