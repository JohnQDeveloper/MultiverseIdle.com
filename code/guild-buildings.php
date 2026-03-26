<?php

declare(strict_types=1);

$Guild = new Guild();
$Guild->setSeasonId(isset($_SESSION['active_season_id']) ? (int)$_SESSION['active_season_id'] : null);
$current_user_id = (int)$_SESSION['auth_user_id'];

$user_guild_id = $Guild->GetUserGuildId($current_user_id);
$user_role     = $Guild->GetUserRole($current_user_id);

$valid_buildings = ['farm', 'iron_mine', 'gem_mine', 'market', 'gym', 'tavern'];

// Upgrade a building
if (isset($_POST['upgrade_building'])) {
    $building = trim($_POST['building'] ?? '');

    if (!in_array($building, $valid_buildings, true)) {
        $alert_danger = t('guild_buildings.alert.invalid');
    } elseif (!in_array($user_role, ['guild_master', 'officer'], true)) {
        $alert_danger = t('guild_buildings.alert.no_perm');
    } elseif ($user_guild_id === null) {
        $alert_danger = t('guild_buildings.alert.not_in_guild');
    } else {
        // Resource is determined server-side from Redis — not from user input
        $resource = getOrAssignBuildingResource($user_guild_id, $building);
        if ($Guild->UpgradeBuilding($building, $resource, $current_user_id)) {
            // Clear so the next upgrade draws a fresh random resource
            $redis->del("guild_building_resource:{$user_guild_id}:{$building}");
            $alert_success = t('guild_buildings.alert.upgraded', ['building' => t('guild_buildings.' . $building)]);
        } else {
            $alert_danger = t('guild_buildings.alert.fail', ['resource' => t('res.' . strtolower($resource))]);
        }
    }
}

// Load data for display
$guild_data       = [];
$guild_bank       = ['gold' => 0, 'iron' => 0, 'herbs' => 0, 'gems' => 0];
$building_levels  = ['farm' => 0, 'iron_mine' => 0, 'gem_mine' => 0, 'market' => 0, 'gym' => 0, 'tavern' => 0];
$member_count     = 0;

$building_resources = [];

if ($user_guild_id !== null) {
    $Guild->LoadGuildById($user_guild_id);
    $guild_data      = $Guild->Data;
    $guild_bank      = $Guild->GetBankBalances($user_guild_id);
    $building_levels = $Guild->GetBuildingLevels($user_guild_id);
    $member_count    = $Guild->GetMemberCount($user_guild_id);

    foreach ($valid_buildings as $b) {
        $building_resources[$b] = getOrAssignBuildingResource($user_guild_id, $b);
    }
}

require_once('../pages/guild-buildings.php');
