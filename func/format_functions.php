<?php

declare(strict_types=1);

/**
 * Returns CSS class for button based on affordability
 */
function affordable_button(int|float $current_gold, int|float $cost): string
{
    if ($current_gold >= $cost) {
        return 'success-button contrast';
    }

    return 'danger-button secondary';
}

/**
 * Format large numbers with human-readable suffixes
 */
function human_num(mixed $num): string|int|float
{
    if (!is_numeric($num)) {
        return $num;
    }

    if ($num >= 1_000_000_000_000_000_000_000_000) { // septillion
        return round($num / 1_000_000_000_000_000_000_000_000, 2) . 'sp';
    } elseif ($num >= 1_000_000_000_000_000_000_000) { // sextillion
        return round($num / 1_000_000_000_000_000_000_000, 2) . 'sx';
    } elseif ($num >= 1_000_000_000_000_000_000) { // quintillion
        return round($num / 1_000_000_000_000_000_000, 2) . 'qi';
    } elseif ($num >= 1_000_000_000_000_000) { // quadrillion
        return round($num / 1_000_000_000_000_000, 2) . 'qa';
    } elseif ($num >= 1_000_000_000_000) { // trillion
        return round($num / 1_000_000_000_000, 2) . 't';
    } elseif ($num >= 1_000_000_000) { // billion
        return round($num / 1_000_000_000, 2) . 'b';
    } elseif ($num >= 1_000_000) { // million
        return round($num / 1_000_000, 2) . 'm';
    } elseif ($num >= 1_000) { // thousand
        return round($num / 1_000, 2) . 'k';
    }

    return $num;
}
