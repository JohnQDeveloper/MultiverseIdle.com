<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1><?php echo t('rifts.title'); ?></h1>
        <p><?php echo t('rifts.desc', ['max' => $rift_queue_max]); ?></p>

        <div class="info-box">
            <h3 class="heading--no-top-margin"><?php echo t('rifts.how_title'); ?></h3>
            <ul>
                <li><?php echo t('rifts.li_queue', ['max' => $rift_queue_max, 'sub_note' => $has_active_sub ? '' : t('rifts.li_queue_sub_note', ['upgrade_link' => '<a href="/store">' . t('rifts.li_queue_upgrade') . '</a>'])]); ?></li>
                <li><?php echo t('rifts.li_auto'); ?></li>
                <li><?php echo t('rifts.li_battles'); ?></li>
                <li><?php echo t('rifts.li_heal'); ?></li>
                <li><?php echo t('rifts.li_all_or_nothing'); ?></li>
            </ul>
        </div>

        <!-- Rift Queue Section -->
        <h2><?php echo t('rifts.queue_title', ['count' => count($queued_rifts), 'max' => $rift_queue_max]); ?></h2>

        <?php if ($has_active_sub && !empty($queued_rifts)): ?>
            <form method="POST" action="/rifts" class="form--inline" style="margin-bottom: 16px;">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                <input type="submit" role="button" name="simulate_queue" value="<?php echo t('rifts.simulate_queue'); ?>">
            </form>
        <?php endif; ?>

        <?php if (empty($queued_rifts)): ?>
            <p><em><?php echo t('rifts.queue_empty'); ?></em></p>
        <?php else: ?>
            <div style="margin-bottom: 30px;">
                <?php foreach ($queued_rifts as $rift): ?>
                    <div class="card card--active">
                        <div class="grid">
                            <div>
                                <h3 class="heading--no-top-margin">
                                    <span class="badge">
                                        <?php echo t('rifts.position', ['pos' => $rift['queue_position']]); ?>
                                    </span>
                                    <?php echo htmlspecialchars($rift['name']); ?>
                                </h3>
                                <p><b><?php echo t('common.rift_level'); ?></b> <?php echo $rift['level']; ?></p>

                                <p><b><?php echo t('common.reward_implicit'); ?></b></p>
                                <ul class="list--compact">
                                    <li class="list-item--positive">
                                        <?php echo htmlspecialchars($rift_stone_implicit_definitions[$rift['implicit']]['description']); ?>
                                    </li>
                                </ul>

                                <p><b><?php echo t('common.diff_affixes'); ?></b></p>
                                <ul class="list--compact">
                                    <?php foreach ($rift['affixes'] as $affix_key): ?>
                                        <li class="<?php echo RiftStone::isRewardAffix($affix_key) ? 'list-item--positive' : 'list-item--negative'; ?>">
                                            <?php echo htmlspecialchars($rift_stone_affix_definitions[$affix_key]['description']); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div>
                                <form method="POST" action="/rifts" class="form--inline" onsubmit="return confirm('<?php echo t('confirm.remove_rift'); ?>');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                    <input type="hidden" name="rift_stone_id" value="<?php echo $rift['id']; ?>">
                                    <input type="submit" role="button" name="remove_rift" value="<?php echo t('rifts.remove_queue'); ?>" class="secondary">
                                </form>
                                <?php if (!empty($rift_simulation_results[$rift['id']])): ?>
                                    <?php
                                        $sim = $rift_simulation_results[$rift['id']];
                                        $sim_pct = round(($sim['won'] / $sim['total']) * 100, 1);
                                        if ($sim_pct >= 90) {
                                            $sim_class = 'list-item--success';
                                        } else {
                                            $sim_class = 'list-item--negative';
                                        }
                                    ?>
                                    <p><b><?php echo t('rifts.sim_result', ['pct' => $sim_pct]); ?></b>
                                    <small><?php echo t('rifts.sim_wins_losses', ['won' => $sim['won'], 'lost' => $sim['lost']]); ?></small></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Available Rift Stones Section -->
        <h2><?php echo t('rifts.available_title'); ?></h2>

        <?php if (count($queued_rifts) >= $rift_queue_max): ?>
            <p class="text--warning"><em><?php echo t('rifts.queue_full', ['count' => $rift_queue_max, 'max' => $rift_queue_max]); ?></em></p>
        <?php endif; ?>

        <?php if (empty($available_rift_stones)): ?>
            <p><em><?php echo t('rifts.available_empty', ['craft_link' => '<a href="/craft?tab=rift_stones">' . t('rifts.craft_more') . '</a>']); ?></em></p>
        <?php else: ?>
            <p><b><?php echo t('rifts.total_available', ['count' => count($available_rift_stones)]); ?></b></p>

            <?php foreach ($available_rift_stones as $rift_stone): ?>
                <div class="card">
                    <div class="grid">
                        <div>
                            <h3 class="heading--no-top-margin"><?php echo htmlspecialchars($rift_stone['name']); ?></h3>
                            <p><b><?php echo t('common.rift_level'); ?></b> <?php echo $rift_stone['level']; ?></p>

                            <p><b><?php echo t('common.reward_implicit'); ?></b></p>
                            <ul class="list--compact">
                                <li class="list-item--positive">
                                    <?php echo htmlspecialchars($rift_stone_implicit_definitions[$rift_stone['implicit']]['description']); ?>
                                </li>
                            </ul>

                            <p><b><?php echo t('common.diff_affixes'); ?></b></p>
                            <ul class="list--compact">
                                <?php foreach ($rift_stone['affixes'] as $affix_key): ?>
                                    <li class="<?php echo RiftStone::isRewardAffix($affix_key) ? 'list-item--positive' : 'list-item--negative'; ?>">
                                        <?php echo htmlspecialchars($rift_stone_affix_definitions[$affix_key]['description']); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <p><small><?php echo t('common.crafted_at', ['level' => $rift_stone['party_level_at_craft'] ?? t('common.n_a')]); ?></small></p>
                        </div>
                        <div>
                            <form method="POST" action="/rifts" class="form--inline">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <input type="hidden" name="rift_stone_id" value="<?php echo $rift_stone['id']; ?>">
                                <input type="submit" role="button" name="queue_rift" value="<?php echo t('rifts.add_to_queue'); ?>" <?php echo count($queued_rifts) >= $rift_queue_max ? 'disabled' : ''; ?>>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Rift Battle Log -->
        <?php if (!empty($Character->Data['last_rift_log'])): ?>
            <h2><?php echo t('rifts.log_title'); ?></h2>
            <p><small><?php echo t('rifts.last_completed', ['time' => $Character->Data['last_rift_time'] ?? t('rifts.never')]); ?></small></p>
            <div class="log-box">
                <?php echo localize_battle_log((string)$Character->Data['last_rift_log']); ?>
            </div>
        <?php endif; ?>

    </article>
    </div>
    </div>
</main>
