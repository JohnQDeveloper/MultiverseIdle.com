#!/usr/local/bin/php
<?php

declare(strict_types=1);

$time_start = microtime(true);
require_once('../config.php');

/**
 * Season-End Cron
 *
 * Runs daily. Finds any active seasons whose end_date has passed,
 * merges each season character's stats and resources into the owner's
 * perpetual character, then marks the season as ended.
 */

// Find active seasons that have expired
$expired_seasons = $DAL->r(
    "SELECT * FROM seasons WHERE status = 'active' AND end_date < NOW()"
);

if (empty($expired_seasons)) {
    echo "No expired seasons to process.\n";
    $time_end = microtime(true);
    echo "Season-end cron completed in " . ($time_end - $time_start) . " seconds.\n";
    return;
}

foreach ($expired_seasons as $season) {
    $season_id   = (int)$season['id'];
    $season_name = $season['name'];
    echo "Processing ended season: $season_name (id: $season_id)\n";

    // Fetch all season characters for this season
    $season_characters = $DAL->r(
        "SELECT id, user_id FROM characters WHERE season_id = :season_id AND user_id IS NOT NULL",
        ['season_id' => $season_id]
    );

    if (empty($season_characters)) {
        echo "  No characters found for season $season_id.\n";
    } else {
        foreach ($season_characters as $row) {
            $character_id = (int)$row['id'];
            $user_id      = (int)$row['user_id'];
            echo "  Merging character $character_id (user $user_id) to perpetual...\n";

            $SeasonCharacter = new Character();
            if (!$SeasonCharacter->LoadById($character_id)) {
                echo "  Warning: could not load character $character_id, skipping.\n";
                continue;
            }

            if ($SeasonCharacter->MergeSeasonToPerpetual($user_id)) {
                echo "  Merged successfully.\n";
            } else {
                echo "  Warning: merge failed for character $character_id (user $user_id).\n";
            }
        }
    }

    // Mark the season as ended
    $DAL->w(
        "UPDATE seasons SET status = 'ended' WHERE id = :id",
        ['id' => $season_id]
    );
    echo "Season $season_id ($season_name) marked as ended.\n";
}

$time_end = microtime(true);
echo "Season-end cron completed in " . ($time_end - $time_start) . " seconds.\n";
