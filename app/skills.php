<?php

class Skills {
    static function calculateSkillGains($base, $amount) {

        while($amount > 0) {
            $global_sum = Skills::skillsSum() + $gains;
            $local_sum = $base + $gains;

            $local_stat_gains = 300/ceil(($local_sum-1)^1.3)*10;
            $global_stat_gains = 300/ceil(($global_sum-1)^1.3)*10;

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

    static function skillsSum() {
        return $_SESSION['healing'] + $_SESSION['runemastery'] + $_SESSION['forging'] + $_SESSION['mining']
            + $_SESSION['puzzles'] + $_SESSION['traps'];
    }
}
