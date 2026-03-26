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

if (str_starts_with($message, '/')) {
    $parts = preg_split('/\s+/', $message);
    $command = strtolower((string)($parts[0] ?? ''));
    $targetUsername = trim((string)($parts[1] ?? ''));

    if (in_array($command, ['/ignore', '/unignore'], true)) {
        if ($targetUsername === '') {
            echo json_encode([
                'success' => false,
                'error' => "Usage: {$command} <username>",
            ]);
            exit;
        }

        $targetUser = $Chat->findUserByUsername($targetUsername);
        if ($targetUser === null) {
            echo json_encode(['success' => false, 'error' => 'User not found']);
            exit;
        }

        if ((int)$targetUser['id'] === $userId) {
            echo json_encode(['success' => false, 'error' => 'You cannot ignore yourself']);
            exit;
        }

        if ($command === '/ignore') {
            if ($Chat->isIgnoringUser($userId, (int)$targetUser['id'])) {
                echo json_encode([
                    'success' => true,
                    'notice' => "{$targetUser['username']} is already ignored.",
                ]);
                exit;
            }

            if (!$Chat->ignoreUser($userId, (int)$targetUser['id'])) {
                echo json_encode(['success' => false, 'error' => 'Could not ignore that user']);
                exit;
            }

            echo json_encode([
                'success' => true,
                'notice' => "You are now ignoring {$targetUser['username']}.",
            ]);
            exit;
        }

        if (!$Chat->isIgnoringUser($userId, (int)$targetUser['id'])) {
            echo json_encode([
                'success' => true,
                'notice' => "{$targetUser['username']} was not ignored.",
            ]);
            exit;
        }

        if (!$Chat->unignoreUser($userId, (int)$targetUser['id'])) {
            echo json_encode(['success' => false, 'error' => 'Could not unignore that user']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'notice' => "You are no longer ignoring {$targetUser['username']}.",
        ]);
        exit;
    }

    if ($command === '/ignored') {
        $ignoredUsers = $Chat->getIgnoredUsers($userId);
        if ($ignoredUsers === []) {
            echo json_encode([
                'success' => true,
                'notice' => 'You are not ignoring anyone.',
            ]);
            exit;
        }

        $names = array_map(
            static fn(array $ignoredUser): string => (string)$ignoredUser['username'],
            $ignoredUsers
        );

        echo json_encode([
            'success' => true,
            'notice' => 'Ignored users: ' . implode(', ', $names),
        ]);
        exit;
    }
}

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

    if ($Chat->isIgnoringUser($userId, $toUserId)) {
        echo json_encode(['success' => false, 'error' => 'Unignore this user before sending them a DM']);
        exit;
    }

    if ($Chat->isIgnoringUser($toUserId, $userId)) {
        echo json_encode(['success' => false, 'error' => 'That user is ignoring you']);
        exit;
    }

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
