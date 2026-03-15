<?php

declare(strict_types=1);

require_once('../config.php');

header('Content-Type: application/json');

$isLoggedIn = isset($_SESSION['auth_logged_in']) && $_SESSION['auth_logged_in'] == 1
           && isset($_SESSION['auth_user_id']) && $_SESSION['auth_user_id'] > 0;

if (!$isLoggedIn) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Must be logged in']);
    exit;
}

$userId = (int)$_SESSION['auth_user_id'];
$Chat   = new Chat();

echo json_encode([
    'success'       => true,
    'conversations' => $Chat->getDMConversations($userId),
]);
