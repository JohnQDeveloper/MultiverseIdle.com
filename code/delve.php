<?php
    if( (5 > $_SESSION['nerve']) && (5 > $_SESSION['life']) && isset($_POST['delve'])) {
        $failure_message = true;
    }
    else if(in_array($_POST['delve'], ['mining', 'puzzles', 'traps', 'hunt'])) {

        $_SESSION['nerve'] -= 5;

        if($_POST['delve'] == 'mining') {
            $stat = 'strength';
            $skill = 'mining';
        } else if($_POST['delve'] == 'puzzles') {
            $stat = 'intelligence';
            $skill = 'puzzles';
        } else if($_POST['delve'] == 'traps') {
            $stat = 'dexterity';
            $skill = 'agility';
        } else if($_POST['delve'] == 'hunt') {
        }

        if($_POST['delve'] == 'traps') {
            $stat_gains = Stats::calculateStatGains($_SESSION[$stat], 2);
            $_SESSION[$stat] = $stat_gains;
            $skill_gains = Stats::calculateStatGains($_SESSION[$skill], 2);
            $_SESSION[$skill] = $skill_gains;
        }
        else if($_POST['delve'] != 'hunt') {
            $stat_gains = Stats::calculateStatGains($_SESSION[$stat], 2);
            $_SESSION[$stat] = $stat_gains;
            $skill_gains = Skills::calculateSkillGains($_SESSION[$skill], 2);
            $_SESSION[$skill] = $skill_gains;
        }

        # Minimum failure chance = 1%
        if(rand(1,100) <= 1) {
            $success_message = "<b>Failure:</b> You were ambushed and killed.";
            $_SESSION['life'] = 0;
            $_SESSION['nerve'] = 0;
        }

        $result = Delves::Perform($_POST['delve'], $risk_factor = $_POST['risk']);

        if($_POST['delve'] != 'hunt') {
            if($result['success']) {
                $success_message = "<b>Success:</b> You successfully completed the delve!";
                $success_message .= "<p>Target Number: " . $result['target'] . "</p>";
                $success_message .= "<p>Rolled: " . $result['rolled'] . "</p>";
                $success_message .= "<p>You gained " . $stat_gains . " $stat</p>";
                $success_message .= "<p>You gained " . $skill_gains . " $skill</p>";

                foreach($result['loot'] as $loot_type => $amt) {
                    if($loot_type == 'money') {
                        $success_message .= "<p>You found " . $amt . " gold!</p>";
                    } else if($loot_type == 'mithral') {
                        $success_message .= "<p>You found " . $amt . " mithral!</p>";
                    } else if($loot_type == 'minor runes') {
                        $success_message .= "<p>You found " . $amt . " minor runes!</p>";
                    } else if($loot_type == 'major runes') {
                        $success_message .= "<p>You found " . $amt . " major runes!</p>";
                    } else if($loot_type == 'stat books') {
                        $success_message .= "<p>You found " . $amt . " stat books!</p>";
                    } else if($loot_type == 'potion ingredients') {
                        $success_message .= "<p>You found " . $amt . " potion ingredients!</p>";
                    }
                }
            } else {
                $success_message = "<b>Failure:</b> You could not complete the delve!";
                $success_message .= "<p>Target Number: " . $result['target'] . "</p>";
                $success_message .= "<p>Rolled: " . $result['rolled'] . "</p>";
                $_SESSION['life'] = 0;
                $_SESSION['nerve'] = 0;
            }
        }
        else {
            $success_message = "<b>Hunt:</b> You went on a hunt! See Logs for full combat details!";

        }
    }
