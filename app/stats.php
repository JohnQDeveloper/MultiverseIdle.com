<?php

class Stats {
    static function calculateStatGains($base, $amount) {

        while($amount > 0) {
            $global_sum = $_SESSION['strength'] + $_SESSION['agility'] + $_SESSION['dexterity'] + $_SESSION['constitution']
            + $_SESSION['intelligence'] + $gains;
            $local_sum = $base + $gains;

            $local_stat_gains = 300/ceil(($local_sum-1)^1.3)*10;
            $global_stat_gains = 300/ceil(($global_sum-1)^1.3)*10;

            $total_gains = ($local_stat_gains + $global_stat_gains)/100;

            #echo ceil(($local_sum-1)^1.3)."<BR />";
           # echo $total_gains;
           # die();

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
}
