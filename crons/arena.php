<?php

    require_once('../config.php');

    // Must interact every 3 days to be marked as active
    $row = ActiveUsers();

    $ArenaLog = "";
    foreach($row as $r) {
        $Character = new Character();
        $Character->LoadByUserId($r['user_id']);
        $party_config = $Character->Data['party_json'];

        $arena_floor = $Character->Data['arena_floor'];

        echo "Loaded & running party for user_id: ".$r['user_id']."\n";

        $monster_strength = 10 * $arena_floor;
        $monster_dexterity = 10 * $arena_floor;
        $monster_health = 10 * $arena_floor;
        $monster_wisdom = 10 * $arena_floor;
        $monster_ability = SKILL_GEMS[array_rand(SKILL_GEMS)]['Name'];
        echo "Generated monsters for floor $arena_floor\n";
        $ArenaLog .= "Monster stats are $monster_strength STR, $monster_dexterity DEX,
        $monster_health HEALTH, $monster_wisdom WIS. Monster ability: $monster_ability. <BR />\n";

        // Simulate Battle
        $Battle = new Battle();
        $battle_result = $Battle->Battle(
        //player party
        $party_config,
        //monster party
        [
            "members" => [
                "frontline" => [
                    "class" => "warrior",
                    "level" => $arena_floor,
                    "strength" => $monster_strength,
                    "dexterity" => $monster_dexterity,
                    "health" => $monster_health,
                    "wisdom" => $monster_wisdom,
                    "gear" => [],
                    "skills" => [$monster_ability, $monster_ability],
                ],
                "backline" => [
                    "class" => "warrior",
                    "level" => $arena_floor,
                    "strength" => $monster_strength,
                    "dexterity" => $monster_dexterity,
                    "health" => $monster_health,
                    "wisdom" => $monster_wisdom,
                    "gear" => [],
                    "skills" => [$monster_ability, $monster_ability],
                ]
            ]
        ]);

        if($battle_result['player_won']) {
            $ArenaLog .= "<span class='success'>You won the arena battle on floor $arena_floor!</span><BR />\n";
        } else {
            $ArenaLog .= "<span class='danger'>You lost the arena battle on floor $arena_floor.</span><BR />\n";
        }

        $ArenaLog .= implode("<BR />\n", $battle_result['log'])."\n";
        $Character->Data['last_arena_time'] = date('Y-m-d H:i:s');
        $Character->Data['last_arena_log'] = $ArenaLog;
        $Character->SaveByUserId($r['user_id']);
        echo "Saved arena results for user_id: ".$r['user_id']."\n";
    }
