<?php

    class Delves {
        public static function Perform($type, $risk_factor = 1) {
            if(in_array($type, ['mining', 'puzzles', 'traps', 'hunt'])) {

                $effective_level = Stats::statsSum($_SESSION) + Skills::skillsSum($_SESSION);
                $risk_factor = $risk_factor / 100;

                # Mining Delve
                if($type == 'mining') {

                    $target_number = ($effective_level) * ($risk_factor);
                    $r = rand($_SESSION['mining'], $_SESSION['strength']+$_SESSION['mining']);

                    if($r >= $target_number) {
                        $x = rand(1,100);
                        $loot_type = 'money';
                        $amt = ceil(rand($target_number/3, $target_number));
                        if($x <= 40) {
                            $type = 'money';
                            $_SESSION['money'] += $amt;
                        }
                        else if($x <= 90) {
                            $loot_type = 'mithral';
                            $_SESSION['mithral'] += $amt;
                        }
                        else if($x <= 99 && round(10 * $risk_factor) > 0) {
                            $loot_type = 'minor runes';
                            $_SESSION['minor runes'] += round(10 * $risk_factor);
                        }
                        else if($x == 100 && round(2 * $risk_factor) > 0) {
                            $loot_type = 'major runes';
                            $_SESSION['major runes'] += round(2 * $risk_factor);
                        }
                        else {
                            $loot_type = 'money';
                            $_SESSION['money'] += $amt;
                        }

                        $loot[$loot_type] = $amt;

                        return ["success" => true, "target" => $target_number, "rolled" => $r, "loot" => $loot];
                    }
                    else {
                        return ["success" => false, "target" => $target_number, "rolled" => $r];
                    }

                }
                # Puzzles Delve
                else if($type == 'puzzles') {
                    $target_number = ($effective_level) * ($risk_factor);
                    $r = rand($_SESSION['puzzles'], $_SESSION['intelligence']+$_SESSION['puzzles']);

                    if($r >= $target_number) {
                        $x = rand(1,100);
                        $loot_type = 'money';
                        $amt = ceil(rand($target_number/3, $target_number));
                        if($x <= 10 && round($target_number / 100) > 0) {
                            $loot_type = 'stat books';
                            $_SESSION['stat books'] += round($target_number / 100);
                        }
                        else if($x <= 60) {
                            $loot_type = 'potion ingredients';
                            $_SESSION['potion ingredients'] += round($target_number / 100) + 1;
                        }
                        else {
                            $loot_type = 'money';
                            $_SESSION['money'] += $amt;
                        }

                        $loot[$loot_type] = $amt;

                        return ["success" => true, "target" => $target_number, "rolled" => $r, "loot" => $loot];
                    }
                    else {
                        return ["success" => false, "target" => $target_number, "rolled" => $r];
                    }
                }
                # Traps Delve
                else if($type == 'traps') {
                    $target_number = ($effective_level) * ($risk_factor);
                    $r = rand($_SESSION['agility'], $_SESSION['dexterity']+$_SESSION['agility']);

                    if($r >= $target_number) {
                        $x = rand(1,100);
                        $loot_type = 'money';
                        $amt = ceil(rand($target_number/3, $target_number));
                        if($x <= 10 && round($target_number / 100) > 0) {
                            $loot_type = 'stat books';
                            $_SESSION['stat books'] += round($target_number / 100);
                        }
                        else if($x <= 60) {
                            $loot_type = 'potion ingredients';
                            $_SESSION['potion ingredients'] += round($target_number / 100) + 1;
                        }
                        else {
                            $loot_type = 'money';
                            $_SESSION['money'] += $amt;
                        }

                        $loot[$loot_type] = $amt;

                        return ["success" => true, "target" => $target_number, "rolled" => $r, "loot" => $loot];
                    }
                    else {
                        return ["success" => false, "target" => $target_number, "rolled" => $r];
                    }
                }
                # Monster Hunter!
                else if($type == 'hunt') {
                    if(Combat::RunPvE(($effective_level) * ($risk_factor))) {
                        $loot = [];
                        $i = 0;
                        while($i < 2)
                        {
                            $i++;
                            $x = rand(1,100);
                            $amt = ceil(rand(($effective_level) * ($risk_factor)/3, ($effective_level) * ($risk_factor)));
                            if($x <= 25) {
                                $loot_type = 'money';
                                $_SESSION['money'] += $amt;
                            }
                            else if($x <= 45) {
                                $loot_type = 'mithral';
                                $_SESSION['mithral'] += $amt;
                            }
                            else if($x <= 65) {
                                $loot_type = 'potion ingredients';
                                $amt = rand(1, 10);
                                $_SESSION['potion ingredients'] += round($target_number / 100) + 1;
                            }
                            else if($x <= 85 && round($target_number / 100) > 0) {
                                $loot_type = 'stat books';
                                $amt = rand(1, 10);
                                $_SESSION['stat books'] += round($target_number / 100);
                            }
                            else if($x <= 95 && round($target_number / 100) > 0 ) {
                                $loot_type = 'minor runes';
                                $amt = rand(1, 5);
                                $_SESSION['minor runes'] += round(10 * $risk_factor);
                            }
                            else if (round(2 * $risk_factor) > 0) {
                                $loot_type = 'major runes';
                                $_SESSION['major runes'] += round(2 * $risk_factor);
                            }
                            else {
                                $loot_type = 'money';
                                $_SESSION['money'] += $amt;
                            }

                            if(!isset($loot[$loot_type])) {
                                $loot[$loot_type] = 0;
                            }

                            $loot[$loot_type] += $amt;
                        }

                        return ["success" => true, "loot" => $loot];
                    }
                    else {
                        return ["success" => false];
                    }
                }
            }

            return ["success" => false];
        }
    }
