<?php

    $gear = new Gear();
    $potion = new Potion();
    $rift_stone = new RiftStone();

    # Destroy Item
    if (isset($_POST['destroy_item'])) {
        $gear_id = intval($_POST['gear_id']);
        $owner_id = $_SESSION['auth_user_id'];

        if ($gear->DestroyItem($gear_id, $owner_id)) {
            $alert_success = 'Item has been destroyed.';
        } else {
            $alert_danger = 'Failed to destroy item.';
        }
    }

    # Toggle Favorite
    if (isset($_POST['toggle_favorite'])) {
        $gear_id = intval($_POST['gear_id']);
        $owner_id = $_SESSION['auth_user_id'];

        if ($gear->ToggleFavorite($gear_id, $owner_id)) {
            $alert_success = 'Item favorite status updated.';
        } else {
            $alert_danger = 'Failed to update favorite status.';
        }
    }

    # Destroy Potion
    if (isset($_POST['destroy_potion'])) {
        $potion_id = intval($_POST['potion_id']);
        $owner_id = $_SESSION['auth_user_id'];

        if ($potion->DestroyPotion($potion_id, $owner_id)) {
            $alert_success = 'Potion has been destroyed.';
        } else {
            $alert_danger = 'Failed to destroy potion.';
        }
    }

    # Use Potion
    if (isset($_POST['use_potion'])) {
        $potion_id = intval($_POST['potion_id']);
        $owner_id = $_SESSION['auth_user_id'];
        $character_id = $Character->Data['id'];

        # Check if there's already an active potion
        $active_potion = $potion->GetActivePotion($character_id);
        if ($active_potion) {
            $alert_danger = 'You already have an active potion. Wait for it to expire before using another.';
        } else {
            if ($potion->UsePotion($potion_id, $character_id, $owner_id)) {
                $alert_success = 'Potion activated! Effects will last for 24 hours.';
            } else {
                $alert_danger = 'Failed to use potion.';
            }
        }
    }

    # Cancel Active Potion
    if (isset($_POST['cancel_potion'])) {
        $owner_id = $_SESSION['auth_user_id'];
        $character_id = $Character->Data['id'];

        if ($potion->CancelActivePotion($character_id, $owner_id)) {
            $alert_success = 'Active potion has been cancelled and deleted.';
        } else {
            $alert_danger = 'Failed to cancel active potion.';
        }
    }

    # Destroy Rift Stone
    if (isset($_POST['destroy_rift_stone'])) {
        $rift_stone_id = intval($_POST['rift_stone_id']);
        $owner_id = $_SESSION['auth_user_id'];

        if ($rift_stone->DestroyRiftStone($rift_stone_id, $owner_id)) {
            $alert_success = 'Rift stone has been destroyed.';
        } else {
            $alert_danger = 'Failed to destroy rift stone.';
        }
    }

    # Load all player's items, potions, and rift stones
    $player_items = $gear->GetAllItemsByOwner($_SESSION['auth_user_id']);
    $player_potions = $potion->GetAllPotionsByOwner($_SESSION['auth_user_id']);
    $player_rift_stones = $rift_stone->GetAllRiftStonesByOwner($_SESSION['auth_user_id']);

    # Load active potion if any
    $active_potion = $potion->GetActivePotion($Character->Data['id']);

    # Get equipped gear IDs for display
    $equipped_gear_ids = [
        $Character->Data['party_json']['members']['frontline']['equipped_weapon'] ?? 0,
        $Character->Data['party_json']['members']['frontline']['equipped_armor'] ?? 0,
        $Character->Data['party_json']['members']['backline']['equipped_weapon'] ?? 0,
        $Character->Data['party_json']['members']['backline']['equipped_armor'] ?? 0,
    ];
    $equipped_gear_ids = array_filter($equipped_gear_ids); # Remove zeros
