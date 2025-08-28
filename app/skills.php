<?php

class Skills {
    static function calculateSkillGains($base, $amount, $player_data) {

        if($player_data === null) {
            $player_data = $_SESSION;
        }

        while($amount > 0) {
            $global_sum = Skills::skillsSum($player_data) + $gains;
            $local_sum = $base + $gains;

            $local_stat_gains = max(300/ceil(($local_sum-1)^1.3)*10, 0);
            $global_stat_gains = max(300/ceil(($global_sum-1)^1.3)*10, 0);

            $total_gains = ($local_stat_gains + $global_stat_gains)/100;

            $whole = floor($total_gains);
            $fraction = $total_gains - $whole;

            if(rand(1,100) <= $fraction * 100) {
                $gains += $whole + 1;
            }

            $gains += $whole;
            $amount--;
        }

        return $gains;
    }

    static function skillsSum($player_data) {
        return $player_data['healing'] + $player_data['runemastery'] + $player_data['forging'] + $player_data['mining']
            + $player_data['puzzles'] + $player_data['traps'];
    }
}
