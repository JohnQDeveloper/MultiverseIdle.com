<?php
    if( (5 > $_SESSION['energy']) && (5 > $_SESSION['nerve']) && isset($_POST['job'])) {
        $failure_message = true;
    }
    else if(in_array($_POST['job'], ['healer', 'runeforger', 'armorer', 'alchemist'])) {

        if(SecurityTools::VerifyCSRFToken('job')) {
          // DO nothing
        }
        else {
          die("CSRF Token Verification Failed");
        }

        $stat = '';
        $skill = '';

        #if($guest_mode)
        #    $_SESSION['money'] += 100;
        #else {
        #    $_SESSION['money'] += Jobs::Salary($_POST['job']);
        #}

        $_SESSION['last_job'] = $_POST['job'];

        if(5 < $_SESSION['energy']) {
            $_SESSION['energy'] -= 5;
            $resource_type = 'energy';
        }
        else {
            $_SESSION['nerve'] -= 5;
            $resource_type = 'nerve';
        }

        $gains = Jobs::PerformJob($_POST['job'], $_SESSION);

        $success_message = "";
        foreach($gains as $stat => $gain) {
            $_SESSION[$stat] += $gain;
            $success_message .= "<p>You increased your " . $stat . " by " . $gain . " consuming 5 $resource_type.</p>\n";
        }

    }
