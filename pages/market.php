<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1>Market</h1>
        <p>Trade Herbs, Iron, Gems, and Credits for Gold. Resources and Gold are held in escrow until an order is filled or cancelled.</p>

        <!-- Tab Navigation -->
        <div class="tab-nav">
            <a href="/market?tab=orders&resource=<?php echo htmlspecialchars($resource_filter); ?>" class="tab-nav-item <?php echo $active_tab === 'orders' ? 'active' : ''; ?>">Resource Orders</a>
            <a href="/market?tab=items&item_type=<?php echo htmlspecialchars($item_type_filter); ?>" class="tab-nav-item <?php echo $active_tab === 'items' ? 'active' : ''; ?>">Items</a>
            <a href="/market?tab=post_order" class="tab-nav-item <?php echo $active_tab === 'post_order' ? 'active' : ''; ?>">Post Order</a>
            <a href="/market?tab=list_item&item_type=<?php echo htmlspecialchars($item_type_filter); ?>" class="tab-nav-item <?php echo $active_tab === 'list_item' ? 'active' : ''; ?>">List Item</a>
            <a href="/market?tab=my_orders" class="tab-nav-item <?php echo $active_tab === 'my_orders' ? 'active' : ''; ?>">My Listings<?php if ($my_listings_badge > 0): ?> <span class="badge"><?php echo $my_listings_badge; ?></span><?php endif; ?></a>
        </div>

        <?php if ($active_tab === 'orders'): ?>
        <!-- Orders Tab -->
        <h2 class="heading--no-top-margin">Open Market Orders</h2>

        <!-- Resource Filter Nav -->
        <div class="market-resource-nav">
            <?php foreach ($valid_resources as $res): ?>
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

        <?php elseif ($active_tab === 'items'): ?>
        <!-- Items Tab -->
        <h2 class="heading--no-top-margin">Item Market</h2>
        <p>Browse unique items listed for sale by other players. Your Gold: <span class="text--gold"><?php echo human_num((int)$Character->Data['gold']); ?></span></p>

        <!-- Item Type Sub-Nav -->
        <div class="market-resource-nav">
            <a href="/market?tab=items&item_type=gear" class="market-resource-tab <?php echo $item_type_filter === 'gear' ? 'active' : ''; ?>">Gear</a>
            <a href="/market?tab=items&item_type=rift_stone" class="market-resource-tab <?php echo $item_type_filter === 'rift_stone' ? 'active' : ''; ?>">Rift Stones</a>
            <a href="/market?tab=items&item_type=potion" class="market-resource-tab <?php echo $item_type_filter === 'potion' ? 'active' : ''; ?>">Potions</a>
        </div>

        <?php if ($item_type_filter === 'gear'): ?>
        <!-- Affix Filter -->
        <div class="market-resource-nav">
            <a href="/market?tab=items&item_type=gear" class="market-resource-tab <?php echo empty($affix_filter) ? 'active' : ''; ?>">All</a>
            <?php foreach ($gear_affix_defs as $affix_key => $affix_def):
                $is_active = in_array($affix_key, $affix_filter, true);
                if ($is_active) {
                    $toggled = array_values(array_filter($affix_filter, fn($a) => $a !== $affix_key));
                } else {
                    $toggled = array_merge($affix_filter, [$affix_key]);
                }
                $affix_href = '/market?tab=items&item_type=gear' . (!empty($toggled) ? '&' . http_build_query(['affix' => $toggled]) : '');
            ?>
                <a href="<?php echo htmlspecialchars($affix_href); ?>" class="market-resource-tab <?php echo $is_active ? 'active' : ''; ?>"><?php echo htmlspecialchars($affix_def['name']); ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($listed_items)): ?>
            <p><em>No <?php echo $item_type_filter === 'rift_stone' ? 'Rift Stones' : ucfirst($item_type_filter) . 's'; ?> listed for sale.</em></p>
        <?php else: ?>
            <?php foreach ($listed_items as $item): ?>
                <div class="card <?php echo $item['is_own'] ? 'market-own-listing-card' : ''; ?>">
                    <div class="grid">
                        <div>
                            <?php if ($item_type_filter === 'gear'): ?>
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p>
                                    <b>Type:</b> <?php echo htmlspecialchars(ucfirst($item['type'] ?? '')); ?> |
                                    <b>Slot:</b> <?php echo htmlspecialchars(ucfirst($item['slot'] ?? '')); ?>
                                </p>
                                <?php if (!empty($item['base_bonuses'])): ?>
                                    <p><b>Base:</b> <?php
                                        $b = [];
                                        foreach ($item['base_bonuses'] as $stat => $val) {
                                            $b[] = '+' . $val . '% ' . ucfirst($stat);
                                        }
                                        echo htmlspecialchars(implode(', ', $b));
                                    ?></p>
                                <?php endif; ?>
                                <?php if (!empty($item['affixes'])): ?>
                                    <ul class="list--compact">
                                        <?php foreach ($item['affixes'] as $affix): ?>
                                            <li class="list-item--positive">
                                                +<?php echo $affix['value']; ?><?php echo $affix['type'] === 'percent' ? '%' : ''; ?>
                                                <?php echo htmlspecialchars($gear_affix_defs[$affix['name']]['name'] ?? $affix['name']); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            <?php elseif ($item_type_filter === 'rift_stone'): ?>
                                <h3><?php echo htmlspecialchars($item['name'] ?? 'Rift Stone'); ?></h3>
                                <p><b>Level:</b> <?php echo (int)($item['level'] ?? 0); ?></p>
                                <?php if (!empty($item['implicit']) && isset($rift_implicit_defs[$item['implicit']])): ?>
                                    <p><b>Implicit:</b> <span class="list-item--positive"><?php echo htmlspecialchars($rift_implicit_defs[$item['implicit']]['description']); ?></span></p>
                                <?php endif; ?>
                                <?php if (!empty($item['affixes'])): ?>
                                    <ul class="list--compact">
                                        <?php foreach ($item['affixes'] as $affix_key): ?>
                                            <?php if (isset($rift_affix_defs[$affix_key])): ?>
                                                <li class="list-item--negative"><?php echo htmlspecialchars($rift_affix_defs[$affix_key]['description']); ?></li>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            <?php else: /* potion */ ?>
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p><b>Level:</b> <?php echo $item['level']; ?></p>
                                <ul class="list--compact">
                                    <?php if (isset($potion_prefix_defs[$item['prefix']])): ?>
                                        <li>+<?php echo $item['level'] * $potion_prefix_defs[$item['prefix']]['per_level']; ?>% <?php echo htmlspecialchars($potion_prefix_defs[$item['prefix']]['name']); ?></li>
                                    <?php endif; ?>
                                    <?php if (isset($potion_suffix_defs[$item['suffix']])): ?>
                                        <li>+<?php echo $item['level'] * $potion_suffix_defs[$item['suffix']]['per_level']; ?>% <?php echo htmlspecialchars($potion_suffix_defs[$item['suffix']]['name']); ?></li>
                                    <?php endif; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                        <div>
                            <p class="market-item-price"><span class="text--gold"><?php echo human_num($item['market_price']); ?> Gold</span></p>
                            <?php if ($item['is_own']): ?>
                                <em class="market-own-order">Your Listing</em>
                            <?php else: ?>
                                <form method="POST" action="/market?tab=items&item_type=<?php echo htmlspecialchars($item_type_filter); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                    <input type="hidden" name="item_type" value="<?php echo htmlspecialchars($item_type_filter); ?>">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="expected_price" value="<?php echo $item['market_price']; ?>">
                                    <input type="submit" name="buy_item" value="Buy for <?php echo human_num($item['market_price']); ?> Gold" class="success-button">
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php elseif ($active_tab === 'list_item'): ?>
        <!-- List Item Tab -->
        <h2 class="heading--no-top-margin">List an Item</h2>
        <p><small>Set a Gold price to list an item on the market. It will be visible to all players.</small></p>

        <!-- Item Type Sub-Nav -->
        <div class="market-resource-nav">
            <a href="/market?tab=list_item&item_type=gear" class="market-resource-tab <?php echo $item_type_filter === 'gear' ? 'active' : ''; ?>">Gear</a>
            <a href="/market?tab=list_item&item_type=rift_stone" class="market-resource-tab <?php echo $item_type_filter === 'rift_stone' ? 'active' : ''; ?>">Rift Stones</a>
            <a href="/market?tab=list_item&item_type=potion" class="market-resource-tab <?php echo $item_type_filter === 'potion' ? 'active' : ''; ?>">Potions</a>
        </div>

        <?php
            $unlisted = match($item_type_filter) {
                'rift_stone' => $my_unlisted_rifts,
                'potion'     => $my_unlisted_potions,
                default      => $my_unlisted_gear,
            };
        ?>
        <?php if (empty($unlisted)): ?>
            <p><em>No <?php echo $item_type_filter === 'rift_stone' ? 'Rift Stones' : ucfirst($item_type_filter) . 's'; ?> available to list.</em></p>
        <?php else: ?>
            <?php foreach ($unlisted as $item): ?>
                <div class="card market-list-card">
                    <div class="grid">
                        <div>
                            <?php if ($item_type_filter === 'gear'): ?>
                                <p>
                                    <b><?php echo htmlspecialchars($item['name']); ?></b>
                                    &mdash; <?php echo htmlspecialchars(ucfirst($item['type'] ?? '')); ?>, <?php echo htmlspecialchars(ucfirst($item['slot'] ?? '')); ?>
                                </p>
                                <?php if (!empty($item['affixes'])): ?>
                                    <p class="market-list-affixes"><?php
                                        $aff = [];
                                        foreach ($item['affixes'] as $affix) {
                                            $aff[] = '+' . $affix['value'] . ($affix['type'] === 'percent' ? '%' : '') . ' ' . ($gear_affix_defs[$affix['name']]['name'] ?? $affix['name']);
                                        }
                                        echo htmlspecialchars(implode(' &bull; ', $aff));
                                    ?></p>
                                <?php endif; ?>
                            <?php elseif ($item_type_filter === 'rift_stone'): ?>
                                <p>
                                    <b><?php echo htmlspecialchars($item['name'] ?? 'Rift Stone'); ?></b>
                                    &mdash; Level <?php echo (int)($item['level'] ?? 0); ?>
                                    <?php if (!empty($item['implicit']) && isset($rift_implicit_defs[$item['implicit']])): ?>
                                        &mdash; <span class="list-item--positive"><?php echo htmlspecialchars($rift_implicit_defs[$item['implicit']]['name']); ?></span>
                                    <?php endif; ?>
                                </p>
                            <?php else: ?>
                                <p>
                                    <b><?php echo htmlspecialchars($item['name']); ?></b>
                                    &mdash; Level <?php echo $item['level']; ?>
                                    <?php if (isset($potion_prefix_defs[$item['prefix']])): ?>
                                        &bull; +<?php echo $item['level'] * $potion_prefix_defs[$item['prefix']]['per_level']; ?>% <?php echo htmlspecialchars($potion_prefix_defs[$item['prefix']]['name']); ?>
                                    <?php endif; ?>
                                    <?php if (isset($potion_suffix_defs[$item['suffix']])): ?>
                                        &bull; +<?php echo $item['level'] * $potion_suffix_defs[$item['suffix']]['per_level']; ?>% <?php echo htmlspecialchars($potion_suffix_defs[$item['suffix']]['name']); ?>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <form method="POST" action="/market?tab=list_item&item_type=<?php echo htmlspecialchars($item_type_filter); ?>" class="market-list-form">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <input type="hidden" name="item_type" value="<?php echo htmlspecialchars($item_type_filter); ?>">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <input type="number" name="list_price" min="1" placeholder="Gold price" required class="market-fill-input" style="width:110px;">
                                <input type="submit" name="list_item" value="List" class="market-fill-btn">
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

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
                    <?php if (!$is_season_character): ?>
                    <option value="credits">Credits &mdash; have: <?php echo human_num((int)($Character->Data['credits'] ?? 0)); ?></option>
                    <?php endif; ?>
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
        <!-- My Listings Tab -->
        <h2 class="heading--no-top-margin">My Listings</h2>

        <!-- Resource Orders -->
        <h3>Resource Orders</h3>
        <?php if (empty($my_orders)): ?>
            <p><em>No open resource orders. <a href="/market?tab=post_order">Post an order</a> to get started.</em></p>
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

        <!-- Active Item Listings -->
        <hr>
        <h3>Item Listings</h3>
        <?php
            $has_listed_items = !empty($my_listed_gear) || !empty($my_listed_rifts) || !empty($my_listed_potions);
        ?>
        <?php if (!$has_listed_items): ?>
            <p><em>No items currently listed. Use the section below to list items from your inventory.</em></p>
        <?php else: ?>
            <?php foreach ($my_listed_gear as $item): ?>
                <div class="card">
                    <div class="grid">
                        <div>
                            <h3><span class="badge">GEAR</span> <?php echo htmlspecialchars($item['name']); ?></h3>
                            <p>
                                <b>Type:</b> <?php echo htmlspecialchars(ucfirst($item['type'] ?? '')); ?> |
                                <b>Slot:</b> <?php echo htmlspecialchars(ucfirst($item['slot'] ?? '')); ?>
                            </p>
                            <?php if (!empty($item['affixes'])): ?>
                                <ul class="list--compact">
                                    <?php foreach ($item['affixes'] as $affix): ?>
                                        <li class="list-item--positive">+<?php echo $affix['value']; ?><?php echo $affix['type'] === 'percent' ? '%' : ''; ?> <?php echo htmlspecialchars($gear_affix_defs[$affix['name']]['name'] ?? $affix['name']); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            <p><b>Listed for:</b> <span class="text--gold"><?php echo human_num($item['market_price']); ?> Gold</span></p>
                        </div>
                        <div>
                            <form method="POST" action="/market?tab=my_orders" onsubmit="return confirm('Unlist this item?');">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <input type="hidden" name="item_type" value="gear">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <input type="submit" name="unlist_item" value="Unlist" class="contrast">
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php foreach ($my_listed_rifts as $item): ?>
                <div class="card">
                    <div class="grid">
                        <div>
                            <h3><span class="badge">RIFT STONE</span> <?php echo htmlspecialchars($item['name'] ?? 'Rift Stone'); ?></h3>
                            <p><b>Level:</b> <?php echo (int)($item['level'] ?? 0); ?></p>
                            <?php if (!empty($item['implicit']) && isset($rift_implicit_defs[$item['implicit']])): ?>
                                <p><span class="list-item--positive"><?php echo htmlspecialchars($rift_implicit_defs[$item['implicit']]['description']); ?></span></p>
                            <?php endif; ?>
                            <p><b>Listed for:</b> <span class="text--gold"><?php echo human_num($item['market_price']); ?> Gold</span></p>
                        </div>
                        <div>
                            <form method="POST" action="/market?tab=my_orders" onsubmit="return confirm('Unlist this item?');">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <input type="hidden" name="item_type" value="rift_stone">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <input type="submit" name="unlist_item" value="Unlist" class="contrast">
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php foreach ($my_listed_potions as $item): ?>
                <div class="card">
                    <div class="grid">
                        <div>
                            <h3><span class="badge">POTION</span> <?php echo htmlspecialchars($item['name']); ?></h3>
                            <p><b>Level:</b> <?php echo $item['level']; ?></p>
                            <ul class="list--compact">
                                <?php if (isset($potion_prefix_defs[$item['prefix']])): ?>
                                    <li>+<?php echo $item['level'] * $potion_prefix_defs[$item['prefix']]['per_level']; ?>% <?php echo htmlspecialchars($potion_prefix_defs[$item['prefix']]['name']); ?></li>
                                <?php endif; ?>
                                <?php if (isset($potion_suffix_defs[$item['suffix']])): ?>
                                    <li>+<?php echo $item['level'] * $potion_suffix_defs[$item['suffix']]['per_level']; ?>% <?php echo htmlspecialchars($potion_suffix_defs[$item['suffix']]['name']); ?></li>
                                <?php endif; ?>
                            </ul>
                            <p><b>Listed for:</b> <span class="text--gold"><?php echo human_num($item['market_price']); ?> Gold</span></p>
                        </div>
                        <div>
                            <form method="POST" action="/market?tab=my_orders" onsubmit="return confirm('Unlist this item?');">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <input type="hidden" name="item_type" value="potion">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <input type="submit" name="unlist_item" value="Unlist" class="contrast">
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <p><small><a href="/market?tab=list_item">List an item on the market &rarr;</a></small></p>

        <?php endif; ?>

    </article>
    </div>
    </div>
</main>
