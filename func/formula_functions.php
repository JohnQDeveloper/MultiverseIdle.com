<?php

declare(strict_types=1);

/**
 * Calculate worker yield based on upgrades and bonuses
 */
function worker_yield(
    int $harvests,
    int $speed_upgrades,
    int $skill_level,
    int $num_workers,
    int|float $potion_bonus_percent = 0
): float {
    $base_yield = $harvests * (1 + ($speed_upgrades * 0.05)) * (1 + ($skill_level * 0.05)) * $num_workers;
    $potion_multiplier = 1 + ($potion_bonus_percent / 100);

    return round($base_yield * $potion_multiplier);
}

/**
 * Display the worker yield formula for debugging
 */
function display_worker_yield_formula(
    int $harvests,
    int $speed_upgrades,
    int $skill_level,
    int $num_workers,
    int|float $potion_bonus_percent = 0
): void {
    echo 'round(10 * (1 + ($speed_upgrades * 0.05)) * (1 + ($skill_level * 0.05)) * $num_workers * $potion_multiplier) <BR />';
    $potion_multiplier = 1 + ($potion_bonus_percent / 100);
    echo "round($harvests * (1 + ($speed_upgrades * 0.05)) * (1 + ($skill_level * 0.05)) * $num_workers * $potion_multiplier)";
}

/**
 * Calculate monster attribute based on level
 */
function calculate_monster_attribute(int $monster_level): float
{
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
 * @return float The calculated cost for the next upgrade/hire
 */
function calculate_worker_cost(int $base_cost, int $multiplier, int $current_level): float
{
    return round($base_cost * pow($multiplier, $current_level));
}
