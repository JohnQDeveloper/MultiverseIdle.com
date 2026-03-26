<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1><?php echo t('inventory.title'); ?></h1>
        <p><?php echo t('inventory.desc'); ?></p>

        <?php
            $active_tab = $_GET['tab'] ?? 'gear';

            # Load rift stone definitions for display
            $rift_stone_implicit_definitions = RiftStone::getImplicitDefinitions();
            $rift_stone_affix_definitions = RiftStone::getAffixDefinitions();
        ?>

        <!-- Tab Navigation -->
        <div class="tab-nav">
            <a href="/inventory?tab=gear" class="tab-nav-item <?php echo $active_tab === 'gear' ? 'active' : ''; ?>"><?php echo t('inventory.tab.gear'); ?></a>
            <a href="/inventory?tab=potions" class="tab-nav-item <?php echo $active_tab === 'potions' ? 'active' : ''; ?>"><?php echo t('inventory.tab.potions'); ?></a>
            <a href="/inventory?tab=rift_stones" class="tab-nav-item <?php echo $active_tab === 'rift_stones' ? 'active' : ''; ?>"><?php echo t('inventory.tab.rift_stones'); ?></a>
        </div>

        <?php if ($active_tab === 'gear'): ?>
        <!-- Gear Tab -->
        <h2><?php echo t('inventory.gear.title'); ?></h2>
        <p><?php echo t('inventory.gear.desc'); ?></p>

        <?php if (empty($player_items)): ?>
            <p><em><?php echo t('inventory.gear.empty', ['craft_link' => '<a href="/craft?tab=gear">' . t('inventory.gear.craft_link') . '</a>']); ?></em></p>
        <?php else: ?>
            <p><b><?php echo t('inventory.gear.total', ['count' => count($player_items)]); ?></b></p>

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
                                    <span class="badge"><?php echo t('inventory.gear.equipped'); ?></span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($item['name']); ?>
                            </h3>
                            <p>
                                <b><?php echo t('common.type'); ?></b> <?php echo htmlspecialchars(t('gear.item.' . $item['type'])); ?> |
                                <b><?php echo t('common.slot'); ?></b> <?php echo htmlspecialchars(t('inventory.slot.' . $item['slot'])); ?>
                                <?php if (in_array($item['id'], $equipped_gear_ids)): ?>
                                    <?php
                                        # Determine which character has this equipped
                                        $equipped_by = [];
                                        if (($Character->Data['party_json']['members']['frontline']['equipped_weapon'] ?? 0) == $item['id'] ||
                                            ($Character->Data['party_json']['members']['frontline']['equipped_armor'] ?? 0) == $item['id']) {
                                            $equipped_by[] = t('inventory.gear.frontline');
                                        }
                                        if (($Character->Data['party_json']['members']['backline']['equipped_weapon'] ?? 0) == $item['id'] ||
                                            ($Character->Data['party_json']['members']['backline']['equipped_armor'] ?? 0) == $item['id']) {
                                            $equipped_by[] = t('inventory.gear.backline');
                                        }
                                    ?>
                                    | <b class="text--primary"><?php echo t('inventory.gear.equipped_by'); ?></b> <?php echo implode(', ', $equipped_by); ?>
                                <?php endif; ?>
                            </p>

                            <?php if (!empty($item['base_bonuses'])): ?>
                                <p><b><?php echo t('common.base_bonuses'); ?></b>
                                <?php
                                    $bonuses = [];
                                    foreach ($item['base_bonuses'] as $stat => $value) {
                                        $bonuses[] = '+' . $value . '% ' . htmlspecialchars(t('gear.stat_bonus.' . $stat));
                                    }
                                    echo implode(', ', $bonuses);
                                ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($item['affixes'])): ?>
                                <p><b><?php echo t('common.affixes'); ?></b></p>
                                <ul>
                                <?php foreach ($item['affixes'] as $affix): ?>
                                    <?php
                                        $affix_key = $affix['key'] ?? '';
                                        $affix_name = $affix_key !== '' ? t('gear.affix.' . $affix_key) : (string)($affix['name'] ?? '');
                                    ?>
                                    <li>
                                        +<?php echo $affix['value']; ?><?php echo $affix['type'] === 'percent' ? '%' : ''; ?>
                                        <?php echo htmlspecialchars($affix_name); ?>
                                        <?php echo t('inventory.level_short', ['level' => (int)$affix['level']]); ?>
                                    </li>
                                <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <p><small><?php echo t('common.crafted_at', ['level' => $item['party_level_at_craft'] ?? t('common.n_a')]); ?></small></p>
                        </div>
                        <div>
                            <form method="POST" action="/inventory?tab=gear" class="form--inline">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <input type="hidden" name="gear_id" value="<?php echo $item['id']; ?>">
                                <input type="submit" role="button" name="toggle_favorite" value="<?php echo $item['favorite'] ? t('inventory.gear.unfavorite') : t('inventory.gear.favorite'); ?>" class="<?php echo $item['favorite'] ? 'secondary' : ''; ?>">
                            </form>
                            <form method="POST" action="/inventory?tab=gear" class="form--inline" onsubmit="return confirm('<?php echo t('confirm.destroy_item'); ?>');">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <input type="hidden" name="gear_id" value="<?php echo $item['id']; ?>">
                                <input type="submit" role="button" name="destroy_item" value="<?php echo t('common.destroy'); ?>" class="contrast">
                            </form>
                            <?php if (!$is_equipped && ($item['market_price'] ?? 0) == 0): ?>
                                <form method="POST" action="/market?tab=my_orders" class="market-list-form" style="margin-top:6px;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                    <input type="hidden" name="item_type" value="gear">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <input type="number" name="list_price" min="1" placeholder="<?php echo t('inventory.gear.list_price'); ?>" required class="market-fill-input" style="width:100px;">
                                    <input type="submit" name="list_item" value="<?php echo t('common.sell'); ?>">
                                </form>
                            <?php elseif (($item['market_price'] ?? 0) > 0): ?>
                                <p><small class="text--warning"><?php echo t('inventory.gear.listed'); ?></small></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?> <!-- end of empty check for gear -->

        <?php elseif ($active_tab === 'potions'): ?>
        <!-- Potions Tab -->
        <h2><?php echo t('inventory.potions.title'); ?></h2>
        <p><?php echo t('inventory.potions.desc'); ?></p>

        <?php if (empty($player_potions)): ?>
            <p><em><?php echo t('inventory.potions.empty', ['craft_link' => '<a href="/craft?tab=potions">' . t('inventory.potions.craft_link') . '</a>']); ?></em></p>
        <?php else: ?>
            <p><b><?php echo t('inventory.potions.total', ['count' => count($player_potions)]); ?></b></p>

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
                                    <span class="text--success"><?php echo t('inventory.potions.active'); ?></span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($potion['name']); ?>
                            </h3>
                            <p><b><?php echo t('common.level'); ?></b> <?php echo $potion['level']; ?></p>

                            <p><b><?php echo t('common.effects'); ?></b></p>
                            <ul>
                                <li>
                                    +<?php echo $prefix_value; ?>%
                                    <?php echo htmlspecialchars(t('potion.affix.' . $potion['prefix'])); ?>
                                </li>
                                <li>
                                    +<?php echo $suffix_value; ?>%
                                    <?php echo htmlspecialchars(t('potion.affix.' . $potion['suffix'])); ?>
                                </li>
                            </ul>

                            <?php if ($is_active): ?>
                                <p><small><b class="text--success"><?php echo t('common.expires_in', ['h' => $hours_remaining, 'm' => $minutes_remaining]); ?></b></small></p>
                            <?php else: ?>
                                <p><small><?php echo t('inventory.potions.created', ['date' => $potion['created_at']]); ?></small></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if (!$is_active): ?>
                                <form method="POST" action="/inventory?tab=potions" class="form--inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                    <input type="hidden" name="potion_id" value="<?php echo $potion['id']; ?>">
                                    <input type="submit" role="button" name="use_potion" value="<?php echo t('inventory.potions.use'); ?>" <?php echo $active_potion ? 'disabled' : ''; ?>>
                                </form>
                                <form method="POST" action="/inventory?tab=potions" class="form--inline" onsubmit="return confirm('<?php echo t('confirm.destroy_potion'); ?>');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                    <input type="hidden" name="potion_id" value="<?php echo $potion['id']; ?>">
                                    <input type="submit" role="button" name="destroy_potion" value="<?php echo t('common.destroy'); ?>" class="contrast">
                                </form>
                                <form method="POST" action="/market?tab=my_orders" class="market-list-form" style="margin-top:6px;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                    <input type="hidden" name="item_type" value="potion">
                                    <input type="hidden" name="item_id" value="<?php echo $potion['id']; ?>">
                                    <input type="number" name="list_price" min="1" placeholder="<?php echo t('inventory.gear.list_price'); ?>" required class="market-fill-input" style="width:100px;">
                                    <input type="submit" name="list_item" value="<?php echo t('common.sell'); ?>">
                                </form>
                            <?php else: ?>
                                <form method="POST" action="/inventory?tab=potions" class="form--inline" onsubmit="return confirm('<?php echo t('confirm.cancel_potion'); ?>');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                    <input type="submit" role="button" name="cancel_potion" value="<?php echo t('inventory.potions.cancel'); ?>" class="contrast">
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?> <!-- end of empty check for potions -->

        <?php elseif ($active_tab === 'rift_stones'): ?>
        <!-- Rift Stones Tab -->
        <h2><?php echo t('inventory.rifts.title'); ?></h2>
        <p><?php echo t('inventory.rifts.desc'); ?></p>

        <?php if (empty($player_rift_stones)): ?>
            <p><em><?php echo t('inventory.rifts.empty', ['craft_link' => '<a href="/craft?tab=rift_stones">' . t('inventory.rifts.craft_link') . '</a>']); ?></em></p>
        <?php else: ?>
            <p><b><?php echo t('inventory.rifts.total', ['count' => count($player_rift_stones)]); ?></b></p>

            <?php foreach ($player_rift_stones as $rift_stone): ?>
                <div class="card">
                    <div class="grid">
                        <div>
                            <h3><?php echo htmlspecialchars($rift_stone['name']); ?></h3>
                            <p><b><?php echo t('common.rift_level'); ?></b> <?php echo $rift_stone['level']; ?></p>

                            <p><b><?php echo t('common.reward_implicit'); ?></b></p>
                            <ul>
                                <li class="list-item--positive">
                                    <?php echo htmlspecialchars(t('riftstone.implicit.' . $rift_stone['implicit'] . '.description')); ?>
                                </li>
                            </ul>

                            <p><b><?php echo t('common.diff_affixes'); ?></b></p>
                            <ul>
                                <?php foreach ($rift_stone['affixes'] as $affix_key): ?>
                                    <li class="list-item--negative">
                                        <?php echo htmlspecialchars(t('riftstone.affix.' . $affix_key . '.description')); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <p><small><?php echo t('common.crafted_at', ['level' => $rift_stone['party_level_at_craft'] ?? t('common.n_a')]); ?></small></p>
                            <p><small><?php echo t('inventory.rifts.created', ['date' => $rift_stone['created_at']]); ?></small></p>
                        </div>
                        <div>
                            <form method="POST" action="/inventory?tab=rift_stones" class="form--inline" onsubmit="return confirm('<?php echo t('confirm.destroy_rift'); ?>');">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <input type="hidden" name="rift_stone_id" value="<?php echo $rift_stone['id']; ?>">
                                <input type="submit" role="button" name="destroy_rift_stone" value="<?php echo t('common.destroy'); ?>" class="contrast">
                            </form>
                            <?php if ($rift_stone['queue_position'] === null && ($rift_stone['market_price'] ?? 0) == 0): ?>
                                <form method="POST" action="/market?tab=my_orders" class="market-list-form" style="margin-top:6px;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                    <input type="hidden" name="item_type" value="rift_stone">
                                    <input type="hidden" name="item_id" value="<?php echo $rift_stone['id']; ?>">
                                    <input type="number" name="list_price" min="1" placeholder="<?php echo t('inventory.gear.list_price'); ?>" required class="market-fill-input" style="width:100px;">
                                    <input type="submit" name="list_item" value="<?php echo t('common.sell'); ?>">
                                </form>
                            <?php elseif (($rift_stone['market_price'] ?? 0) > 0): ?>
                                <p><small class="text--warning"><?php echo t('inventory.gear.listed'); ?></small></p>
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
