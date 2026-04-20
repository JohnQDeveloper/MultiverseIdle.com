#!/usr/local/bin/php
<?php
    $run_all_time_start = microtime(true);

    // Run all crons
    echo "Starting Workers Cron...\n";
    require_once('workers.php');
    echo "Workers Cron Completed.\n";

    echo "Starting Arena Cron...\n";
    require_once('arena.php');
    echo "Arena Cron Completed.\n";

    echo "Starting Potions Cron...\n";
    require_once('potions.php');
    echo "Potions Cron Completed.\n";

    echo "Starting World Boss Cron...\n";
    require_once('world-boss.php');
    echo "World Boss Cron Completed.\n";

    echo "Starting Avatar of Corruption Cron...\n";
    require_once('avatar-of-corruption.php');
    echo "Avatar of Corruption Cron Completed.\n";

    echo "Starting Rifts Cron...\n";
    require_once('rifts.php');
    echo "Rifts Cron Completed.\n";

    echo "Starting Treasure Chests Cron...\n";
    require_once('treasure-chests.php');
    echo "Treasure Chests Cron Completed.\n";

    echo "Starting Season-End Cron...\n";
    require_once('season-end.php');
    echo "Season-End Cron Completed.\n";

    echo "All Crons Completed.\n";

    $run_all_time_end = microtime(true);
    $run_all_execution_time = ($run_all_time_end - $run_all_time_start);
    echo "Total Crons execution time: ".$run_all_execution_time." seconds\n";
