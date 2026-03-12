<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1>Inventory</h1>
        <p>Manage your gear, potion, and rift stone collections.</p>

        <?php
            $active_tab = $_GET['tab'] ?? 'gear';

            # Load rift stone definitions for display
            $rift_stone_implicit_definitions = RiftStone::getImplicitDefinitions();
            $rift_stone_affix_definitions = RiftStone::getAffixDefinitions();
        ?>

        <!-- Tab Navigation -->
        <div class="tab-nav">
            <a href="/inventory?tab=gear" class="tab-nav-item <?php echo $active_tab === 'gear' ? 'active' : ''; ?>">Gear</a>
            <a href="/inventory?tab=potions" class="tab-nav-item <?php echo $active_tab === 'potions' ? 'active' : ''; ?>">Potions</a>
            <a href="/inventory?tab=rift_stones" class="tab-nav-item <?php echo $active_tab === 'rift_stones' ? 'active' : ''; ?>">Rift Stones</a>
        </div>

        <?php if ($active_tab === 'gear'): ?>
        <!-- Gear Tab -->
        <h2>Gear Inventory</h2>
        <p>Favorite items to keep them at the top, or destroy items you no longer need.</p>

        <?php if (empty($player_items)): ?>
            <p><em>You don't have any gear yet. Visit the <a href="/craft?tab=gear">Craft</a> page to create some!</em></p>
        <?php else: ?>
            <p><b>Total Items:</b> <?php echo count($player_items); ?></p>

            <?php foreach ($player_items as $item): ?>
                <?php
                    $is_equipped = in_array($item['id'], $equipped_gear_ids);
                    $card_class = 'card';
                    if ($is_equipped) {
                        $card_class .= ' card--equipped';
                    } elseif ($item['favorite']) {
                        $card_class .= ' card--favorite';
                    }
                ?>
                <div class="<?php echo $card_class; ?>">
                    <div class="grid">
                        <div>
                            <h3>
                                <?php if ($item['favorite']): ?>
                                    <span class="text--gold">&#9733;</span>
                                <?php endif; ?>
                                <?php if (in_array($item['id'], $equipped_gear_ids)): ?>
                                    <span class="badge">EQUIPPED</span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($item['name']); ?>
                            </h3>
                            <p>
                                <b>Type:</b> <?php echo htmlspecialchars(ucfirst($item['type'])); ?> |
                                <b>Slot:</b> <?php echo htmlspecialchars(ucfirst($item['slot'])); ?>
                                <?php if (in_array($item['id'], $equipped_gear_ids)): ?>
                                    <?php
                                        # Determine which character has this equipped
                                        $equipped_by = [];
                                        if (($Character->Data['party_json']['members']['frontline']['equipped_weapon'] ?? 0) == $item['id'] ||
                                            ($Character->Data['party_json']['members']['frontline']['equipped_armor'] ?? 0) == $item['id']) {
                                            $equipped_by[] = 'Frontline';
                                        }
                                        if (($Character->Data['party_json']['members']['backline']['equipped_weapon'] ?? 0) == $item['id'] ||
                                            ($Character->Data['party_json']['members']['backline']['equipped_armor'] ?? 0) == $item['id']) {
                                            $equipped_by[] = 'Backline';
                                        }
                                    ?>
                                    | <b class="text--primary">Equipped by:</b> <?php echo implode(', ', $equipped_by); ?>
                                <?php endif; ?>
                            </p>

                            <?php if (!empty($item['base_bonuses'])): ?>
                                <p><b>Base Bonuses:</b>
                                <?php
                                    $bonuses = [];
                                    foreach ($item['base_bonuses'] as $stat => $value) {
                                        $bonuses[] = '+' . $value . '% ' . htmlspecialchars(ucfirst($stat));
                                    }
                                    echo implode(', ', $bonuses);
                                ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($item['affixes'])): ?>
                                <p><b>Affixes:</b></p>
                                <ul>
                                <?php foreach ($item['affixes'] as $affix): ?>
                                    <li>
                                        +<?php echo $affix['value']; ?><?php echo $affix['type'] === 'percent' ? '%' : ''; ?>
                                        <?php echo htmlspecialchars($affix['name']); ?>
                                        (Level <?php echo $affix['level']; ?>)
                                    </li>
                                <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <p><small>Crafted at Party Level: <?php echo $item['party_level_at_craft'] ?? 'N/A'; ?></small></p>
                        </div>
                        <div>
                            <form method="POST" action="/inventory?tab=gear" class="form--inline">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <input type="hidden" name="gear_id" value="<?php echo $item['id']; ?>">
                                <input type="submit" role="button" name="toggle_favorite" value="<?php echo $item['favorite'] ? 'Unfavorite' : 'Favorite'; ?>" class="<?php echo $item['favorite'] ? 'secondary' : ''; ?>">
                            </form>
                            <form method="POST" action="/inventory?tab=gear" class="form--inline" onsubmit="return confirm('Are you sure you want to destroy this item? This cannot be undone.');">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <input type="hidden" name="gear_id" value="<?php echo $item['id']; ?>">
                                <input type="submit" role="button" name="destroy_item" value="Destroy" class="contrast">
                            </form>
                            <?php if (!$is_equipped && ($item['market_price'] ?? 0) == 0): ?>
                                <form method="POST" action="/market?tab=my_orders" class="market-list-form" style="margin-top:6px;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                    <input type="hidden" name="item_type" value="gear">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <input type="number" name="list_price" min="1" placeholder="List price" required class="market-fill-input" style="width:100px;">
                                    <input type="submit" name="list_item" value="Sell">
                                </form>
                            <?php elseif (($item['market_price'] ?? 0) > 0): ?>
                                <p><small class="text--warning">Listed on market</small></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?> <!-- end of empty check for gear -->

        <?php elseif ($active_tab === 'potions'): ?>
        <!-- Potions Tab -->
        <h2>Potion Inventory</h2>
        <p>Manage your potion collection. Use potions for 24-hour buffs or destroy potions you no longer need.</p>

        <?php if (empty($player_potions)): ?>
            <p><em>You don't have any potions yet. Visit the <a href="/craft?tab=potions">Craft</a> page to create some!</em></p>
        <?php else: ?>
            <p><b>Total Potions:</b> <?php echo count($player_potions); ?></p>

            <?php
                # Load potion definitions for display (from Potion class)
                $potion_prefix_definitions = Potion::getPrefixDefinitions();
                $potion_suffix_definitions = Potion::getSuffixDefinitions();

                # Sort potions: active potion first, then by creation date
                usort($player_potions, function($a, $b) use ($active_potion) {
                    if ($active_potion && $active_potion['id'] == $a['id']) return -1;
                    if ($active_potion && $active_potion['id'] == $b['id']) return 1;
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });
            ?>

            <?php foreach ($player_potions as $potion): ?>
                <?php
                    # Calculate potion values
                    $prefix_value = $potion['level'] * $potion_prefix_definitions[$potion['prefix']]['per_level'];
                    $suffix_value = $potion['level'] * $potion_suffix_definitions[$potion['suffix']]['per_level'];
                    $is_active = $active_potion && $active_potion['id'] == $potion['id'];

                    # Calculate time remaining if active
                    if ($is_active) {
                        $time_remaining = strtotime($active_potion['expire_time']) - time();
                        $hours_remaining = floor($time_remaining / 3600);
                        $minutes_remaining = floor(($time_remaining % 3600) / 60);
                    }
                ?>
                <div class="card <?php echo $is_active ? 'potion-effect-box' : ''; ?>">
                    <div class="grid">
                        <div>
                            <h3>
                                <?php if ($is_active): ?>
                                    <span class="text--success">[ACTIVE]</span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($potion['name']); ?>
                            </h3>
                            <p><b>Level:</b> <?php echo $potion['level']; ?></p>

                            <p><b>Effects:</b></p>
                            <ul>
                                <li>
                                    +<?php echo $prefix_value; ?>%
                                    <?php echo htmlspecialchars($potion_prefix_definitions[$potion['prefix']]['name']); ?>
                                </li>
                                <li>
                                    +<?php echo $suffix_value; ?>%
                                    <?php echo htmlspecialchars($potion_suffix_definitions[$potion['suffix']]['name']); ?>
                                </li>
                            </ul>

                            <?php if ($is_active): ?>
                                <p><small><b class="text--success">Expires in: <?php echo $hours_remaining; ?>h <?php echo $minutes_remaining; ?>m</b></small></p>
                            <?php else: ?>
                                <p><small>Created: <?php echo $potion['created_at']; ?></small></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if (!$is_active): ?>
                                <form method="POST" action="/inventory?tab=potions" class="form--inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                    <input type="hidden" name="potion_id" value="<?php echo $potion['id']; ?>">
                                    <input type="submit" role="button" name="use_potion" value="Use" <?php echo $active_potion ? 'disabled' : ''; ?>>
                                </form>
                                <form method="POST" action="/inventory?tab=potions" class="form--inline" onsubmit="return confirm('Are you sure you want to destroy this potion? This cannot be undone.');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                    <input type="hidden" name="potion_id" value="<?php echo $potion['id']; ?>">
                                    <input type="submit" role="button" name="destroy_potion" value="Destroy" class="contrast">
                                </form>
                                <form method="POST" action="/market?tab=my_orders" class="market-list-form" style="margin-top:6px;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                    <input type="hidden" name="item_type" value="potion">
                                    <input type="hidden" name="item_id" value="<?php echo $potion['id']; ?>">
                                    <input type="number" name="list_price" min="1" placeholder="List price" required class="market-fill-input" style="width:100px;">
                                    <input type="submit" name="list_item" value="Sell">
                                </form>
                            <?php else: ?>
                                <form method="POST" action="/inventory?tab=potions" class="form--inline" onsubmit="return confirm('Are you sure you want to cancel this active potion? The potion will be deleted and effects will end immediately.');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                    <input type="submit" role="button" name="cancel_potion" value="Cancel Potion" class="contrast">
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?> <!-- end of empty check for potions -->

        <?php elseif ($active_tab === 'rift_stones'): ?>
        <!-- Rift Stones Tab -->
        <h2>Rift Stone Inventory</h2>
        <p>Manage your rift stone collection. Each stone can be used to run a 10-battle Rift Delve for rewards.</p>

        <?php if (empty($player_rift_stones)): ?>
            <p><em>You don't have any rift stones yet. Visit the <a href="/craft?tab=rift_stones">Craft</a> page to create some!</em></p>
        <?php else: ?>
            <p><b>Total Rift Stones:</b> <?php echo count($player_rift_stones); ?></p>

            <?php foreach ($player_rift_stones as $rift_stone): ?>
                <div class="card">
                    <div class="grid">
                        <div>
                            <h3><?php echo htmlspecialchars($rift_stone['name']); ?></h3>
                            <p><b>Rift Level:</b> <?php echo $rift_stone['level']; ?></p>

                            <p><b>Reward Implicit:</b></p>
                            <ul>
                                <li class="list-item--positive">
                                    <?php echo htmlspecialchars($rift_stone_implicit_definitions[$rift_stone['implicit']]['description']); ?>
                                </li>
                            </ul>

                            <p><b>Difficulty Affixes:</b></p>
                            <ul>
                                <?php foreach ($rift_stone['affixes'] as $affix_key): ?>
                                    <li class="list-item--negative">
                                        <?php echo htmlspecialchars($rift_stone_affix_definitions[$affix_key]['description']); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <p><small>Crafted at Party Level: <?php echo $rift_stone['party_level_at_craft'] ?? 'N/A'; ?></small></p>
                            <p><small>Created: <?php echo $rift_stone['created_at']; ?></small></p>
                        </div>
                        <div>
                            <form method="POST" action="/inventory?tab=rift_stones" class="form--inline" onsubmit="return confirm('Are you sure you want to destroy this rift stone? This cannot be undone.');">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <input type="hidden" name="rift_stone_id" value="<?php echo $rift_stone['id']; ?>">
                                <input type="submit" role="button" name="destroy_rift_stone" value="Destroy" class="contrast">
                            </form>
                            <?php if ($rift_stone['queue_position'] === null && ($rift_stone['market_price'] ?? 0) == 0): ?>
                                <form method="POST" action="/market?tab=my_orders" class="market-list-form" style="margin-top:6px;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                    <input type="hidden" name="item_type" value="rift_stone">
                                    <input type="hidden" name="item_id" value="<?php echo $rift_stone['id']; ?>">
                                    <input type="number" name="list_price" min="1" placeholder="List price" required class="market-fill-input" style="width:100px;">
                                    <input type="submit" name="list_item" value="Sell">
                                </form>
                            <?php elseif (($rift_stone['market_price'] ?? 0) > 0): ?>
                                <p><small class="text--warning">Listed on market</small></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?> <!-- end of empty check for rift stones -->

        <?php endif; ?> <!-- end of tab check -->

    </article>
    </div>
    </div>
</main>
