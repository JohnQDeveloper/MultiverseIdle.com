<?php

    function worker_yield($harvests, $speed_upgrades, $skill_level, $num_workers) {
        return round($harvests * (1 + ($speed_upgrades * 0.05)) * (1 + ($skill_level * 0.05)) * $num_workers);
    }

    function display_worker_yield_formula() {
        #echo 'round($harvests * (1 + ($speed_upgrades * 0.01)) * (1 + ($skill_level * 0.05)) * $num_workers)';
        echo 'round(10 * (1 + ($speed_upgrades * 0.05)) * (1 + ($skill_level * 0.05)) * $num_workers)';
    }

    function calculate_monster_attribute($monster_level) {
        $base_stat = 10;
        return round($base_stat * pow($monster_level, 1.4));
    }

    /**
     * Calculate the cost for a worker upgrade or hire based on current level
     *
     * This function calculates the exponential cost progression where each level
     * costs more than the previous: cost = base_cost * (multiplier ^ current_level)
     *
     * @param int $base_cost The initial cost at level 0
     * @param int $multiplier The cost multiplier per level
     * @param int $current_level The current level (0-indexed)
     * @return int|float The calculated cost for the next upgrade/hire
     */
    function calculate_worker_cost($base_cost, $multiplier, $current_level) {
        return round($base_cost * pow($multiplier, $current_level));
    }
