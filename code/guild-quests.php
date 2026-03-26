<?php

declare(strict_types=1);

$Guild       = new Guild();
$GuildQuests = new GuildQuests();

$Guild->setSeasonId(isset($_SESSION['active_season_id']) ? (int)$_SESSION['active_season_id'] : null);
$GuildQuests->setSeasonId(isset($_SESSION['active_season_id']) ? (int)$_SESSION['active_season_id'] : null);

$current_user_id = (int)$_SESSION['auth_user_id'];
$user_guild_id   = $Guild->GetUserGuildId($current_user_id);
$user_role       = $Guild->GetUserRole($current_user_id);

// Add a new quest (officers/masters only)
if (isset($_POST['add_quest']) && $user_guild_id !== null) {
    $grade = strtolower(trim($_POST['grade'] ?? ''));

    $valid_grades = ['easy', 'normal', 'hard', 'legendary'];
    if (!in_array($grade, $valid_grades, true)) {
        $alert_danger = t('guild_quests.alert.invalid_grade');
    } elseif (!in_array($user_role, ['guild_master', 'officer'], true)) {
        $alert_danger = t('guild_quests.alert.no_permission');
    } else {
        $member_count = $Guild->GetMemberCount($user_guild_id);
        if ($GuildQuests->AddQuest($user_guild_id, $grade, $member_count)) {
            $alert_success = t('guild_quests.alert.added');
        } else {
            $alert_danger = t('guild_quests.alert.add_fail');
        }
    }
}

// Load data for display
$guild_data      = [];
$active_quests   = [];
$recent_quests   = [];
$active_count    = 0;

if ($user_guild_id !== null) {
    $Guild->LoadGuildById($user_guild_id);
    $guild_data    = $Guild->Data;
    $active_quests = $GuildQuests->GetActiveQuests($user_guild_id);
    $recent_quests = $GuildQuests->GetRecentCompletedQuests($user_guild_id);
    $active_count  = count($active_quests);
}

require_once('../pages/guild-quests.php');
