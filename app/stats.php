<?php

class Stats {
    static function calculateStatGains($base, $amount, $player_data = null) {
        if($player_data === null) {
            $player_data = $_SESSION;
        }

        #echo "BASE: $base AMT: $amount \n";

        $gains = 0;
        while($amount > 0) {

            $global_sum = max(Stats::statsSum($player_data) + $gains, 2);
            $local_sum = max($base + $gains, 2);
            #echo "GLOBAL: $global_sum LOCAL: $local_sum \n";
            $local_stat_gains = 300/($local_sum^1.3)*10;
            $global_stat_gains = 300/($global_sum^1.3)*10;
            #echo "GLOBAL_GAIN: $global_stat_gains LOCAL_GAIN: $local_stat_gains\n";
            $total_gains = ($local_stat_gains + $global_stat_gains)/100;
            #echo "TOTAL_GAINS: $total_gains\n";

            $whole = floor($total_gains);
            $fraction = $total_gains - $whole;

            if(rand(1,100) <= $fraction * 100) {
                $gains += $whole + 1;
            }

            $gains += $whole;
            $amount--;
            #echo "GAINS: $gains\n";
        }

        return $gains;
    }

    static function statsSum($player_data) {
        #echo "STR:".$player_data['strength'];
        return $player_data['strength'] + $player_data['agility'] + $player_data['dexterity'] + $player_data['constitution']
            + $player_data['intelligence'];
    }
}
