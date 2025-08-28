<?php
    require_once('/app/config.php');

    $r = $DAL->r("SELECT COUNT(*) as C FROM perpetual_characters WHERE last_save > NOW() - INTERVAL 1 DAY");

    # Get daily active users
    $DAU = $r[0]['C'];

    $players_to_update = $DAL->r("SELECT * FROM perpetual_characters WHERE
    (last_save > NOW() - INTERVAL 1 DAY)
    OR (premium_end_date > NOW() AND last_save > NOW() - INTERVAL 9 DAY)
    ");

    foreach($players_to_update as $k => $player) {
        echo "Updating user_id:".$player['user_id']."\n";
        $p = new Player($DAL);
        $player_session = $p->BuildFromArray($player);
        #print_r($player_session);die();

        $player_session['nerve'] += 15;
        $player_session['energy'] += 15;
        echo "Energy @ ".$player_session['energy']." / ".$player_session['max_energy']."\n";
        echo "Nerve @ ".$player_session['nerve']." / ".$player_session['max_nerve']."\n";

        $jobs_performed = 0;
        if($player_session['max_energy'] < $player_session['energy']) {
            $excess_energy = $player_session['energy'] - $player_session['max_energy'];
            $player_session['energy'] -= $excess_energy;
            $jobs_performed += $excess_energy / 5;
        }

        if($player_session['max_nerve'] < $player_session['nerve']) {
            $excess_nerve = $player_session['nerve'] - $player_session['max_nerve'];
            $player_session['nerve'] -= $excess_nerve;
            $jobs_performed += $excess_nerve / 5;
        }

        while($jobs_performed > 0) {
            $gains = Jobs::PerformJob($player['last_job'], $player_session);
            foreach($gains as $stat => $gain) {
                $player_session[$stat] += $gain;
            }

            foreach($gains as $stat => $gain) {
                $player_session[$stat] += $gain;
            }

            // Logs are backend
            echo "Performed " . $player['last_job']."\n";
            foreach($gains as $stat => $gain) {
                echo "You increased your " . $stat . " by " . $gain . " consuming 5 $resource_type.\n";
            }
            echo "\n";

            $jobs_performed--;
        }



        $p->SaveCharacter($player_session, $players_to_update[$k]['user_id']);
        echo "\n\n";
    }
