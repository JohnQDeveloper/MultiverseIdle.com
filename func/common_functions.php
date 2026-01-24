<?php

declare(strict_types=1);

/**
 * Get all active users (interacted within last 72 hours)
 *
 * @return array<int, array<string, mixed>>|false
 */
function ActiveUsers(): array|false
{
    global $DAL;

    // Must interact every 3 days to be marked as active
    return $DAL->r("SELECT user_id FROM characters WHERE last_seen > DATE_SUB(NOW(), INTERVAL 72 HOUR)");
}
