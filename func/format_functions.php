<?php
    function affordable_button($current_gold, $cost) {
        if($current_gold >= $cost) {
            return 'success-button contrast';
        }
        else {
            return 'danger-button secondary';
        }
    }

    function human_num($num) {
        if(is_numeric($num) == false) { # skip non-numbers for now
            return $num;
        }
        else { # number formating
            if($num >= 1_000_000_000_000_000_000_000_000) { # septillion
                return round($num / 1_000_000_000_000_000_000_000_000, 2) . 'sp';
            }
            else if($num >= 1_000_000_000_000_000_000_000) { # sextillion
                return round($num / 1_000_000_000_000_000_000_000, 2) . 'sx';
            }
            else if($num >= 1_000_000_000_000_000_000) { # quintillion
                return round($num / 1_000_000_000_000_000_000, 2) . 'qi';
            }
            else if($num >= 1_000_000_000_000_000) { # quadrillion
                return round($num / 1_000_000_000_000_000, 2) . 'qa';
            }
            else if($num >= 1_000_000_000_000) { # trillion
                return round($num / 1_000_000_000_000, 2) . 't';
            }
            else if ($num >= 1_000_000_000) { # billion
                return round($num / 1_000_000_000, 2) . 'b';
            } elseif ($num >= 1_000_000) { # million
                return round($num / 1_000_000, 2) . 'm';
            } elseif ($num >= 1_000) { # thousand
                return round($num / 1_000, 2) . 'k';
            } else {
                return $num;
            }
        }
    }
