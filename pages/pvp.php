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
                <li><?php echo t('pvp.li_wyrdstone'); ?></li>
                <li><?php echo t('pvp.li_no_consequence'); ?></li>
            </ul>
        </div>

        <!-- Battle Simulation (QoL sub only) -->
        <div class="info-box">
            <h3 class="heading--no-top-margin"><?php echo t('pvp.simulate_title'); ?></h3>
            <p><?php echo t('pvp.simulate_desc'); ?></p>
            <div id="pvp-simulate-result" hidden
                 data-wins-label="<?php echo htmlspecialchars(t('pvp.simulate_wins'), ENT_QUOTES); ?>"
                 data-losses-label="<?php echo htmlspecialchars(t('pvp.simulate_losses'), ENT_QUOTES); ?>"
                 data-win-rate-label="<?php echo htmlspecialchars(t('pvp.simulate_win_rate'), ENT_QUOTES); ?>"
                 data-mirror-note="<?php echo htmlspecialchars(t('pvp.simulate_mirror_note'), ENT_QUOTES); ?>"
                 data-opponents-note="<?php echo htmlspecialchars(t('pvp.simulate_opponents_note'), ENT_QUOTES); ?>">
            </div>
            <input type="button"
                   id="pvp-simulate-btn"
                   value="<?php echo htmlspecialchars(t('pvp.simulate_btn'), ENT_QUOTES); ?>"
                   onclick="simulatePvP(this)"
                   data-csrf="<?php echo htmlspecialchars($_SESSION['csrf-token'], ENT_QUOTES); ?>"
                   data-loading="<?php echo htmlspecialchars(t('pvp.simulate_loading'), ENT_QUOTES); ?>"
                   data-error-text="<?php echo htmlspecialchars(t('pvp.simulate_error'), ENT_QUOTES); ?>"
                   <?php if (!$has_active_sub): ?>
                   disabled title="<?php echo htmlspecialchars(t('pvp.simulate_requires_sub'), ENT_QUOTES); ?>"
                   <?php endif; ?>>
        </div>

        <!-- Active Queue -->
        <h2><?php echo t('pvp.queue_title', ['count' => count($queued_chests), 'max' => $queue_max]); ?></h2>

        <?php if (empty($queued_chests)): ?>
            <p><em><?php echo t('pvp.queue_empty'); ?></em></p>
        <?php else: ?>
            <div class="pvp-queue-list">
                <?php foreach ($queued_chests as $chest): ?>
                    <?php
                        $queue_type = $chest['queue_type'] ?? TreasureChest::ENTRY_TYPE_CHEST;
                        $size = $TreasureChest->GetQueueEntryDisplaySize($chest);
                        $storage_size = $chest['chest_size'];
                        $is_wyrdstone_node = $queue_type === TreasureChest::ENTRY_TYPE_WYRDSTONE_NODE;
                        $reward = $is_wyrdstone_node
                            ? TreasureChest::WYRDSTONE_NODE_REWARDS[$storage_size]
                            : TreasureChest::REWARD_BASE * TreasureChest::CHEST_MULTIPLIERS[$storage_size];
                    ?>
                    <div class="card card--active">
                        <h3 class="heading--no-top-margin">
                            <span class="badge">
                                <?php echo t('pvp.position', ['pos' => $chest['queue_position']]); ?>
                            </span>
                            <?php echo t($is_wyrdstone_node ? 'pvp.node_' . $size : 'pvp.chest_' . $size); ?>
                        </h3>
                        <ul class="list--compact">
                            <li class="list-item--positive">
                                <?php echo t(
                                    $is_wyrdstone_node ? 'pvp.reward_wyrdstone_on_win' : 'pvp.reward_on_win',
                                    ['amount' => number_format($reward)]
                                ); ?>
                            </li>
                        </ul>
                        <form method="POST" action="/pvp"
                              onsubmit="return confirm('<?php echo t('confirm.remove_pvp_queue'); ?>');">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                            <input type="hidden" name="chest_id" value="<?php echo (int)$chest['id']; ?>">
                            <input type="submit" role="button" name="remove_chest"
                                   value="<?php echo t('pvp.remove_from_queue'); ?>" class="secondary pvp-card__button">
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Add to Queue -->
        <h2><?php echo t('pvp.add_title'); ?></h2>

        <?php if (count($queued_chests) >= $queue_max): ?>
            <p class="text--warning"><em><?php echo t('pvp.queue_full_msg', ['max' => $queue_max]); ?></em></p>
        <?php else: ?>
            <?php $slots_available = $queue_max - count($queued_chests); ?>
            <div class="pvp-queue-grid">
                <?php foreach (TreasureChest::CHEST_BATTLES as $size => $battle_count): ?>
                    <?php $reward = TreasureChest::REWARD_BASE * $battle_count; ?>
                    <div class="card pvp-card">
                        <h3 class="heading--no-top-margin"><?php echo t('pvp.chest_' . $size); ?></h3>
                        <p><?php echo t('pvp.chest_desc_' . $size); ?></p>
                        <ul class="list--compact">
                            <li class="list-item--positive">
                                <?php echo t('pvp.reward_on_win', ['amount' => number_format($reward)]); ?>
                            </li>
                        </ul>
                        <form method="POST" action="/pvp" class="pvp-card__form">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                            <input type="hidden" name="chest_size" value="<?php echo $size; ?>">
                            <div role="group" class="pvp-card__actions">
                                <input type="number" name="quantity" value="1" min="1"
                                       class="pvp-card__quantity"
                                       max="<?php echo $slots_available; ?>">
                                <input type="submit" name="queue_chest"
                                       class="pvp-card__button"
                                       value="<?php echo t('pvp.queue_btn'); ?>">
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
                <?php foreach (TreasureChest::WYRDSTONE_NODE_STORAGE_SIZES as $size => $storage_size): ?>
                    <?php $reward = TreasureChest::WYRDSTONE_NODE_REWARDS[$storage_size]; ?>
                    <div class="card pvp-card">
                        <h3 class="heading--no-top-margin"><?php echo t('pvp.node_' . $size); ?></h3>
                        <p><?php echo t('pvp.node_desc_' . $size); ?></p>
                        <ul class="list--compact">
                            <li class="list-item--positive">
                                <?php echo t('pvp.reward_wyrdstone_on_win', ['amount' => number_format($reward)]); ?>
                            </li>
                        </ul>
                        <form method="POST" action="/pvp" class="pvp-card__form">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                            <input type="hidden" name="node_size" value="<?php echo $size; ?>">
                            <div role="group" class="pvp-card__actions">
                                <input type="number" name="quantity" value="1" min="1"
                                       class="pvp-card__quantity"
                                       max="<?php echo $slots_available; ?>">
                                <input type="submit" name="queue_wyrdstone_node"
                                       class="pvp-card__button"
                                       value="<?php echo t('pvp.queue_node_btn'); ?>">
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
<script src="/js/pvp-simulate.js?v=<?php echo (string)filemtime(__DIR__ . '/../public/js/pvp-simulate.js'); ?>"></script>
