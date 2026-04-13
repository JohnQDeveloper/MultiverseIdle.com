<?php

    define('SKILL_GEMS', [
        'Healing Rain' => ["Name" => "Healing Rain",],
        'Greater Heal' => ["Name" => "Greater Heal",],
        'Antimage' => ["Name" => "Antimage",],
        'Blizzard' => ["Name" => "Blizzard",],
        'Frost Blades' => ["Name" => "Frost Blades",],
        'Flaming Blades' => ["Name" => "Flaming Blades",],
        'Firestorm' => ["Name" => "Firestorm",],
    ]);

    // Valid skill names for validation (derived from SKILL_GEMS keys)
    define('SKILL_GEM_NAMES', array_keys(SKILL_GEMS));
