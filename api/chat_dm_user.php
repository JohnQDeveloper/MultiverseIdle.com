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

$username = trim($_GET['username'] ?? '');

if ($username === '' || mb_strlen($username) > 50) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid username']);
    exit;
}

$Chat = new Chat();
$user = $Chat->findUserByUsername($username);

if ($user === null) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

echo json_encode(['success' => true, 'user' => $user]);
