<?php

declare(strict_types=1);

$Guild = new Guild();
$Guild->setSeasonId(isset($_SESSION['active_season_id']) ? (int)$_SESSION['active_season_id'] : null);
$current_user_id = (int)$_SESSION['auth_user_id'];

$user_guild_id = $Guild->GetUserGuildId($current_user_id);
$user_role     = $Guild->GetUserRole($current_user_id);

// Donate to Guild Bank
if (isset($_POST['donate_to_bank'])) {
    $commodity = strtolower(trim($_POST['commodity'] ?? ''));
    $amount    = (int)($_POST['amount'] ?? 0);

    $validCommodities = ['gold', 'iron', 'herbs', 'gems'];
    if (!in_array($commodity, $validCommodities, true)) {
        $alert_danger = 'Invalid resource.';
    } elseif ($amount <= 0) {
        $alert_danger = 'Amount must be greater than 0.';
    } elseif ((int)($Character->Data[$commodity] ?? 0) < $amount) {
        $alert_danger = 'You don\'t have enough ' . ucfirst($commodity) . '.';
    } elseif ($user_guild_id === null) {
        $alert_danger = 'You are not in a guild.';
    } else {
        // Deduct from in-memory character data; index.php dirty-check saves it at end of request
        $Character->Data[$commodity] -= $amount;

        $DAL->w(
            "INSERT INTO guild_bank (guild_id, `{$commodity}`)
             VALUES (:guild_id, :amount)
             ON DUPLICATE KEY UPDATE `{$commodity}` = `{$commodity}` + :amount2",
            [':guild_id' => $user_guild_id, ':amount' => $amount, ':amount2' => $amount]
        );
        $alert_success = 'Donated ' . number_format($amount) . ' ' . $commodity . ' to the guild bank.';
    }
}

// Set Tax Rate
if (isset($_POST['set_tax_rate'])) {
    $tax_rate = (int)($_POST['tax_rate'] ?? 0);

    if (!in_array($user_role, ['guild_master', 'officer'], true)) {
        $alert_danger = 'Only guild masters and officers can set the tax rate.';
    } elseif ($Guild->SetTaxRate($tax_rate, $current_user_id)) {
        $alert_success = 'Tax rate updated to ' . max(0, min(20, $tax_rate)) . '%.';
    } else {
        $alert_danger = 'Failed to update tax rate.';
    }
}

// Load bank data for display
$guild_data     = [];
$guild_bank     = ['gold' => 0, 'iron' => 0, 'herbs' => 0, 'gems' => 0];
$guild_tax_rate = 0;

if ($user_guild_id !== null) {
    $Guild->LoadGuildById($user_guild_id);
    $guild_data     = $Guild->Data;
    $guild_bank     = $Guild->GetBankBalances($user_guild_id);
    $guild_tax_rate = $Guild->GetTaxRate($user_guild_id);
}

require_once('../pages/guild-bank.php');
