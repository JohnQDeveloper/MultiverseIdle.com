<?php

class Items {
/*
Array
(
    [type] => boots
    [stat_bonus] => 0.2
    [modifiers] => Array
        (
            [0] => Array
                (
                    [intelligence] => 2
                )

            [1] => Array
                (
                    [strength] => 3
                )

        )

)*/
    static function displayItem($item) {
        echo "<b>" . $item['name'] . "</b> <br />";
        echo "Bonus: " . ($item['stat_bonus']*100) . "%<br>";
        foreach($item['modifiers'] as $modifier) {
            foreach($modifier as $stat => $value) {
                echo " - " . ucfirst($stat) . ": " . round($value*(1+$item['stat_bonus'])) . "<br>";
            }
        }
    }

}
