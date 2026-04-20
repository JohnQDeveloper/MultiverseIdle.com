<?php

declare(strict_types=1);

/**
 * @param array<int, int> $counts
 */
function buildAdminChartGridLines(array $counts, float $plotTop, float $plotHeight, int $steps): array
{
    $maxCount = max(1, max($counts));
    $gridLines = [];

    for ($step = 0; $step <= $steps; $step++) {
        $ratio = $steps > 0 ? $step / $steps : 0.0;
        $value = (int)round($maxCount * (1 - $ratio));
        $y = $plotTop + ($plotHeight * $ratio);

        $gridLines[] = [
            'value' => $value,
            'y' => round($y, 2),
        ];
    }

    return $gridLines;
}

function getAdminUserStatusLabel(int $status): string
{
    return match ($status) {
        \Delight\Auth\Status::BANNED => t('admin.ban.status.banned'),
        \Delight\Auth\Status::SUSPENDED => t('admin.ban.status.suspended'),
        \Delight\Auth\Status::NORMAL => t('admin.ban.status.active'),
        default => t('admin.ban.status.other'),
    };
}

$adminPageAllowed = isDelightAdmin($auth);
$alert_success = '';
$alert_danger = '';

$adminSession = [
    'user_id' => (int)($_SESSION['auth_user_id'] ?? 0),
    'username' => (string)($_SESSION['auth_username'] ?? ''),
    'email' => (string)($_SESSION['auth_email'] ?? ''),
    'roles_mask' => (int)($_SESSION['auth_roles'] ?? 0),
];

$adminRoleNames = [];
$adminSummary = [
    'total_users' => 0,
    'verified_users' => 0,
    'active_characters' => 0,
    'admin_accounts' => 0,
];
$adminDailyUsersAvailable = true;
$adminDailyUsersSeries = [];
$adminDailyUsersStats = [
    'last_complete_day' => 0,
    'previous_day' => 0,
    'seven_day_average' => 0.0,
    'peak' => 0,
    'peak_date' => '',
    'range_start' => '',
    'range_end' => '',
    'timezone' => 'UTC',
];
$adminDailyUsersChart = [
    'width' => 760,
    'height' => 280,
    'grid_lines' => [],
    'bars' => [],
    'x_labels' => [],
];
$adminDailyUsersWindowDays = 90;
$adminBanSearchTerm = '';
$adminBanMatches = [];

if (!$adminPageAllowed) {
    http_response_code(403);
    return;
}

$adminRoleNames = array_values($auth->getRoles());

$adminBanSearchTerm = trim((string)($_POST['admin_ban_search_term'] ?? ''));

if (isset($_POST['ban_admin_user'])) {
    $targetUserId = (int)($_POST['target_user_id'] ?? 0);
    $targetCharacterName = trim((string)($_POST['target_character_name'] ?? ''));

    if ($targetUserId <= 0 || $targetCharacterName === '') {
        $alert_danger = t('admin.ban.alert.invalid_target');
    } elseif ($targetUserId === $adminSession['user_id']) {
        $alert_danger = t('admin.ban.alert.self');
    } else {
        $targetRows = $DAL->r(
            'SELECT id, status
             FROM users
             WHERE id = :id
             LIMIT 1',
            [':id' => $targetUserId]
        );

        if (empty($targetRows)) {
            $alert_danger = t('admin.ban.alert.not_found');
        } elseif ((int)$targetRows[0]['status'] === \Delight\Auth\Status::BANNED) {
            $alert_danger = t('admin.ban.alert.already_banned', ['name' => $targetCharacterName]);
        } else {
            $statusUpdated = $DAL->w(
                'UPDATE users
                 SET status = :status,
                     force_logout = force_logout + 1
                 WHERE id = :id',
                [
                    ':status' => \Delight\Auth\Status::BANNED,
                    ':id' => $targetUserId,
                ]
            );

            $rememberTokensCleared = $DAL->w(
                'DELETE FROM users_remembered WHERE user = :user_id',
                [':user_id' => $targetUserId]
            );

            if ($statusUpdated && $rememberTokensCleared) {
                $DAL->w(
                    'INSERT INTO users_audit_log (
                        user_id,
                        event_at,
                        event_type,
                        admin_id,
                        ip_address,
                        user_agent,
                        details_json
                    ) VALUES (
                        :user_id,
                        :event_at,
                        :event_type,
                        :admin_id,
                        NULL,
                        NULL,
                        :details_json
                    )',
                    [
                        ':user_id' => $targetUserId,
                        ':event_at' => time(),
                        ':event_type' => 'admin.ban.character',
                        ':admin_id' => $adminSession['user_id'],
                        ':details_json' => json_encode(
                            ['character_name' => $targetCharacterName],
                            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                        ),
                    ]
                );

                $alert_success = t('admin.ban.alert.banned', ['name' => $targetCharacterName]);
            } else {
                $alert_danger = t('admin.ban.alert.fail');
            }
        }
    }
}

if (isset($_POST['search_admin_ban_user']) && mb_strlen($adminBanSearchTerm) < 2) {
    $alert_danger = t('admin.ban.alert.search_short');
}

if ($adminBanSearchTerm !== '' && mb_strlen($adminBanSearchTerm) >= 2) {
    $banSearchRows = $DAL->r(
        'SELECT
            c.user_id,
            c.name AS character_name,
            c.season_id,
            c.last_seen,
            u.username,
            u.status
         FROM characters c
         INNER JOIN users u ON u.id = c.user_id
         WHERE c.user_id IS NOT NULL
           AND c.user_id > 0
           AND c.name LIKE :search_term
         ORDER BY c.last_seen DESC, c.id DESC
         LIMIT 50',
        [':search_term' => '%' . $adminBanSearchTerm . '%']
    );

    if (!empty($banSearchRows)) {
        $matchesByUserId = [];

        foreach ($banSearchRows as $row) {
            $userId = (int)($row['user_id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }

            $seasonId = $row['season_id'] !== null ? (int)$row['season_id'] : null;
            $modeLabel = $seasonId === null
                ? t('admin.ban.mode.perpetual')
                : t('admin.ban.mode.season', ['id' => (string)$seasonId]);
            $matchedCharacter = trim((string)$row['character_name']) . ' (' . $modeLabel . ')';

            if (!isset($matchesByUserId[$userId])) {
                $matchesByUserId[$userId] = [
                    'user_id' => $userId,
                    'username' => (string)($row['username'] ?? ''),
                    'status' => (int)($row['status'] ?? \Delight\Auth\Status::NORMAL),
                    'status_label' => getAdminUserStatusLabel((int)($row['status'] ?? \Delight\Auth\Status::NORMAL)),
                    'display_name' => (string)($row['character_name'] ?? ''),
                    'target_character_name' => (string)($row['character_name'] ?? ''),
                    'matched_characters' => [],
                    'last_seen' => $row['last_seen'] ?? null,
                ];
            }

            if (!in_array($matchedCharacter, $matchesByUserId[$userId]['matched_characters'], true)) {
                $matchesByUserId[$userId]['matched_characters'][] = $matchedCharacter;
            }
        }

        $adminBanMatches = array_values($matchesByUserId);
    }
}

$summaryRows = $DAL->r(
    'SELECT
        (SELECT COUNT(*) FROM users) AS total_users,
        (SELECT COUNT(*) FROM users WHERE verified = 1) AS verified_users,
        (SELECT COUNT(*) FROM characters WHERE user_id IS NOT NULL AND last_seen > DATE_SUB(NOW(), INTERVAL 72 HOUR)) AS active_characters,
        (
            SELECT COUNT(*)
            FROM users
            WHERE (roles_mask & :admin_role) = :admin_role
               OR (roles_mask & :super_admin_role) = :super_admin_role
        ) AS admin_accounts',
    [
        ':admin_role' => \Delight\Auth\Role::ADMIN,
        ':super_admin_role' => \Delight\Auth\Role::SUPER_ADMIN,
    ]
);

if (!empty($summaryRows[0])) {
    $adminSummary = [
        'total_users' => (int)($summaryRows[0]['total_users'] ?? 0),
        'verified_users' => (int)($summaryRows[0]['verified_users'] ?? 0),
        'active_characters' => (int)($summaryRows[0]['active_characters'] ?? 0),
        'admin_accounts' => (int)($summaryRows[0]['admin_accounts'] ?? 0),
    ];
}

$utcTimezone = new DateTimeZone('UTC');
$completeDayEnd = new DateTimeImmutable('today', $utcTimezone);
$completeDayStart = $completeDayEnd->sub(new DateInterval('P' . $adminDailyUsersWindowDays . 'D'));

/** @var array<string, array<int, true>> $dailyUsersByDate */
$dailyUsersByDate = [];
$cursor = $completeDayStart;
while ($cursor < $completeDayEnd) {
    $dateKey = $cursor->format('Y-m-d');
    $dailyUsersByDate[$dateKey] = [];
    $cursor = $cursor->add(new DateInterval('P1D'));
}

$loginRows = $DAL->r(
    'SELECT user_id, event_at
     FROM users_audit_log
     WHERE user_id IS NOT NULL
       AND event_at >= :start_ts
       AND event_at < :end_ts
       AND (event_type = :login_event OR event_type LIKE :login_prefix)',
    [
        ':start_ts' => $completeDayStart->getTimestamp(),
        ':end_ts' => $completeDayEnd->getTimestamp(),
        ':login_event' => 'login',
        ':login_prefix' => 'login.%',
    ]
);

if ($loginRows === false) {
    $adminDailyUsersAvailable = false;
} else {
    foreach ($loginRows as $row) {
        $userId = (int)($row['user_id'] ?? 0);
        $eventAt = (int)($row['event_at'] ?? 0);

        if ($userId <= 0 || $eventAt <= 0) {
            continue;
        }

        $dateKey = gmdate('Y-m-d', $eventAt);
        if (!array_key_exists($dateKey, $dailyUsersByDate)) {
            continue;
        }

        $dailyUsersByDate[$dateKey][$userId] = true;
    }
}

foreach ($dailyUsersByDate as $dateKey => $dailyUsers) {
    $pointDate = new DateTimeImmutable($dateKey, $utcTimezone);

    $adminDailyUsersSeries[] = [
        'date' => $dateKey,
        'short_label' => $pointDate->format('m/d'),
        'count' => count($dailyUsers),
    ];
}

$adminDailyUsersStats['range_start'] = $completeDayStart->format('Y-m-d');
$adminDailyUsersStats['range_end'] = $completeDayEnd->sub(new DateInterval('P1D'))->format('Y-m-d');

if ($adminDailyUsersSeries !== []) {
    $counts = array_map(
        static fn(array $point): int => (int)$point['count'],
        $adminDailyUsersSeries
    );

    $lastIndex = count($counts) - 1;
    $lastCompleteDayCount = $counts[$lastIndex] ?? 0;
    $previousDayCount = $counts[$lastIndex - 1] ?? 0;
    $sevenDayWindow = array_slice($counts, -7);
    $peakCount = max($counts);
    $peakIndex = array_search($peakCount, $counts, true);
    $peakDate = ($peakCount > 0 && is_int($peakIndex))
        ? (string)$adminDailyUsersSeries[$peakIndex]['date']
        : '';

    $adminDailyUsersStats['last_complete_day'] = $lastCompleteDayCount;
    $adminDailyUsersStats['previous_day'] = $previousDayCount;
    $adminDailyUsersStats['seven_day_average'] = count($sevenDayWindow) > 0
        ? round(array_sum($sevenDayWindow) / count($sevenDayWindow), 1)
        : 0.0;
    $adminDailyUsersStats['peak'] = $peakCount;
    $adminDailyUsersStats['peak_date'] = $peakDate;
}

if ($adminDailyUsersAvailable && $adminDailyUsersSeries !== []) {
    $chartWidth = 760;
    $chartHeight = 280;
    $paddingLeft = 52.0;
    $paddingRight = 18.0;
    $paddingTop = 16.0;
    $paddingBottom = 42.0;
    $plotWidth = $chartWidth - $paddingLeft - $paddingRight;
    $plotHeight = $chartHeight - $paddingTop - $paddingBottom;
    $counts = array_map(
        static fn(array $point): int => (int)$point['count'],
        $adminDailyUsersSeries
    );
    $maxCount = max(1, max($counts));
    $barCount = count($adminDailyUsersSeries);
    $barGap = 4.0;
    $xLabelInterval = max(1, (int)ceil($barCount / 6));
    $barWidth = $barCount > 0
        ? ($plotWidth - (($barCount - 1) * $barGap)) / $barCount
        : $plotWidth;

    $bars = [];
    $xLabels = [];

    foreach ($adminDailyUsersSeries as $index => $point) {
        $count = (int)$point['count'];
        $heightRatio = $maxCount > 0 ? $count / $maxCount : 0.0;
        $barHeight = max(2.0, round($plotHeight * $heightRatio, 2));
        $x = $paddingLeft + (($barWidth + $barGap) * $index);
        $y = $paddingTop + ($plotHeight - $barHeight);

        if ($count === 0) {
            $barHeight = 2.0;
            $y = $paddingTop + ($plotHeight - $barHeight);
        }

        $bars[] = [
            'x' => round($x, 2),
            'y' => round($y, 2),
            'width' => round($barWidth, 2),
            'height' => round($barHeight, 2),
            'count' => $count,
            'date' => (string)$point['date'],
        ];

        if ($index === 0 || $index === $barCount - 1 || $index % $xLabelInterval === 0) {
            $xLabels[] = [
                'x' => round($x + ($barWidth / 2), 2),
                'label' => (string)$point['short_label'],
            ];
        }
    }

    $adminDailyUsersChart = [
        'width' => $chartWidth,
        'height' => $chartHeight,
        'grid_lines' => buildAdminChartGridLines($counts, $paddingTop, $plotHeight, 4),
        'bars' => $bars,
        'x_labels' => $xLabels,
    ];
}
