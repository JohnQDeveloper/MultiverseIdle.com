<?php

declare(strict_types=1);

$userId   = (int)($_SESSION['auth_user_id'] ?? 0);
$seasonId = isset($_SESSION['active_season_id']) ? (int)$_SESSION['active_season_id'] : null;

$wireLog = [];

if ($userId > 0) {
    $rows = $DAL->r(
        "SELECT wl.created_at, wl.amount, wl.commodity,
                wl.sender_user_id, wl.recipient_user_id,
                su.username AS sender_username,
                ru.username AS recipient_username
         FROM `wire_log` wl
         JOIN `users` su ON su.id = wl.sender_user_id
         JOIN `users` ru ON ru.id = wl.recipient_user_id
         WHERE (wl.sender_user_id = :uid OR wl.recipient_user_id = :uid2)
           AND wl.season_id <=> :sid
         ORDER BY wl.created_at DESC
         LIMIT 100",
        [':uid' => $userId, ':uid2' => $userId, ':sid' => $seasonId]
    );

    $wireLog = $rows ?: [];
}
