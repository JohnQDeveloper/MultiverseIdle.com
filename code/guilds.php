<?php

declare(strict_types=1);

$Guild = new Guild();
$Guild->setSeasonId(isset($_SESSION['active_season_id']) ? (int)$_SESSION['active_season_id'] : null);
$current_user_id = (int)$_SESSION['auth_user_id'];

// Create Guild
if (isset($_POST['create_guild'])) {
    $guild_name = trim($_POST['guild_name'] ?? '');
    $guild_description = trim($_POST['guild_description'] ?? '');

    if (strlen($guild_name) < 3 || strlen($guild_name) > 50) {
        $alert_danger = t('guilds.alert.name_length');
    } elseif ($Guild->GetUserGuildId($current_user_id) !== null) {
        $alert_danger = t('guilds.alert.already_in_guild');
    } elseif ($Guild->CreateGuild($guild_name, $guild_description, $current_user_id)) {
        $alert_success = t('guilds.alert.created', ['name' => htmlspecialchars($guild_name)]);
    } else {
        $alert_danger = t('guilds.alert.create_fail');
    }
}

// Send Invite
if (isset($_POST['send_invite'])) {
    $invitee_user_id = (int)($_POST['invitee_user_id'] ?? 0);

    if ($invitee_user_id <= 0) {
        $alert_danger = t('guilds.alert.invalid_user');
    } elseif (!$Guild->CanInviteMembers($current_user_id)) {
        $alert_danger = t('guilds.alert.no_invite_perm');
    } elseif ($Guild->SendInvite($invitee_user_id, $current_user_id)) {
        $alert_success = t('guilds.alert.invite_sent');
    } else {
        $alert_danger = t('guilds.alert.invite_fail');
    }
}

// Accept Invite
if (isset($_POST['accept_invite'])) {
    $invite_id = (int)($_POST['invite_id'] ?? 0);

    if ($invite_id <= 0) {
        $alert_danger = t('guilds.alert.invalid_invite');
    } elseif ($Guild->AcceptInvite($invite_id, $current_user_id)) {
        $alert_success = t('guilds.alert.joined');
    } else {
        $alert_danger = t('guilds.alert.join_fail');
    }
}

// Decline Invite
if (isset($_POST['decline_invite'])) {
    $invite_id = (int)($_POST['invite_id'] ?? 0);

    if ($invite_id <= 0) {
        $alert_danger = t('guilds.alert.invalid_invite');
    } elseif ($Guild->DeclineInvite($invite_id, $current_user_id)) {
        $alert_success = t('guilds.alert.declined');
    } else {
        $alert_danger = t('guilds.alert.decline_fail');
    }
}

// Kick Member
if (isset($_POST['kick_member'])) {
    $target_user_id = (int)($_POST['target_user_id'] ?? 0);

    if ($target_user_id <= 0) {
        $alert_danger = t('guilds.alert.invalid_user2');
    } elseif (!$Guild->CanKickMembers($current_user_id)) {
        $alert_danger = t('guilds.alert.no_kick_perm');
    } elseif ($Guild->KickMember($target_user_id, $current_user_id)) {
        $alert_success = t('guilds.alert.kicked');
    } else {
        $alert_danger = t('guilds.alert.kick_fail');
    }
}

// Leave Guild
if (isset($_POST['leave_guild'])) {
    if ($Guild->IsGuildMaster($current_user_id)) {
        $alert_danger = t('guilds.alert.master_cant_leave');
    } elseif ($Guild->LeaveGuild($current_user_id)) {
        $alert_success = t('guilds.alert.left');
    } else {
        $alert_danger = t('guilds.alert.leave_fail');
    }
}

// Transfer Guild Master
if (isset($_POST['transfer_master'])) {
    $new_master_user_id = (int)($_POST['new_master_user_id'] ?? 0);

    if ($new_master_user_id <= 0) {
        $alert_danger = t('guilds.alert.invalid_user2');
    } elseif (!$Guild->IsGuildMaster($current_user_id)) {
        $alert_danger = t('guilds.alert.no_transfer_perm');
    } elseif ($Guild->TransferGuildMaster($new_master_user_id, $current_user_id)) {
        $alert_success = t('guilds.alert.transfer_done');
    } else {
        $alert_danger = t('guilds.alert.transfer_fail');
    }
}

// Promote to Officer
if (isset($_POST['promote_officer'])) {
    $target_user_id = (int)($_POST['target_user_id'] ?? 0);

    if ($target_user_id <= 0) {
        $alert_danger = t('guilds.alert.invalid_user2');
    } elseif (!$Guild->IsGuildMaster($current_user_id)) {
        $alert_danger = t('guilds.alert.no_promote_perm');
    } elseif ($Guild->PromoteToOfficer($target_user_id, $current_user_id)) {
        $alert_success = t('guilds.alert.promoted');
    } else {
        $alert_danger = t('guilds.alert.promote_fail');
    }
}

// Demote to Member
if (isset($_POST['demote_member'])) {
    $target_user_id = (int)($_POST['target_user_id'] ?? 0);

    if ($target_user_id <= 0) {
        $alert_danger = t('guilds.alert.invalid_user2');
    } elseif (!$Guild->IsGuildMaster($current_user_id)) {
        $alert_danger = t('guilds.alert.no_demote_perm');
    } elseif ($Guild->DemoteToMember($target_user_id, $current_user_id)) {
        $alert_success = t('guilds.alert.demoted');
    } else {
        $alert_danger = t('guilds.alert.demote_fail');
    }
}

// Disband Guild
if (isset($_POST['disband_guild'])) {
    $confirm = trim($_POST['confirm_disband'] ?? '');

    if ($confirm !== 'DISBAND') {
        $alert_danger = t('guilds.alert.disband_confirm');
    } elseif (!$Guild->IsGuildMaster($current_user_id)) {
        $alert_danger = t('guilds.alert.no_disband_perm');
    } elseif ($Guild->DisbandGuild($current_user_id)) {
        $alert_success = t('guilds.alert.disbanded');
    } else {
        $alert_danger = t('guilds.alert.disband_fail');
    }
}

// Load guild data for display
$user_guild_id = $Guild->GetUserGuildId($current_user_id);
$user_role = $Guild->GetUserRole($current_user_id);
$guild_members = [];
$guild_data = [];
$pending_invites = $Guild->GetUserInvites($current_user_id);

if ($user_guild_id !== null) {
    $Guild->LoadGuildById($user_guild_id);
    $guild_data = $Guild->Data;
    $guild_members = $Guild->GetGuildMembers($user_guild_id);
}

// Search for users to invite
$search_results = [];
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = trim($_GET['search']);
    $search_results = $Guild->SearchUsers($search_term);
}

require_once('../pages/guilds.php');
