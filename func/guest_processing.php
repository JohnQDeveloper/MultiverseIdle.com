<?php

declare(strict_types=1);

/**
 * Process catch-up ticks for a guest character based on time elapsed since last_seen.
 * Mirrors what the worker and arena crons would have done for an active registered user.
 * Called once per page load when the character is a guest.
 */
function ProcessGuestCatchUp(Character $Character): void
{
    if (!$Character->IsGuest() || empty($Character->Data)) {
        return;
    }

    $lastSeen = $Character->Data['last_seen'] ?? null;
    if (empty($lastSeen)) {
        return;
    }

    $minutesElapsed = (int)((time() - strtotime($lastSeen)) / 60);
    $ticks = min($minutesElapsed, NUMBER_OF_MINUTES_PER_RUN);

    if ($ticks <= 0) {
        return;
    }

    processGuestWorkers($Character, $ticks);
    processGuestArena($Character, $ticks);

    // Advance last_seen so the same ticks aren't re-processed on the next page load
    $Character->Data['last_seen'] = date('Y-m-d H:i:s');
}

/**
 * Apply worker resource generation for the given number of ticks.
 * Matches the logic in crons/workers.php, batched for performance.
 */
function processGuestWorkers(Character $Character, int $ticks): void
{
    $worker_json = $Character->Data['worker_json'];
    $resource     = $worker_json['resource'];
    $skill_level  = (int)$worker_json['skills'][$resource];
    $num_workers  = (int)$worker_json['workers'];
    $speed_upgrades = (int)($worker_json['speed_upgrades'] ?? 0);

    // Batch: per-tick yield × ticks (no potion bonus for guests)
    $per_tick    = worker_yield(10, $speed_upgrades, $skill_level, $num_workers, 0);
    $total_yield = (int)($per_tick * $ticks);
    $Character->Data[$resource] += $total_yield;

    // Worker skill XP accumulation and level-ups
    if (!isset($worker_json['skill_xp'])) {
        $worker_json['skill_xp'] = ['gold' => 0, 'iron' => 0, 'herbs' => 0, 'gems' => 0];
    }
    $worker_json['skill_xp'][$resource] = ($worker_json['skill_xp'][$resource] ?? 0) + (int)($per_tick * $ticks);

    $xp_needed = 100 * (int)pow($skill_level, 2);
    while ($xp_needed > 0 && $worker_json['skill_xp'][$resource] >= $xp_needed) {
        $worker_json['skills'][$resource]++;
        $worker_json['skill_xp'][$resource] -= $xp_needed;
        $skill_level = (int)$worker_json['skills'][$resource];
        $xp_needed   = 100 * (int)pow($skill_level, 2);
    }

    $Character->Data['worker_json'] = $worker_json;
}

/**
 * Simulate arena battles for the given number of ticks.
 * Matches the logic in crons/arena.php (no potion bonuses for guests).
 */
function processGuestArena(Character $Character, int $ticks): void
{
    $arena_floor = (int)$Character->Data['arena_floor'];
    $arena_log   = '';
    $stats       = ['strength', 'dexterity', 'health', 'wisdom'];

    for ($i = 0; $i < $ticks; $i++) {
        $monster_str    = calculate_monster_attribute($arena_floor);
        $monster_dex    = calculate_monster_attribute($arena_floor);
        $monster_health = calculate_monster_attribute($arena_floor);
        $monster_wis    = calculate_monster_attribute($arena_floor);
        $monster_ability = SKILL_GEMS[array_rand(SKILL_GEMS)]['Name'];

        $Battle = new Battle();
        $result = $Battle->Battle(
            $Character->Data['party_json'],
            [
                "members" => [
                    "frontline" => [
                        "class" => "strength",
                        "level" => $arena_floor,
                        "strength" => $monster_str,
                        "dexterity" => $monster_dex,
                        "health" => $monster_health,
                        "wisdom" => $monster_wis,
                        "gear" => [],
                        "skills" => [$monster_ability, $monster_ability],
                    ],
                    "backline" => [
                        "class" => "strength",
                        "level" => $arena_floor,
                        "strength" => $monster_str,
                        "dexterity" => $monster_dex,
                        "health" => $monster_health,
                        "wisdom" => $monster_wis,
                        "gear" => [],
                        "skills" => [$monster_ability, $monster_ability],
                    ],
                ],
            ],
            false  // suppress CLI-style echo output
        );

        $tick_log = '';
        if ($result['player_won']) {
            // Track daily highest floor for rift stone crafting (guest uses session key)
            $guest_daily_floor = (int)($_SESSION['guest_daily_floor_value'] ?? 0);
            $guest_daily_date  = $_SESSION['guest_daily_floor_date'] ?? '';
            $today = date('Y-m-d');
            if ($guest_daily_date !== $today) {
                $_SESSION['guest_daily_floor_value'] = $arena_floor;
                $_SESSION['guest_daily_floor_date']  = $today;
            } elseif ($arena_floor > $guest_daily_floor) {
                $_SESSION['guest_daily_floor_value'] = $arena_floor;
            }

            $fl_stat = $stats[array_rand($stats)];
            $bl_stat = $stats[array_rand($stats)];

            // Lucky stat roll: favour class stat
            if ($Character->Data['party_json']['members']['frontline']['class'] === $fl_stat) {
                $fl_stat = $Character->Data['party_json']['members']['frontline']['class'];
            }
            if ($Character->Data['party_json']['members']['backline']['class'] === $bl_stat) {
                $bl_stat = $Character->Data['party_json']['members']['backline']['class'];
            }

            $Character->Data['party_json']['members']['frontline'][$fl_stat]++;
            $Character->Data['party_json']['members']['backline'][$bl_stat]++;

            $Character->Data['gold'] += $arena_floor;

            $bonus_resources = ['iron', 'herbs', 'gems'];
            $bonus_resource  = $bonus_resources[array_rand($bonus_resources)];
            $Character->Data[$bonus_resource] += $arena_floor;

            $xp_award = $arena_floor * 10;
            $Character->IncrementPartyXP($xp_award);

            $tick_log = "<span class='success'>Won floor {$arena_floor}: +{$arena_floor} gold, +{$arena_floor} {$bonus_resource}, +{$xp_award} XP, +1 {$fl_stat} (FL), +1 {$bl_stat} (BL).</span><BR />\n";
        } else {
            $tick_log = "<span class='danger'>Lost on floor {$arena_floor}.</span><BR />\n";
        }

        $tick_log .= implode("<BR />\n", $result['log']) . "<BR />\n";
        $arena_log = $tick_log . $arena_log;
    }

    $Character->Data['last_arena_time'] = date('Y-m-d H:i:s');
    $Character->Data['last_arena_log'] =
        "<strong>Catch-up: processed {$ticks} arena tick(s):</strong><BR />\n" . $arena_log;
}
