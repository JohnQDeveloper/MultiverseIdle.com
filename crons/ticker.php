<?php
    require_once('/app/config.php');

    # Economic usage constants
    // relies on created_at to be picked correctly; updating economy constants
    $DAL->w("INSERT INTO economy_stats (type, amt) VALUES (?, ?)", ['mithral_used', 0]);
    $DAL->w("INSERT INTO economy_stats (type, amt) VALUES (?, ?)", ['healing_done', 0]);
    $DAL->w("INSERT INTO economy_stats (type, amt) VALUES (?, ?)", ['runes_forged', 0]);
    $DAL->w("INSERT INTO economy_stats (type, amt) VALUES (?, ?)", ['potions_brewed', 0]);

    $r = $DAL->r("SELECT SUM(amt) as C FROM economy_stats WHERE type='mithral_used' AND created_at > NOW() - INTERVAL 1 DAY");
    $redis->set('mithral_used', $r[0]['C']);

    $r = $DAL->r("SELECT SUM(amt) as C FROM economy_stats WHERE type='healing_done' AND created_at > NOW() - INTERVAL 1 DAY");
    $redis->set('healing_done', $r[0]['C']);

    $r = $DAL->r("SELECT SUM(amt) as C FROM economy_stats WHERE type='runes_forged' AND created_at > NOW() - INTERVAL 1 DAY");
    $redis->set('runes_forged', $r[0]['C']);

    $r = $DAL->r("SELECT SUM(amt) as C FROM economy_stats WHERE type='potions_brewed' AND created_at > NOW() - INTERVAL 1 DAY");
    $redis->set('potions_brewed', $r[0]['C']);

    # Get daily active users
    $r = $DAL->r("SELECT COUNT(*) as C FROM perpetual_characters WHERE last_save > NOW() - INTERVAL 1 DAY");
    $DAU = $r[0]['C'];
    $redis->set('DAU', $DAU);

    $players_to_update = $DAL->r("SELECT * FROM perpetual_characters WHERE
    (last_save > NOW() - INTERVAL 1 DAY)
    OR (premium_end_date > NOW() AND last_save > NOW() - INTERVAL 9 DAY)
    ");

    foreach($players_to_update as $k => $player) {
        echo "\033[31mUpdating user_id:".$player['user_id']."\033[0m\n";
        $p = new Player($DAL);
        $player_session = $p->BuildFromArray($player);
        #print_r($player_session);die();

        # Updating healing done economic constant
        echo "Healing characters\n";
        $healed = max(0, $player_session['max_life'] - $player_session['life']);
        if($healed > 0) {
            $DAL->w("UPDATE economy_stats SET amt = amt + ? WHERE type = 'healing_done' ORDER BY id DESC LIMIT 1", [$healed]);
        }

        $player_session['life'] = $player_session['max_life']; # full heal

        $player_session['nerve'] += 15;
        $player_session['energy'] += 15;
        echo "Energy @ ".$player_session['energy']." / ".$player_session['max_energy']."\n";
        echo "Nerve @ ".$player_session['nerve']." / ".$player_session['max_nerve']."\n";
        echo "Starting Money @ $".$player_session['money']."\n";

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
            $gains = [];
            $gains = Jobs::PerformJob($player_session['last_job'], $player_session);

            foreach($gains as $stat => $gain) {
                $player_session[$stat] += $gain;
            }

            // Logs are backend
            echo "Performed " . $player['last_job']."\n";
            foreach($gains as $stat => $gain) {
                echo "You increased your " . $stat . " by " . $gain . " consuming 5 of nerve/energy.\n";
            }
            echo "\n";

            $jobs_performed--;
        }

        echo "Ending Money @ $".$player_session['money']."\n";
        $p->SaveCharacter($player_session, $players_to_update[$k]['user_id']);
        echo "\033[31mSaved user_id:".$player['user_id']."\033[0m\n";
        echo "\n\n";
    }
