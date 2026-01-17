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

    // Load Character
    $Character = new Character();
    if(isset($_SESSION['auth_logged_in']) && $_SESSION['auth_logged_in'] == 1 &&
    isset($_SESSION['auth_user_id']) && $_SESSION['auth_user_id'] > 0) {

        #echo "<h1>Testing</h1>";
        if(!$Character->CharacterExists($_SESSION['auth_user_id'])) {
            $Character->CreateCharacter($_SESSION['auth_user_id'], $_SESSION['auth_username']);
            #echo "<h1>TestingAfter</h1>";
        }
        else {
            $Character->LoadByUserId($_SESSION['auth_user_id']);
        }
    }
    $CharacterDataCache = $Character->Data;
    /*echo "<h1> EH </h1>";
    print_r($_SESSION);
    echo "<BR />";
    echo $_SESSION['auth_logged_in']."<BR />";
    echo $_SESSION['auth_user_id']."<BR />";
    echo $_SESSION['auth_username']."<BR />";
    die();*/

    if (in_array(ltrim(strtolower($unsafe_main_page).".php","/"), $pages)) {
        if(!isset($_SESSION['auth_logged_in']) && $_SESSION['auth_logged_in'] !== 1 &&
        !in_array(ltrim(strtolower($unsafe_main_page),"/"), ['login', 'register', 'index', 'verify-email'])) {
            require_once("../pages/login.php");
        }
        else {
            if(file_exists("../code/" . ltrim($unsafe_main_page, "/") . ".php")) {
                require_once("../code/" . ltrim($unsafe_main_page, "/") . ".php");
            }
            if(file_exists("../pages/" . ltrim($unsafe_main_page, "/") . ".php")) {
                require_once("../pages/" . ltrim($unsafe_main_page, "/") . ".php");
            }
        }
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

    if($CharacterDataCache != $Character->Data) {
        $Character->SaveByUserId();
    }
    else {
        $Character->ActivityCheck();
    }
