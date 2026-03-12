<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1>Market</h1>
        <p>Trade Herbs, Iron, Gems, and Credits for Gold. Resources and Gold are held in escrow until an order is filled or cancelled.</p>

        <!-- Tab Navigation -->
        <div class="tab-nav">
            <a href="/market?tab=orders&resource=<?php echo htmlspecialchars($resource_filter); ?>" class="tab-nav-item <?php echo $active_tab === 'orders' ? 'active' : ''; ?>">Orders</a>
            <a href="/market?tab=post_order" class="tab-nav-item <?php echo $active_tab === 'post_order' ? 'active' : ''; ?>">Post Order</a>
            <a href="/market?tab=my_orders" class="tab-nav-item <?php echo $active_tab === 'my_orders' ? 'active' : ''; ?>">My Orders<?php if (count($my_orders) > 0): ?> <span class="badge"><?php echo count($my_orders); ?></span><?php endif; ?></a>
        </div>

        <?php if ($active_tab === 'orders'): ?>
        <!-- Orders Tab -->
        <h2 class="heading--no-top-margin">Open Market Orders</h2>

        <!-- Resource Filter Nav -->
        <div class="market-resource-nav">
            <?php foreach (['herbs', 'iron', 'gems', 'credits'] as $res): ?>
                <a href="/market?tab=orders&resource=<?php echo $res; ?>" class="market-resource-tab <?php echo $resource_filter === $res ? 'active' : ''; ?>">
                    <?php echo ucfirst($res); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <p>
            Your <strong><?php echo ucfirst($resource_filter); ?></strong>:
            <span class="text--warning"><?php echo human_num((int)($Character->Data[$resource_filter] ?? 0)); ?></span>
            &nbsp;|&nbsp;
            Your <strong>Gold</strong>:
            <span class="text--gold"><?php echo human_num((int)$Character->Data['gold']); ?></span>
        </p>

        <div class="market-order-columns">

            <!-- Sell Orders: Buy from these players -->
            <div class="market-order-col">
                <h3>Sell Orders <small class="market-subtitle">&mdash; Buy <?php echo ucfirst($resource_filter); ?> with Gold</small></h3>
                <?php if (empty($sell_display)): ?>
                    <p><em>No sell orders listed for <?php echo ucfirst($resource_filter); ?>.</em></p>
                <?php else: ?>
                    <div class="market-table-scroll">
                        <table class="market-table">
                            <thead>
                                <tr>
                                    <th>Available</th>
                                    <th>Price / Unit</th>
                                    <th>Total Cost</th>
                                    <th>Buy</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sell_display as $row): ?>
                                    <tr>
                                        <?php if ($row['is_own']): ?>
                                            <td><?php echo human_num($row['total_remaining']); ?></td>
                                            <td class="text--gold"><?php echo human_num($row['price_per_unit']); ?> G</td>
                                            <td class="text--gold"><?php echo human_num($row['total_remaining'] * $row['price_per_unit']); ?> G</td>
                                            <td><em class="market-own-order">Your Order</em></td>
                                        <?php else: ?>
                                            <td><?php echo human_num($row['total_remaining']); ?></td>
                                            <td class="text--gold"><?php echo human_num($row['price_per_unit']); ?> G</td>
                                            <td class="text--gold"><?php echo human_num($row['total_remaining'] * $row['price_per_unit']); ?> G</td>
                                            <td>
                                                <form method="POST" action="/market?tab=orders&resource=<?php echo htmlspecialchars($resource_filter); ?>" class="market-fill-form">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                                    <input type="hidden" name="fill_order_type" value="sell">
                                                    <input type="hidden" name="fill_resource" value="<?php echo htmlspecialchars($resource_filter); ?>">
                                                    <input type="hidden" name="fill_price" value="<?php echo $row['price_per_unit']; ?>">
                                                    <input type="number" name="fill_amount" value="<?php echo $row['total_remaining']; ?>" min="1" max="<?php echo $row['total_remaining']; ?>" class="market-fill-input">
                                                    <input type="submit" name="fill_order" value="Buy" class="success-button market-fill-btn">
                                                </form>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Buy Orders: Sell to these players -->
            <div class="market-order-col">
                <h3>Buy Orders <small class="market-subtitle">&mdash; Sell <?php echo ucfirst($resource_filter); ?> for Gold</small></h3>
                <?php if (empty($buy_display)): ?>
                    <p><em>No buy orders listed for <?php echo ucfirst($resource_filter); ?>.</em></p>
                <?php else: ?>
                    <div class="market-table-scroll">
                        <table class="market-table">
                            <thead>
                                <tr>
                                    <th>Wanted</th>
                                    <th>Price / Unit</th>
                                    <th>Total Payout</th>
                                    <th>Sell</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($buy_display as $row): ?>
                                    <tr>
                                        <?php if ($row['is_own']): ?>
                                            <td><?php echo human_num($row['total_remaining']); ?></td>
                                            <td class="text--gold"><?php echo human_num($row['price_per_unit']); ?> G</td>
                                            <td class="text--gold"><?php echo human_num($row['total_remaining'] * $row['price_per_unit']); ?> G</td>
                                            <td><em class="market-own-order">Your Order</em></td>
                                        <?php else: ?>
                                            <td><?php echo human_num($row['total_remaining']); ?></td>
                                            <td class="text--gold"><?php echo human_num($row['price_per_unit']); ?> G</td>
                                            <td class="text--gold"><?php echo human_num($row['total_remaining'] * $row['price_per_unit']); ?> G</td>
                                            <td>
                                                <form method="POST" action="/market?tab=orders&resource=<?php echo htmlspecialchars($resource_filter); ?>" class="market-fill-form">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                                    <input type="hidden" name="fill_order_type" value="buy">
                                                    <input type="hidden" name="fill_resource" value="<?php echo htmlspecialchars($resource_filter); ?>">
                                                    <input type="hidden" name="fill_price" value="<?php echo $row['price_per_unit']; ?>">
                                                    <input type="number" name="fill_amount" value="<?php echo $row['total_remaining']; ?>" min="1" max="<?php echo $row['total_remaining']; ?>" class="market-fill-input">
                                                    <input type="submit" name="fill_order" value="Sell" class="danger-button market-fill-btn">
                                                </form>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <?php elseif ($active_tab === 'post_order'): ?>
        <!-- Post Order Tab -->
        <h2 class="heading--no-top-margin">Post a Market Order</h2>
        <p>
            <b>Sell order</b> — your resource is deducted immediately and held in escrow. You receive Gold when a buyer fills it.<br>
            <b>Buy order</b> — your Gold is deducted immediately and held in escrow. You receive the resource when a seller fills it.
        </p>

        <div class="card">
            <form method="POST" action="/market?tab=post_order">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">

                <fieldset>
                    <legend>Order Type</legend>
                    <label>
                        <input type="radio" name="order_type" value="sell" checked>
                        <span class="market-badge--sell-inline">SELL</span> — offer resource, receive Gold
                    </label>
                    <label>
                        <input type="radio" name="order_type" value="buy">
                        <span class="market-badge--buy-inline">BUY</span> — offer Gold, receive resource
                    </label>
                </fieldset>

                <label for="resource">Resource</label>
                <select id="resource" name="resource">
                    <option value="herbs">Herbs &mdash; have: <?php echo human_num((int)($Character->Data['herbs'] ?? 0)); ?></option>
                    <option value="iron">Iron &mdash; have: <?php echo human_num((int)($Character->Data['iron'] ?? 0)); ?></option>
                    <option value="gems">Gems &mdash; have: <?php echo human_num((int)($Character->Data['gems'] ?? 0)); ?></option>
                    <option value="credits">Credits &mdash; have: <?php echo human_num((int)($Character->Data['credits'] ?? 0)); ?></option>
                </select>

                <label for="amount">Amount</label>
                <input type="number" id="amount" name="amount" min="1" placeholder="How much to trade" required>

                <label for="price_per_unit">Price per Unit (Gold)</label>
                <input type="number" id="price_per_unit" name="price_per_unit" min="1" placeholder="Gold per unit" required>

                <p class="market-balance-hint">
                    Your Gold: <span class="text--gold"><?php echo human_num((int)$Character->Data['gold']); ?></span>
                </p>

                <input type="submit" name="post_order" value="Post Order">
            </form>
        </div>

        <?php elseif ($active_tab === 'my_orders'): ?>
        <!-- My Orders Tab -->
        <h2 class="heading--no-top-margin">My Open Orders</h2>

        <?php if (empty($my_orders)): ?>
            <p><em>You have no open orders. <a href="/market?tab=post_order">Post an order</a> to get started.</em></p>
        <?php else: ?>
            <?php foreach ($my_orders as $order): ?>
                <div class="card">
                    <div class="grid">
                        <div>
                            <h3>
                                <?php if ($order['order_type'] === 'sell'): ?>
                                    <span class="badge market-badge--sell">SELL</span>
                                <?php else: ?>
                                    <span class="badge market-badge--buy">BUY</span>
                                <?php endif; ?>
                                <?php echo ucfirst(htmlspecialchars($order['resource'])); ?>
                            </h3>
                            <p>
                                <b>Remaining:</b> <?php echo human_num((int)$order['amount_remaining']); ?> / <?php echo human_num((int)$order['amount']); ?><br>
                                <b>Price:</b> <span class="text--gold"><?php echo human_num((int)$order['price_per_unit']); ?> Gold / unit</span><br>
                                <b>Escrow value:</b> <span class="text--gold"><?php echo human_num((int)$order['amount_remaining'] * (int)$order['price_per_unit']); ?> Gold</span><br>
                                <small>Posted: <?php echo htmlspecialchars($order['created_at']); ?></small>
                            </p>
                            <?php if ($order['order_type'] === 'sell'): ?>
                                <p><small>Cancel returns <span class="text--warning"><?php echo human_num((int)$order['amount_remaining']); ?> <?php echo ucfirst(htmlspecialchars($order['resource'])); ?></span> to inventory.</small></p>
                            <?php else: ?>
                                <p><small>Cancel returns <span class="text--gold"><?php echo human_num((int)$order['amount_remaining'] * (int)$order['price_per_unit']); ?> Gold</span> to account.</small></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <form method="POST" action="/market?tab=my_orders" onsubmit="return confirm('Cancel this order and refund your held resources?');">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                <input type="submit" name="cancel_order" value="Cancel Order" class="contrast">
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php endif; ?>

    </article>
    </div>
    </div>
</main>
