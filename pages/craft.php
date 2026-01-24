<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1>Craft</h1>
        <p>Craft powerful gear and potions to enhance your party's abilities.</p>

        <?php
            $party_level = $Character->Data['party_json']['members']['frontline']['level'];
            $active_tab = $_GET['tab'] ?? 'gear';

            # Load gear definitions for dynamic dropdowns
            $affix_definitions = Gear::getAffixDefinitions();
            $item_type_definitions = Gear::getItemTypeDefinitions();

            # Load rift stone definitions for dynamic dropdowns
            $rift_stone_implicit_definitions = RiftStone::getImplicitDefinitions();
            $rift_stone_affix_definitions = RiftStone::getAffixDefinitions();
        ?>

        <!-- Tab Navigation -->
        <div class="tab-nav">
            <a href="/craft?tab=gear" class="tab-nav-item <?php echo $active_tab === 'gear' ? 'active' : ''; ?>">Gear Crafting</a>
            <a href="/craft?tab=potions" class="tab-nav-item <?php echo $active_tab === 'potions' ? 'active' : ''; ?>">Potion Crafting</a>
            <a href="/craft?tab=rift_stones" class="tab-nav-item <?php echo $active_tab === 'rift_stones' ? 'active' : ''; ?>">Rift Stones</a>
        </div>

        <?php if ($active_tab === 'gear'): ?>
        <!-- Gear Crafting Tab -->
        <h2>Gear Crafting</h2>
        <p>Craft powerful gear to enhance your party's abilities. Each item type provides different stat bonuses.</p>

        <form method="POST" action="/craft?tab=gear">
            <b>Select Item to Craft:</b><br />
            <select name="item_type">
                <?php
                    # Group items by slot type
                    $items_by_slot = [];
                    foreach ($item_type_definitions as $key => $item) {
                        $items_by_slot[$item['slot']][$key] = $item;
                    }
                ?>
                <?php foreach ($items_by_slot as $slot => $items): ?>
                <optgroup label="<?php echo htmlspecialchars(ucfirst($slot) . 's'); ?>">
                    <?php foreach ($items as $key => $item): ?>
                        <?php
                            # Build bonus description
                            $bonus_parts = [];
                            foreach ($item['bonuses'] as $bonus_stat => $bonus_value) {
                                $bonus_parts[] = '+' . $bonus_value . '% ' . ucfirst($bonus_stat);
                            }
                            $bonus_text = implode(', ', $bonus_parts);
                        ?>
                        <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($item['name']); ?> (<?php echo htmlspecialchars($bonus_text); ?>)</option>
                    <?php endforeach; ?>
                </optgroup>
                <?php endforeach; ?>
            </select>
            <br /><br />

            <b>Select First Affix:</b><br />
            <select name="affix_1">
                <?php
                    # Group affixes by type
                    $affixes_by_group = [
                        'Stats' => [],
                        'Damage' => [],
                        'Resistances' => []
                    ];
                    foreach ($affix_definitions as $key => $affix) {
                        if (str_contains($key, 'damage')) {
                            $affixes_by_group['Damage'][$key] = $affix;
                        } elseif (str_contains($key, 'resistance')) {
                            $affixes_by_group['Resistances'][$key] = $affix;
                        } else {
                            $affixes_by_group['Stats'][$key] = $affix;
                        }
                    }
                ?>
                <?php foreach ($affixes_by_group as $group_name => $affixes): ?>
                    <?php if (!empty($affixes)): ?>
                    <optgroup label="<?php echo htmlspecialchars($group_name); ?>">
                        <?php foreach ($affixes as $key => $affix): ?>
                            <?php
                                $suffix = $affix['type'] === 'percent' ? '%' : '';
                                $description = htmlspecialchars($affix['name']) . ' (+' . $affix['per_level'] . $suffix . ' per level)';
                            ?>
                            <option value="<?php echo htmlspecialchars($key); ?>"><?php echo $description; ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <br /><br />

            <b>Select Second Affix:</b><br />
            <select name="affix_2">
                <?php foreach ($affixes_by_group as $group_name => $affixes): ?>
                    <?php if (!empty($affixes)): ?>
                    <optgroup label="<?php echo htmlspecialchars($group_name); ?>">
                        <?php foreach ($affixes as $key => $affix): ?>
                            <?php
                                $suffix = $affix['type'] === 'percent' ? '%' : '';
                                $description = htmlspecialchars($affix['name']) . ' (+' . $affix['per_level'] . $suffix . ' per level)';
                            ?>
                            <option value="<?php echo htmlspecialchars($key); ?>"><?php echo $description; ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <br /><br />

            <p>
                <b>Potential:</b> <?php echo $party_level; ?> (100% of Party Level <?php echo $party_level; ?>)<br />
                <small>Each affix level consumes 1-5 potential and 100 Iron. Upgrades alternate between affixes until potential is exhausted.</small>
            </p>

            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
            <input type="submit" role="button" name="craft_item" value="Craft Item">
        </form>

        <?php elseif ($active_tab === 'potions'): ?>
        <!-- Potion Crafting Tab -->
        <h2>Potion Crafting</h2>
        <p>Brew potions to boost your resource gains and experience. Potions have a prefix and suffix affix.</p>

        <form method="POST" action="/craft?tab=potions">
            <b>Select Prefix Affix:</b><br />
            <select name="prefix_affix">
                <optgroup label="Worker Yields">
                    <?php foreach (['herb_worker_yield', 'gold_worker_yield', 'iron_worker_yield', 'gems_worker_yield'] as $key): ?>
                    <option value="<?php echo $key; ?>"><?php echo $potion_prefix_definitions[$key]['name']; ?> (+<?php echo $potion_prefix_definitions[$key]['per_level']; ?>% per level)</option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Resource Drops">
                    <?php foreach (['arena_resource_drops', 'rift_drops'] as $key): ?>
                    <option value="<?php echo $key; ?>"><?php echo $potion_prefix_definitions[$key]['name']; ?> (+<?php echo $potion_prefix_definitions[$key]['per_level']; ?>% per level)</option>
                    <?php endforeach; ?>
                </optgroup>
            </select>
            <br /><br />

            <b>Select Suffix Affix:</b><br />
            <select name="suffix_affix">
                <optgroup label="Experience Gains">
                    <?php foreach (['arena_xp', 'rift_xp', 'world_boss_xp'] as $key): ?>
                    <option value="<?php echo $key; ?>"><?php echo $potion_suffix_definitions[$key]['name']; ?> (+<?php echo $potion_suffix_definitions[$key]['per_level']; ?>% per level)</option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Stat Gains">
                    <?php foreach (['arena_stat_gains', 'rift_stat_gains'] as $key): ?>
                    <option value="<?php echo $key; ?>"><?php echo $potion_suffix_definitions[$key]['name']; ?> (+<?php echo $potion_suffix_definitions[$key]['per_level']; ?>% per level)</option>
                    <?php endforeach; ?>
                </optgroup>
            </select>
            <br /><br />

            <p>
                <b>Potion Level:</b> <?php echo $party_level; ?> (Equal to Party Level <?php echo $party_level; ?>)<br />
                <small>Crafting cost: <?php echo ($party_level * 100); ?> Herbs. Potion level is fixed at your current party level.</small>
            </p>

            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
            <input type="submit" role="button" name="craft_potion" value="Craft Potion">
        </form>

        <?php elseif ($active_tab === 'rift_stones'): ?>
        <!-- Rift Stones Crafting Tab -->
        <h2>Rift Stone Crafting</h2>
        <p>Craft Rift Stones to unlock challenging 10-battle Rift Delves. Choose one implicit modifier that defines your rewards, and receive 3 random difficulty affixes.</p>

        <div>
            <h3 class="heading--no-top-margin">What are Rift Delves?</h3>
            <ul>
                <li><b>10 consecutive battles</b> against random monsters</li>
                <li><b>Heal to full</b> before each battle</li>
                <li><b>Rewards only if you win all 10 battles</b></li>
                <li><b>Rift Level:</b> Rolls between 80-100% of your party level</li>
                <li><b>3 random affixes</b> make monsters stronger (can repeat)</li>
            </ul>
        </div>

        <form method="POST" action="/craft?tab=rift_stones">
            <b>Select Reward Implicit:</b><br />
            <select name="implicit">
                <?php foreach ($rift_stone_implicit_definitions as $key => $implicit): ?>
                    <option value="<?php echo htmlspecialchars($key); ?>">
                        <?php echo htmlspecialchars($implicit['name']); ?> - <?php echo htmlspecialchars($implicit['description']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br /><br />

            <b>Select Rift Level:</b><br />
            <select name="rift_level">
                <?php
                    $min_rift_level = (int)floor($party_level * 0.8);
                    for ($level = $party_level; $level >= $min_rift_level; $level--):
                ?>
                    <option value="<?php echo $level; ?>"><?php echo $level; ?></option>
                <?php endfor; ?>
            </select>
            <br />
            <small>Choose a rift level between <?php echo $min_rift_level; ?> and <?php echo $party_level; ?> (80-100% of Party Level <?php echo $party_level; ?>)</small>
            <br /><br />

            <div>
                <b>Random Affixes (3 will be rolled automatically):</b>
                <ul style="margin: 5px 0;">
                    <?php foreach ($rift_stone_affix_definitions as $affix): ?>
                        <li><?php echo htmlspecialchars($affix['description']); ?></li>
                    <?php endforeach; ?>
                </ul>
                <small><i>These affixes increase monster difficulty and can appear multiple times on the same stone.</i></small>
            </div>
            <br />
            <div>
                <b>Crafting Cost:</b> <?php echo ($party_level * 50); ?> Gems
            </div>
            <br />
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
            <input type="submit" role="button" name="craft_rift_stone" value="Craft Rift Stone">
        </form>

        <?php endif; ?>

    </article>
    </div>
    </div>
</main>
