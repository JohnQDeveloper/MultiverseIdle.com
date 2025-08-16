<?php

    class Combat {
        public static function RunPvE($target_level) {

            $monster_strength = ceil($target_level/8);
            $monster_agility = ceil($target_level/8);
            $monster_dexterity = ceil($target_level/8);
            $monster_constitution = ceil($target_level/8);
            $monster_intelligence = ceil($target_level/8);
            $monster_life = ceil($target_level/8);

            $_SESSION['combat_log'] = "";
            $both_alive = true;
            $x = 0;
            while($both_alive) {
                $x++;
                $player_hit = Combat::CalcTargetHit($_SESSION['dexterity'], $monster_agility);
                $monster_hit = Combat::CalcTargetHit($monster_dexterity, $_SESSION['agility']);
                $player_skill_triggered = Combat::CalcSkillTrigger($_SESSION['intelligence'], $monster_intelligence);

                $_SESSION['combat_log'] .= "Monster Dex: $monster_dexterity<br>";
                $_SESSION['combat_log'] .= "Monster Agi: $monster_agility<br>";
                $_SESSION['combat_log'] .= "Monster Str: $monster_strength<br>";
                $_SESSION['combat_log'] .= "Player Dex: ".$_SESSION['dexterity']."<br>";
                $_SESSION['combat_log'] .= "Player Agi: ".$_SESSION['agility']."<br>";
                $_SESSION['combat_log'] .= "Player Str: ".$_SESSION['strength']."<br>";

                $_SESSION['combat_log'] .= "<br /><hr /><br /><p><b>Combat Round $x:</b></p>";
                $_SESSION['combat_log'] .= "<p>Your skill proc chance is ".
                round(Combat::SkillChance($_SESSION['intelligence'], $monster_intelligence)*100)." %.</p>";
                if($player_skill_triggered) {
                    // add skill logic here
                    $_SESSION['combat_log'] .= "<p>You would proc a skill if you had one equipped via a Major Rune.</p>";
                }

                $_SESSION['combat_log'] .= "<p>The monster hit chance is ".
                    round(Combat::HitChance($monster_dexterity, $_SESSION['agility'])*100)."%.</p>";
                if($monster_hit) {
                    $_SESSION['life'] -= round($monster_strength/5);

                    $_SESSION['combat_log'] .= "<p>The monster hit you for ".round($monster_strength/5)." damage.</p>";
                    $_SESSION['combat_log'] .= "<p>You have ".$_SESSION['life']." life.</p>";
                }

                $_SESSION['combat_log'] .= "<p>Your hit chance is ".
                    round(Combat::HitChance($_SESSION['dexterity'], $monster_agility)*100)."%.</p>";
                if($player_hit) {
                    $monster_life -= round($_SESSION['strength']/5);
                    $_SESSION['combat_log'] .= "<p>You hit the monster for ".round($_SESSION['strength']/5)." damage.</p>";
                    $_SESSION['combat_log'] .= "<p>They have ".$monster_life." life.</p>";
                }

                if($monster_life <= 0) {
                    $_SESSION['combat_log'] .= "<p>The player won! And also wins ties!</p>";
                    return true; // Player wins
                }
                else if($_SESSION['life'] <= 0) {
                    $_SESSION['combat_log'] .= "<p>The monster won!</p>";
                    return false; // Monster wins
                }

                if($x > 200) {
                    $_SESSION['combat_log'] .= "<p>The battle lasted too long and ended in a draw.</p>";
                    return false;
                }
            }

            return false;
        }

        public static function CalcSkillTrigger($src_intelligence, $target_intelligence) {
            $ToProc = Combat::SkillChance($src_intelligence, $target_intelligence);
            return rand(1, 100) <= ($ToProc * 100);
        }

        public static function SkillChance($src_intelligence, $target_intelligence) {
            return $src_intelligence / ($src_intelligence + $target_intelligence);
        }

        public static function CalcTargetHit($src_dexterity, $target_agility) {
            $hit_chance = Combat::HitChance($src_dexterity, $target_agility);
            return rand(1, 100) <= ($hit_chance*100);
        }

        public static function HitChance($src_dexterity, $target_agility) {
            return $src_dexterity / ($target_agility + $src_dexterity);
        }
    }
