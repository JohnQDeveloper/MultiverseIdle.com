<?php


    require_once('../config.php');
    require_once('../templates/header.php');

    // Sanitize user input
    $unsafe_uri = strtok($_SERVER["REQUEST_URI"], '?');;
    $unsafe_qs = $_SERVER['QUERY_STRING'];

    # find valid pages
    $pages = scandir("../pages");


    if (!in_array(ltrim($unsafe_uri.".php","/"), $pages)) {
        require_once("../pages/index.php");
    }
    else {
        require_once("../pages/" . ltrim($unsafe_uri, "/") . ".php");
    }


    require_once('../templates/footer.php');
