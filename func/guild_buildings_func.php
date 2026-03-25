<?php

declare(strict_types=1);

/**
 * Return the guild building bonus levels for a user's guild.
 * Each value is the raw upgrade count (1 count = 1% bonus).
 * Returns all zeros when the user is not in a guild or has no buildings row.
 *
 * @return array<string, int>
 */
/**
 * Get or assign the random resource required for the next upgrade of a guild building.
 * The assignment is persisted in Redis so refreshing the page cannot change it.
 * The key is deleted by the controller after a successful upgrade so the next
 * upgrade draws a fresh random resource.
 */
function getOrAssignBuildingResource(int $guild_id, string $building): string
{
    global $redis;
    $key      = "guild_building_resource:{$guild_id}:{$building}";
    $existing = $redis->get($key);
    if ($existing !== false && $existing !== null && $existing !== '') {
        return (string)$existing;
    }
    $resources = ['gold', 'iron', 'herbs', 'gems'];
    $resource  = $resources[array_rand($resources)];
    $redis->setex($key, 604800, $resource); // 7-day TTL
    return $resource;
}

function getGuildBuildingBonuses(int $user_id, ?int $season_id): array
{
    $defaults = ['farm' => 0, 'iron_mine' => 0, 'gem_mine' => 0, 'market' => 0, 'gym' => 0, 'tavern' => 0];

    if ($user_id <= 0) {
        return $defaults;
    }

    global $DAL;

    $result = $DAL->r(
        "SELECT gb.farm, gb.iron_mine, gb.gem_mine, gb.market, gb.gym, gb.tavern
         FROM guild_buildings gb
         JOIN guild_members gm ON gm.guild_id = gb.guild_id
         JOIN guilds g ON g.id = gm.guild_id
         WHERE gm.user_id = :user_id AND g.season_id <=> :season_id
         LIMIT 1",
        [':user_id' => $user_id, ':season_id' => $season_id]
    );

    if (!$result || empty($result)) {
        return $defaults;
    }

    return [
        'farm'      => (int)$result[0]['farm'],
        'iron_mine' => (int)$result[0]['iron_mine'],
        'gem_mine'  => (int)$result[0]['gem_mine'],
        'market'    => (int)$result[0]['market'],
        'gym'       => (int)$result[0]['gym'],
        'tavern'    => (int)$result[0]['tavern'],
    ];
}
