<?php

declare(strict_types=1);

/**
 * Collect guild tax on resource income for a registered (non-guest) user.
 *
 * Looks up the user's guild and tax rate, calculates the tax, deposits it
 * into guild_bank, and returns the amount taken so the caller can deduct it
 * from the player's earned income.
 *
 * Guests are never in guilds, so this should not be called from guest_processing.php.
 *
 * @param int    $user_id   The user earning resources
 * @param ?int   $season_id The active season ID (null = perpetual)
 * @param string $resource  Resource type: gold | iron | herbs | gems
 * @param int    $amount    Gross amount earned before tax
 * @return int              Amount taxed (0 when not in a guild or rate is 0)
 */
function collectGuildTax(int $user_id, ?int $season_id, string $resource, int $amount): int
{
    $valid_resources = ['gold', 'iron', 'herbs', 'gems'];
    if ($amount <= 0 || !in_array($resource, $valid_resources, true)) {
        return 0;
    }

    global $DAL;

    $query = "SELECT g.id AS guild_id, g.tax_rate
              FROM guilds g
              JOIN guild_members gm ON gm.guild_id = g.id
              WHERE gm.user_id = :user_id AND g.season_id <=> :season_id
              LIMIT 1";
    $result = $DAL->r($query, [':user_id' => $user_id, ':season_id' => $season_id]);

    if (!$result || empty($result)) {
        return 0;
    }

    $tax_rate = (int)$result[0]['tax_rate'];
    if ($tax_rate <= 0) {
        return 0;
    }

    $tax_amount = (int)floor($amount * $tax_rate / 100);
    if ($tax_amount <= 0) {
        return 0;
    }

    $guild_id = (int)$result[0]['guild_id'];

    // Upsert: guild_bank row is created when the guild is created, but guard with ON DUPLICATE KEY
    $DAL->w(
        "INSERT INTO guild_bank (guild_id, `{$resource}`)
         VALUES (:guild_id, :amount)
         ON DUPLICATE KEY UPDATE `{$resource}` = `{$resource}` + :amount2",
        [':guild_id' => $guild_id, ':amount' => $tax_amount, ':amount2' => $tax_amount]
    );

    return $tax_amount;
}
