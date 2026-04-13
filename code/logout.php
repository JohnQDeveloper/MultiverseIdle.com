<?php

if ($auth->isLoggedIn()) {
    $auth->logOutEverywhere();
}

session_destroy();

Header('Location: /');
