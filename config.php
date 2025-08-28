<?php
    # turn on debug printing
    define('DEBUG', true);
    define('URL', 'http://localhost:3456/');

    error_reporting(E_ALL ^ E_DEPRECATED ^ E_WARNING); # otherwise barf on sessions due to headers already being sent

    spl_autoload_register(function ($class_name) {
        include_once( __DIR__ . "/app/" . strtolower($class_name) . '.php');
    });

    # require composer
    require __DIR__ . '/vendor/autoload.php';

    $db = new \PDO('mysql:dbname=MultiverseIdle;host='.getenv('DB_HOST').';charset=utf8mb4', getenv('DB_USER'), getenv('DB_PASSWORD'));
    $auth = new \Delight\Auth\Auth($db);

    $DAL = new DAL($db); // modified to work off the same basis as Delight so 1 connect / 1 request

    # authentication
    if(isset($_SESSION['auth_logged_in']) && $_SESSION['auth_logged_in'] === 1) {
        $_SESSION['user_id'] = $_SESSION['auth_user_id'];
        $_SESSION['email'] = $_SESSION['auth_email'];
        $_SESSION['username'] = $_SESSION['auth_username'];
    }
