<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1>Inventory</h1>
        <p>Manage your gear and potion collections.</p>

        <?php
            $active_tab = $_GET['tab'] ?? 'gear';
        ?>

        <!-- Tab Navigation -->
        <div style="margin-bottom: 20px; border-bottom: 2px solid #ccc;">
            <a href="/inventory?tab=gear" style="display: inline-block; padding: 10px 20px; margin-right: 5px; text-decoration: none; <?php echo $active_tab === 'gear' ? 'border-bottom: 3px solid #007bff; font-weight: bold;' : ''; ?>">Gear</a>
            <a href="/inventory?tab=potions" style="display: inline-block; padding: 10px 20px; text-decoration: none; <?php echo $active_tab === 'potions' ? 'border-bottom: 3px solid #007bff; font-weight: bold;' : ''; ?>">Potions</a>
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
                <div class="inventory-item" style="border: 1px solid #ccc; padding: 15px; margin-bottom: 15px; border-radius: 5px; <?php echo $item['favorite'] ? 'border-color: gold; background-color: rgba(255, 215, 0, 0.1);' : ''; ?>">
                    <div class="grid">
                        <div>
                            <h3>
                                <?php if ($item['favorite']): ?>
                                    <span style="color: gold;">&#9733;</span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($item['name']); ?>
                            </h3>
                            <p>
                                <b>Type:</b> <?php echo htmlspecialchars(ucfirst($item['type'])); ?> |
                                <b>Slot:</b> <?php echo htmlspecialchars(ucfirst($item['slot'])); ?>
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
                            <form method="POST" action="/inventory?tab=gear" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <input type="hidden" name="gear_id" value="<?php echo $item['id']; ?>">
                                <input type="submit" role="button" name="toggle_favorite" value="<?php echo $item['favorite'] ? 'Unfavorite' : 'Favorite'; ?>" class="<?php echo $item['favorite'] ? 'secondary' : ''; ?>">
                            </form>
                            <form method="POST" action="/inventory?tab=gear" style="display: inline;" onsubmit="return confirm('Are you sure you want to destroy this item? This cannot be undone.');">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <input type="hidden" name="gear_id" value="<?php echo $item['id']; ?>">
                                <input type="submit" role="button" name="destroy_item" value="Destroy" class="contrast">
                            </form>
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
                # Load potion definitions for display
                $potion_prefix_definitions = [
                    'herb_worker_yield' => ['name' => 'Herb Worker Yield', 'per_level' => 1],
                    'gold_worker_yield' => ['name' => 'Gold Worker Yield', 'per_level' => 1],
                    'iron_worker_yield' => ['name' => 'Iron Worker Yield', 'per_level' => 1],
                    'gems_worker_yield' => ['name' => 'Gems Worker Yield', 'per_level' => 1],
                    'arena_resource_drops' => ['name' => 'Arena Resource Drops', 'per_level' => 1],
                    'rift_drops' => ['name' => 'Rift Drops', 'per_level' => 1],
                ];

                $potion_suffix_definitions = [
                    'arena_xp' => ['name' => 'Arena XP', 'per_level' => 1],
                    'arena_stat_gains' => ['name' => 'Arena Stat Gains', 'per_level' => 1],
                    'rift_xp' => ['name' => 'Rift XP', 'per_level' => 1],
                    'rift_stat_gains' => ['name' => 'Rift Stat Gains', 'per_level' => 1],
                    'world_boss_xp' => ['name' => 'World Boss XP', 'per_level' => 100],
                ];

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
                <div class="inventory-item" style="border: <?php echo $is_active ? '3px solid #28a745' : '1px solid #ccc'; ?>; padding: 15px; margin-bottom: 15px; border-radius: 5px; <?php echo $is_active ? 'background-color: rgba(40, 167, 69, 0.1);' : ''; ?>">
                    <div class="grid">
                        <div>
                            <h3>
                                <?php if ($is_active): ?>
                                    <span style="color: #28a745; font-weight: bold;">[ACTIVE]</span>
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
                                <p><small><b style="color: #28a745;">Expires in: <?php echo $hours_remaining; ?>h <?php echo $minutes_remaining; ?>m</b></small></p>
                            <?php else: ?>
                                <p><small>Created: <?php echo $potion['created_at']; ?></small></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if (!$is_active): ?>
                                <form method="POST" action="/inventory?tab=potions" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                    <input type="hidden" name="potion_id" value="<?php echo $potion['id']; ?>">
                                    <input type="submit" role="button" name="use_potion" value="Use" <?php echo $active_potion ? 'disabled' : ''; ?>>
                                </form>
                                <form method="POST" action="/inventory?tab=potions" style="display: inline;" onsubmit="return confirm('Are you sure you want to destroy this potion? This cannot be undone.');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                    <input type="hidden" name="potion_id" value="<?php echo $potion['id']; ?>">
                                    <input type="submit" role="button" name="destroy_potion" value="Destroy" class="contrast">
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?> <!-- end of empty check for potions -->

        <?php endif; ?> <!-- end of tab check -->

    </article>
    </div>
    </div>
</main>
