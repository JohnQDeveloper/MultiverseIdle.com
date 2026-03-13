<?php

declare(strict_types=1);

/**
 * Get all active characters (interacted within last 72 hours).
 * Returns both id and user_id so crons can load each character by its primary key,
 * which correctly handles users with both a perpetual and a season character.
 *
 * @return array<int, array<string, mixed>>|false
 */
function ActiveUsers(): array|false
{
    global $DAL;

    // Must interact every 3 days to be marked as active
    return $DAL->r(
        "SELECT id, user_id FROM characters WHERE last_seen > DATE_SUB(NOW(), INTERVAL 72 HOUR) AND user_id IS NOT NULL"
    );
}
