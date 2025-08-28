<?php

    class Player {
        private $DAL;
        function __construct($DAL) {
            $this->DAL = $DAL;

        }

        // This is a bad way to use a class but its twisted logic means we can DRY
        public function SaveCharacter($temp = [], $user_id = null) {
            $uid = null;
            if($temp == [] && $user_id === null) $temp = $_SESSION;
            else {
                // We are operating without a session id
                // We need to kill this off as its not under the auth_ variable namespacing
                $uid = $user_id;
                unset($temp['user_id']);
            }

            // remove auth variables, we shouldn't store those
            foreach($temp as $key => $value) {
                if(stristr($key, "auth_") !== false) {
                    unset($temp[$key]);
                }
            }

            #print_r($temp);
            $energy = $temp['energy'];
            unset($temp['energy']);
            $max_energy = $temp['max_energy'];
            unset($temp['max_energy']);
            $nerve = $temp['nerve'];
            unset($temp['nerve']);
            $max_nerve = $temp['max_nerve'];
            unset($temp['max_nerve']);
            $life = $temp['life'];
            unset($temp['life']);
            $max_life = $temp['max_life'];
            unset($temp['max_life']);
            $toxicity = $temp['toxicity'];
            unset($temp['toxicity']);
            $money = $temp['money'];
            unset($temp['money']);
            $last_job = $temp['last_job'];
            unset($temp['last_job']);

           # echo "ENERGY:".$energy."<BR />";
           # echo "MONEY:".$money."<BR />";

            $json_save = json_encode($temp);

            $this->DAL->w("INSERT INTO perpetual_characters SET user_id=:user_id, json_save=:json_save, energy=:energy,
            max_energy=:max_energy, nerve=:nerve, max_nerve=:max_nerve, life=:life, max_life=:max_life, toxicity=:toxicity, money=:money
            ON DUPLICATE KEY UPDATE json_save=:json_save, energy=:energy,
            max_energy=:max_energy, nerve=:nerve, max_nerve=:max_nerve,
            life=:life, max_life=:max_life, toxicity=:toxicity, money=:money, last_job=:last_job",
            [':user_id' => isset($_SESSION['auth_user_id']) ? $_SESSION['auth_user_id'] : $uid
            , ':json_save' => $json_save, ':energy' => $energy,
            ':max_energy' => $max_energy, ':nerve' => $nerve, ':max_nerve' => $max_nerve,
            ':life' => $life, ':max_life' => $max_life, ':toxicity' => $toxicity, ':money' => $money,
            ':last_job' => $last_job]);

        }

        public function BuildFromArray($player) {
            $return = [];

                $player_data = json_decode($player['json_save'], true);
                foreach($player_data as $key => $value) {
                    if(stristr($key, "auth_") === false) {
                        $return[$key] = $value;
                    }
                }
                unset($return['user_id']);

                $return['energy'] = $player['energy'];
                $return['max_energy'] = $player['max_energy'];
                $return['nerve'] = $player['nerve'];
                $return['max_nerve'] = $player['max_nerve'];
                $return['life'] = $player['life'];
                $return['max_life'] = $player['max_life'];
                $return['toxicity'] = $player['toxicity'];
                $return['money'] = $player['money'];
                $return['last_job'] = $player['last_job'];

            return $return;
        }

        public function LoadCharacter($guest_mode = false, $user_id = null) {
            #return;
            if($user_id === null) $user_id = $_SESSION['auth_user_id'];
            $player = $this->DAL->r("SELECT * FROM perpetual_characters WHERE user_id = ?", [$user_id]);

            if($player === [] && isset($_SESSION['auth_user_id'])) {
                if(!isset($_SESSION['agility'])) {
                    $_SESSION['energy'] = 120;
                    $_SESSION['max_energy'] = 120;
                    $_SESSION['nerve'] = 120;
                    $_SESSION['max_nerve'] = 120;
                    $_SESSION['life'] = 120;
                    $_SESSION['max_life'] = 120;
                    $_SESSION['toxicity'] = 0;
                    $_SESSION['money'] = 0;

                    # STATS
                    $_SESSION['agility'] = 10;
                    $_SESSION['dexterity'] = 10;
                    $_SESSION['constitution'] = 10;
                    $_SESSION['strength'] = 10;
                    $_SESSION['intelligence'] = 10;

                    # SKILLS
                    $_SESSION['healing'] = 10;
                    $_SESSION['runemastery'] = 10;
                    $_SESSION['forging'] = 10;
                    $_SESSION['mining'] = 10;
                    $_SESSION['puzzles'] = 10;
                    $_SESSION['traps'] = 10;
                }
                # do nothing because a character is loaded in the session
                else {}
            }
            else{
                $player_data = json_decode($player[0]['json_save'], true);
                foreach($player_data as $key => $value) {
                    if(stristr($key, "auth_") === false) {
                        $_SESSION[$key] = $value;
                    }
                }

                $_SESSION['energy'] = $player[0]['energy'];
                $_SESSION['max_energy'] = $player[0]['max_energy'];
                $_SESSION['nerve'] = $player[0]['nerve'];
                $_SESSION['max_nerve'] = $player[0]['max_nerve'];
                $_SESSION['life'] = $player[0]['life'];
                $_SESSION['max_life'] = $player[0]['max_life'];
                $_SESSION['toxicity'] = $player[0]['toxicity'];
                $_SESSION['money'] = $player[0]['money'];
            }
        }
    }
