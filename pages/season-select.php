<?php require_once('../templates/game-header.php'); ?>
    <div class="wrapper">
    <article class="main">
        <h1><?php echo t('season.title'); ?></h1>
        <p><?php echo t('season.desc'); ?></p>

        <?php if ($current_season_id === null): ?>
            <p><?php echo t('season.in_perpetual'); ?></p>
        <?php else: ?>
            <p><?php echo t('season.in_season'); ?>
            <?php if ($current_season): ?>
                <?php echo t('season.ends', ['name' => htmlspecialchars($current_season['name']), 'date' => htmlspecialchars(date('M j, Y', strtotime($current_season['end_date'])))]); ?>
            <?php endif; ?>
            </p>
        <?php endif; ?>

        <div class="grid">

            <!-- Perpetual Mode Card -->
            <div class="card">
                <h2><?php echo t('season.perpetual.title'); ?></h2>
                <p><?php echo t('season.perpetual.desc'); ?></p>
                <ul>
                    <li><?php echo t('season.perpetual.li_runs'); ?></li>
                    <li><?php echo t('season.perpetual.li_merge'); ?></li>
                    <li><?php echo t('season.perpetual.li_market'); ?></li>
                </ul>
                <?php if ($current_season_id === null): ?>
                    <p><em><?php echo t('season.perpetual.active'); ?></em></p>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf-token']); ?>">
                        <button type="submit" name="play_perpetual"><?php echo t('season.perpetual.switch'); ?></button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Season Mode Card -->
            <div class="card">
                <?php if ($active_season): ?>
                    <h2><?php echo htmlspecialchars($active_season['name']); ?></h2>
                    <p><?php echo t('season.season.desc', ['date' => htmlspecialchars(date('M j, Y', strtotime($active_season['end_date'])))]); ?></p>
                    <ul>
                        <li><?php echo t('season.season.li_fresh'); ?></li>
                        <li><?php echo t('season.season.li_market'); ?></li>
                        <li><?php echo t('season.season.li_merge'); ?></li>
                    </ul>
                    <?php
                    $days_remaining = max(0, (int)ceil((strtotime($active_season['end_date']) - time()) / 86400));
                    ?>
                    <p><?php echo t('season.season.days_left', ['days' => $days_remaining]); ?></p>

                    <?php if ($current_season_id === (int)$active_season['id']): ?>
                        <p><em><?php echo t('season.season.active'); ?></em></p>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf-token']); ?>">
                            <input type="hidden" name="season_id" value="<?php echo (int)$active_season['id']; ?>">
                            <button type="submit" name="play_season">
                                <?php echo $has_season_character ? t('season.season.switch') : t('season.season.join'); ?>
                            </button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <h2><?php echo t('season.season.title'); ?></h2>
                    <p><?php echo t('season.season.no_active'); ?></p>
                <?php endif; ?>
            </div>

        </div>
    </article>
    </div>
    </div>
</main>
