<?php

declare(strict_types=1);

require_once('../config.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$isLoggedIn = isset($_SESSION['auth_logged_in']) && $_SESSION['auth_logged_in'] == 1
           && isset($_SESSION['auth_user_id']) && $_SESSION['auth_user_id'] > 0;

if (!$isLoggedIn) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf-token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$user_id   = (int)$_SESSION['auth_user_id'];
$season_id = isset($_SESSION['active_season_id']) ? (int)$_SESSION['active_season_id'] : null;

$Character = new Character();
if (!$Character->LoadByUserId($user_id, $season_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Character not found']);
    exit;
}

$has_active_sub = !empty($Character->Data['subscription_expires'])
    && strtotime((string)$Character->Data['subscription_expires']) > time();

if (!$has_active_sub) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Requires QoL subscription']);
    exit;
}

$arena_floor  = (int)$Character->Data['arena_floor'];
$party_config = $Character->Data['party_json'];

$min_floor = (int)floor($arena_floor * 0.85);
$max_floor = (int)ceil($arena_floor * 1.15);

// Fetch up to 50 opponents in the player's floor range (no queue requirement — full pool)
$opponents = $DAL->r(
    "SELECT party_json FROM characters
     WHERE user_id != :user_id
       AND arena_floor BETWEEN :min_floor AND :max_floor
       AND season_id <=> :season_id
     ORDER BY RAND()
     LIMIT 50",
    [
        ':user_id'   => $user_id,
        ':min_floor' => $min_floor,
        ':max_floor' => $max_floor,
        ':season_id' => $season_id,
    ]
);

$Battle = new Battle();
$won    = 0;
$lost   = 0;
$mirror = false;

if (empty($opponents)) {
    // No opponents in range — run 100 mirror matches.
    // The Battle engine always gives first-mover advantage to arg 1 ("party" side).
    // Randomly swap initiative each iteration so the result reflects an honest ~50/50
    // rather than the ~97% win rate that results from always going first against yourself.
    $mirror = true;
    for ($i = 0; $i < 100; $i++) {
        $result     = $Battle->Battle($party_config, $party_config, false);
        $player_won = $result['player_won'];
        if (rand(0, 1) === 1) {
            // Give initiative to the "opponent" this rep
            $player_won = !$player_won;
        }
        if ($player_won) {
            $won++;
        } else {
            $lost++;
        }
    }
} else {
    foreach ($opponents as $opp) {
        $opp_party = json_decode((string)$opp['party_json'], true);
        if (!is_array($opp_party)) {
            continue;
        }
        $result = $Battle->Battle($party_config, $opp_party, false);
        if ($result['player_won']) {
            $won++;
        } else {
            $lost++;
        }
    }
}

echo json_encode([
    'success'     => true,
    'won'         => $won,
    'lost'        => $lost,
    'total'       => $won + $lost,
    'mirror'      => $mirror,
    'floor_range' => ['min' => $min_floor, 'max' => $max_floor],
]);
