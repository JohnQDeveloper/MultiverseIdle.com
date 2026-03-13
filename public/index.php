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
    $isLoggedIn = isset($_SESSION['auth_logged_in']) && $_SESSION['auth_logged_in'] == 1
               && isset($_SESSION['auth_user_id']) && $_SESSION['auth_user_id'] > 0;
    $isGuest = isset($_SESSION['guest_mode']) && $_SESSION['guest_mode'] === true;

    if ($isLoggedIn) {
        if(!$Character->CharacterExists($_SESSION['auth_user_id'])) {
            $Character->CreateCharacter($_SESSION['auth_user_id'], $_SESSION['auth_username']);
        }
        else {
            $Character->LoadByUserId($_SESSION['auth_user_id']);
        }
    }
    elseif ($isGuest) {
        if (!$Character->CharacterExists()) {
            $Character->CreateCharacter(0, 'Guest');
        }
        else {
            $Character->LoadByUserId(0);
        }
    }
    $CharacterDataCache = $Character->Data;

    // Run catch-up ticks for guest AFTER caching so diff detection triggers a session save
    if ($isGuest && $Character->IsGuest()) {
        ProcessGuestCatchUp($Character);
    }

    $isAuthenticated = $isLoggedIn || $isGuest;
    $publicPages = ['login', 'register', 'index', 'verify-email', 'forgot-password', 'reset-password', 'guest'];

    if (in_array(ltrim(strtolower($unsafe_main_page).".php","/"), $pages)) {
        if(!$isAuthenticated && !in_array(ltrim(strtolower($unsafe_main_page),"/"), $publicPages)) {
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
        if(!$isAuthenticated) {
            require_once("../pages/index.php");
        }
        else {
            require_once("../pages/play-now.php");
        }
    }


    require_once('../templates/footer.php');

    if($CharacterDataCache != $Character->Data) {
        $Character->Data['last_seen'] = date('Y-m-d H:i:s');
        $Character->SaveByUserId();
    }
    else {
        $Character->ActivityCheck();
    }
