<?php

declare(strict_types=1);

$alert_success = '';
$alert_danger = '';

$character_id     = (int)$Character->Data['id'];
$user_id          = (int)$_SESSION['auth_user_id'];
$market_season_id = isset($Character->Data['season_id']) ? (int)$Character->Data['season_id'] : null;
$is_season_character = $market_season_id !== null;
$valid_resources  = $is_season_character
    ? ['herbs', 'iron', 'gems', 'lucky_wyrdstone']
    : ['herbs', 'iron', 'gems', 'lucky_wyrdstone', 'credits'];
$valid_item_types = ['gear', 'rift_stone', 'potion'];
$item_table_map   = ['gear' => 'gear', 'rift_stone' => 'rifts', 'potion' => 'potions'];

$active_tab       = $_GET['tab'] ?? 'orders';
$resource_filter  = $_GET['resource'] ?? 'herbs';
$item_type_filter = $_GET['item_type'] ?? 'gear';
$affix_filter     = (array)($_GET['affix'] ?? []);

if (!in_array($resource_filter, $valid_resources, true)) {
    $resource_filter = 'herbs';
}
if (!in_array($item_type_filter, $valid_item_types, true)) {
    $item_type_filter = 'gear';
}
$valid_affixes = array_keys(Gear::getAffixDefinitions());
$affix_filter  = array_values(array_filter($affix_filter, fn($a) => in_array($a, $valid_affixes, true)));
$level_min     = isset($_GET['level_min']) && $_GET['level_min'] !== '' ? max(0, (int)$_GET['level_min']) : null;
$level_max     = isset($_GET['level_max']) && $_GET['level_max'] !== '' ? max(0, (int)$_GET['level_max']) : null;
$resource_label = static fn (string $resource): string => t('res.' . strtolower($resource));

$get_resource_amount = static function (array $character_data, string $resource): int {
    if ($resource === 'lucky_wyrdstone') {
        return (int)($character_data['inventory_json']['special_resources']['lucky_wyrdstone'] ?? 0);
    }

    return (int)($character_data[$resource] ?? 0);
};

$set_resource_amount = static function (array &$character_data, string $resource, int $amount): void {
    if ($resource === 'lucky_wyrdstone') {
        if (!isset($character_data['inventory_json']) || !is_array($character_data['inventory_json'])) {
            $character_data['inventory_json'] = [];
        }
        if (!isset($character_data['inventory_json']['special_resources']) || !is_array($character_data['inventory_json']['special_resources'])) {
            $character_data['inventory_json']['special_resources'] = [];
        }
        $character_data['inventory_json']['special_resources']['lucky_wyrdstone'] = max(0, $amount);
        return;
    }

    $character_data[$resource] = max(0, $amount);
};

$add_resource_to_character_by_id = static function (int $target_character_id, string $resource, int $amount) use ($DAL): void {
    if ($amount <= 0) {
        return;
    }

    if ($resource !== 'lucky_wyrdstone') {
        $col_map = ['herbs' => 'herbs', 'iron' => 'iron', 'gems' => 'gems', 'credits' => 'credits', 'gold' => 'gold'];
        $column = $col_map[$resource] ?? null;

        if ($column === null) {
            return;
        }

        $DAL->w(
            "UPDATE characters SET {$column} = {$column} + :amt WHERE id = :cid",
            ['amt' => $amount, 'cid' => $target_character_id]
        );
        return;
    }

    $TargetCharacter = new Character();
    if (!$TargetCharacter->LoadById($target_character_id)) {
        return;
    }

    $current_amount = (int)($TargetCharacter->Data['inventory_json']['special_resources']['lucky_wyrdstone'] ?? 0);
    $TargetCharacter->Data['inventory_json']['special_resources']['lucky_wyrdstone'] = $current_amount + $amount;
    $TargetCharacter->SaveByUserId((int)$TargetCharacter->Data['user_id']);
};

// CSRF validation for all POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf-token']) {
        die('CSRF token validation failed');
    }
}

// --- POST: Post a new order ---
if (isset($_POST['post_order'])) {
    $order_type    = $_POST['order_type'] ?? '';
    $resource      = $_POST['resource'] ?? '';
    $amount        = (int)$_POST['amount'];
    $price_per_unit = (int)$_POST['price_per_unit'];

    if (!in_array($order_type, ['buy', 'sell'], true)) {
        $alert_danger = t('market.alert.invalid_order_type');
    } elseif (!in_array($resource, $valid_resources, true)) {
        $alert_danger = t('market.alert.invalid_resource');
    } elseif ($amount <= 0) {
        $alert_danger = t('market.alert.amount_zero');
    } elseif ($price_per_unit <= 0) {
        $alert_danger = t('market.alert.price_zero');
    } elseif ($order_type === 'sell') {
        $player_resource = $get_resource_amount($Character->Data, $resource);
        if ($player_resource < $amount) {
            $alert_danger = t('market.alert.no_resource', ['resource' => $resource_label($resource), 'amount' => human_num($player_resource)]);
        } else {
            $set_resource_amount($Character->Data, $resource, $player_resource - $amount);
            $inserted = $DAL->w(
                "INSERT INTO market_orders (character_id, season_id, order_type, resource, amount, amount_remaining, price_per_unit)
                 VALUES (:cid, :season_id, 'sell', :resource, :amount, :amount2, :ppu)",
                ['cid' => $character_id, 'season_id' => $market_season_id, 'resource' => $resource, 'amount' => $amount, 'amount2' => $amount, 'ppu' => $price_per_unit]
            );
            if ($inserted) {
                $alert_success = t('market.alert.sell_posted', ['amount' => human_num($amount), 'resource' => $resource_label($resource), 'price' => human_num($price_per_unit)]);
                $active_tab = 'my_orders';
            } else {
                $set_resource_amount($Character->Data, $resource, $player_resource);
                $alert_danger = t('market.alert.post_fail');
            }
        }
    } else {
        // Buy order — escrow Gold
        $total_gold  = $amount * $price_per_unit;
        $player_gold = (int)$Character->Data['gold'];
        if ($player_gold < $total_gold) {
            $alert_danger = t('market.alert.no_gold', ['need' => human_num($total_gold), 'have' => human_num($player_gold)]);
        } else {
            $Character->Data['gold'] = $player_gold - $total_gold;
            $inserted = $DAL->w(
                "INSERT INTO market_orders (character_id, season_id, order_type, resource, amount, amount_remaining, price_per_unit)
                 VALUES (:cid, :season_id, 'buy', :resource, :amount, :amount2, :ppu)",
                ['cid' => $character_id, 'season_id' => $market_season_id, 'resource' => $resource, 'amount' => $amount, 'amount2' => $amount, 'ppu' => $price_per_unit]
            );
            if ($inserted) {
                $alert_success = t('market.alert.buy_posted', ['amount' => human_num($amount), 'resource' => $resource_label($resource), 'price' => human_num($price_per_unit)]);
                $active_tab = 'my_orders';
            } else {
                $Character->Data['gold'] = $player_gold;
                $alert_danger = t('market.alert.post_fail');
            }
        }
    }
}

// --- POST: Fill orders at a price point (FIFO across stacked orders) ---
if (isset($_POST['fill_order'])) {
    $fill_price      = (int)$_POST['fill_price'];
    $fill_amount_req = (int)$_POST['fill_amount'];
    $fill_order_type = $_POST['fill_order_type'] ?? '';
    $fill_resource   = $_POST['fill_resource'] ?? '';

    if ($fill_price <= 0 || $fill_amount_req <= 0) {
        $alert_danger = t('market.alert.invalid_fill');
    } elseif (!in_array($fill_order_type, ['sell', 'buy'], true)) {
        $alert_danger = t('market.alert.invalid_order_type');
    } elseif (!in_array($fill_resource, $valid_resources, true)) {
        $alert_danger = t('market.alert.invalid_resource');
    } else {
        // Fetch all open orders at this price point, oldest first (FIFO), excluding own, same market pool
        $orders_at_price = $DAL->r(
            "SELECT * FROM market_orders
             WHERE resource = :res AND order_type = :otype AND price_per_unit = :price
               AND status = 'open' AND character_id != :cid AND season_id <=> :season_id
             ORDER BY created_at ASC",
            ['res' => $fill_resource, 'otype' => $fill_order_type, 'price' => $fill_price, 'cid' => $character_id, 'season_id' => $market_season_id]
        ) ?: [];

        if (empty($orders_at_price)) {
            $alert_danger = t('market.alert.no_orders');
        } else {
            $total_available = (int)array_sum(array_column($orders_at_price, 'amount_remaining'));
            $actual_fill     = min($fill_amount_req, $total_available);

            if ($fill_order_type === 'sell') {
                // Player buys: pays Gold, receives resource
                $gold_needed = $actual_fill * $fill_price;
                $player_gold = (int)$Character->Data['gold'];
                if ($player_gold < $gold_needed) {
                    $alert_danger = t('market.alert.no_gold_buy', ['need' => human_num($gold_needed)]);
                } else {
                    $Character->Data['gold'] -= $gold_needed;
                    $filled_total = 0;

                    foreach ($orders_at_price as $order) {
                        if ($filled_total >= $actual_fill) {
                            break;
                        }
                        $order_season = isset($order['season_id']) ? (int)$order['season_id'] : null;
                        if ($order_season !== $market_season_id) {
                            continue;
                        }
                        $take = min($actual_fill - $filled_total, (int)$order['amount_remaining']);
                        $DAL->w(
                            "UPDATE market_orders
                             SET amount_remaining = amount_remaining - :amt1,
                                 status = IF(amount_remaining - :amt2 <= 0, 'filled', 'open')
                             WHERE id = :id AND status = 'open' AND amount_remaining >= :amt3
                               AND season_id <=> :season_id",
                            ['amt1' => $take, 'amt2' => $take, 'amt3' => $take, 'id' => (int)$order['id'], 'season_id' => $market_season_id]
                        );
                        if ($DAL->rows_affected() > 0) {
                            $filled_total += $take;
                            $set_resource_amount(
                                $Character->Data,
                                $fill_resource,
                                $get_resource_amount($Character->Data, $fill_resource) + $take
                            );
                            $add_resource_to_character_by_id((int)$order['character_id'], 'gold', $take * $fill_price);
                        }
                    }

                    // Refund gold for any portion that couldn't be filled
                    $unfilled = $actual_fill - $filled_total;
                    if ($unfilled > 0) {
                        $Character->Data['gold'] += $unfilled * $fill_price;
                    }

                    if ($filled_total > 0) {
                        $alert_success = t('market.alert.purchased', ['amount' => human_num($filled_total), 'resource' => $resource_label($fill_resource), 'total' => human_num($filled_total * $fill_price)]);
                    } else {
                        $Character->Data['gold'] += $gold_needed;
                        $alert_danger = t('market.alert.orders_gone');
                    }
                }
            } else {
                // Player sells: provides resource, receives Gold from escrow
                $player_resource = $get_resource_amount($Character->Data, $fill_resource);
                if ($player_resource < $actual_fill) {
                    $alert_danger = t('market.alert.no_resource_sell', ['resource' => $resource_label($fill_resource), 'amount' => human_num($player_resource)]);
                } else {
                    $set_resource_amount($Character->Data, $fill_resource, $player_resource - $actual_fill);
                    $filled_total = 0;

                    foreach ($orders_at_price as $order) {
                        if ($filled_total >= $actual_fill) {
                            break;
                        }
                        $order_season = isset($order['season_id']) ? (int)$order['season_id'] : null;
                        if ($order_season !== $market_season_id) {
                            continue;
                        }
                        $take = min($actual_fill - $filled_total, (int)$order['amount_remaining']);
                        $DAL->w(
                            "UPDATE market_orders
                             SET amount_remaining = amount_remaining - :amt1,
                                 status = IF(amount_remaining - :amt2 <= 0, 'filled', 'open')
                             WHERE id = :id AND status = 'open' AND amount_remaining >= :amt3
                               AND season_id <=> :season_id",
                            ['amt1' => $take, 'amt2' => $take, 'amt3' => $take, 'id' => (int)$order['id'], 'season_id' => $market_season_id]
                        );
                        if ($DAL->rows_affected() > 0) {
                            $filled_total += $take;
                            $Character->Data['gold'] = ((int)$Character->Data['gold']) + ($take * $fill_price);
                            $add_resource_to_character_by_id((int)$order['character_id'], $fill_resource, $take);
                        }
                    }

                    // Refund resource for any portion that couldn't be filled
                    $unfilled = $actual_fill - $filled_total;
                    if ($unfilled > 0) {
                        $set_resource_amount($Character->Data, $fill_resource, $get_resource_amount($Character->Data, $fill_resource) + $unfilled);
                    }

                    if ($filled_total > 0) {
                        $alert_success = t('market.alert.sold', ['amount' => human_num($filled_total), 'resource' => $resource_label($fill_resource), 'total' => human_num($filled_total * $fill_price)]);
                    } else {
                        $set_resource_amount($Character->Data, $fill_resource, $player_resource);
                        $alert_danger = t('market.alert.orders_gone');
                    }
                }
            }
        }
    }
}

// --- POST: Cancel own order ---
if (isset($_POST['cancel_order'])) {
    $order_id  = (int)$_POST['order_id'];
    $order_row = $DAL->r(
        "SELECT * FROM market_orders WHERE id = :id AND character_id = :cid AND status = 'open'",
        ['id' => $order_id, 'cid' => $character_id]
    );

    if (!$order_row) {
        $alert_danger = t('market.alert.order_not_found');
    } else {
        $order    = $order_row[0];
        $resource = $order['resource'];
        if (!in_array($resource, $valid_resources, true)) {
            $alert_danger = t('market.alert.invalid_order');
        } else {
            $DAL->w(
                "UPDATE market_orders SET status = 'cancelled' WHERE id = :id AND character_id = :cid",
                ['id' => $order_id, 'cid' => $character_id]
            );
            if ($DAL->rows_affected() > 0) {
                if ($order['order_type'] === 'sell') {
                    $set_resource_amount(
                        $Character->Data,
                        $resource,
                        $get_resource_amount($Character->Data, $resource) + (int)$order['amount_remaining']
                    );
                    $alert_success = t('market.alert.cancelled_sell', ['amount' => human_num((int)$order['amount_remaining']), 'resource' => $resource_label($resource)]);
                } else {
                    $refund = (int)$order['amount_remaining'] * (int)$order['price_per_unit'];
                    $Character->Data['gold'] = ((int)$Character->Data['gold']) + $refund;
                    $alert_success = t('market.alert.cancelled_buy', ['amount' => human_num($refund)]);
                }
            } else {
                $alert_danger = t('market.alert.cancel_fail');
            }
        }
    }
}

// --- POST: List an item for sale ---
if (isset($_POST['list_item'])) {
    $item_type  = $_POST['item_type'] ?? '';
    $item_id    = (int)$_POST['item_id'];
    $list_price = (int)$_POST['list_price'];

    if (!in_array($item_type, $valid_item_types, true)) {
        $alert_danger = t('market.alert.invalid_item_type');
    } elseif ($item_id <= 0) {
        $alert_danger = t('market.alert.invalid_item');
    } elseif ($list_price <= 0) {
        $alert_danger = t('market.alert.price_zero_list');
    } elseif ($item_type === 'gear' && in_array($item_id, array_filter([
        (int)($Character->Data['party_json']['members']['frontline']['equipped_weapon'] ?? 0),
        (int)($Character->Data['party_json']['members']['frontline']['equipped_armor'] ?? 0),
        (int)($Character->Data['party_json']['members']['backline']['equipped_weapon'] ?? 0),
        (int)($Character->Data['party_json']['members']['backline']['equipped_armor'] ?? 0),
    ]))) {
        $alert_danger = t('market.alert.equipped_gear');
    } else {
        $table = $item_table_map[$item_type];
        // Set season_id on the item when listing so it remains in the correct market pool
        $DAL->w(
            "UPDATE {$table} SET market_price = :price, season_id = :season_id WHERE id = :id AND owner_id = :uid AND (market_price = 0 OR market_price IS NULL)",
            ['price' => $list_price, 'season_id' => $market_season_id, 'id' => $item_id, 'uid' => $user_id]
        );
        if ($DAL->rows_affected() > 0) {
            $alert_success = t('market.alert.item_listed', ['price' => human_num($list_price)]);
            $active_tab = 'my_orders';
        } else {
            $alert_danger = t('market.alert.list_fail');
        }
    }
}

// --- POST: Unlist an item ---
if (isset($_POST['unlist_item'])) {
    $item_type = $_POST['item_type'] ?? '';
    $item_id   = (int)$_POST['item_id'];

    if (!in_array($item_type, $valid_item_types, true)) {
        $alert_danger = t('market.alert.invalid_item_type');
    } elseif ($item_id <= 0) {
        $alert_danger = t('market.alert.invalid_item');
    } else {
        $table = $item_table_map[$item_type];
        $DAL->w(
            "UPDATE {$table} SET market_price = 0 WHERE id = :id AND owner_id = :uid AND market_price > 0",
            ['id' => $item_id, 'uid' => $user_id]
        );
        if ($DAL->rows_affected() > 0) {
            $alert_success = t('market.alert.unlisted');
            $active_tab = 'my_orders';
        } else {
            $alert_danger = t('market.alert.unlist_fail');
        }
    }
}

// --- POST: Buy a listed item ---
if (isset($_POST['buy_item'])) {
    $item_type      = $_POST['item_type'] ?? '';
    $item_id        = (int)$_POST['item_id'];
    $expected_price = (int)$_POST['expected_price'];

    if (!in_array($item_type, $valid_item_types, true)) {
        $alert_danger = t('market.alert.invalid_item_type');
    } elseif ($item_id <= 0 || $expected_price <= 0) {
        $alert_danger = t('market.alert.invalid_buy_params');
    } else {
        $table    = $item_table_map[$item_type];
        $item_row = $DAL->r(
            "SELECT * FROM {$table} WHERE id = :id AND market_price = :price AND owner_id != :uid AND season_id <=> :season_id",
            ['id' => $item_id, 'price' => $expected_price, 'uid' => $user_id, 'season_id' => $market_season_id]
        );

        if (!$item_row) {
            $alert_danger = t('market.alert.not_available');
        } else {
            $item        = $item_row[0];
            $price       = (int)$item['market_price'];
            $seller_uid  = (int)$item['owner_id'];
            $item_season = isset($item['season_id']) ? (int)$item['season_id'] : null;

            if ($item_season !== $market_season_id) {
                $alert_danger = t('market.alert.wrong_league');
            } elseif ((int)$Character->Data['gold'] < $price) {
                $alert_danger = t('market.alert.no_gold_item', ['price' => human_num($price)]);
            } else {
                // Atomic ownership transfer — reset favorite on gear, enforce season on write
                $extra = ($item_type === 'gear') ? ', favorite = 0' : '';
                $DAL->w(
                    "UPDATE {$table} SET owner_id = :buyer, market_price = 0{$extra} WHERE id = :id AND owner_id = :seller AND market_price = :price AND season_id <=> :season_id",
                    ['buyer' => $user_id, 'id' => $item_id, 'seller' => $seller_uid, 'price' => $price, 'season_id' => $market_season_id]
                );
                if ($DAL->rows_affected() === 0) {
                    $alert_danger = t('market.alert.item_gone');
                } else {
                    $Character->Data['gold'] -= $price;
                    // Credit the seller's character in the same market pool (season or perpetual)
                    $DAL->w(
                        "UPDATE characters SET gold = gold + :gold WHERE user_id = :uid AND season_id <=> :season_id",
                        ['gold' => $price, 'uid' => $seller_uid, 'season_id' => $market_season_id]
                    );
                    // Resolve display name
                    if ($item_type === 'gear') {
                        $item_name = htmlspecialchars($item['name']);
                    } elseif ($item_type === 'rift_stone') {
                        $details   = json_decode($item['details'], true);
                        $item_name = htmlspecialchars($details['name'] ?? t('market.items.tab.rifts'));
                    } else {
                        $item_name = htmlspecialchars($item['name']);
                    }
                    $alert_success = t('market.alert.item_bought', ['name' => $item_name, 'price' => human_num($price)]);
                }
            }
        }
    }
}

// --- Load aggregated orders (other players, grouped by price point, same market pool) ---
$sell_orders_agg = $DAL->r(
    "SELECT price_per_unit, SUM(amount_remaining) AS total_remaining
     FROM market_orders
     WHERE resource = :res AND order_type = 'sell' AND status = 'open'
       AND character_id != :cid AND season_id <=> :season_id
     GROUP BY price_per_unit
     ORDER BY price_per_unit ASC
     LIMIT 50",
    ['res' => $resource_filter, 'cid' => $character_id, 'season_id' => $market_season_id]
) ?: [];

$buy_orders_agg = $DAL->r(
    "SELECT price_per_unit, SUM(amount_remaining) AS total_remaining
     FROM market_orders
     WHERE resource = :res AND order_type = 'buy' AND status = 'open'
       AND character_id != :cid AND season_id <=> :season_id
     GROUP BY price_per_unit
     ORDER BY price_per_unit DESC
     LIMIT 50",
    ['res' => $resource_filter, 'cid' => $character_id, 'season_id' => $market_season_id]
) ?: [];

// --- Load own orders for the current resource (aggregated by price point) ---
$sell_orders_own = $DAL->r(
    "SELECT price_per_unit, SUM(amount_remaining) AS total_remaining
     FROM market_orders
     WHERE resource = :res AND order_type = 'sell' AND status = 'open' AND character_id = :cid
     GROUP BY price_per_unit
     ORDER BY price_per_unit ASC",
    ['res' => $resource_filter, 'cid' => $character_id]
) ?: [];

$buy_orders_own = $DAL->r(
    "SELECT price_per_unit, SUM(amount_remaining) AS total_remaining
     FROM market_orders
     WHERE resource = :res AND order_type = 'buy' AND status = 'open' AND character_id = :cid
     GROUP BY price_per_unit
     ORDER BY price_per_unit DESC",
    ['res' => $resource_filter, 'cid' => $character_id]
) ?: [];

// --- Merge and sort for display ---
$sell_display = [];
foreach ($sell_orders_agg as $row) {
    $sell_display[] = ['is_own' => false, 'price_per_unit' => (int)$row['price_per_unit'], 'total_remaining' => (int)$row['total_remaining']];
}
foreach ($sell_orders_own as $row) {
    $sell_display[] = ['is_own' => true, 'price_per_unit' => (int)$row['price_per_unit'], 'total_remaining' => (int)$row['total_remaining']];
}
usort($sell_display, fn($a, $b) => $a['price_per_unit'] - $b['price_per_unit']);

$buy_display = [];
foreach ($buy_orders_agg as $row) {
    $buy_display[] = ['is_own' => false, 'price_per_unit' => (int)$row['price_per_unit'], 'total_remaining' => (int)$row['total_remaining']];
}
foreach ($buy_orders_own as $row) {
    $buy_display[] = ['is_own' => true, 'price_per_unit' => (int)$row['price_per_unit'], 'total_remaining' => (int)$row['total_remaining']];
}
usort($buy_display, fn($a, $b) => $b['price_per_unit'] - $a['price_per_unit']);

// --- Load my open orders (all resources, for My Orders tab + badge) ---
$my_orders = $DAL->r(
    "SELECT * FROM market_orders WHERE character_id = :cid AND status = 'open' ORDER BY created_at DESC",
    ['cid' => $character_id]
) ?: [];

// Badge count: resource orders + gear/rift item listings
$item_badge_raw = $DAL->r(
    "SELECT
        (SELECT COUNT(*) FROM gear WHERE owner_id = :uid1 AND market_price > 0) +
        (SELECT COUNT(*) FROM rifts WHERE owner_id = :uid2 AND market_price > 0) AS item_total",
    ['uid1' => $user_id, 'uid2' => $user_id]
);
$my_listings_badge = count($my_orders) + ($item_badge_raw ? (int)$item_badge_raw[0]['item_total'] : 0);

// --- Item definitions (shared across Items + My Listings tabs) ---
$gear_affix_defs    = [];
$rift_implicit_defs = [];
$rift_affix_defs    = [];
$potion_prefix_defs = [];
$potion_suffix_defs = [];

if (in_array($active_tab, ['items', 'my_orders', 'list_item'])) {
    $gear_affix_defs    = Gear::getAffixDefinitions();
    $rift_implicit_defs = RiftStone::getImplicitDefinitions();
    $rift_affix_defs    = RiftStone::getAffixDefinitions();
    $potion_prefix_defs = Potion::getPrefixDefinitions();
    $potion_suffix_defs = Potion::getSuffixDefinitions();
}

// --- Load listed items for browse (Items tab) ---
$listed_items = [];
if ($active_tab === 'items') {
    if ($item_type_filter === 'gear') {
        $gear_params = ['season_id' => $market_season_id];
        $gear_affix_clause = '';
        foreach ($affix_filter as $i => $affix) {
            $param_key = 'affix_' . $i;
            $gear_affix_clause .= " AND JSON_CONTAINS(JSON_EXTRACT(details, '$.affixes[*].key'), JSON_QUOTE(:{$param_key}))";
            $gear_params[$param_key] = $affix;
        }
        if ($level_min !== null) {
            $gear_affix_clause .= " AND CAST(JSON_EXTRACT(details, '$.party_level_at_craft') AS UNSIGNED) >= :level_min";
            $gear_params['level_min'] = $level_min;
        }
        if ($level_max !== null) {
            $gear_affix_clause .= " AND CAST(JSON_EXTRACT(details, '$.party_level_at_craft') AS UNSIGNED) <= :level_max";
            $gear_params['level_max'] = $level_max;
        }
        $raw = $DAL->r("SELECT * FROM gear WHERE market_price > 0 AND season_id <=> :season_id{$gear_affix_clause} ORDER BY market_price ASC LIMIT 50", $gear_params) ?: [];
        foreach ($raw as $r) {
            $item                = json_decode($r['details'], true) ?? [];
            $item['id']          = (int)$r['id'];
            $item['name']        = $r['name'];
            $item['market_price'] = (int)$r['market_price'];
            $item['owner_id']    = (int)$r['owner_id'];
            $item['is_own']      = ((int)$r['owner_id'] === $user_id);
            $listed_items[]      = $item;
        }
    } elseif ($item_type_filter === 'rift_stone') {
        $rift_params = ['season_id' => $market_season_id];
        $rift_level_clause = '';
        if ($level_min !== null) {
            $rift_level_clause .= " AND CAST(JSON_EXTRACT(details, '$.level') AS UNSIGNED) >= :level_min";
            $rift_params['level_min'] = $level_min;
        }
        if ($level_max !== null) {
            $rift_level_clause .= " AND CAST(JSON_EXTRACT(details, '$.level') AS UNSIGNED) <= :level_max";
            $rift_params['level_max'] = $level_max;
        }
        $raw = $DAL->r("SELECT * FROM rifts WHERE market_price > 0 AND season_id <=> :season_id{$rift_level_clause} ORDER BY market_price ASC LIMIT 50", $rift_params) ?: [];
        foreach ($raw as $r) {
            $item                = json_decode($r['details'], true) ?? [];
            $item['id']          = (int)$r['id'];
            $item['market_price'] = (int)$r['market_price'];
            $item['owner_id']    = (int)$r['owner_id'];
            $item['is_own']      = ((int)$r['owner_id'] === $user_id);
            $listed_items[]      = $item;
        }
    } else {
        $potion_params = ['season_id' => $market_season_id];
        $potion_level_clause = '';
        if ($level_min !== null) {
            $potion_level_clause .= ' AND level >= :level_min';
            $potion_params['level_min'] = $level_min;
        }
        if ($level_max !== null) {
            $potion_level_clause .= ' AND level <= :level_max';
            $potion_params['level_max'] = $level_max;
        }
        $raw = $DAL->r("SELECT * FROM potions WHERE market_price > 0 AND season_id <=> :season_id{$potion_level_clause} ORDER BY market_price ASC LIMIT 50", $potion_params) ?: [];
        foreach ($raw as $r) {
            $listed_items[] = [
                'id'           => (int)$r['id'],
                'name'         => $r['name'],
                'prefix'       => $r['prefix'],
                'suffix'       => $r['suffix'],
                'level'        => (int)$r['level'],
                'market_price' => (int)$r['market_price'],
                'owner_id'     => (int)$r['owner_id'],
                'is_own'       => ((int)$r['owner_id'] === $user_id),
            ];
        }
    }
}

// --- Load own active item listings (My Listings tab) ---
$my_listed_gear    = [];
$my_listed_rifts   = [];
$my_listed_potions = [];

if ($active_tab === 'my_orders') {
    $raw = $DAL->r("SELECT * FROM gear WHERE owner_id = :uid AND market_price > 0 ORDER BY market_price ASC", ['uid' => $user_id]) ?: [];
    foreach ($raw as $r) {
        $item                 = json_decode($r['details'], true) ?? [];
        $item['id']           = (int)$r['id'];
        $item['name']         = $r['name'];
        $item['market_price'] = (int)$r['market_price'];
        $my_listed_gear[]     = $item;
    }

    $raw = $DAL->r("SELECT * FROM rifts WHERE owner_id = :uid AND market_price > 0 ORDER BY market_price ASC", ['uid' => $user_id]) ?: [];
    foreach ($raw as $r) {
        $item                 = json_decode($r['details'], true) ?? [];
        $item['id']           = (int)$r['id'];
        $item['market_price'] = (int)$r['market_price'];
        $my_listed_rifts[]    = $item;
    }

    $raw = $DAL->r("SELECT * FROM potions WHERE owner_id = :uid AND market_price > 0 ORDER BY market_price ASC", ['uid' => $user_id]) ?: [];
    foreach ($raw as $r) {
        $my_listed_potions[] = [
            'id'           => (int)$r['id'],
            'name'         => $r['name'],
            'prefix'       => $r['prefix'],
            'suffix'       => $r['suffix'],
            'level'        => (int)$r['level'],
            'market_price' => (int)$r['market_price'],
        ];
    }
}

// --- Load unlisted inventory items (List Item tab) ---
$my_unlisted_gear    = [];
$my_unlisted_rifts   = [];
$my_unlisted_potions = [];

if ($active_tab === 'list_item') {
    $equipped_ids = array_filter([
        (int)($Character->Data['party_json']['members']['frontline']['equipped_weapon'] ?? 0),
        (int)($Character->Data['party_json']['members']['frontline']['equipped_armor'] ?? 0),
        (int)($Character->Data['party_json']['members']['backline']['equipped_weapon'] ?? 0),
        (int)($Character->Data['party_json']['members']['backline']['equipped_armor'] ?? 0),
    ]);

    $raw = $DAL->r(
        "SELECT * FROM gear WHERE owner_id = :uid AND (market_price = 0 OR market_price IS NULL) ORDER BY created_at DESC LIMIT 50",
        ['uid' => $user_id]
    ) ?: [];
    foreach ($raw as $r) {
        if (in_array((int)$r['id'], $equipped_ids, true)) {
            continue;
        }
        $item         = json_decode($r['details'], true) ?? [];
        $item['id']   = (int)$r['id'];
        $item['name'] = $r['name'];
        $my_unlisted_gear[] = $item;
    }

    $raw = $DAL->r(
        "SELECT * FROM rifts WHERE owner_id = :uid AND (market_price = 0 OR market_price IS NULL) AND queue_position IS NULL ORDER BY created_at DESC LIMIT 50",
        ['uid' => $user_id]
    ) ?: [];
    foreach ($raw as $r) {
        $item       = json_decode($r['details'], true) ?? [];
        $item['id'] = (int)$r['id'];
        $my_unlisted_rifts[] = $item;
    }

    $raw = $DAL->r(
        "SELECT * FROM potions WHERE owner_id = :uid AND (market_price = 0 OR market_price IS NULL) ORDER BY created_at DESC LIMIT 50",
        ['uid' => $user_id]
    ) ?: [];
    foreach ($raw as $r) {
        $my_unlisted_potions[] = [
            'id'     => (int)$r['id'],
            'name'   => $r['name'],
            'prefix' => $r['prefix'],
            'suffix' => $r['suffix'],
            'level'  => (int)$r['level'],
        ];
    }
}
