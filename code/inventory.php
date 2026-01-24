<?php

    $gear = new Gear();
    $potion = new Potion();

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

    # Load all player's items and potions
    $player_items = $gear->GetAllItemsByOwner($_SESSION['auth_user_id']);
    $player_potions = $potion->GetAllPotionsByOwner($_SESSION['auth_user_id']);

    # Load active potion if any
    $active_potion = $potion->GetActivePotion($Character->Data['id']);
