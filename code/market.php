<?php

declare(strict_types=1);

$alert_success = '';
$alert_danger = '';

$character_id  = (int)$Character->Data['id'];
$valid_resources = ['herbs', 'iron', 'gems', 'credits'];
$active_tab      = $_GET['tab'] ?? 'orders';
$resource_filter = $_GET['resource'] ?? 'herbs';

if (!in_array($resource_filter, $valid_resources, true)) {
    $resource_filter = 'herbs';
}

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
                "INSERT INTO market_orders (character_id, order_type, resource, amount, amount_remaining, price_per_unit)
                 VALUES (:cid, 'sell', :resource, :amount, :amount2, :ppu)",
                ['cid' => $character_id, 'resource' => $resource, 'amount' => $amount, 'amount2' => $amount, 'ppu' => $price_per_unit]
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
                "INSERT INTO market_orders (character_id, order_type, resource, amount, amount_remaining, price_per_unit)
                 VALUES (:cid, 'buy', :resource, :amount, :amount2, :ppu)",
                ['cid' => $character_id, 'resource' => $resource, 'amount' => $amount, 'amount2' => $amount, 'ppu' => $price_per_unit]
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
        // Fetch all open orders at this price point, oldest first (FIFO), excluding own
        $orders_at_price = $DAL->r(
            "SELECT * FROM market_orders
             WHERE resource = :res AND order_type = :otype AND price_per_unit = :price
               AND status = 'open' AND character_id != :cid
             ORDER BY created_at ASC",
            ['res' => $fill_resource, 'otype' => $fill_order_type, 'price' => $fill_price, 'cid' => $character_id]
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

// --- Load aggregated orders (other players, grouped by price point) ---
$sell_orders_agg = $DAL->r(
    "SELECT price_per_unit, SUM(amount_remaining) AS total_remaining
     FROM market_orders
     WHERE resource = :res AND order_type = 'sell' AND status = 'open' AND character_id != :cid
     GROUP BY price_per_unit
     ORDER BY price_per_unit ASC
     LIMIT 50",
    ['res' => $resource_filter, 'cid' => $character_id]
) ?: [];

$buy_orders_agg = $DAL->r(
    "SELECT price_per_unit, SUM(amount_remaining) AS total_remaining
     FROM market_orders
     WHERE resource = :res AND order_type = 'buy' AND status = 'open' AND character_id != :cid
     GROUP BY price_per_unit
     ORDER BY price_per_unit DESC
     LIMIT 50",
    ['res' => $resource_filter, 'cid' => $character_id]
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
