<?php

declare(strict_types=1);

require_once('../config.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Only logged-in users may send messages (not guests)
$isLoggedIn = isset($_SESSION['auth_logged_in']) && $_SESSION['auth_logged_in'] == 1
           && isset($_SESSION['auth_user_id']) && $_SESSION['auth_user_id'] > 0;

if (!$isLoggedIn) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Must be logged in to chat']);
    exit;
}

// CSRF check
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf-token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$userId   = (int)$_SESSION['auth_user_id'];
$username = htmlspecialchars(trim($_SESSION['auth_username'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8');
$channel  = trim($_POST['channel'] ?? 'global');
$message  = trim($_POST['message'] ?? '');

if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
    exit;
}

$Chat = new Chat();

if (!$Chat->isValidChannel($channel)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid channel']);
    exit;
}

// DM channel: verify the sender is a participant, then send
if ($Chat->isDMChannel($channel)) {
    if (!$Chat->validateDMAccess($channel, $userId)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Not a participant in this DM']);
        exit;
    }

    if ($Chat->isMuted($userId)) {
        $info  = $Chat->getMuteInfo($userId);
        $until = (int)($info['until'] ?? -1);
        $label = $until === -1 ? 'permanently' : 'until ' . date('M j g:ia', $until);
        echo json_encode(['success' => false, 'error' => "You are muted {$label}."]);
        exit;
    }

    preg_match('/^dm:(\d+):(\d+)$/', $channel, $m);
    $toUserId = ((int)$m[1] === $userId) ? (int)$m[2] : (int)$m[1];

    $result = $Chat->sendDM($channel, $userId, $username, $toUserId, $message);

    if ($result === false) {
        echo json_encode(['success' => false, 'error' => 'Could not send DM. You may be rate-limited or the message is too long (max 500 chars).']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => $result]);
    exit;
}

// Guild channel: verify membership
if (str_starts_with($channel, 'guild:') && !$Chat->validateGuildAccess($channel, $userId)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Not a guild member']);
    exit;
}

if ($Chat->isMuted($userId)) {
    $info  = $Chat->getMuteInfo($userId);
    $until = (int)($info['until'] ?? -1);
    $label = $until === -1 ? 'permanently' : 'until ' . date('M j g:ia', $until);
    echo json_encode(['success' => false, 'error' => "You are muted {$label}."]);
    exit;
}

$result = $Chat->sendMessage($channel, $userId, $username, $message);

if ($result === false) {
    echo json_encode(['success' => false, 'error' => 'Could not send message. You may be rate-limited or the message is too long (max 500 chars).']);
    exit;
}

echo json_encode(['success' => true, 'message' => $result]);
