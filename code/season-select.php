<?php

declare(strict_types=1);

$alert_success = '';
$alert_danger  = '';

$user_id    = (int)$_SESSION['auth_user_id'];
$Season     = new Season();
$active_season = $Season->GetActiveSeason();

// CSRF validation for all POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf-token']) {
        die('CSRF token validation failed');
    }
}

// --- POST: Switch to perpetual mode ---
if (isset($_POST['play_perpetual'])) {
    unset($_SESSION['active_season_id']);
    header('Location: /play-now');
    exit;
}

// --- POST: Switch to season mode (and create character if needed) ---
if (isset($_POST['play_season'])) {
    $season_id = (int)($_POST['season_id'] ?? 0);

    if ($season_id <= 0) {
        $alert_danger = 'Invalid season.';
    } else {
        $SeasonData = $Season->GetSeasonById($season_id);

        if (!$SeasonData || $SeasonData['status'] !== 'active') {
            $alert_danger = 'That season is no longer active.';
        } else {
            $SeasonCharacter = new Character();
            if (!$SeasonCharacter->CharacterExists($user_id, $season_id)) {
                // Create the season character using the same username
                $SeasonCharacter->CreateCharacter($user_id, (string)$_SESSION['auth_username'], $season_id);
            }
            $_SESSION['active_season_id'] = $season_id;
            header('Location: /play-now');
            exit;
        }
    }
}

// --- Load current mode info ---
$current_season_id = isset($_SESSION['active_season_id']) ? (int)$_SESSION['active_season_id'] : null;
$current_season    = ($current_season_id !== null) ? $Season->GetSeasonById($current_season_id) : null;

// Check if user already has a season character for the active season
$has_season_character = false;
if ($active_season) {
    $has_season_character = $Season->UserHasSeasonCharacter($user_id, (int)$active_season['id']);
}
