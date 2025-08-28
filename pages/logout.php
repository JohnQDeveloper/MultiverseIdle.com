<?php
    $auth->logOutEverywhere();
    session_destroy();
    header("Location: /");
    exit;
