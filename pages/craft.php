<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1><?php echo t('craft.title'); ?></h1>
        <p><?php echo t('craft.desc'); ?></p>

        <?php
            $party_level = $Character->Data['party_json']['members']['frontline']['level'];
            $active_tab = $_GET['tab'] ?? 'gear';

            # Load gear definitions for dynamic dropdowns
            $affix_definitions = Gear::getAffixDefinitions();
            $item_type_definitions = Gear::getItemTypeDefinitions();

            # Load rift stone definitions for dynamic dropdowns
            $rift_stone_implicit_definitions = RiftStone::getImplicitDefinitions();
            $rift_stone_affix_definitions = RiftStone::getAffixDefinitions();

            # Get daily highest floor completed for rift stone crafting
            $rift_owner_id = isset($_SESSION['auth_user_id']) ? (int)$_SESSION['auth_user_id'] : 0;
            if ($rift_owner_id > 0) {
                $daily_floor_key = 'daily_floor:' . $rift_owner_id . ':' . date('Y-m-d');
                $daily_highest_floor = (int)($redis->get($daily_floor_key) ?? 0);
            } else {
                $guest_daily_date = $_SESSION['guest_daily_floor_date'] ?? '';
                $daily_highest_floor = ($guest_daily_date === date('Y-m-d'))
                    ? (int)($_SESSION['guest_daily_floor_value'] ?? 0)
                    : 0;
            }
        ?>

        <!-- Tab Navigation -->
        <div class="tab-nav">
            <a href="/craft?tab=gear" class="tab-nav-item <?php echo $active_tab === 'gear' ? 'active' : ''; ?>"><?php echo t('craft.tab.gear'); ?></a>
            <a href="/craft?tab=potions" class="tab-nav-item <?php echo $active_tab === 'potions' ? 'active' : ''; ?>"><?php echo t('craft.tab.potions'); ?></a>
            <a href="/craft?tab=rift_stones" class="tab-nav-item <?php echo $active_tab === 'rift_stones' ? 'active' : ''; ?>"><?php echo t('craft.tab.rift_stones'); ?></a>
        </div>

        <?php if ($active_tab === 'gear'): ?>
        <!-- Gear Crafting Tab -->
        <h2><?php echo t('craft.gear.title'); ?></h2>
        <p><?php echo t('craft.gear.desc'); ?></p>

        <form method="POST" action="/craft?tab=gear">
            <b><?php echo t('craft.gear.select_item'); ?></b><br />
            <select name="item_type">
                <?php
                    # Group items by slot type
                    $items_by_slot = [];
                    foreach ($item_type_definitions as $key => $item) {
                        $items_by_slot[$item['slot']][$key] = $item;
                    }
                ?>
                <?php foreach ($items_by_slot as $slot => $items): ?>
                <optgroup label="<?php echo htmlspecialchars(t('craft.gear.slot.' . $slot)); ?>">
                    <?php foreach ($items as $key => $item): ?>
                        <?php
                            # Build bonus description
                            $bonus_parts = [];
                            foreach ($item['bonuses'] as $bonus_stat => $bonus_value) {
                                $bonus_parts[] = '+' . $bonus_value . '% ' . t('gear.stat_bonus.' . $bonus_stat);
                            }
                            $bonus_text = implode(', ', $bonus_parts);
                        ?>
                        <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars(t('gear.item.' . $key)); ?> (<?php echo htmlspecialchars($bonus_text); ?>)</option>
                    <?php endforeach; ?>
                </optgroup>
                <?php endforeach; ?>
            </select>
            <br /><br />

            <b><?php echo t('craft.gear.select_affix1'); ?></b><br />
            <select name="affix_1">
                <?php
                    # Group affixes by type
                    $affixes_by_group = [
                        'stats' => [],
                        'damage' => [],
                        'resistances' => []
                    ];
                    foreach ($affix_definitions as $key => $affix) {
                        if (str_contains($key, 'damage')) {
                            $affixes_by_group['damage'][$key] = $affix;
                        } elseif (str_contains($key, 'resistance')) {
                            $affixes_by_group['resistances'][$key] = $affix;
                        } else {
                            $affixes_by_group['stats'][$key] = $affix;
                        }
                    }
                ?>
                <?php foreach ($affixes_by_group as $group_name => $affixes): ?>
                    <?php if (!empty($affixes)): ?>
                    <optgroup label="<?php echo htmlspecialchars(t('craft.gear.group.' . $group_name)); ?>">
                        <?php foreach ($affixes as $key => $affix): ?>
                            <?php
                                $suffix = $affix['type'] === 'percent' ? '%' : '';
                                $description = htmlspecialchars(t('gear.affix.' . $key)) . ' (' . t('common.per_level', ['value' => $affix['per_level'], 'suffix' => $suffix]) . ')';
                            ?>
                            <option value="<?php echo htmlspecialchars($key); ?>"><?php echo $description; ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <br /><br />

            <b><?php echo t('craft.gear.select_affix2'); ?></b><br />
            <select name="affix_2">
                <?php foreach ($affixes_by_group as $group_name => $affixes): ?>
                    <?php if (!empty($affixes)): ?>
                    <optgroup label="<?php echo htmlspecialchars(t('craft.gear.group.' . $group_name)); ?>">
                        <?php foreach ($affixes as $key => $affix): ?>
                            <?php
                                $suffix = $affix['type'] === 'percent' ? '%' : '';
                                $description = htmlspecialchars(t('gear.affix.' . $key)) . ' (' . t('common.per_level', ['value' => $affix['per_level'], 'suffix' => $suffix]) . ')';
                            ?>
                            <option value="<?php echo htmlspecialchars($key); ?>"><?php echo $description; ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <br /><br />

            <p>
                <b><?php echo t('craft.gear.potential', ['level' => $party_level]); ?></b><br />
                <small><?php echo t('craft.gear.potential_note'); ?></small>
            </p>

            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
            <input type="submit" role="button" name="craft_item" value="<?php echo t('craft.gear.submit'); ?>">
        </form>

        <?php elseif ($active_tab === 'potions'): ?>
        <!-- Potion Crafting Tab -->
        <h2><?php echo t('craft.potion.title'); ?></h2>
        <p><?php echo t('craft.potion.desc'); ?></p>

        <form method="POST" action="/craft?tab=potions">
            <b><?php echo t('craft.potion.select_prefix'); ?></b><br />
            <select name="prefix_affix">
                <optgroup label="<?php echo t('craft.potion.group.worker_yields'); ?>">
                    <?php foreach (['herb_worker_yield', 'gold_worker_yield', 'iron_worker_yield', 'gems_worker_yield'] as $key): ?>
                    <option value="<?php echo $key; ?>"><?php echo t('potion.affix.' . $key); ?> (<?php echo t('common.per_level', ['value' => $potion_prefix_definitions[$key]['per_level'], 'suffix' => '%']); ?>)</option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="<?php echo t('craft.potion.group.resource_drops'); ?>">
                    <?php foreach (['arena_resource_drops', 'rift_drops'] as $key): ?>
                    <option value="<?php echo $key; ?>"><?php echo t('potion.affix.' . $key); ?> (<?php echo t('common.per_level', ['value' => $potion_prefix_definitions[$key]['per_level'], 'suffix' => '%']); ?>)</option>
                    <?php endforeach; ?>
                </optgroup>
            </select>
            <br /><br />

            <b><?php echo t('craft.potion.select_suffix'); ?></b><br />
            <select name="suffix_affix">
                <optgroup label="<?php echo t('craft.potion.group.xp_gains'); ?>">
                    <?php foreach (['arena_xp', 'rift_xp', 'world_boss_xp'] as $key): ?>
                    <option value="<?php echo $key; ?>"><?php echo t('potion.affix.' . $key); ?> (<?php echo t('common.per_level', ['value' => $potion_suffix_definitions[$key]['per_level'], 'suffix' => '%']); ?>)</option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="<?php echo t('craft.potion.group.stat_gains'); ?>">
                    <?php foreach (['arena_stat_gains', 'rift_stat_gains'] as $key): ?>
                    <option value="<?php echo $key; ?>"><?php echo t('potion.affix.' . $key); ?> (<?php echo t('common.per_level', ['value' => $potion_suffix_definitions[$key]['per_level'], 'suffix' => '%']); ?>)</option>
                    <?php endforeach; ?>
                </optgroup>
            </select>
            <br /><br />

            <p>
                <b><?php echo t('craft.potion.level', ['level' => $party_level]); ?></b><br />
                <small><?php echo t('craft.potion.cost_note', ['cost' => number_format($party_level * 1500)]); ?></small>
            </p>

            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
            <input type="submit" role="button" name="craft_potion" value="<?php echo t('craft.potion.submit'); ?>">
        </form>

        <?php elseif ($active_tab === 'rift_stones'): ?>
        <!-- Rift Stones Crafting Tab -->
        <h2><?php echo t('craft.rift.title'); ?></h2>
        <p><?php echo t('craft.rift.desc'); ?></p>

        <div>
            <h3 class="heading--no-top-margin"><?php echo t('craft.rift.what_title'); ?></h3>
            <ul>
                <li><?php echo t('craft.rift.li_battles'); ?></li>
                <li><?php echo t('craft.rift.li_heal'); ?></li>
                <li><?php echo t('craft.rift.li_rewards'); ?></li>
                <li><?php echo t('craft.rift.li_level'); ?></li>
                <li><?php echo t('craft.rift.li_affixes'); ?></li>
            </ul>
        </div>

        <form method="POST" action="/craft?tab=rift_stones">
            <b><?php echo t('craft.rift.select_implicit'); ?></b><br />
            <select name="implicit">
                <?php foreach ($rift_stone_implicit_definitions as $key => $implicit): ?>
                    <option value="<?php echo htmlspecialchars($key); ?>">
                        <?php echo htmlspecialchars(t('riftstone.implicit.' . $key . '.name')); ?> - <?php echo htmlspecialchars(t('riftstone.implicit.' . $key . '.description')); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br /><br />

            <b><?php echo t('craft.rift.select_level'); ?></b><br />
            <?php if ($daily_highest_floor <= 0): ?>
                <p><i><?php echo t('craft.rift.no_battles'); ?></i></p>
            <?php else: ?>
            <select name="rift_level">
                <?php
                    $min_rift_level = (int)floor($daily_highest_floor * 0.8);
                    for ($level = $daily_highest_floor; $level >= $min_rift_level; $level--):
                ?>
                    <option value="<?php echo $level; ?>"><?php echo $level; ?></option>
                <?php endfor; ?>
            </select>
            <br />
            <small><?php echo t('craft.rift.level_range', ['min' => $min_rift_level, 'max' => $daily_highest_floor, 'floor' => $daily_highest_floor]); ?></small>
            <?php endif; ?>
            <br /><br />

            <div>
                <b><?php echo t('craft.rift.random_affixes'); ?></b>
                <ul style="margin: 5px 0;">
                    <?php foreach ($rift_stone_affix_definitions as $affix_key => $affix): ?>
                        <li><?php echo htmlspecialchars(t('riftstone.affix.' . $affix_key . '.description')); ?></li>
                    <?php endforeach; ?>
                </ul>
                <small><i><?php echo t('craft.rift.affixes_note'); ?></i></small>
            </div>
            <br />
            <div>
                <b><?php echo t('craft.rift.cost', ['cost' => number_format($daily_highest_floor * 15)]); ?></b>
            </div>
            <br />
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
            <input type="submit" role="button" name="craft_rift_stone" value="<?php echo t('craft.rift.submit'); ?>">
        </form>

        <?php endif; ?>

    </article>
    </div>
    </div>
</main>
