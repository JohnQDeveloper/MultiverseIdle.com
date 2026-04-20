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

/**
 * Whether Essence League systems should be available for the given character.
 *
 * @param array<string, mixed>|null $character_data
 */
function isEssenceLeagueAvailable(?array $character_data = null): bool
{
    if (!ESSENCE_LEAGUE_ENABLED) {
        return false;
    }

    if (ESSENCE_LEAGUE_PERPETUAL) {
        return true;
    }

    return ($character_data['season_id'] ?? null) !== null;
}

function isDelightAdmin(\Delight\Auth\Auth $auth): bool
{
    return $auth->hasAnyRole(
        \Delight\Auth\Role::ADMIN,
        \Delight\Auth\Role::SUPER_ADMIN
    );
}

function getAuthUserStatusById(int $userId): ?int
{
    global $DAL;

    if ($userId <= 0) {
        return null;
    }

    $rows = $DAL->r(
        'SELECT status FROM users WHERE id = :id LIMIT 1',
        [':id' => $userId]
    );

    if (empty($rows)) {
        return null;
    }

    return (int)$rows[0]['status'];
}

function isBlockedAuthStatus(int $status): bool
{
    return in_array(
        $status,
        [
            \Delight\Auth\Status::BANNED,
            \Delight\Auth\Status::SUSPENDED,
        ],
        true
    );
}

function getBlockedAuthStatusMessage(int $status): string
{
    return match ($status) {
        \Delight\Auth\Status::BANNED => t('auth.login.alert.banned'),
        \Delight\Auth\Status::SUSPENDED => t('auth.login.alert.suspended'),
        default => t('auth.login.alert.blocked'),
    };
}
