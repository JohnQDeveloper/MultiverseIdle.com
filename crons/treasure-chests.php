<?php

declare(strict_types=1);

$time_start = microtime(true);
require_once('../config.php');

/**
 * PvP Queue Cron
 *
 * Runs once per hour. For each active user with a PvP reward queued at position 1,
 * finds a PvP opponent within +/-15% arena floor. Falls back to a mirror match
 * (clone of the player's own party) when no opponent is available.
 *
 * Treasure chests and wyrdstone nodes share the same queue and battle counts.
 * Each battle starts fully healed. Any loss in the sequence means no reward.
 * The queued entry is consumed either way.
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
    echo "Processing PvP queue for user_id: " . $r['user_id'] . "\n";

    $TreasureChest = new TreasureChest();
    $queued_chests = $TreasureChest->GetQueuedChestsByOwner($r['user_id']);

    if (empty($queued_chests)) {
        echo "  No queued entries.\n";
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
        echo "  No queue entry at position 1.\n";
        continue;
    }

    $Character = new Character();
    $Character->LoadById($r['id']);

    $arena_floor   = (int)$Character->Data['arena_floor'];
    $queue_type       = (string)($current_chest['queue_type'] ?? TreasureChest::ENTRY_TYPE_CHEST);
    $queue_size       = $TreasureChest->GetQueueEntryDisplaySize($current_chest);
    $storage_size     = (string)$current_chest['chest_size'];
    $battle_count     = TreasureChest::CHEST_BATTLES[$storage_size];
    $is_wyrdstone_node = $queue_type === TreasureChest::ENTRY_TYPE_WYRDSTONE_NODE;
    $reward_amount    = $is_wyrdstone_node
        ? TreasureChest::WYRDSTONE_NODE_REWARDS[$storage_size]
        : TreasureChest::REWARD_BASE * $battle_count;

    $entry_label = $is_wyrdstone_node ? ucfirst($queue_size) . ' Wyrdstone Node' : ucfirst($queue_size) . ' Chest';

    echo "  Processing {$entry_label} ({$battle_count} battle(s), {$reward_amount} perfect-clear reward).\n";

    $pvp_log  = "<h3>PvP Queue Battle</h3>\n";
    if ($is_wyrdstone_node) {
        $pvp_log .= "<p><b>Node:</b> {$entry_label} &mdash; {$battle_count} battle(s), {$reward_amount} lucky wyrdstone only if you win every battle</p>\n";
    } else {
        $pvp_log .= "<p><b>Chest:</b> {$entry_label} &mdash; {$battle_count} battle(s), {$reward_amount} of a random resource only if you win every battle</p>\n";
    }

    $Battle = new Battle();
    $pvp_wins = 0;
    $all_battles_won = true;
    $reward_resource = null;

    for ($battle_number = 1; $battle_number <= $battle_count; $battle_number++) {
        $pvp_log .= "<hr />\n";
        $pvp_log .= "<h4>Battle {$battle_number} of {$battle_count}</h4>\n";
        if ($battle_number > 1) {
            $pvp_log .= "<p><em>Your party was fully healed before this battle.</em></p>\n";
        }

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
            echo "  Battle {$battle_number}: no opponent found — mirror match.\n";
        } else {
            $opponent_party = $opponent['party_json'];
            $opponent_name  = htmlspecialchars((string)($opponent['name'] ?? 'Unknown'));
            $pvp_log .= "<p><b>Opponent:</b> {$opponent_name} (Arena Floor: " . (int)$opponent['arena_floor'] . ")</p>\n";
            echo "  Battle {$battle_number}: opponent found: user_id " . $opponent['user_id'] . "\n";
        }

        $battle_result = $Battle->Battle(
            $Character->Data['party_json'],
            $opponent_party,
            false // suppress echo
        );

        if ($battle_result['player_won']) {
            $pvp_wins++;

            $pvp_log .= "<span class='success'>Victory! You defeated {$opponent_name}!</span><BR />\n";
            $pvp_log .= "<span class='success'>Battle {$battle_number} cleared.</span><BR />\n";
            echo "  Battle {$battle_number}: victory.\n";
        } else {
            $all_battles_won = false;
            $pvp_log .= "<span class='danger'>Defeat! {$opponent_name} was stronger this time.</span><BR />\n";
            $pvp_log .= "<p>The queue reward is lost because this entry must be cleared perfectly.</p>\n";
            echo "  Battle {$battle_number}: defeat — queue reward failed.\n";
        }

        if (!empty($battle_result['log'])) {
            $pvp_log .= "<details><summary>Battle {$battle_number} Log</summary>\n";
            $pvp_log .= implode("<BR />\n", $battle_result['log']);
            $pvp_log .= "</details>\n";
        }
    }

    if ($all_battles_won) {
        $pvp_log .= "<p><b>Total victories:</b> {$pvp_wins} / {$battle_count}</p>\n";

        if ($is_wyrdstone_node) {
            $Character->Data['inventory_json']['special_resources']['lucky_wyrdstone'] =
                (int)($Character->Data['inventory_json']['special_resources']['lucky_wyrdstone'] ?? 0) + $reward_amount;
            $pvp_log .= "<span class='success'>Perfect clear! You earned {$reward_amount} lucky wyrdstone from the {$entry_label}.</span><BR />\n";
            echo "  Node cleared perfectly — awarded {$reward_amount} lucky wyrdstone.\n";
        } else {
            $resources = ['gold', 'iron', 'herbs', 'gems'];
            $reward_resource = $resources[array_rand($resources)];
            $Character->Data[$reward_resource] += $reward_amount;
            $pvp_log .= "<span class='success'>Perfect clear! You earned {$reward_amount} {$reward_resource} from the {$entry_label}.</span><BR />\n";
            echo "  Chest cleared perfectly — awarded {$reward_amount} {$reward_resource}.\n";
        }
    } else {
        $pvp_log .= "<p><b>Total victories:</b> {$pvp_wins} / {$battle_count}</p>\n";
        $pvp_log .= "<p>No queue reward earned.</p>\n";
        echo "  Queue entry not cleared perfectly — no reward.\n";
    }

    // Consume the queue entry and save
    $TreasureChest->RemoveChest((int)$current_chest['id'], $r['user_id']);

    $Character->Data['last_pvp_time'] = date('Y-m-d H:i:s');
    $Character->Data['last_pvp_log']  = $pvp_log;
    $Character->SaveByUserId($r['user_id']);

    // Increment guild quest progress for PvP wins
    if ($all_battles_won) {
        $GuildQuestsPvp = new GuildQuests();
        $GuildQuestsPvp->setSeasonId($Character->Data['season_id'] ?? null);
        $pvp_guild_id = (new Guild())->GetUserGuildId($r['user_id']);
        if ($pvp_guild_id !== null) {
            $GuildQuestsPvp->IncrementProgress($pvp_guild_id, 'pvp_wins', $pvp_wins);
        }
    }

    echo "  Saved PvP results for user_id: " . $r['user_id'] . "\n";
}

$time_end = microtime(true);
echo "PvP queue cron execution time: " . ($time_end - $time_start) . " seconds\n";
