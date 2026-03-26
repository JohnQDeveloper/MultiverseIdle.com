<?php

declare(strict_types=1);

$TreasureChest = new TreasureChest();
$user_id       = isset($_SESSION['auth_user_id']) ? (int)$_SESSION['auth_user_id'] : 0;

$has_active_sub = !empty($Character->Data['subscription_expires'])
    && strtotime($Character->Data['subscription_expires']) > time();

$queue_max = $has_active_sub ? TreasureChest::QUEUE_MAX_SUB : TreasureChest::QUEUE_MAX;

// Queue a chest
if (isset($_POST['queue_chest'])) {
    $chest_size = (string)($_POST['chest_size'] ?? '');
    $quantity   = max(1, (int)($_POST['quantity'] ?? 1));

    if (!array_key_exists($chest_size, TreasureChest::CHEST_MULTIPLIERS)) {
        $alert_danger = t('pvp.alert.invalid_size');
    } else {
        $queued    = $TreasureChest->GetQueuedChestsByOwner($user_id);
        $available = $queue_max - count($queued);

        if ($available <= 0) {
            $alert_danger = t('pvp.alert.queue_full', ['max' => $queue_max]);
        } else {
            $quantity  = min($quantity, $available);
            $season_id = $Character->Data['season_id'] ?? null;
            $queued_count = count($queued);
            $failed    = 0;

            for ($i = 0; $i < $quantity; $i++) {
                $next_position = $queued_count + $i + 1;
                if (!$TreasureChest->QueueChest($chest_size, $user_id, $next_position, $season_id)) {
                    $failed++;
                }
            }

            $added = $quantity - $failed;
            if ($added > 0) {
                $alert_success = t('pvp.alert.queued_multi', ['count' => $added, 'max' => $queue_max]);
            }
            if ($failed > 0) {
                $alert_danger = t('pvp.alert.queue_fail');
            }
        }
    }
}

// Queue a wyrdstone node
if (isset($_POST['queue_wyrdstone_node'])) {
    $node_size = (string)($_POST['node_size'] ?? '');
    $quantity  = max(1, (int)($_POST['quantity'] ?? 1));

    if (!array_key_exists($node_size, TreasureChest::WYRDSTONE_NODE_STORAGE_SIZES)) {
        $alert_danger = t('pvp.alert.invalid_node_size');
    } else {
        $queued    = $TreasureChest->GetQueuedChestsByOwner($user_id);
        $available = $queue_max - count($queued);

        if ($available <= 0) {
            $alert_danger = t('pvp.alert.queue_full', ['max' => $queue_max]);
        } else {
            $quantity     = min($quantity, $available);
            $season_id    = $Character->Data['season_id'] ?? null;
            $queued_count = count($queued);
            $failed       = 0;

            for ($i = 0; $i < $quantity; $i++) {
                $next_position = $queued_count + $i + 1;
                if (!$TreasureChest->QueueWyrdstoneNode($node_size, $user_id, $next_position, $season_id)) {
                    $failed++;
                }
            }

            $added = $quantity - $failed;
            if ($added > 0) {
                $alert_success = t('pvp.alert.queued_nodes_multi', ['count' => $added]);
            }
            if ($failed > 0) {
                $alert_danger = t('pvp.alert.queue_fail');
            }
        }
    }
}

// Remove a chest from queue
if (isset($_POST['remove_chest'])) {
    $chest_id = (int)($_POST['chest_id'] ?? 0);

    if ($TreasureChest->RemoveChest($chest_id, $user_id)) {
        $alert_success = t('pvp.alert.removed');
    } else {
        $alert_danger = t('pvp.alert.remove_fail');
    }
}

// Load current queue for display
$queued_chests = $TreasureChest->GetQueuedChestsByOwner($user_id);
