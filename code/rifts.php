<?php

    $rift_stone = new RiftStone();
    $owner_id = isset($_SESSION['auth_user_id']) ? (int)$_SESSION['auth_user_id'] : 0;

    $has_active_sub = !empty($Character->Data['subscription_expires'])
        && strtotime($Character->Data['subscription_expires']) > time();
    $rift_queue_max = $has_active_sub ? 8 : 2;

    # Queue Rift Stone
    if (isset($_POST['queue_rift'])) {
        $rift_stone_id = intval($_POST['rift_stone_id']);

        # Verify ownership
        if (!$rift_stone->VerifyOwnership($rift_stone_id, $owner_id)) {
            $alert_danger = t('rifts.alert.not_owner');
        } else {
            # Check how many rifts are currently queued
            $queued_rifts = $rift_stone->GetQueuedRiftsByOwner($owner_id);

            if (count($queued_rifts) >= $rift_queue_max) {
                $alert_danger = t('rifts.alert.queue_full', ['max' => $rift_queue_max]);
            } else {
                # Load the rift stone to queue it
                if ($rift_stone->LoadRiftStoneByID($rift_stone_id)) {
                    # Determine next queue position
                    $next_position = count($queued_rifts) + 1;

                    # Update rift stone with queue position
                    if ($rift_stone->QueueRift($rift_stone_id, $next_position, $owner_id)) {
                        $alert_success = t('rifts.alert.queued', ['pos' => $next_position, 'max' => $rift_queue_max]);
                    } else {
                        $alert_danger = t('rifts.alert.queue_fail');
                    }
                } else {
                    $alert_danger = t('rifts.alert.load_fail');
                }
            }
        }
    }

    # Remove Rift from Queue
    if (isset($_POST['remove_rift'])) {
        $rift_stone_id = intval($_POST['rift_stone_id']);

        # Verify ownership
        if (!$rift_stone->VerifyOwnership($rift_stone_id, $owner_id)) {
            $alert_danger = t('rifts.alert.not_owner');
        } else {
            if ($rift_stone->RemoveFromQueue($rift_stone_id, $owner_id)) {
                $alert_success = t('rifts.alert.removed');
            } else {
                $alert_danger = t('rifts.alert.remove_fail');
            }
        }
    }

    # Load available rift stones (not queued)
    $available_rift_stones = $rift_stone->GetAvailableRiftStonesByOwner($owner_id);

    # Load queued rifts
    $queued_rifts = $rift_stone->GetQueuedRiftsByOwner($owner_id);

    # Simulate entire queue (QoL subscribers only)
    /** @var array<int, array{won: int, lost: int, total: int}> $rift_simulation_results */
    $rift_simulation_results = [];
    if ($has_active_sub && isset($_POST['simulate_queue'])) {
        $Battle = new Battle();
        foreach ($queued_rifts as $queued_rift) {
            $rift_simulation_results[$queued_rift['id']] = $Battle->SimulateRift($queued_rift, $Character);
        }
    }

    # Load rift stone definitions for display
    $rift_stone_implicit_definitions = RiftStone::getImplicitDefinitions();
    $rift_stone_affix_definitions = RiftStone::getAffixDefinitions();
