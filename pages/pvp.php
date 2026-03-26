<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1><?php echo t('pvp.title'); ?></h1>
        <p><?php echo t('pvp.desc', ['max' => $queue_max]); ?></p>

        <!-- Last Battle Log -->
        <?php if (!empty($Character->Data['last_pvp_log'])): ?>
            <h2><?php echo t('pvp.log_title'); ?></h2>
            <p><small>
                <?php echo t('pvp.last_completed', ['time' => $Character->Data['last_pvp_time'] ?? t('pvp.never')]); ?>
            </small></p>
            <div class="log-box">
                <?php echo localize_battle_log((string)$Character->Data['last_pvp_log']); ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <h3 class="heading--no-top-margin"><?php echo t('pvp.how_title'); ?></h3>
            <ul>
                <li><?php echo t('pvp.li_queue', [
                    'max'      => $queue_max,
                    'sub_note' => $has_active_sub
                        ? ''
                        : t('pvp.li_queue_sub_note', [
                            'upgrade_link' => '<a href="/store">' . t('pvp.li_queue_upgrade') . '</a>',
                        ]),
                ]); ?></li>
                <li><?php echo t('pvp.li_matchmaking'); ?></li>
                <li><?php echo t('pvp.li_mirror'); ?></li>
                <li><?php echo t('pvp.li_reward'); ?></li>
                <li><?php echo t('pvp.li_no_consequence'); ?></li>
            </ul>
        </div>

        <!-- Active Queue -->
        <h2><?php echo t('pvp.queue_title', ['count' => count($queued_chests), 'max' => $queue_max]); ?></h2>

        <?php if (empty($queued_chests)): ?>
            <p><em><?php echo t('pvp.queue_empty'); ?></em></p>
        <?php else: ?>
            <div style="margin-bottom: 30px;">
                <?php foreach ($queued_chests as $chest): ?>
                    <?php
                        $size       = $chest['chest_size'];
                        $multiplier = TreasureChest::CHEST_MULTIPLIERS[$size];
                        $reward     = TreasureChest::REWARD_BASE * $multiplier;
                    ?>
                    <div class="card card--active">
                        <div class="grid">
                            <div>
                                <h3 class="heading--no-top-margin">
                                    <span class="badge">
                                        <?php echo t('pvp.position', ['pos' => $chest['queue_position']]); ?>
                                    </span>
                                    <?php echo t('pvp.chest_' . $size); ?>
                                </h3>
                                <ul class="list--compact">
                                    <li class="list-item--positive">
                                        <?php echo t('pvp.reward_on_win', ['amount' => number_format($reward)]); ?>
                                    </li>
                                </ul>
                            </div>
                            <div>
                                <form method="POST" action="/pvp" class="form--inline"
                                      onsubmit="return confirm('<?php echo t('confirm.remove_chest'); ?>');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                    <input type="hidden" name="chest_id" value="<?php echo (int)$chest['id']; ?>">
                                    <input type="submit" role="button" name="remove_chest"
                                           value="<?php echo t('pvp.remove_from_queue'); ?>" class="secondary">
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Add to Queue -->
        <h2><?php echo t('pvp.add_title'); ?></h2>

        <?php if (count($queued_chests) >= $queue_max): ?>
            <p class="text--warning"><em><?php echo t('pvp.queue_full_msg', ['max' => $queue_max]); ?></em></p>
        <?php else: ?>
            <div class="grid">
                <?php foreach (TreasureChest::CHEST_BATTLES as $size => $battle_count): ?>
                    <?php $reward = TreasureChest::REWARD_BASE * $battle_count; ?>
                    <div class="card">
                        <h3 class="heading--no-top-margin"><?php echo t('pvp.chest_' . $size); ?></h3>
                        <p><?php echo t('pvp.chest_desc_' . $size); ?></p>
                        <ul class="list--compact">
                            <li class="list-item--positive">
                                <?php echo t('pvp.reward_on_win', ['amount' => number_format($reward)]); ?>
                            </li>
                        </ul>
                        <?php $slots_available = $queue_max - count($queued_chests); ?>
                        <form method="POST" action="/pvp" style="margin-top: 12px;">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                            <input type="hidden" name="chest_size" value="<?php echo $size; ?>">
                            <div role="group">
                                <input type="number" name="quantity" value="1" min="1"
                                       max="<?php echo $slots_available; ?>">
                                <input type="submit" name="queue_chest"
                                       value="<?php echo t('pvp.queue_btn'); ?>">
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </article>
    </div>
    </div>
</main>
