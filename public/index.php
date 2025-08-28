<?php


    require_once('../config.php');
    require_once('../templates/header.php');

    // Sanitize user input
    $unsafe_main_page = strtok(strtok($_SERVER["REQUEST_URI"], '?'), '/');
    $unsafe_qs = $_SERVER['QUERY_STRING'];

    $unsafe_second_page = explode('/', strtok($_SERVER["REQUEST_URI"], '?'));
    $unsafe_second_page = $unsafe_second_page[2] ?? '';

    # find valid pages
    $pages = scandir("../pages");
    #print_r($_SESSION);die();

    if (in_array(ltrim(strtolower($unsafe_main_page).".php","/"), $pages)) {
        if(file_exists("../code/" . ltrim($unsafe_main_page, "/") . ".php")) {
            require_once("../code/" . ltrim($unsafe_main_page, "/") . ".php");
        }
        require_once("../pages/" . ltrim($unsafe_main_page, "/") . ".php");
    }
    else {
        if(!isset($_SESSION['auth_logged_in']) && $_SESSION['auth_logged_in'] !== 1) {
            require_once("../pages/index.php");
        }
        else {
            require_once("../pages/play-now.php");
        }
    }


    require_once('../templates/footer.php');
