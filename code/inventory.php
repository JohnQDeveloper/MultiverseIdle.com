<?php

    $gear = new Gear();

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

    # Load all player's items
    $player_items = $gear->GetAllItemsByOwner($_SESSION['auth_user_id']);
