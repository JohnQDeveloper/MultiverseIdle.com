<?php require_once('../templates/game-header.php'); ?>
    <div class="wrapper">
    <article class="main">
        <h1><?php echo t('guild_bank.title'); ?></h1>

        <?php if ($user_guild_id === null): ?>
            <div class="info-box">
                <h3 class="heading--no-top-margin"><?php echo t('guild_bank.not_in_guild'); ?></h3>
                <p><?php echo t('guild_bank.not_in_desc'); ?></p>
            </div>
        <?php else: ?>
            <p style="margin-top: 0;">
                <a href="/guilds"><?php echo t('guild_bank.back', ['name' => htmlspecialchars($guild_data['name'])]); ?></a>
            </p>

            <!-- Balances -->
            <div class="card">
                <h2 class="heading--no-top-margin"><?php echo t('guild_bank.balances'); ?></h2>
                <div class="grid" style="grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 10px;">
                    <?php foreach (['gold', 'iron', 'herbs', 'gems'] as $key): ?>
                        <div style="text-align: center; padding: 10px; border: 1px solid #444;">
                            <div style="font-size: 0.85em; color: #999;"><?php echo t('res.' . $key); ?></div>
                            <div style="font-size: 1.2em; font-weight: bold;"><?php echo number_format($guild_bank[$key]); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Donate -->
            <div class="card">
                <h2 class="heading--no-top-margin"><?php echo t('guild_bank.donate'); ?></h2>
                <form method="post" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                    <select name="commodity" style="padding: 6px;">
                        <option value="gold"><?php echo t('res.gold'); ?></option>
                        <option value="iron"><?php echo t('res.iron'); ?></option>
                        <option value="herbs"><?php echo t('res.herbs'); ?></option>
                        <option value="gems"><?php echo t('res.gems'); ?></option>
                    </select>
                    <input type="number" name="amount" min="1" placeholder="<?php echo t('guild_bank.amount_ph'); ?>" style="width: 120px; padding: 6px;">
                    <button type="submit" name="donate_to_bank" class="button button--primary"><?php echo t('guild_bank.donate_submit'); ?></button>
                </form>
            </div>

            <!-- Tax Rate (officers/master only) -->
            <?php if ($user_role === 'guild_master' || $user_role === 'officer'): ?>
                <div class="card">
                    <h2 class="heading--no-top-margin"><?php echo t('guild_bank.tax_title'); ?></h2>
                    <p style="margin-top: 0; color: #999;">
                        <?php echo t('guild_bank.tax_desc', ['rate' => $guild_tax_rate]); ?>
                    </p>
                    <form method="post" style="display: flex; align-items: center; gap: 10px;">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                        <label for="tax_rate"><b><?php echo t('guild_bank.tax_label'); ?></b></label>
                        <input type="number" id="tax_rate" name="tax_rate" min="0" max="20" value="<?php echo $guild_tax_rate; ?>" style="width: 70px; padding: 6px; text-align: center;">
                        <span style="color: #999;"><?php echo t('guild_bank.tax_range'); ?></span>
                        <button type="submit" name="set_tax_rate" class="button button--primary"><?php echo t('guild_bank.tax_submit'); ?></button>
                    </form>
                </div>
            <?php else: ?>
                <div class="info-box">
                    <p style="margin: 0;"><?php echo t('guild_bank.tax_view', ['rate' => $guild_tax_rate]); ?>
                        <small style="color: #999;"><?php echo t('guild_bank.tax_set_by'); ?></small>
                    </p>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </article>
    </div>
<?php require_once('../templates/footer.php'); ?>
