<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1><?php echo t('market.title'); ?></h1>
        <p><?php echo t('market.desc'); ?></p>

        <!-- Tab Navigation -->
        <div class="tab-nav">
            <a href="/market?tab=orders&resource=<?php echo htmlspecialchars($resource_filter); ?>" class="tab-nav-item <?php echo $active_tab === 'orders' ? 'active' : ''; ?>"><?php echo t('market.tab.orders'); ?></a>
            <a href="/market?tab=items&item_type=<?php echo htmlspecialchars($item_type_filter); ?>" class="tab-nav-item <?php echo $active_tab === 'items' ? 'active' : ''; ?>"><?php echo t('market.tab.items'); ?></a>
            <a href="/market?tab=post_order" class="tab-nav-item <?php echo $active_tab === 'post_order' ? 'active' : ''; ?>"><?php echo t('market.tab.post_order'); ?></a>
            <a href="/market?tab=list_item&item_type=<?php echo htmlspecialchars($item_type_filter); ?>" class="tab-nav-item <?php echo $active_tab === 'list_item' ? 'active' : ''; ?>"><?php echo t('market.tab.list_item'); ?></a>
            <a href="/market?tab=my_orders" class="tab-nav-item <?php echo $active_tab === 'my_orders' ? 'active' : ''; ?>"><?php echo t('market.tab.my_orders'); ?><?php if ($my_listings_badge > 0): ?> <span class="badge"><?php echo $my_listings_badge; ?></span><?php endif; ?></a>
        </div>

        <?php if ($active_tab === 'orders'): ?>
        <!-- Orders Tab -->
        <h2 class="heading--no-top-margin"><?php echo t('market.orders.title'); ?></h2>

        <!-- Resource Filter Nav -->
        <div class="market-resource-nav">
            <?php foreach ($valid_resources as $res): ?>
                <a href="/market?tab=orders&resource=<?php echo $res; ?>" class="market-resource-tab <?php echo $resource_filter === $res ? 'active' : ''; ?>">
                    <?php echo t('res.' . strtolower($res)); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <p>
            <?php echo t('market.orders.your_resource', ['resource' => t('res.' . strtolower($resource_filter))]); ?>
            <span class="text--warning"><?php echo human_num((int)($Character->Data[$resource_filter] ?? 0)); ?></span>
            &nbsp;|&nbsp;
            <?php echo t('market.orders.your_gold'); ?>
            <span class="text--gold"><?php echo human_num((int)$Character->Data['gold']); ?></span>
        </p>

        <div class="market-order-columns">

            <!-- Sell Orders: Buy from these players -->
            <div class="market-order-col">
                <h3><?php echo t('market.orders.sell_title'); ?> <small class="market-subtitle">&mdash; <?php echo t('market.orders.sell_sub', ['resource' => t('res.' . strtolower($resource_filter))]); ?></small></h3>
                <?php if (empty($sell_display)): ?>
                    <p><em><?php echo t('market.orders.no_sell', ['resource' => t('res.' . strtolower($resource_filter))]); ?></em></p>
                <?php else: ?>
                    <div class="market-table-scroll">
                        <table class="market-table">
                            <thead>
                                <tr>
                                    <th><?php echo t('market.orders.available'); ?></th>
                                    <th><?php echo t('market.orders.price_unit'); ?></th>
                                    <th><?php echo t('market.orders.total_cost'); ?></th>
                                    <th><?php echo t('common.buy'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sell_display as $row): ?>
                                    <tr>
                                        <?php if ($row['is_own']): ?>
                                            <td><?php echo human_num($row['total_remaining']); ?></td>
                                            <td class="text--gold"><?php echo human_num($row['price_per_unit']); ?> G</td>
                                            <td class="text--gold"><?php echo human_num($row['total_remaining'] * $row['price_per_unit']); ?> G</td>
                                            <td><em class="market-own-order"><?php echo t('market.orders.your_order'); ?></em></td>
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
                                                    <input type="submit" name="fill_order" value="<?php echo t('common.buy'); ?>" class="success-button market-fill-btn">
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
                <h3><?php echo t('market.orders.buy_title'); ?> <small class="market-subtitle">&mdash; <?php echo t('market.orders.buy_sub', ['resource' => t('res.' . strtolower($resource_filter))]); ?></small></h3>
                <?php if (empty($buy_display)): ?>
                    <p><em><?php echo t('market.orders.no_buy', ['resource' => t('res.' . strtolower($resource_filter))]); ?></em></p>
                <?php else: ?>
                    <div class="market-table-scroll">
                        <table class="market-table">
                            <thead>
                                <tr>
                                    <th><?php echo t('market.orders.wanted'); ?></th>
                                    <th><?php echo t('market.orders.price_unit'); ?></th>
                                    <th><?php echo t('market.orders.total_payout'); ?></th>
                                    <th><?php echo t('common.sell'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($buy_display as $row): ?>
                                    <tr>
                                        <?php if ($row['is_own']): ?>
                                            <td><?php echo human_num($row['total_remaining']); ?></td>
                                            <td class="text--gold"><?php echo human_num($row['price_per_unit']); ?> G</td>
                                            <td class="text--gold"><?php echo human_num($row['total_remaining'] * $row['price_per_unit']); ?> G</td>
                                            <td><em class="market-own-order"><?php echo t('market.orders.your_order'); ?></em></td>
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
                                                    <input type="submit" name="fill_order" value="<?php echo t('common.sell'); ?>" class="danger-button market-fill-btn">
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
        <h2 class="heading--no-top-margin"><?php echo t('market.items.title'); ?></h2>
        <p><?php echo t('market.items.your_gold'); ?> <span class="text--gold"><?php echo human_num((int)$Character->Data['gold']); ?></span></p>

        <!-- Item Type Sub-Nav -->
        <div class="market-resource-nav">
            <a href="/market?tab=items&item_type=gear" class="market-resource-tab <?php echo $item_type_filter === 'gear' ? 'active' : ''; ?>"><?php echo t('market.items.tab.gear'); ?></a>
            <a href="/market?tab=items&item_type=rift_stone" class="market-resource-tab <?php echo $item_type_filter === 'rift_stone' ? 'active' : ''; ?>"><?php echo t('market.items.tab.rifts'); ?></a>
            <a href="/market?tab=items&item_type=potion" class="market-resource-tab <?php echo $item_type_filter === 'potion' ? 'active' : ''; ?>"><?php echo t('market.items.tab.potions'); ?></a>
        </div>

        <?php if ($item_type_filter === 'gear'): ?>
        <!-- Affix Filter -->
        <div class="market-resource-nav">
            <a href="/market?tab=items&item_type=gear" class="market-resource-tab <?php echo empty($affix_filter) ? 'active' : ''; ?>"><?php echo t('market.items.all'); ?></a>
            <?php foreach ($gear_affix_defs as $affix_key => $affix_def):
                $is_active = in_array($affix_key, $affix_filter, true);
                if ($is_active) {
                    $toggled = array_values(array_filter($affix_filter, fn($a) => $a !== $affix_key));
                } else {
                    $toggled = array_merge($affix_filter, [$affix_key]);
                }
                $affix_href = '/market?tab=items&item_type=gear' . (!empty($toggled) ? '&' . http_build_query(['affix' => $toggled]) : '');
            ?>
                <a href="<?php echo htmlspecialchars($affix_href); ?>" class="market-resource-tab <?php echo $is_active ? 'active' : ''; ?>"><?php echo htmlspecialchars(t('gear.affix.' . $affix_key)); ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($listed_items)): ?>
            <p><em><?php echo t('market.items.no_items', ['type' => $item_type_filter === 'rift_stone' ? t('market.items.tab.rifts') : ($item_type_filter === 'gear' ? t('market.items.tab.gear') : t('market.items.tab.potions'))]); ?></em></p>
        <?php else: ?>
            <?php foreach ($listed_items as $item): ?>
                <div class="card <?php echo $item['is_own'] ? 'market-own-listing-card' : ''; ?>">
                    <div class="grid">
                        <div>
                            <?php if ($item_type_filter === 'gear'): ?>
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p>
                                    <b><?php echo t('common.type'); ?></b> <?php echo htmlspecialchars(t('gear.item.' . ($item['type'] ?? ''))); ?> |
                                    <b><?php echo t('common.slot'); ?></b> <?php echo htmlspecialchars(t('inventory.slot.' . ($item['slot'] ?? ''))); ?>
                                </p>
                                <?php if (!empty($item['base_bonuses'])): ?>
                                    <p><b><?php echo t('market.items.base'); ?></b> <?php
                                        $b = [];
                                        foreach ($item['base_bonuses'] as $stat => $val) {
                                            $b[] = '+' . $val . '% ' . t('gear.stat_bonus.' . $stat);
                                        }
                                        echo htmlspecialchars(implode(', ', $b));
                                    ?></p>
                                <?php endif; ?>
                                <?php if (!empty($item['affixes'])): ?>
                                    <ul class="list--compact">
                                        <?php foreach ($item['affixes'] as $affix): ?>
                                            <?php $affix_key = $affix['key'] ?? ($affix['name'] ?? ''); ?>
                                            <li class="list-item--positive">
                                                +<?php echo $affix['value']; ?><?php echo $affix['type'] === 'percent' ? '%' : ''; ?>
                                                <?php echo htmlspecialchars(t('gear.affix.' . $affix_key)); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            <?php elseif ($item_type_filter === 'rift_stone'): ?>
                                <h3><?php echo htmlspecialchars($item['name'] ?? t('market.items.tab.rifts')); ?></h3>
                                <p><b><?php echo t('common.level'); ?></b> <?php echo (int)($item['level'] ?? 0); ?></p>
                                <?php if (!empty($item['implicit']) && isset($rift_implicit_defs[$item['implicit']])): ?>
                                    <p><b><?php echo t('market.items.implicit'); ?></b> <span class="list-item--positive"><?php echo htmlspecialchars(t('riftstone.implicit.' . $item['implicit'] . '.description')); ?></span></p>
                                <?php endif; ?>
                                <?php if (!empty($item['affixes'])): ?>
                                    <ul class="list--compact">
                                        <?php foreach ($item['affixes'] as $affix_key): ?>
                                            <?php if (isset($rift_affix_defs[$affix_key])): ?>
                                                <li class="list-item--negative"><?php echo htmlspecialchars(t('riftstone.affix.' . $affix_key . '.description')); ?></li>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            <?php else: /* potion */ ?>
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p><b><?php echo t('common.level'); ?></b> <?php echo $item['level']; ?></p>
                                <ul class="list--compact">
                                    <?php if (isset($potion_prefix_defs[$item['prefix']])): ?>
                                        <li>+<?php echo $item['level'] * $potion_prefix_defs[$item['prefix']]['per_level']; ?>% <?php echo htmlspecialchars(t('potion.affix.' . $item['prefix'])); ?></li>
                                    <?php endif; ?>
                                    <?php if (isset($potion_suffix_defs[$item['suffix']])): ?>
                                        <li>+<?php echo $item['level'] * $potion_suffix_defs[$item['suffix']]['per_level']; ?>% <?php echo htmlspecialchars(t('potion.affix.' . $item['suffix'])); ?></li>
                                    <?php endif; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                        <div>
                            <p class="market-item-price"><span class="text--gold"><?php echo human_num($item['market_price']); ?> <?php echo t('res.gold'); ?></span></p>
                            <?php if ($item['is_own']): ?>
                                <em class="market-own-order"><?php echo t('market.items.your_listing'); ?></em>
                            <?php else: ?>
                                <form method="POST" action="/market?tab=items&item_type=<?php echo htmlspecialchars($item_type_filter); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                    <input type="hidden" name="item_type" value="<?php echo htmlspecialchars($item_type_filter); ?>">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="expected_price" value="<?php echo $item['market_price']; ?>">
                                    <input type="submit" name="buy_item" value="<?php echo t('market.items.buy_for', ['price' => human_num($item['market_price'])]); ?>" class="success-button">
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php elseif ($active_tab === 'list_item'): ?>
        <!-- List Item Tab -->
        <h2 class="heading--no-top-margin"><?php echo t('market.list.title'); ?></h2>
        <p><small><?php echo t('market.list.desc'); ?></small></p>

        <!-- Item Type Sub-Nav -->
        <div class="market-resource-nav">
            <a href="/market?tab=list_item&item_type=gear" class="market-resource-tab <?php echo $item_type_filter === 'gear' ? 'active' : ''; ?>"><?php echo t('market.items.tab.gear'); ?></a>
            <a href="/market?tab=list_item&item_type=rift_stone" class="market-resource-tab <?php echo $item_type_filter === 'rift_stone' ? 'active' : ''; ?>"><?php echo t('market.items.tab.rifts'); ?></a>
            <a href="/market?tab=list_item&item_type=potion" class="market-resource-tab <?php echo $item_type_filter === 'potion' ? 'active' : ''; ?>"><?php echo t('market.items.tab.potions'); ?></a>
        </div>

        <?php
            $unlisted = match($item_type_filter) {
                'rift_stone' => $my_unlisted_rifts,
                'potion'     => $my_unlisted_potions,
                default      => $my_unlisted_gear,
            };
        ?>
        <?php if (empty($unlisted)): ?>
            <p><em><?php echo t('market.list.no_items', ['type' => $item_type_filter === 'rift_stone' ? t('market.items.tab.rifts') : ($item_type_filter === 'gear' ? t('market.items.tab.gear') : t('market.items.tab.potions'))]); ?></em></p>
        <?php else: ?>
            <?php foreach ($unlisted as $item): ?>
                <div class="card market-list-card">
                    <div class="grid">
                        <div>
                            <?php if ($item_type_filter === 'gear'): ?>
                                <p>
                                    <b><?php echo htmlspecialchars($item['name']); ?></b>
                                    &mdash; <?php echo htmlspecialchars(t('gear.item.' . ($item['type'] ?? ''))); ?>, <?php echo htmlspecialchars(t('inventory.slot.' . ($item['slot'] ?? ''))); ?>
                                </p>
                                <?php if (!empty($item['affixes'])): ?>
                                    <p class="market-list-affixes"><?php
                                        $aff = [];
                                        foreach ($item['affixes'] as $affix) {
                                            $affix_key = $affix['key'] ?? ($affix['name'] ?? '');
                                            $aff[] = '+' . $affix['value'] . ($affix['type'] === 'percent' ? '%' : '') . ' ' . t('gear.affix.' . $affix_key);
                                        }
                                        echo htmlspecialchars(implode(' &bull; ', $aff));
                                    ?></p>
                                <?php endif; ?>
                            <?php elseif ($item_type_filter === 'rift_stone'): ?>
                                <p>
                                    <b><?php echo htmlspecialchars($item['name'] ?? t('market.items.tab.rifts')); ?></b>
                                    &mdash; <?php echo t('market.list.level', ['level' => (int)($item['level'] ?? 0)]); ?>
                                    <?php if (!empty($item['implicit']) && isset($rift_implicit_defs[$item['implicit']])): ?>
                                        &mdash; <span class="list-item--positive"><?php echo htmlspecialchars(t('riftstone.implicit.' . $item['implicit'] . '.name')); ?></span>
                                    <?php endif; ?>
                                </p>
                            <?php else: ?>
                                <p>
                                    <b><?php echo htmlspecialchars($item['name']); ?></b>
                                    &mdash; <?php echo t('market.list.level', ['level' => $item['level']]); ?>
                                    <?php if (isset($potion_prefix_defs[$item['prefix']])): ?>
                                        &bull; +<?php echo $item['level'] * $potion_prefix_defs[$item['prefix']]['per_level']; ?>% <?php echo htmlspecialchars(t('potion.affix.' . $item['prefix'])); ?>
                                    <?php endif; ?>
                                    <?php if (isset($potion_suffix_defs[$item['suffix']])): ?>
                                        &bull; +<?php echo $item['level'] * $potion_suffix_defs[$item['suffix']]['per_level']; ?>% <?php echo htmlspecialchars(t('potion.affix.' . $item['suffix'])); ?>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <form method="POST" action="/market?tab=list_item&item_type=<?php echo htmlspecialchars($item_type_filter); ?>" class="market-list-form">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <input type="hidden" name="item_type" value="<?php echo htmlspecialchars($item_type_filter); ?>">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <input type="number" name="list_price" min="1" placeholder="<?php echo t('market.list.gold_price'); ?>" required class="market-fill-input" style="width:110px;">
                                <input type="submit" name="list_item" value="<?php echo t('market.list.submit'); ?>" class="market-fill-btn">
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php elseif ($active_tab === 'post_order'): ?>
        <!-- Post Order Tab -->
        <h2 class="heading--no-top-margin"><?php echo t('market.post.title'); ?></h2>
        <p>
            <?php echo t('market.post.sell_desc'); ?><br>
            <?php echo t('market.post.buy_desc'); ?>
        </p>

        <div class="card">
            <form method="POST" action="/market?tab=post_order">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">

                <fieldset>
                    <legend><?php echo t('market.post.order_type'); ?></legend>
                    <label>
                        <input type="radio" name="order_type" value="sell" checked>
                        <span class="market-badge--sell-inline"><?php echo t('market.post.sell_label'); ?></span> — <?php echo t('market.post.sell_desc2'); ?>
                    </label>
                    <label>
                        <input type="radio" name="order_type" value="buy">
                        <span class="market-badge--buy-inline"><?php echo t('market.post.buy_label'); ?></span> — <?php echo t('market.post.buy_desc2'); ?>
                    </label>
                </fieldset>

                <label for="resource"><?php echo t('market.post.resource'); ?></label>
                <select id="resource" name="resource">
                    <option value="herbs"><?php echo t('market.post.herbs_have', ['amount' => human_num((int)($Character->Data['herbs'] ?? 0))]); ?></option>
                    <option value="iron"><?php echo t('market.post.iron_have', ['amount' => human_num((int)($Character->Data['iron'] ?? 0))]); ?></option>
                    <option value="gems"><?php echo t('market.post.gems_have', ['amount' => human_num((int)($Character->Data['gems'] ?? 0))]); ?></option>
                    <?php if (!$is_season_character): ?>
                    <option value="credits"><?php echo t('market.post.credits_have', ['amount' => human_num((int)($Character->Data['credits'] ?? 0))]); ?></option>
                    <?php endif; ?>
                </select>

                <label for="amount"><?php echo t('market.post.amount'); ?></label>
                <input type="number" id="amount" name="amount" min="1" placeholder="<?php echo t('market.post.amount_ph'); ?>" required>

                <label for="price_per_unit"><?php echo t('market.post.price_unit'); ?></label>
                <input type="number" id="price_per_unit" name="price_per_unit" min="1" placeholder="<?php echo t('market.post.price_ph'); ?>" required>

                <p class="market-balance-hint">
                    <?php echo t('market.post.your_gold'); ?> <span class="text--gold"><?php echo human_num((int)$Character->Data['gold']); ?></span>
                </p>

                <input type="submit" name="post_order" value="<?php echo t('market.post.submit'); ?>">
            </form>
        </div>

        <?php elseif ($active_tab === 'my_orders'): ?>
        <!-- My Listings Tab -->
        <h2 class="heading--no-top-margin"><?php echo t('market.my.title'); ?></h2>

        <!-- Resource Orders -->
        <h3><?php echo t('market.my.res_orders'); ?></h3>
        <?php if (empty($my_orders)): ?>
            <p><em><?php echo t('market.my.no_orders', ['post_link' => '<a href="/market?tab=post_order">' . t('market.my.post_link') . '</a>']); ?></em></p>
        <?php else: ?>
            <?php foreach ($my_orders as $order): ?>
                <div class="card">
                    <div class="grid">
                        <div>
                            <h3>
                                <?php if ($order['order_type'] === 'sell'): ?>
                                    <span class="badge market-badge--sell"><?php echo t('market.my.sell_badge'); ?></span>
                                <?php else: ?>
                                    <span class="badge market-badge--buy"><?php echo t('market.my.buy_badge'); ?></span>
                                <?php endif; ?>
                                <?php echo t('res.' . strtolower(htmlspecialchars($order['resource']))); ?>
                            </h3>
                            <p>
                                <b><?php echo t('market.my.remaining'); ?></b> <?php echo human_num((int)$order['amount_remaining']); ?> / <?php echo human_num((int)$order['amount']); ?><br>
                                <b><?php echo t('market.my.price'); ?></b> <span class="text--gold"><?php echo human_num((int)$order['price_per_unit']); ?> <?php echo t('market.my.gold_unit'); ?></span><br>
                                <b><?php echo t('market.my.escrow'); ?></b> <span class="text--gold"><?php echo human_num((int)$order['amount_remaining'] * (int)$order['price_per_unit']); ?> <?php echo t('res.gold'); ?></span><br>
                                <small><?php echo t('market.my.posted', ['date' => htmlspecialchars($order['created_at'])]); ?></small>
                            </p>
                            <?php if ($order['order_type'] === 'sell'): ?>
                                <p><small><?php echo t('market.my.cancel_sell', ['amount' => human_num((int)$order['amount_remaining']), 'resource' => t('res.' . strtolower((string)$order['resource']))]); ?></small></p>
                            <?php else: ?>
                                <p><small><?php echo t('market.my.cancel_buy', ['amount' => human_num((int)$order['amount_remaining'] * (int)$order['price_per_unit'])]); ?></small></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <form method="POST" action="/market?tab=my_orders" onsubmit="return confirm('<?php echo t('confirm.cancel_order'); ?>');">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                <input type="submit" name="cancel_order" value="<?php echo t('market.my.cancel_order'); ?>" class="contrast">
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Active Item Listings -->
        <hr>
        <h3><?php echo t('market.my.items_title'); ?></h3>
        <?php
            $has_listed_items = !empty($my_listed_gear) || !empty($my_listed_rifts) || !empty($my_listed_potions);
        ?>
        <?php if (!$has_listed_items): ?>
            <p><em><?php echo t('market.my.no_items'); ?></em></p>
        <?php else: ?>
            <?php foreach ($my_listed_gear as $item): ?>
                <div class="card">
                    <div class="grid">
                        <div>
                            <h3><span class="badge"><?php echo t('market.my.gear_badge'); ?></span> <?php echo htmlspecialchars($item['name']); ?></h3>
                            <p>
                                <b><?php echo t('common.type'); ?></b> <?php echo htmlspecialchars(t('gear.item.' . ($item['type'] ?? ''))); ?> |
                                <b><?php echo t('common.slot'); ?></b> <?php echo htmlspecialchars(t('inventory.slot.' . ($item['slot'] ?? ''))); ?>
                            </p>
                            <?php if (!empty($item['affixes'])): ?>
                                <ul class="list--compact">
                                    <?php foreach ($item['affixes'] as $affix): ?>
                                        <?php $affix_key = $affix['key'] ?? ($affix['name'] ?? ''); ?>
                                        <li class="list-item--positive">+<?php echo $affix['value']; ?><?php echo $affix['type'] === 'percent' ? '%' : ''; ?> <?php echo htmlspecialchars(t('gear.affix.' . $affix_key)); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            <p><b><?php echo t('market.my.listed_for'); ?></b> <span class="text--gold"><?php echo human_num($item['market_price']); ?> <?php echo t('res.gold'); ?></span></p>
                        </div>
                        <div>
                            <form method="POST" action="/market?tab=my_orders" onsubmit="return confirm('<?php echo t('confirm.unlist_item'); ?>');">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <input type="hidden" name="item_type" value="gear">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <input type="submit" name="unlist_item" value="<?php echo t('market.my.unlist'); ?>" class="contrast">
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php foreach ($my_listed_rifts as $item): ?>
                <div class="card">
                    <div class="grid">
                        <div>
                            <h3><span class="badge"><?php echo t('market.my.rift_badge'); ?></span> <?php echo htmlspecialchars($item['name'] ?? t('market.items.tab.rifts')); ?></h3>
                            <p><b><?php echo t('common.level'); ?></b> <?php echo (int)($item['level'] ?? 0); ?></p>
                            <?php if (!empty($item['implicit']) && isset($rift_implicit_defs[$item['implicit']])): ?>
                                <p><span class="list-item--positive"><?php echo htmlspecialchars(t('riftstone.implicit.' . $item['implicit'] . '.description')); ?></span></p>
                            <?php endif; ?>
                            <p><b><?php echo t('market.my.listed_for'); ?></b> <span class="text--gold"><?php echo human_num($item['market_price']); ?> <?php echo t('res.gold'); ?></span></p>
                        </div>
                        <div>
                            <form method="POST" action="/market?tab=my_orders" onsubmit="return confirm('<?php echo t('confirm.unlist_item'); ?>');">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <input type="hidden" name="item_type" value="rift_stone">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <input type="submit" name="unlist_item" value="<?php echo t('market.my.unlist'); ?>" class="contrast">
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php foreach ($my_listed_potions as $item): ?>
                <div class="card">
                    <div class="grid">
                        <div>
                            <h3><span class="badge"><?php echo t('market.my.potion_badge'); ?></span> <?php echo htmlspecialchars($item['name']); ?></h3>
                            <p><b><?php echo t('common.level'); ?></b> <?php echo $item['level']; ?></p>
                            <ul class="list--compact">
                                <?php if (isset($potion_prefix_defs[$item['prefix']])): ?>
                                    <li>+<?php echo $item['level'] * $potion_prefix_defs[$item['prefix']]['per_level']; ?>% <?php echo htmlspecialchars(t('potion.affix.' . $item['prefix'])); ?></li>
                                <?php endif; ?>
                                <?php if (isset($potion_suffix_defs[$item['suffix']])): ?>
                                    <li>+<?php echo $item['level'] * $potion_suffix_defs[$item['suffix']]['per_level']; ?>% <?php echo htmlspecialchars(t('potion.affix.' . $item['suffix'])); ?></li>
                                <?php endif; ?>
                            </ul>
                            <p><b><?php echo t('market.my.listed_for'); ?></b> <span class="text--gold"><?php echo human_num($item['market_price']); ?> <?php echo t('res.gold'); ?></span></p>
                        </div>
                        <div>
                            <form method="POST" action="/market?tab=my_orders" onsubmit="return confirm('<?php echo t('confirm.unlist_item'); ?>');">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <input type="hidden" name="item_type" value="potion">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <input type="submit" name="unlist_item" value="<?php echo t('market.my.unlist'); ?>" class="contrast">
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <p><small><a href="/market?tab=list_item"><?php echo t('market.my.list_link'); ?> &rarr;</a></small></p>

        <?php endif; ?>

    </article>
    </div>
    </div>
</main>
