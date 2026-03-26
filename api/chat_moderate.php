<?php

declare(strict_types=1);

require_once('../config.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Must be logged in
$isLoggedIn = isset($_SESSION['auth_logged_in']) && $_SESSION['auth_logged_in'] == 1
           && isset($_SESSION['auth_user_id']) && $_SESSION['auth_user_id'] > 0;

if (!$isLoggedIn) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// CSRF check
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf-token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$actorId = (int)$_SESSION['auth_user_id'];
$Chat    = new Chat();
global $DAL;

// All moderation actions require moderator status
if (!$Chat->isModerator($actorId)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
    exit;
}

$action = trim($_POST['action'] ?? '');
$characterName = trim((string)($_POST['character_name'] ?? ''));

if ($characterName === '') {
    echo json_encode(['success' => false, 'error' => 'Invalid character name']);
    exit;
}

$rows = $DAL->r(
    'SELECT user_id
     FROM characters
     WHERE name = :character_name
       AND user_id IS NOT NULL
     GROUP BY user_id',
    [':character_name' => $characterName]
);

if (empty($rows)) {
    echo json_encode(['success' => false, 'error' => 'Character not found']);
    exit;
}

if (count($rows) > 1) {
    echo json_encode(['success' => false, 'error' => 'Character name is ambiguous']);
    exit;
}

$targetId = (int)$rows[0]['user_id'];

// Moderators cannot act on other moderators unless they are site admin
if ($Chat->isModerator($targetId)) {
    // Only site admins (roles_mask > 0) may act on other mods
    $rows = $DAL->r('SELECT roles_mask FROM users WHERE id = :id LIMIT 1', [':id' => $actorId]);
    $isSiteAdmin = !empty($rows) && (int)$rows[0]['roles_mask'] > 0;
    if (!$isSiteAdmin && $action !== 'promote') {
        echo json_encode(['success' => false, 'error' => 'Cannot moderate another moderator']);
        exit;
    }
}

switch ($action) {
    case 'promote':
        if (!$Chat->promoteModerator($targetId, $actorId)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to store moderator promotion']);
            exit;
        }
        echo json_encode(['success' => true, 'message' => 'Character promoted to moderator']);
        break;

    case 'demote':
        if (!$Chat->demoteModerator($targetId)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to store moderator demotion']);
            exit;
        }
        echo json_encode(['success' => true, 'message' => 'Character demoted from moderator']);
        break;

    case 'mute':
        // Duration in seconds; special value -1 = permanent
        $duration = (int)($_POST['duration'] ?? 3600);
        $reason   = trim(substr($_POST['reason'] ?? 'No reason given', 0, 200));

        // Allowed durations: 1h, 24h, 7d, permanent
        $allowed = [3600, 86400, 604800, -1];
        if (!in_array($duration, $allowed, true)) {
            echo json_encode(['success' => false, 'error' => 'Invalid mute duration']);
            exit;
        }

        if (!$Chat->muteUser($targetId, $duration, $reason, $actorId)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to store mute']);
            exit;
        }
        $label = match ($duration) {
            3600   => '1 hour',
            86400  => '24 hours',
            604800 => '7 days',
            -1     => 'permanently',
            default => "{$duration}s",
        };
        echo json_encode(['success' => true, 'message' => "Character muted for {$label}"]);
        break;

    case 'unmute':
        if (!$Chat->unmuteUser($targetId)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to remove mute']);
            exit;
        }
        echo json_encode(['success' => true, 'message' => 'Character unmuted']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
}
