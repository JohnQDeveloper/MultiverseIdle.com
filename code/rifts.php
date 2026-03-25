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
            $alert_danger = 'You do not own that rift stone.';
        } else {
            # Check how many rifts are currently queued
            $queued_rifts = $rift_stone->GetQueuedRiftsByOwner($owner_id);

            if (count($queued_rifts) >= $rift_queue_max) {
                $alert_danger = 'You can only queue up to ' . $rift_queue_max . ' rifts at a time. Wait for some to complete.';
            } else {
                # Load the rift stone to queue it
                if ($rift_stone->LoadRiftStoneByID($rift_stone_id)) {
                    # Determine next queue position
                    $next_position = count($queued_rifts) + 1;

                    # Update rift stone with queue position
                    if ($rift_stone->QueueRift($rift_stone_id, $next_position, $owner_id)) {
                        $alert_success = 'Rift stone queued successfully! Position: ' . $next_position . '/' . $rift_queue_max;
                    } else {
                        $alert_danger = 'Failed to queue rift stone.';
                    }
                } else {
                    $alert_danger = 'Failed to load rift stone.';
                }
            }
        }
    }

    # Remove Rift from Queue
    if (isset($_POST['remove_rift'])) {
        $rift_stone_id = intval($_POST['rift_stone_id']);

        # Verify ownership
        if (!$rift_stone->VerifyOwnership($rift_stone_id, $owner_id)) {
            $alert_danger = 'You do not own that rift stone.';
        } else {
            if ($rift_stone->RemoveFromQueue($rift_stone_id, $owner_id)) {
                $alert_success = 'Rift stone removed from queue.';
            } else {
                $alert_danger = 'Failed to remove rift stone from queue.';
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
