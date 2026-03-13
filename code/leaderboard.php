<?php

declare(strict_types=1);

// Determine which leaderboard pool to show: perpetual (null) or active season (int)
$lb_mode      = $_GET['mode'] ?? 'perpetual';
$Season       = new Season();
$active_season = $Season->GetActiveSeason();

$lb_season_id = null; // perpetual by default
if ($lb_mode === 'season' && $active_season) {
    $lb_season_id = (int)$active_season['id'];
}

// Fetch leaderboard data for combined stats
$combined_stats_query = "
    SELECT
        c.name,
        c.user_id,
        JSON_UNQUOTE(JSON_EXTRACT(c.party_json, '$.members.frontline.strength')) as fl_str,
        JSON_UNQUOTE(JSON_EXTRACT(c.party_json, '$.members.frontline.dexterity')) as fl_dex,
        JSON_UNQUOTE(JSON_EXTRACT(c.party_json, '$.members.frontline.health')) as fl_hp,
        JSON_UNQUOTE(JSON_EXTRACT(c.party_json, '$.members.frontline.wisdom')) as fl_wis,
        JSON_UNQUOTE(JSON_EXTRACT(c.party_json, '$.members.backline.strength')) as bl_str,
        JSON_UNQUOTE(JSON_EXTRACT(c.party_json, '$.members.backline.dexterity')) as bl_dex,
        JSON_UNQUOTE(JSON_EXTRACT(c.party_json, '$.members.backline.health')) as bl_hp,
        JSON_UNQUOTE(JSON_EXTRACT(c.party_json, '$.members.backline.wisdom')) as bl_wis,
        (
            CAST(JSON_UNQUOTE(JSON_EXTRACT(c.party_json, '$.members.frontline.strength')) AS UNSIGNED) +
            CAST(JSON_UNQUOTE(JSON_EXTRACT(c.party_json, '$.members.frontline.dexterity')) AS UNSIGNED) +
            CAST(JSON_UNQUOTE(JSON_EXTRACT(c.party_json, '$.members.frontline.health')) AS UNSIGNED) +
            CAST(JSON_UNQUOTE(JSON_EXTRACT(c.party_json, '$.members.frontline.wisdom')) AS UNSIGNED) +
            CAST(JSON_UNQUOTE(JSON_EXTRACT(c.party_json, '$.members.backline.strength')) AS UNSIGNED) +
            CAST(JSON_UNQUOTE(JSON_EXTRACT(c.party_json, '$.members.backline.dexterity')) AS UNSIGNED) +
            CAST(JSON_UNQUOTE(JSON_EXTRACT(c.party_json, '$.members.backline.health')) AS UNSIGNED) +
            CAST(JSON_UNQUOTE(JSON_EXTRACT(c.party_json, '$.members.backline.wisdom')) AS UNSIGNED)
        ) as total_stats
    FROM characters c
    WHERE c.party_json IS NOT NULL AND c.season_id <=> :season_id
    ORDER BY total_stats DESC
    LIMIT 100
";
$combined_stats_leaderboard = $DAL->r($combined_stats_query, ['season_id' => $lb_season_id]);

// Fetch leaderboard data for individual stats (highest single stat across both party members)
$strength_query = "
    SELECT
        c.name,
        c.user_id,
        GREATEST(
            CAST(JSON_UNQUOTE(JSON_EXTRACT(c.party_json, '$.members.frontline.strength')) AS UNSIGNED),
            CAST(JSON_UNQUOTE(JSON_EXTRACT(c.party_json, '$.members.backline.strength')) AS UNSIGNED)
        ) as max_strength
    FROM characters c
    WHERE c.party_json IS NOT NULL AND c.season_id <=> :season_id
    ORDER BY max_strength DESC
    LIMIT 100
";
$strength_leaderboard = $DAL->r($strength_query, ['season_id' => $lb_season_id]);

$dexterity_query = "
    SELECT
        c.name,
        c.user_id,
        GREATEST(
            CAST(JSON_UNQUOTE(JSON_EXTRACT(c.party_json, '$.members.frontline.dexterity')) AS UNSIGNED),
            CAST(JSON_UNQUOTE(JSON_EXTRACT(c.party_json, '$.members.backline.dexterity')) AS UNSIGNED)
        ) as max_dexterity
    FROM characters c
    WHERE c.party_json IS NOT NULL AND c.season_id <=> :season_id
    ORDER BY max_dexterity DESC
    LIMIT 100
";
$dexterity_leaderboard = $DAL->r($dexterity_query, ['season_id' => $lb_season_id]);

$health_query = "
    SELECT
        c.name,
        c.user_id,
        GREATEST(
            CAST(JSON_UNQUOTE(JSON_EXTRACT(c.party_json, '$.members.frontline.health')) AS UNSIGNED),
            CAST(JSON_UNQUOTE(JSON_EXTRACT(c.party_json, '$.members.backline.health')) AS UNSIGNED)
        ) as max_health
    FROM characters c
    WHERE c.party_json IS NOT NULL AND c.season_id <=> :season_id
    ORDER BY max_health DESC
    LIMIT 100
";
$health_leaderboard = $DAL->r($health_query, ['season_id' => $lb_season_id]);

$wisdom_query = "
    SELECT
        c.name,
        c.user_id,
        GREATEST(
            CAST(JSON_UNQUOTE(JSON_EXTRACT(c.party_json, '$.members.frontline.wisdom')) AS UNSIGNED),
            CAST(JSON_UNQUOTE(JSON_EXTRACT(c.party_json, '$.members.backline.wisdom')) AS UNSIGNED)
        ) as max_wisdom
    FROM characters c
    WHERE c.party_json IS NOT NULL AND c.season_id <=> :season_id
    ORDER BY max_wisdom DESC
    LIMIT 100
";
$wisdom_leaderboard = $DAL->r($wisdom_query, ['season_id' => $lb_season_id]);

// Fetch leaderboard data for highest arena floor
$arena_query = "
    SELECT
        c.name,
        c.user_id,
        c.arena_floor
    FROM characters c
    WHERE c.arena_floor IS NOT NULL AND c.arena_floor > 0 AND c.season_id <=> :season_id
    ORDER BY c.arena_floor DESC
    LIMIT 100
";
$arena_leaderboard = $DAL->r($arena_query, ['season_id' => $lb_season_id]);

// Fetch leaderboard data for highest completed rift
$rift_query = "
    SELECT
        c.name,
        c.user_id,
        c.highest_rift_level
    FROM characters c
    WHERE c.highest_rift_level IS NOT NULL AND c.highest_rift_level > 0 AND c.season_id <=> :season_id
    ORDER BY c.highest_rift_level DESC
    LIMIT 100
";
$rift_leaderboard = $DAL->r($rift_query, ['season_id' => $lb_season_id]);

// Get current user's character for highlighting
$current_user_id = $_SESSION['auth_user_id'] ?? 0;
