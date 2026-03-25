<?php

declare(strict_types=1);

$alert_success = '';
$alert_danger = '';

$character_id     = (int)$Character->Data['id'];
$user_id          = (int)$_SESSION['auth_user_id'];
$market_season_id = isset($Character->Data['season_id']) ? (int)$Character->Data['season_id'] : null;
$is_season_character = $market_season_id !== null;
$valid_resources  = $is_season_character
    ? ['herbs', 'iron', 'gems']
    : ['herbs', 'iron', 'gems', 'credits'];
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
        $alert_danger = 'Invalid order type.';
    } elseif (!in_array($resource, $valid_resources, true)) {
        $alert_danger = 'Invalid resource.';
    } elseif ($amount <= 0) {
        $alert_danger = 'Amount must be greater than 0.';
    } elseif ($price_per_unit <= 0) {
        $alert_danger = 'Price per unit must be greater than 0.';
    } elseif ($order_type === 'sell') {
        $player_resource = (int)($Character->Data[$resource] ?? 0);
        if ($player_resource < $amount) {
            $alert_danger = 'Not enough ' . ucfirst($resource) . '. You have ' . human_num($player_resource) . '.';
        } else {
            $Character->Data[$resource] = $player_resource - $amount;
            $inserted = $DAL->w(
                "INSERT INTO market_orders (character_id, season_id, order_type, resource, amount, amount_remaining, price_per_unit)
                 VALUES (:cid, :season_id, 'sell', :resource, :amount, :amount2, :ppu)",
                ['cid' => $character_id, 'season_id' => $market_season_id, 'resource' => $resource, 'amount' => $amount, 'amount2' => $amount, 'ppu' => $price_per_unit]
            );
            if ($inserted) {
                $alert_success = 'Sell order posted: ' . human_num($amount) . ' ' . ucfirst($resource) . ' at ' . human_num($price_per_unit) . ' Gold each.';
                $active_tab = 'my_orders';
            } else {
                $Character->Data[$resource] = $player_resource;
                $alert_danger = 'Failed to post order. Please try again.';
            }
        }
    } else {
        // Buy order — escrow Gold
        $total_gold  = $amount * $price_per_unit;
        $player_gold = (int)$Character->Data['gold'];
        if ($player_gold < $total_gold) {
            $alert_danger = 'Not enough Gold. Need ' . human_num($total_gold) . ', have ' . human_num($player_gold) . '.';
        } else {
            $Character->Data['gold'] = $player_gold - $total_gold;
            $inserted = $DAL->w(
                "INSERT INTO market_orders (character_id, season_id, order_type, resource, amount, amount_remaining, price_per_unit)
                 VALUES (:cid, :season_id, 'buy', :resource, :amount, :amount2, :ppu)",
                ['cid' => $character_id, 'season_id' => $market_season_id, 'resource' => $resource, 'amount' => $amount, 'amount2' => $amount, 'ppu' => $price_per_unit]
            );
            if ($inserted) {
                $alert_success = 'Buy order posted: ' . human_num($amount) . ' ' . ucfirst($resource) . ' at ' . human_num($price_per_unit) . ' Gold each.';
                $active_tab = 'my_orders';
            } else {
                $Character->Data['gold'] = $player_gold;
                $alert_danger = 'Failed to post order. Please try again.';
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
        $alert_danger = 'Invalid fill parameters.';
    } elseif (!in_array($fill_order_type, ['sell', 'buy'], true)) {
        $alert_danger = 'Invalid order type.';
    } elseif (!in_array($fill_resource, $valid_resources, true)) {
        $alert_danger = 'Invalid resource.';
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
            $alert_danger = 'No orders available at that price.';
        } else {
            $total_available = (int)array_sum(array_column($orders_at_price, 'amount_remaining'));
            $actual_fill     = min($fill_amount_req, $total_available);
            $col_map         = ['herbs' => 'herbs', 'iron' => 'iron', 'gems' => 'gems', 'credits' => 'credits'];

            if ($fill_order_type === 'sell') {
                // Player buys: pays Gold, receives resource
                $gold_needed = $actual_fill * $fill_price;
                $player_gold = (int)$Character->Data['gold'];
                if ($player_gold < $gold_needed) {
                    $alert_danger = 'Not enough Gold. Need ' . human_num($gold_needed) . '.';
                } else {
                    $Character->Data['gold'] -= $gold_needed;
                    $filled_total = 0;

                    foreach ($orders_at_price as $order) {
                        if ($filled_total >= $actual_fill) {
                            break;
                        }
                        $take = min($actual_fill - $filled_total, (int)$order['amount_remaining']);
                        $DAL->w(
                            "UPDATE market_orders
                             SET amount_remaining = amount_remaining - :amt1,
                                 status = IF(amount_remaining - :amt2 <= 0, 'filled', 'open')
                             WHERE id = :id AND status = 'open' AND amount_remaining >= :amt3",
                            ['amt1' => $take, 'amt2' => $take, 'amt3' => $take, 'id' => (int)$order['id']]
                        );
                        if ($DAL->rows_affected() > 0) {
                            $filled_total += $take;
                            $Character->Data[$fill_resource] = ((int)($Character->Data[$fill_resource] ?? 0)) + $take;
                            $DAL->w(
                                "UPDATE characters SET gold = gold + :gold WHERE id = :cid",
                                ['gold' => $take * $fill_price, 'cid' => (int)$order['character_id']]
                            );
                        }
                    }

                    // Refund gold for any portion that couldn't be filled
                    $unfilled = $actual_fill - $filled_total;
                    if ($unfilled > 0) {
                        $Character->Data['gold'] += $unfilled * $fill_price;
                    }

                    if ($filled_total > 0) {
                        $alert_success = 'Purchased ' . human_num($filled_total) . ' ' . ucfirst($fill_resource) . ' for ' . human_num($filled_total * $fill_price) . ' Gold.';
                    } else {
                        $Character->Data['gold'] += $gold_needed;
                        $alert_danger = 'Orders no longer available. Refresh and try again.';
                    }
                }
            } else {
                // Player sells: provides resource, receives Gold from escrow
                $player_resource = (int)($Character->Data[$fill_resource] ?? 0);
                if ($player_resource < $actual_fill) {
                    $alert_danger = 'Not enough ' . ucfirst($fill_resource) . '. Have ' . human_num($player_resource) . '.';
                } else {
                    $Character->Data[$fill_resource] = $player_resource - $actual_fill;
                    $col          = $col_map[$fill_resource];
                    $filled_total = 0;

                    foreach ($orders_at_price as $order) {
                        if ($filled_total >= $actual_fill) {
                            break;
                        }
                        $take = min($actual_fill - $filled_total, (int)$order['amount_remaining']);
                        $DAL->w(
                            "UPDATE market_orders
                             SET amount_remaining = amount_remaining - :amt1,
                                 status = IF(amount_remaining - :amt2 <= 0, 'filled', 'open')
                             WHERE id = :id AND status = 'open' AND amount_remaining >= :amt3",
                            ['amt1' => $take, 'amt2' => $take, 'amt3' => $take, 'id' => (int)$order['id']]
                        );
                        if ($DAL->rows_affected() > 0) {
                            $filled_total += $take;
                            $Character->Data['gold'] = ((int)$Character->Data['gold']) + ($take * $fill_price);
                            $DAL->w(
                                "UPDATE characters SET {$col} = {$col} + :amt WHERE id = :cid",
                                ['amt' => $take, 'cid' => (int)$order['character_id']]
                            );
                        }
                    }

                    // Refund resource for any portion that couldn't be filled
                    $unfilled = $actual_fill - $filled_total;
                    if ($unfilled > 0) {
                        $Character->Data[$fill_resource] = ((int)($Character->Data[$fill_resource] ?? 0)) + $unfilled;
                    }

                    if ($filled_total > 0) {
                        $alert_success = 'Sold ' . human_num($filled_total) . ' ' . ucfirst($fill_resource) . ' for ' . human_num($filled_total * $fill_price) . ' Gold.';
                    } else {
                        $Character->Data[$fill_resource] = $player_resource;
                        $alert_danger = 'Orders no longer available. Refresh and try again.';
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
        $alert_danger = 'Order not found.';
    } else {
        $order    = $order_row[0];
        $resource = $order['resource'];
        if (!in_array($resource, $valid_resources, true)) {
            $alert_danger = 'Invalid order.';
        } else {
            $DAL->w(
                "UPDATE market_orders SET status = 'cancelled' WHERE id = :id AND character_id = :cid",
                ['id' => $order_id, 'cid' => $character_id]
            );
            if ($DAL->rows_affected() > 0) {
                if ($order['order_type'] === 'sell') {
                    $Character->Data[$resource] = ((int)($Character->Data[$resource] ?? 0)) + (int)$order['amount_remaining'];
                    $alert_success = 'Order cancelled. ' . human_num((int)$order['amount_remaining']) . ' ' . ucfirst($resource) . ' returned to inventory.';
                } else {
                    $refund = (int)$order['amount_remaining'] * (int)$order['price_per_unit'];
                    $Character->Data['gold'] = ((int)$Character->Data['gold']) + $refund;
                    $alert_success = 'Order cancelled. ' . human_num($refund) . ' Gold returned to account.';
                }
            } else {
                $alert_danger = 'Failed to cancel order.';
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
        $alert_danger = 'Invalid item type.';
    } elseif ($item_id <= 0) {
        $alert_danger = 'Invalid item.';
    } elseif ($list_price <= 0) {
        $alert_danger = 'List price must be greater than 0.';
    } elseif ($item_type === 'gear' && in_array($item_id, array_filter([
        (int)($Character->Data['party_json']['members']['frontline']['equipped_weapon'] ?? 0),
        (int)($Character->Data['party_json']['members']['frontline']['equipped_armor'] ?? 0),
        (int)($Character->Data['party_json']['members']['backline']['equipped_weapon'] ?? 0),
        (int)($Character->Data['party_json']['members']['backline']['equipped_armor'] ?? 0),
    ]))) {
        $alert_danger = 'Cannot list equipped gear. Unequip it first.';
    } else {
        $table = $item_table_map[$item_type];
        // Set season_id on the item when listing so it remains in the correct market pool
        $DAL->w(
            "UPDATE {$table} SET market_price = :price, season_id = :season_id WHERE id = :id AND owner_id = :uid AND (market_price = 0 OR market_price IS NULL)",
            ['price' => $list_price, 'season_id' => $market_season_id, 'id' => $item_id, 'uid' => $user_id]
        );
        if ($DAL->rows_affected() > 0) {
            $alert_success = 'Item listed for ' . human_num($list_price) . ' Gold.';
            $active_tab = 'my_orders';
        } else {
            $alert_danger = 'Failed to list item. It may already be listed or you do not own it.';
        }
    }
}

// --- POST: Unlist an item ---
if (isset($_POST['unlist_item'])) {
    $item_type = $_POST['item_type'] ?? '';
    $item_id   = (int)$_POST['item_id'];

    if (!in_array($item_type, $valid_item_types, true)) {
        $alert_danger = 'Invalid item type.';
    } elseif ($item_id <= 0) {
        $alert_danger = 'Invalid item.';
    } else {
        $table = $item_table_map[$item_type];
        $DAL->w(
            "UPDATE {$table} SET market_price = 0 WHERE id = :id AND owner_id = :uid AND market_price > 0",
            ['id' => $item_id, 'uid' => $user_id]
        );
        if ($DAL->rows_affected() > 0) {
            $alert_success = 'Item unlisted and returned to your inventory.';
            $active_tab = 'my_orders';
        } else {
            $alert_danger = 'Failed to unlist item.';
        }
    }
}

// --- POST: Buy a listed item ---
if (isset($_POST['buy_item'])) {
    $item_type      = $_POST['item_type'] ?? '';
    $item_id        = (int)$_POST['item_id'];
    $expected_price = (int)$_POST['expected_price'];

    if (!in_array($item_type, $valid_item_types, true)) {
        $alert_danger = 'Invalid item type.';
    } elseif ($item_id <= 0 || $expected_price <= 0) {
        $alert_danger = 'Invalid purchase parameters.';
    } else {
        $table    = $item_table_map[$item_type];
        $item_row = $DAL->r(
            "SELECT * FROM {$table} WHERE id = :id AND market_price = :price AND owner_id != :uid AND season_id <=> :season_id",
            ['id' => $item_id, 'price' => $expected_price, 'uid' => $user_id, 'season_id' => $market_season_id]
        );

        if (!$item_row) {
            $alert_danger = 'Item not available or price has changed. Please refresh.';
        } else {
            $item       = $item_row[0];
            $price      = (int)$item['market_price'];
            $seller_uid = (int)$item['owner_id'];

            if ((int)$Character->Data['gold'] < $price) {
                $alert_danger = 'Not enough Gold. Need ' . human_num($price) . '.';
            } else {
                // Atomic ownership transfer — reset favorite on gear
                $extra = ($item_type === 'gear') ? ', favorite = 0' : '';
                $DAL->w(
                    "UPDATE {$table} SET owner_id = :buyer, market_price = 0{$extra} WHERE id = :id AND owner_id = :seller AND market_price = :price",
                    ['buyer' => $user_id, 'id' => $item_id, 'seller' => $seller_uid, 'price' => $price]
                );
                if ($DAL->rows_affected() === 0) {
                    $alert_danger = 'Item no longer available. Refresh and try again.';
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
                        $item_name = htmlspecialchars($details['name'] ?? 'Rift Stone');
                    } else {
                        $item_name = htmlspecialchars($item['name']);
                    }
                    $alert_success = 'Purchased ' . $item_name . ' for ' . human_num($price) . ' Gold.';
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
        $raw = $DAL->r("SELECT * FROM rifts WHERE market_price > 0 AND season_id <=> :season_id ORDER BY market_price ASC LIMIT 50", ['season_id' => $market_season_id]) ?: [];
        foreach ($raw as $r) {
            $item                = json_decode($r['details'], true) ?? [];
            $item['id']          = (int)$r['id'];
            $item['market_price'] = (int)$r['market_price'];
            $item['owner_id']    = (int)$r['owner_id'];
            $item['is_own']      = ((int)$r['owner_id'] === $user_id);
            $listed_items[]      = $item;
        }
    } else {
        $raw = $DAL->r("SELECT * FROM potions WHERE market_price > 0 AND season_id <=> :season_id ORDER BY market_price ASC LIMIT 50", ['season_id' => $market_season_id]) ?: [];
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
