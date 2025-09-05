<?php

class Crafting {
    public static function createItem($gear_slot, $mithral) {
        $skill_bonus = round(($_SESSION['forging']/2)+($_SESSION['strength']/20))*2;
        $return = ["type" => $gear_slot, "stat_bonus" => ($mithral*2 + $skill_bonus)/100, "modifiers" => []];

        # combat stats only
        if($gear_slot != "ring" && $gear_slot != "amulet") {
            $modifiers = ["strength", "dexterity", "intelligence", "constitution", "agility"];
            $stat1 = rand(0, count($modifiers)-1);
            $stat2 = rand(0, count($modifiers)-1);

            $return['modifiers'][] = [$modifiers[$stat1] => rand(1, 4)];
            $return['modifiers'][] = [$modifiers[$stat2] => rand(1, 4)];
        }
        # chance to roll non-combat stats on the items
        else if ($gear_slot == "ring" || $gear_slot == "amulet") {
            $modifiers = ["forging", "runemastery", "healing", "agility", "dexterity"];
            $stat1 = rand(0, count($modifiers)-1);
            $stat2 = rand(0, count($modifiers)-1);

            $return['modifiers'][] = [$modifiers[$stat1] => rand(1, 4)];
            $return['modifiers'][] = [$modifiers[$stat2] => rand(1, 4)];
        }

        $return['name'] = GEAR_NAMING['descriptors'][array_rand(GEAR_NAMING['descriptors'])] .
                        " " . GEAR_NAMING['materials'][array_rand(GEAR_NAMING['materials'])] .
                        " " . ucfirst($gear_slot) .
                        " " . GEAR_NAMING['adjectives'][array_rand(GEAR_NAMING['adjectives'])];

        return $return;
    }
}
