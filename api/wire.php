<?php

declare(strict_types=1);

require_once('../config.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$isLoggedIn = isset($_SESSION['auth_logged_in']) && $_SESSION['auth_logged_in'] == 1
           && isset($_SESSION['auth_user_id']) && $_SESSION['auth_user_id'] > 0;

if (!$isLoggedIn) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Must be logged in to wire resources']);
    exit;
}

if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf-token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$senderUserId   = (int)$_SESSION['auth_user_id'];
$senderUsername = htmlspecialchars(trim($_SESSION['auth_username'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8');
$seasonId       = isset($_SESSION['active_season_id']) ? (int)$_SESSION['active_season_id'] : null;

$recipientUsername = trim($_POST['recipient'] ?? '');
$amount            = (int)($_POST['amount'] ?? 0);
$commodity         = strtolower(trim($_POST['commodity'] ?? ''));

$validCommodities = ['gold', 'iron', 'herbs', 'gems'];
if (!in_array($commodity, $validCommodities, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid commodity. Choose: gold, iron, herbs, or gems']);
    exit;
}

if ($amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Amount must be greater than 0']);
    exit;
}

if ($recipientUsername === '' || mb_strlen($recipientUsername) > 50) {
    echo json_encode(['success' => false, 'error' => 'Invalid recipient username']);
    exit;
}

$Chat = new Chat();

$recipientUser = $Chat->findUserByUsername($recipientUsername);
if ($recipientUser === null) {
    echo json_encode(['success' => false, 'error' => "Player '{$recipientUsername}' not found"]);
    exit;
}

$recipientUserId = $recipientUser['id'];

if ($recipientUserId === $senderUserId) {
    echo json_encode(['success' => false, 'error' => 'You cannot wire resources to yourself']);
    exit;
}

// Verify recipient has a character in this season
$recipientCheck = $DAL->r(
    'SELECT `id` FROM `characters` WHERE `user_id` = :uid AND `season_id` <=> :sid LIMIT 1',
    [':uid' => $recipientUserId, ':sid' => $seasonId]
);

if (empty($recipientCheck)) {
    echo json_encode(['success' => false, 'error' => "Player '{$recipientUsername}' has no character in this season"]);
    exit;
}

// Atomically deduct from sender — only succeeds if they have enough
$DAL->w(
    "UPDATE `characters`
     SET `{$commodity}` = `{$commodity}` - :amount
     WHERE `user_id` = :uid AND `season_id` <=> :sid AND `{$commodity}` >= :amount2",
    [':amount' => $amount, ':uid' => $senderUserId, ':sid' => $seasonId, ':amount2' => $amount]
);

if ($DAL->rows_affected() === 0) {
    echo json_encode(['success' => false, 'error' => "You don't have enough " . ucfirst($commodity) . ' to wire']);
    exit;
}

// Add to recipient
$DAL->w(
    "UPDATE `characters` SET `{$commodity}` = `{$commodity}` + :amount
     WHERE `user_id` = :uid AND `season_id` <=> :sid",
    [':amount' => $amount, ':uid' => $recipientUserId, ':sid' => $seasonId]
);

// Log the transaction
$DAL->w(
    'INSERT INTO `wire_log` (`sender_user_id`, `recipient_user_id`, `amount`, `commodity`, `season_id`)
     VALUES (:sender, :recipient, :amount, :commodity, :season)',
    [
        ':sender'    => $senderUserId,
        ':recipient' => $recipientUserId,
        ':amount'    => $amount,
        ':commodity' => $commodity,
        ':season'    => $seasonId,
    ]
);

// Send a DM to the recipient notifying them of the wire
$lo      = min($senderUserId, $recipientUserId);
$hi      = max($senderUserId, $recipientUserId);
$dmChannel = "dm:{$lo}:{$hi}";
$displayCommodity = ucfirst($commodity);
$dmMsg   = "{$senderUsername} wired you " . number_format($amount) . " {$displayCommodity}. Check your log for details.";
$Chat->sendDM($dmChannel, $senderUserId, $senderUsername, $recipientUserId, $dmMsg);

echo json_encode(['success' => true, 'message' => null]);
