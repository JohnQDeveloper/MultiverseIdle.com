<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1>Inventory</h1>
        <p>Manage your gear collection. Favorite items to keep them at the top, or destroy items you no longer need.</p>

        <?php if (empty($player_items)): ?>
            <p><em>You don't have any gear yet. Visit the <a href="/craft">Craft</a> page to create some!</em></p>
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
                            <form method="POST" action="/inventory" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <input type="hidden" name="gear_id" value="<?php echo $item['id']; ?>">
                                <input type="submit" role="button" name="toggle_favorite" value="<?php echo $item['favorite'] ? 'Unfavorite' : 'Favorite'; ?>" class="<?php echo $item['favorite'] ? 'secondary' : ''; ?>">
                            </form>
                            <form method="POST" action="/inventory" style="display: inline;" onsubmit="return confirm('Are you sure you want to destroy this item? This cannot be undone.');">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <input type="hidden" name="gear_id" value="<?php echo $item['id']; ?>">
                                <input type="submit" role="button" name="destroy_item" value="Destroy" class="contrast">
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </article>
    </div>
    </div>
</main>
