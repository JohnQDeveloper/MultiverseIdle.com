<?php
    # turn on debug printing
    define('DEBUG', true);

    error_reporting(E_ALL ^ E_DEPRECATED ^ E_WARNING); # otherwise barf on sessions due to headers already being sent

    # require composer
    require __DIR__ . '/vendor/autoload.php';

    spl_autoload_register(function ($class_name) {
        require_once( __DIR__ . "/app/" . strtolower($class_name) . '.php');
    });

    $db = new \PDO('mysql:dbname=MultiverseIdle;host='.getenv('DB_HOST').';charset=utf8mb4', getenv('DB_USER'), getenv('DB_PASSWORD'));
    $auth = new \Delight\Auth\Auth($db);
