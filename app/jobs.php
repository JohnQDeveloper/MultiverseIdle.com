<?php

    class Jobs {
        static function Salary($job) {
            return DAU; // 1 DAU = $1 as the job baseline
        }

        static function PerformJob($job, $stat_array) {
            #print_r($stat_array);

            if($job == 'alchemist') {
                $stat = 'dexterity';
                $skill = 'agility';
            } else if($job == 'runeforger') {
                $stat = 'intelligence';
                $skill = 'runemastery';
            } else if($job == 'armorer') {
                $stat = 'strength';
                $skill = 'forging';
            } else if($job == 'healer') {
                $stat = 'intelligence';
                $skill = 'healing';
            }

            $gains = [];

            // Gain 2 Skill Rolls or Stats for Alchemist
            if($job == 'alchemist') {
                $gains[$skill] = Stats::calculateStatGains($stat_array[$skill], 2, $stat_array);
            } else {
                $gains[$skill] = Skills::calculateSkillGains($stat_array[$skill], 2, $stat_array);
            }

            // Gain 2 Stat Gain Rolls
            #echo $stat_array[$stat]."\n";
            $gains[$stat] = Stats::calculateStatGains($stat_array[$stat], 2, $stat_array);

            $gains['money'] = Jobs::Salary($job);

           # print_r($gains);
           # die();

            return $gains;
        }
    }
