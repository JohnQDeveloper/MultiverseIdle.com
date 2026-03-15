<?php

declare(strict_types=1);

require_once('../config.php');

header('Content-Type: application/json');

// Must be authenticated (logged-in or guest can read)
$isLoggedIn = isset($_SESSION['auth_logged_in']) && $_SESSION['auth_logged_in'] == 1
           && isset($_SESSION['auth_user_id']) && $_SESSION['auth_user_id'] > 0;
$isGuest    = isset($_SESSION['guest_mode']) && $_SESSION['guest_mode'] === true;

if (!$isLoggedIn && !$isGuest) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId   = $isLoggedIn ? (int)$_SESSION['auth_user_id'] : 0;
$channel  = trim($_GET['channel'] ?? 'global');
$sinceId  = max(0, (int)($_GET['since_id'] ?? 0));
$limit    = min(100, max(1, (int)($_GET['limit'] ?? 50)));

$Chat = new Chat();

if (!$Chat->isValidChannel($channel)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid channel']);
    exit;
}

// Guild channel requires membership
if (str_starts_with($channel, 'guild:') && ($userId <= 0 || !$Chat->validateGuildAccess($channel, $userId))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Not a guild member']);
    exit;
}

// DM channel requires the requesting user to be a participant
if ($Chat->isDMChannel($channel)) {
    if ($userId <= 0 || !$Chat->validateDMAccess($channel, $userId)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Not a participant in this DM']);
        exit;
    }
}

$messages = $Chat->getMessages($channel, $sinceId, $limit);
$lastId   = 0;
if (!empty($messages)) {
    $lastId = (int)end($messages)['id'];
}

$muteInfo = null;
if ($userId > 0 && $Chat->isMuted($userId)) {
    $info     = $Chat->getMuteInfo($userId);
    $muteInfo = [
        'until'  => (int)($info['until'] ?? -1),
        'reason' => $info['reason'] ?? '',
    ];
}

echo json_encode([
    'success'  => true,
    'messages' => $messages,
    'last_id'  => $lastId,
    'is_mod'   => $userId > 0 && $Chat->isModerator($userId),
    'muted'    => $muteInfo,
]);
