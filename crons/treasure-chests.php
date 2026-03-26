<?php

declare(strict_types=1);

$time_start = microtime(true);
require_once('../config.php');

/**
 * Treasure Chests PvP Cron
 *
 * Runs once per hour. For each active user with a chest queued at position 1,
 * finds a PvP opponent within +/-15% arena floor. Falls back to a mirror match
 * (clone of the player's own party) when no opponent is available.
 *
 * Win: 5000 * X of a random resource (X = 1/2/3 for small/medium/large).
 * Loss: no reward. The chest is consumed either way.
 */

// Dedup: run at most once per hour
$hour_window = date('Y-m-d_H');
$dedup_key   = 'treasure_chests_ran_' . $hour_window;
if ($redis->exists($dedup_key)) {
    echo "Treasure chests cron already ran this hour, skipping.\n";
    return;
}
$redis->setex($dedup_key, 3600, '1');

$row = ActiveUsers();

foreach ($row as $r) {
    echo "Processing treasure chests for user_id: " . $r['user_id'] . "\n";

    $TreasureChest = new TreasureChest();
    $queued_chests = $TreasureChest->GetQueuedChestsByOwner($r['user_id']);

    if (empty($queued_chests)) {
        echo "  No queued chests.\n";
        continue;
    }

    // Only process queue position 1
    $current_chest = null;
    foreach ($queued_chests as $chest) {
        if ((int)$chest['queue_position'] === 1) {
            $current_chest = $chest;
            break;
        }
    }

    if ($current_chest === null) {
        echo "  No chest at position 1.\n";
        continue;
    }

    $Character = new Character();
    $Character->LoadById($r['id']);

    $arena_floor   = (int)$Character->Data['arena_floor'];
    $chest_size    = $current_chest['chest_size'];
    $multiplier    = TreasureChest::CHEST_MULTIPLIERS[$chest_size];
    $reward_amount = TreasureChest::REWARD_BASE * $multiplier;

    echo "  Processing {$chest_size} chest (reward: {$reward_amount}).\n";

    $pvp_log  = "<h3>PvP Treasure Chest Battle</h3>\n";
    $pvp_log .= "<p><b>Chest:</b> " . ucfirst($chest_size) . " &mdash; reward on win: {$reward_amount} of a random resource</p>\n";

    // Find opponent or fall back to mirror
    $opponent = $TreasureChest->FindOpponent(
        $arena_floor,
        $r['user_id'],
        $Character->Data['season_id'] ?? null
    );

    if ($opponent === null) {
        // Mirror match: fight a clone of yourself
        $opponent_party = $Character->Data['party_json'];
        $opponent_name  = $Character->Data['name'] . ' (Mirror)';
        $pvp_log .= "<p><em>No opponent found within range &mdash; fighting a mirror clone of yourself!</em></p>\n";
        echo "  No opponent found — mirror match.\n";
    } else {
        $opponent_party = $opponent['party_json'];
        $opponent_name  = htmlspecialchars((string)($opponent['name'] ?? 'Unknown'));
        $pvp_log .= "<p><b>Opponent:</b> {$opponent_name} (Arena Floor: " . (int)$opponent['arena_floor'] . ")</p>\n";
        echo "  Opponent found: user_id " . $opponent['user_id'] . "\n";
    }

    $pvp_log .= "<hr />\n";

    // Run the battle
    $Battle       = new Battle();
    $battle_result = $Battle->Battle(
        $Character->Data['party_json'],
        $opponent_party,
        false // suppress echo
    );

    if ($battle_result['player_won']) {
        $resources = ['gold', 'iron', 'herbs', 'gems'];
        $resource  = $resources[array_rand($resources)];

        $Character->Data[$resource] += $reward_amount;

        $pvp_log .= "<span class='success'>Victory! You defeated {$opponent_name}!</span><BR />\n";
        $pvp_log .= "<span class='success'>You earned {$reward_amount} {$resource} from the " . ucfirst($chest_size) . " Chest!</span><BR />\n";
        echo "  Victory — awarded {$reward_amount} {$resource}.\n";
    } else {
        $pvp_log .= "<span class='danger'>Defeat! {$opponent_name} was stronger this time.</span><BR />\n";
        $pvp_log .= "<p>No reward — better luck next time.</p>\n";
        echo "  Defeat — no reward.\n";
    }

    if (!empty($battle_result['log'])) {
        $pvp_log .= "<details><summary>Battle Log</summary>\n";
        $pvp_log .= implode("<BR />\n", $battle_result['log']);
        $pvp_log .= "</details>\n";
    }

    // Consume the chest and save
    $TreasureChest->RemoveChest((int)$current_chest['id'], $r['user_id']);

    $Character->Data['last_pvp_time'] = date('Y-m-d H:i:s');
    $Character->Data['last_pvp_log']  = $pvp_log;
    $Character->SaveByUserId($r['user_id']);

    // Increment guild quest progress for PvP wins
    if ($battle_result['player_won']) {
        $GuildQuestsPvp = new GuildQuests();
        $GuildQuestsPvp->setSeasonId($Character->Data['season_id'] ?? null);
        $pvp_guild_id = (new Guild())->GetUserGuildId($r['user_id']);
        if ($pvp_guild_id !== null) {
            $GuildQuestsPvp->IncrementProgress($pvp_guild_id, 'pvp_wins');
        }
    }

    echo "  Saved PvP results for user_id: " . $r['user_id'] . "\n";
}

$time_end = microtime(true);
echo "Treasure chests cron execution time: " . ($time_end - $time_start) . " seconds\n";
