<?php
    if( (5 > $_SESSION['energy']) && (5 > $_SESSION['nerve']) && isset($_POST['job'])) {
        $failure_message = true;
    }
    else if(in_array($_POST['job'], ['healer', 'runeforger', 'armorer', 'alchemist'])) {
        $stat = '';
        $skill = '';

        if($guest_mode)
            $_SESSION['money'] += 100;
        else {
            // tbd; need demand calculations
        }

        $_SESSION['last_job'] = $_POST['job'];

        if(5 < $_SESSION['energy']) {
            $_SESSION['energy'] -= 5;
            $resource_type = 'energy';
        }
        else {
            $_SESSION['nerve'] -= 5;
            $resource_type = 'nerve';
        }

        if($_POST['job'] == 'alchemist') {
            $stat = 'dexterity';
            $skill = 'agility';
        } else if($_POST['job'] == 'runeforger') {
            $stat = 'intelligence';
            $skill = 'runemastery';
        } else if($_POST['job'] == 'armorer') {
            $stat = 'strength';
            $skill = 'forging';
        } else if($_POST['job'] == 'healer') {
            $stat = 'intelligence';
            $skill = 'healing';
        }

        $gains = Stats::calculateStatGains($_SESSION[$stat], 2);
        $_SESSION[$stat] += $gains;
        $_SESSION['energy'] -= intval($_POST['amount']);

        if($_POST['job'] == 'alchemist') {
            $gains = Stats::calculateStatGains($_SESSION[$skill], 2);
            $_SESSION[$skill] += $gains;
        } else {
            $gains = Skills::calculateSkillGains($_SESSION[$skill], 2);
            $_SESSION[$skill] += $gains;
        }

        $gains = Stats::calculateStatGains($_SESSION[$stat], 2);
        $_SESSION[$stat] += $gains;
        $_SESSION['energy'] -= intval($_POST['amount']);

        $success_message = "<p>You increased your " . $stat . " by " . $gains . " consuming 5 $resource_type.</p>\n";
        $success_message .= "<p>You increased your " . $skill . " by " . $gains . " consuming 5 $resource_type.</p>\n";
    }
