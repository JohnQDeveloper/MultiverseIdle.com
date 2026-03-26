<?php require_once('../templates/game-header.php'); ?>
    <div class="wrapper">
    <article class="main">
        <h1><?php echo t('guild_quests.title'); ?></h1>

        <?php if ($user_guild_id === null): ?>
            <div class="info-box">
                <h3 class="heading--no-top-margin"><?php echo t('guild_quests.not_in_guild'); ?></h3>
                <p><?php echo t('guild_quests.not_in_desc'); ?></p>
            </div>
        <?php else: ?>

            <p style="margin-top: 0;">
                <a href="/guilds"><?php echo t('guild_quests.back', ['name' => htmlspecialchars($guild_data['name'])]); ?></a>
            </p>

            <?php if (!empty($alert_success)): ?>
                <div class="alert alert--success"><?php echo htmlspecialchars($alert_success); ?></div>
            <?php endif; ?>
            <?php if (!empty($alert_danger)): ?>
                <div class="alert alert--danger"><?php echo htmlspecialchars($alert_danger); ?></div>
            <?php endif; ?>

            <!-- Active Quests -->
            <div class="card">
                <h2 class="heading--no-top-margin">
                    <?php echo t('guild_quests.active_title', ['count' => $active_count, 'max' => GuildQuests::MAX_ACTIVE_QUESTS]); ?>
                </h2>

                <?php if (empty($active_quests)): ?>
                    <p style="color: #999; margin: 0;"><?php echo t('guild_quests.no_active'); ?></p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ($active_quests as $quest): ?>
                            <?php
                                $grade       = $quest['grade'];
                                $quest_type  = $quest['quest_type'];
                                $resource    = $quest['resource_type'];
                                $current     = (int)$quest['current_amount'];
                                $target      = (int)$quest['target_amount'];
                                $reward      = (int)$quest['reward_amount'];
                                $pct         = $target > 0 ? min(100, (int)round($current / $target * 100)) : 0;

                                $grade_colors = [
                                    'easy'      => '#4caf50',
                                    'normal'    => '#2196f3',
                                    'hard'      => '#ff9800',
                                    'legendary' => '#9c27b0',
                                ];
                                $grade_color = $grade_colors[$grade] ?? '#999';
                            ?>
                            <div style="border: 1px solid #444; padding: 12px; border-radius: 4px; border-left: 4px solid <?php echo $grade_color; ?>;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 6px;">
                                    <div>
                                        <span style="font-size: 0.75em; font-weight: bold; color: <?php echo $grade_color; ?>; text-transform: uppercase;">
                                            <?php echo t('guild_quests.grade.' . $grade); ?>
                                        </span>
                                        <div style="font-weight: bold; margin-top: 2px;">
                                            <?php echo t('guild_quests.type.' . $quest_type, ['target' => number_format($target)]); ?>
                                        </div>
                                    </div>
                                    <div style="text-align: right; color: #aaa; font-size: 0.9em;">
                                        <?php echo t('guild_quests.reward', ['amount' => number_format($reward), 'resource' => t('res.' . $resource)]); ?>
                                    </div>
                                </div>
                                <!-- Progress bar -->
                                <div style="margin-top: 10px;">
                                    <div style="display: flex; justify-content: space-between; font-size: 0.85em; color: #bbb; margin-bottom: 4px;">
                                        <span><?php echo t('guild_quests.progress', ['current' => number_format($current), 'target' => number_format($target)]); ?></span>
                                        <span><?php echo $pct; ?>%</span>
                                    </div>
                                    <div style="height: 8px; background: #333; border-radius: 4px; overflow: hidden;">
                                        <div style="height: 100%; width: <?php echo $pct; ?>%; background: <?php echo $grade_color; ?>; border-radius: 4px; transition: width 0.3s;"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Add Quest (officers/masters only) -->
            <?php if (in_array($user_role, ['guild_master', 'officer'], true)): ?>
                <?php if ($active_count >= GuildQuests::MAX_ACTIVE_QUESTS): ?>
                    <div class="info-box">
                        <p style="margin: 0;"><?php echo t('guild_quests.queue_full', ['max' => GuildQuests::MAX_ACTIVE_QUESTS]); ?></p>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <h2 class="heading--no-top-margin"><?php echo t('guild_quests.add_title'); ?></h2>
                        <p style="margin-top: 0; color: #999;">
                            <?php echo t('guild_quests.add_desc', ['max' => GuildQuests::MAX_ACTIVE_QUESTS]); ?>
                        </p>
                        <form method="post" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                            <label for="quest_grade"><b><?php echo t('guild_quests.grade_label'); ?></b></label>
                            <select id="quest_grade" name="grade" style="padding: 6px;">
                                <option value="easy"><?php echo t('guild_quests.grade.easy'); ?></option>
                                <option value="normal"><?php echo t('guild_quests.grade.normal'); ?></option>
                                <option value="hard"><?php echo t('guild_quests.grade.hard'); ?></option>
                                <option value="legendary"><?php echo t('guild_quests.grade.legendary'); ?></option>
                            </select>
                            <button type="submit" name="add_quest" class="button button--primary">
                                <?php echo t('guild_quests.add_submit'); ?>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Recent Completions -->
            <div class="card">
                <h2 class="heading--no-top-margin"><?php echo t('guild_quests.history_title'); ?></h2>
                <?php if (empty($recent_quests)): ?>
                    <p style="color: #999; margin: 0;"><?php echo t('guild_quests.no_history'); ?></p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <?php foreach ($recent_quests as $quest): ?>
                            <?php
                                $grade      = $quest['grade'];
                                $quest_type = $quest['quest_type'];
                                $resource   = $quest['resource_type'];
                                $target     = (int)$quest['target_amount'];
                                $reward     = (int)$quest['reward_amount'];
                                $completed  = $quest['completed_at'] ?? '';

                                $grade_colors = [
                                    'easy'      => '#4caf50',
                                    'normal'    => '#2196f3',
                                    'hard'      => '#ff9800',
                                    'legendary' => '#9c27b0',
                                ];
                                $grade_color = $grade_colors[$grade] ?? '#999';
                            ?>
                            <div style="border: 1px solid #333; padding: 10px; border-radius: 4px; border-left: 4px solid <?php echo $grade_color; ?>; opacity: 0.75;">
                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px;">
                                    <div>
                                        <span style="font-size: 0.75em; font-weight: bold; color: <?php echo $grade_color; ?>; text-transform: uppercase;">
                                            <?php echo t('guild_quests.grade.' . $grade); ?>
                                        </span>
                                        <span style="margin-left: 8px;">
                                            <?php echo t('guild_quests.type.' . $quest_type, ['target' => number_format($target)]); ?>
                                        </span>
                                    </div>
                                    <div style="text-align: right; font-size: 0.85em; color: #aaa;">
                                        <div><?php echo t('guild_quests.reward', ['amount' => number_format($reward), 'resource' => t('res.' . $resource)]); ?></div>
                                        <?php if ($completed): ?>
                                            <div><?php echo t('guild_quests.completed_at', ['date' => htmlspecialchars($completed)]); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>
    </article>
    </div>
<?php require_once('../templates/footer.php'); ?>
