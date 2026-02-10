<?php

declare(strict_types=1);

$Character = new Character();
$Character->LoadByUserId($_SESSION['auth_user_id']);

$Guild = new Guild();

// Create Guild
if (isset($_POST['create_guild'])) {
    $guild_name = trim($_POST['guild_name'] ?? '');
    $guild_description = trim($_POST['guild_description'] ?? '');

    if (strlen($guild_name) < 3 || strlen($guild_name) > 50) {
        $alert_danger = 'Guild name must be between 3 and 50 characters.';
    } elseif ($Guild->GetUserGuildId($_SESSION['auth_user_id']) !== null) {
        $alert_danger = 'You are already in a guild. Leave your current guild before creating a new one.';
    } elseif ($Guild->CreateGuild($guild_name, $guild_description, $_SESSION['auth_user_id'])) {
        $alert_success = 'Guild "' . htmlspecialchars($guild_name) . '" created successfully!';
    } else {
        $alert_danger = 'Failed to create guild. The name might already be taken.';
    }
}

// Send Invite
if (isset($_POST['send_invite'])) {
    $invitee_user_id = (int)($_POST['invitee_user_id'] ?? 0);

    if ($invitee_user_id <= 0) {
        $alert_danger = 'Invalid user selected.';
    } elseif (!$Guild->CanInviteMembers($_SESSION['auth_user_id'])) {
        $alert_danger = 'You do not have permission to invite members.';
    } elseif ($Guild->SendInvite($invitee_user_id, $_SESSION['auth_user_id'])) {
        $alert_success = 'Invite sent successfully!';
    } else {
        $alert_danger = 'Failed to send invite. User may already be in a guild or guild is full.';
    }
}

// Accept Invite
if (isset($_POST['accept_invite'])) {
    $invite_id = (int)($_POST['invite_id'] ?? 0);

    if ($invite_id <= 0) {
        $alert_danger = 'Invalid invite.';
    } elseif ($Guild->AcceptInvite($invite_id, $_SESSION['auth_user_id'])) {
        $alert_success = 'You have joined the guild!';
    } else {
        $alert_danger = 'Failed to accept invite. The guild may be full or the invite expired.';
    }
}

// Decline Invite
if (isset($_POST['decline_invite'])) {
    $invite_id = (int)($_POST['invite_id'] ?? 0);

    if ($invite_id <= 0) {
        $alert_danger = 'Invalid invite.';
    } elseif ($Guild->DeclineInvite($invite_id, $_SESSION['auth_user_id'])) {
        $alert_success = 'Invite declined.';
    } else {
        $alert_danger = 'Failed to decline invite.';
    }
}

// Kick Member
if (isset($_POST['kick_member'])) {
    $target_user_id = (int)($_POST['target_user_id'] ?? 0);

    if ($target_user_id <= 0) {
        $alert_danger = 'Invalid user.';
    } elseif (!$Guild->CanKickMembers($_SESSION['auth_user_id'])) {
        $alert_danger = 'You do not have permission to kick members.';
    } elseif ($Guild->KickMember($target_user_id, $_SESSION['auth_user_id'])) {
        $alert_success = 'Member kicked successfully.';
    } else {
        $alert_danger = 'Failed to kick member. You may not have permission.';
    }
}

// Leave Guild
if (isset($_POST['leave_guild'])) {
    if ($Guild->IsGuildMaster($_SESSION['auth_user_id'])) {
        $alert_danger = 'Guild masters cannot leave. Transfer leadership or disband the guild first.';
    } elseif ($Guild->LeaveGuild($_SESSION['auth_user_id'])) {
        $alert_success = 'You have left the guild.';
    } else {
        $alert_danger = 'Failed to leave guild.';
    }
}

// Transfer Guild Master
if (isset($_POST['transfer_master'])) {
    $new_master_user_id = (int)($_POST['new_master_user_id'] ?? 0);

    if ($new_master_user_id <= 0) {
        $alert_danger = 'Invalid user.';
    } elseif (!$Guild->IsGuildMaster($_SESSION['auth_user_id'])) {
        $alert_danger = 'Only the guild master can transfer leadership.';
    } elseif ($Guild->TransferGuildMaster($new_master_user_id, $_SESSION['auth_user_id'])) {
        $alert_success = 'Guild master role transferred successfully!';
    } else {
        $alert_danger = 'Failed to transfer guild master role.';
    }
}

// Promote to Officer
if (isset($_POST['promote_officer'])) {
    $target_user_id = (int)($_POST['target_user_id'] ?? 0);

    if ($target_user_id <= 0) {
        $alert_danger = 'Invalid user.';
    } elseif (!$Guild->IsGuildMaster($_SESSION['auth_user_id'])) {
        $alert_danger = 'Only the guild master can promote officers.';
    } elseif ($Guild->PromoteToOfficer($target_user_id, $_SESSION['auth_user_id'])) {
        $alert_success = 'Member promoted to officer!';
    } else {
        $alert_danger = 'Failed to promote member.';
    }
}

// Demote to Member
if (isset($_POST['demote_member'])) {
    $target_user_id = (int)($_POST['target_user_id'] ?? 0);

    if ($target_user_id <= 0) {
        $alert_danger = 'Invalid user.';
    } elseif (!$Guild->IsGuildMaster($_SESSION['auth_user_id'])) {
        $alert_danger = 'Only the guild master can demote officers.';
    } elseif ($Guild->DemoteToMember($target_user_id, $_SESSION['auth_user_id'])) {
        $alert_success = 'Officer demoted to member.';
    } else {
        $alert_danger = 'Failed to demote officer.';
    }
}

// Disband Guild
if (isset($_POST['disband_guild'])) {
    $confirm = trim($_POST['confirm_disband'] ?? '');

    if ($confirm !== 'DISBAND') {
        $alert_danger = 'You must type "DISBAND" to confirm guild deletion.';
    } elseif (!$Guild->IsGuildMaster($_SESSION['auth_user_id'])) {
        $alert_danger = 'Only the guild master can disband the guild.';
    } elseif ($Guild->DisbandGuild($_SESSION['auth_user_id'])) {
        $alert_success = 'Guild disbanded successfully.';
    } else {
        $alert_danger = 'Failed to disband guild.';
    }
}

// Load guild data for display
$user_guild_id = $Guild->GetUserGuildId($_SESSION['auth_user_id']);
$user_role = $Guild->GetUserRole($_SESSION['auth_user_id']);
$guild_members = [];
$guild_data = [];
$pending_invites = $Guild->GetUserInvites($_SESSION['auth_user_id']);

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
