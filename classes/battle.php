<?php

    class Battle {

        public function Battle($party_config, $monster_config) {
          $return_me_log = [];
        // Actual health is 5x normal for battle purposes
          $party_config['members']['frontline']['current_health'] =
          $party_config['members']['frontline']['health'] * 5;
          $party_config['members']['backline']['current_health'] =
          $party_config['members']['backline']['health'] * 5;;

          $monster_config['members']['frontline']['current_health'] =
          $monster_config['members']['frontline']['health'] * 5;
          $monster_config['members']['backline']['current_health'] =
          $monster_config['members']['backline']['health'] * 5;

          $running = true;
           while($running) {

            if ($party_config['members']['frontline']['current_health'] <= 0 &&
            $party_config['members']['backline']['current_health'] <= 0) {
                echo "Finished with party @ ".$party_config['members']['frontline']['current_health'].", ".
                $party_config['members']['backline']['current_health']."\n";
                $running = false;
            }

            if ($monster_config['members']['frontline']['current_health'] <= 0 &&
            $monster_config['members']['backline']['current_health'] <= 0) {
                echo "Finished with monsters @ ".$monster_config['members']['frontline']['current_health'].", ".
                $monster_config['members']['backline']['current_health']."\n";
                $running = false;
            }

               // Player turn

                /// frontliner attacks
                if($party_config['members']['frontline']['current_health'] <= 0) {
                    // dead skip turn
                    #$return_me_log[] = "Party Frontliner is down and cannot attack.";
                }
                else {
                    $target = 'frontline';
                    if($monster_config['members']['frontline']['current_health'] <= 0) {
                        $target = 'backline';
                    }

                    $dex = $party_config['members']['frontline']['dexterity'];
                    $str = $party_config['members']['frontline']['strength'];
                    $wis = $party_config['members']['frontline']['wisdom'];
                    $hit_chance = $dex / ($dex + $monster_config['members'][$target]['dexterity']);
                    if(rand(0, 100) / 100 <= $hit_chance) {
                        // Hit
                        $monster_config['members'][$target]['current_health'] -= $str;
                        $return_me_log[] = "Party Frontliner hits $target for $str damage.";
                    } else {
                        // Miss
                        $return_me_log[] = "Party Frontliner misses $target.";
                    }
                }

                /// backliner attacks
                if($party_config['members']['backline']['current_health'] <= 0) {
                    // dead skip turn
                    #$return_me_log[] = "Party Backliner is down and cannot attack.";
                }
                else {
                    $target = 'frontline';
                    if($monster_config['members']['frontline']['current_health'] <= 0) {
                        $target = 'backline';
                    }

                    $dex = $party_config['members']['backline']['dexterity'];
                    $str = $party_config['members']['backline']['strength'];
                    $wis = $party_config['members']['backline']['wisdom'];
                    $hit_chance = $dex / ($dex + $monster_config['members'][$target]['dexterity']);
                    if(rand(0, 100) / 100 <= $hit_chance) {
                        // Hit
                        $monster_config['members'][$target]['current_health'] -= $str;
                        $return_me_log[] = "Party Backliner hits $target for $str damage.";
                    } else {
                        // Miss
                        $return_me_log[] = "Party Backliner misses $target.";
                    }
                }

               // Monster turn

                /// frontliner attacks
                if($monster_config['members']['frontline']['current_health'] <= 0) {
                    // dead skip turn
                    #$return_me_log[] = "Monster Frontliner is down and cannot attack.";
                }
                else {
                    $target = 'frontline';
                    if($party_config['members']['frontline']['current_health'] <= 0) {
                        $target = 'backline';
                    }

                    $dex = $monster_config['members']['frontline']['dexterity'];
                    $str = $monster_config['members']['frontline']['strength'];
                    $wis = $monster_config['members']['frontline']['wisdom'];
                    $hit_chance = $dex / ($dex + $party_config['members'][$target]['dexterity']);
                    if(rand(0, 100) / 100 <= $hit_chance) {
                        // Hit
                        $party_config['members'][$target]['current_health'] -= $str;
                        $return_me_log[] = "Monster Frontliner hits $target for $str damage.";
                    } else {
                        // Miss
                        $return_me_log[] = "Monster Frontliner misses $target.";
                    }
                }

                /// backliner attacks
                if($monster_config['members']['backline']['current_health'] <= 0) {
                    // dead skip turn
                    #$return_me_log[] = "Monster Backliner is down and cannot attack.";
                }
                else {
                    $target = 'frontline';
                    if($party_config['members']['frontline']['current_health'] <= 0) {
                        $target = 'backline';
                    }
                    $dex = $monster_config['members']['backline']['dexterity'];
                    $str = $monster_config['members']['backline']['strength'];
                    $wis = $monster_config['members']['backline']['wisdom'];
                    $hit_chance = $dex / ($dex + $party_config['members'][$target]['dexterity']);
                    if(rand(0, 100) / 100 <= $hit_chance) {
                        // Hit
                        $party_config['members'][$target]['current_health'] -= $str;
                        $return_me_log[] = "Monster Backliner hits $target for $str damage.";
                    } else {
                        // Miss
                        $return_me_log[] = "Monster Backliner misses $target.";
                    }
                }

           }

           $return_me_log[] = "Battle ended.";
           $return_me_log[] = "Party Frontline Health: ".$party_config['members']['frontline']['current_health'];
           $return_me_log[] = "Party Backline Health: ".$party_config['members']['backline']['current_health'];
           $return_me_log[] = "Monster Frontline Health: ".$monster_config['members']['frontline']['current_health'];
           $return_me_log[] = "Monster Backline Health: ".$monster_config['members']['backline']['current_health'];

           return [
            'player_won' =>
            ($party_config['members']['frontline']['current_health'] > 0 ||
            $party_config['members']['backline']['current_health'] > 0) ? true : false,

            'log' => $return_me_log
           ];
        }

    }
