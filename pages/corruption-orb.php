<?php require_once('../templates/game-header.php'); ?>
    <div class="wrapper">
    <article class="main">
        <h1><?php echo t('corruption.orb.title'); ?></h1>

        <p><?php echo t('corruption.orb.desc'); ?></p>

        <div class="resource-display">
            <strong><?php echo t('corruption.orb.owned'); ?>:</strong>
            <?php echo number_format($corruption_orbs); ?>
        </div>

        <?php if ($corruption_orbs > 0 && !empty($corruptible_gear)): ?>
        <hr>
        <h3><?php echo t('corruption.orb.apply_title'); ?></h3>
        <p><?php echo t('corruption.orb.apply_desc'); ?></p>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
            <div class="form-group">
                <label for="gear_id"><?php echo t('corruption.orb.select_gear'); ?></label>
                <select name="gear_id" id="gear_id" required>
                    <option value=""><?php echo t('corruption.orb.select_placeholder'); ?></option>
                    <?php foreach ($corruptible_gear as $item): ?>
                    <option value="<?php echo (int)$item['id']; ?>">
                        <?php echo htmlspecialchars($item['name']); ?>
                        (<?php echo htmlspecialchars(ucfirst($item['slot'] ?? '')); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <br>
            <input type="submit" name="apply_orb" value="<?php echo t('corruption.orb.apply_btn'); ?>" class="button-danger" />
        </form>
        <?php elseif ($corruption_orbs < 1): ?>
        <p class="text--warning"><?php echo t('corruption.orb.no_orbs_msg'); ?></p>
        <?php else: ?>
        <p class="text--warning"><?php echo t('corruption.orb.no_gear_msg'); ?></p>
        <?php endif; ?>

        <hr>

        <?php if (!empty($user_gear)): ?>
        <h3><?php echo t('corruption.orb.inventory_title'); ?></h3>
        <div class="gear-list">
            <?php foreach ($user_gear as $item): ?>
            <div class="gear-item<?php echo isset($item['corruption_modifier']) ? ' gear-item--corrupted' : ''; ?>">
                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                <span class="gear-slot">(<?php echo htmlspecialchars(ucfirst($item['slot'] ?? '')); ?>)</span>
                <?php if (isset($item['corruption_modifier'])): ?>
                    <?php
                    $mod     = (float)$item['corruption_modifier'];
                    $pct_int = (int)round(($mod - 1.0) * 100);
                    $pct_str = ($pct_int >= 0 ? '+' : '') . $pct_int . '%';
                    ?>
                    <?php if ($mod > 1.0): ?>
                        <span class="text--success">✦ <?php echo $pct_str; ?> <?php echo t('corruption.orb.tag_corrupted'); ?></span>
                    <?php else: ?>
                        <span class="text--danger">✦ <?php echo $pct_str; ?> <?php echo t('corruption.orb.tag_corrupted'); ?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <hr>
        <div class="boss-info">
            <h3><?php echo t('corruption.orb.how_title'); ?></h3>
            <ul>
                <li><?php echo t('corruption.orb.li_source'); ?></li>
                <li><?php echo t('corruption.orb.li_chance'); ?></li>
                <li><?php echo t('corruption.orb.li_one_use'); ?></li>
            </ul>
        </div>

    </article>
    </div>
    </div>
</main>
