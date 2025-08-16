<?php
    $guest_mode = true;
    if(isset($_SESSION['player_id'])) {
        $guest_mode = false;
    }
    else {
        if(!isset($_SESSION['energy'])) {

            # STATUS BAR
            $_SESSION['energy'] = 120;
            $_SESSION['max_energy'] = 120;
            $_SESSION['nerve'] = 120;
            $_SESSION['max_nerve'] = 120;
            $_SESSION['life'] = 120;
            $_SESSION['max_life'] = 120;
            $_SESSION['toxicity'] = 0;

            # STATS
            $_SESSION['agility'] = 10;
            $_SESSION['dexterity'] = 10;
            $_SESSION['constitution'] = 10;
            $_SESSION['strength'] = 10;
            $_SESSION['intelligence'] = 10;

            # SKILLS
            $_SESSION['healing'] = 10;
            $_SESSION['runemastery'] = 10;
            $_SESSION['forging'] = 10;
            $_SESSION['mining'] = 10;
            $_SESSION['puzzles'] = 10;
            $_SESSION['traps'] = 10;
        }

    }

    if($unsafe_second_page != "" && in_array(ltrim(strtolower($unsafe_second_page).".php","/"), $pages)) {
        require_once('../code/' . $unsafe_second_page . '.php');
    }
?>
<table class="main">
    <tr>
        <td colspan="3">
            <?php require_once('../templates/head-game-nav.php'); ?>
        </td>
    </tr>
    <tr>
        <td class='left'>
            <?php require_once('../templates/play-now/left-game-nav.php'); ?>
        </td>
        <td class='center'>
            <?php
            if($unsafe_second_page != "" && in_array(ltrim(strtolower($unsafe_second_page).".php","/"), $pages)) {
                require_once('../pages/' . $unsafe_second_page . '.php');
            }
            else {
                require_once('../templates/play-now/main-game-screen.php');
            }
            ?>
        </td>
    </tr>
</table>
