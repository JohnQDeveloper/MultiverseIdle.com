<?php

    $gear = new Gear();
    $potion = new Potion();
    $rift_stone = new RiftStone();

    # Destroy Item
    if (isset($_POST['destroy_item'])) {
        $gear_id = intval($_POST['gear_id']);
        $owner_id = $_SESSION['auth_user_id'];

        if ($gear->DestroyItem($gear_id, $owner_id)) {
            $alert_success = t('inventory.alert.destroyed');
        } else {
            $alert_danger = t('inventory.alert.destroy_fail');
        }
    }

    # Toggle Favorite
    if (isset($_POST['toggle_favorite'])) {
        $gear_id = intval($_POST['gear_id']);
        $owner_id = $_SESSION['auth_user_id'];

        if ($gear->ToggleFavorite($gear_id, $owner_id)) {
            $alert_success = t('inventory.alert.fav_updated');
        } else {
            $alert_danger = t('inventory.alert.fav_fail');
        }
    }

    # Destroy Potion
    if (isset($_POST['destroy_potion'])) {
        $potion_id = intval($_POST['potion_id']);
        $owner_id = $_SESSION['auth_user_id'];

        if ($potion->DestroyPotion($potion_id, $owner_id)) {
            $alert_success = t('inventory.alert.potion_destroyed');
        } else {
            $alert_danger = t('inventory.alert.potion_dest_fail');
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
            $alert_danger = t('inventory.alert.potion_active');
        } else {
            if ($potion->UsePotion($potion_id, $character_id, $owner_id)) {
                $alert_success = t('inventory.alert.potion_used');
            } else {
                $alert_danger = t('inventory.alert.potion_use_fail');
            }
        }
    }

    # Cancel Active Potion
    if (isset($_POST['cancel_potion'])) {
        $owner_id = $_SESSION['auth_user_id'];
        $character_id = $Character->Data['id'];

        if ($potion->CancelActivePotion($character_id, $owner_id)) {
            $alert_success = t('inventory.alert.potion_cancelled');
        } else {
            $alert_danger = t('inventory.alert.potion_cancel_fail');
        }
    }

    # Destroy Rift Stone
    if (isset($_POST['destroy_rift_stone'])) {
        $rift_stone_id = intval($_POST['rift_stone_id']);
        $owner_id = $_SESSION['auth_user_id'];

        if ($rift_stone->DestroyRiftStone($rift_stone_id, $owner_id)) {
            $alert_success = t('inventory.alert.rift_destroyed');
        } else {
            $alert_danger = t('inventory.alert.rift_dest_fail');
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
