<?php

declare(strict_types=1);

// This page is seasonal + feature-flag gated
if (!CORRUPTION_SEASON_ENABLED || (!CORRUPTION_SEASON_PERPETUAL && ($Character->Data['season_id'] ?? null) === null)) {
    header('Location: /');
    exit;
}

$orb_result   = null; // 'bonus' | 'penalty'
$orb_gear_id  = null;

if (isset($_POST['apply_orb'])) {
    $gear_id = (int)($_POST['gear_id'] ?? 0);

    if ($gear_id <= 0) {
        $alert_danger = t('corruption.orb.alert.invalid_gear');
    } elseif ((int)($Character->Data['inventory_json']['special_resources']['corruption_orbs'] ?? 0) < 1) {
        $alert_danger = t('corruption.orb.alert.no_orbs');
    } else {
        $Gear = new Gear();
        $result = $Gear->ApplyCorruptionOrb($gear_id, (int)$_SESSION['auth_user_id']);

        if ($result === false) {
            $alert_danger = t('corruption.orb.alert.already_corrupted');
        } else {
            // Deduct one orb
            $Character->Data['inventory_json']['special_resources']['corruption_orbs'] =
                max(0, (int)($Character->Data['inventory_json']['special_resources']['corruption_orbs']) - 1);

            $orb_result  = $result;
            $orb_gear_id = $gear_id;

            $alert_success = ($result === 'bonus')
                ? t('corruption.orb.alert.bonus')
                : t('corruption.orb.alert.penalty');
        }
    }
}

// Load user's gear for display
$Gear_List = new Gear();
$user_gear  = $Gear_List->GetAllItemsByOwner((int)$_SESSION['auth_user_id']);

// Filter to items that are NOT already corrupted and owned by this seasonal character
$corruptible_gear = array_filter($user_gear, fn($item) => !isset($item['corruption_modifier']));

$corruption_orbs = (int)($Character->Data['inventory_json']['special_resources']['corruption_orbs'] ?? 0);
