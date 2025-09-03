<?php
    if($_POST['amount'] > $_SESSION['energy'] && isset($_POST['stat']))  {
        $failure_message = true;
    }
    else if(in_array($_POST['stat'], ['strength', 'agility', 'dexterity', 'constitution', 'intelligence'])) {

        if(SecurityTools::VerifyCSRFToken('gym')) {
            // CSRF token is valid, process the request
        } else {
            die("Invalid CSRF token");
        }

        $gains = Stats::calculateStatGains($_SESSION[$_POST['stat']], intval($_POST['amount']));
        $_SESSION[$_POST['stat']] += $gains;
        $_SESSION['energy'] -= intval($_POST['amount']);
        $success_message = "You increased your " . $_POST['stat'] . " by " . $gains . " consuming "
        . intval($_POST['amount']) . " energy.";
    }
