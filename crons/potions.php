<?php

declare(strict_types=1);

$time_start = microtime(true);
require_once('../config.php');

echo "Starting potion expiration cleanup...\n";

// Find all characters with expired potions
$expired_potions_query = "SELECT
    c.id as character_id,
    c.active_potion_id,
    c.potion_expire_time,
    c.user_id
FROM characters c
WHERE c.active_potion_id IS NOT NULL
AND c.potion_expire_time IS NOT NULL
AND c.potion_expire_time < NOW()";

$expired_records = $DAL->r($expired_potions_query);

if ($expired_records && !empty($expired_records)) {
    $expired_count = count($expired_records);
    echo "Found $expired_count expired potions to clean up.\n";

    foreach ($expired_records as $record) {
        $character_id = (int)$record['character_id'];
        $potion_id = (int)$record['active_potion_id'];
        $user_id = (int)$record['user_id'];

        echo "Processing character $character_id (user $user_id) - expired potion $potion_id\n";

        // Delete the consumed potion
        $DAL->w("DELETE FROM potions WHERE id = :potion_id AND owner_id = :owner_id", [
            ':potion_id' => $potion_id,
            ':owner_id' => $user_id
        ]);

        if ($DAL->rows_affected() > 0) {
            echo "  Deleted consumed potion $potion_id\n";
        } else {
            echo "  Warning: Potion $potion_id not found or already deleted\n";
        }

        // Clear the active potion from character
        $DAL->w("UPDATE characters SET active_potion_id = NULL, potion_expire_time = NULL WHERE id = :character_id", [
            ':character_id' => $character_id
        ]);

        if ($DAL->rows_affected() > 0) {
            echo "  Cleared active potion from character $character_id\n";
        }
    }

    echo "Completed cleanup of $expired_count expired potions.\n";
} else {
    echo "No expired potions found.\n";
}

$time_end = microtime(true);
$execution_time = ($time_end - $time_start);
echo "Potions cron execution time: " . $execution_time . " seconds\n";
