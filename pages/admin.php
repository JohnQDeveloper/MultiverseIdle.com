<?php require_once('../templates/game-header.php'); ?>
    <div class="wrapper">
    <article class="main admin-page">
        <h1><?php echo t('admin.title'); ?></h1>

        <?php if (!$adminPageAllowed): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars(t('admin.access_denied'), ENT_QUOTES, 'UTF-8'); ?></div>
            <p><?php echo t('admin.desc'); ?></p>
        <?php else: ?>
            <p><?php echo t('admin.desc'); ?></p>

            <section class="card">
                <h3><?php echo t('admin.chart.title'); ?></h3>
                <p><?php echo t('admin.chart.desc'); ?></p>

                <?php if (!$adminDailyUsersAvailable): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars(t('admin.chart.unavailable'), ENT_QUOTES, 'UTF-8'); ?></div>
                <?php else: ?>
                    <div class="admin-metrics">
                        <div class="admin-metric">
                            <span class="admin-metric__label"><?php echo t('admin.chart.last_complete_day'); ?></span>
                            <strong class="admin-metric__value"><?php echo number_format((int)$adminDailyUsersStats['last_complete_day']); ?></strong>
                        </div>
                        <div class="admin-metric">
                            <span class="admin-metric__label"><?php echo t('admin.chart.previous_day'); ?></span>
                            <strong class="admin-metric__value"><?php echo number_format((int)$adminDailyUsersStats['previous_day']); ?></strong>
                        </div>
                        <div class="admin-metric">
                            <span class="admin-metric__label"><?php echo t('admin.chart.average_7'); ?></span>
                            <strong class="admin-metric__value"><?php echo number_format((float)$adminDailyUsersStats['seven_day_average'], 1); ?></strong>
                        </div>
                        <div class="admin-metric">
                            <span class="admin-metric__label"><?php echo t('admin.chart.peak'); ?></span>
                            <strong class="admin-metric__value"><?php echo number_format((int)$adminDailyUsersStats['peak']); ?></strong>
                            <small class="admin-metric__meta">
                                <?php
                                echo htmlspecialchars(
                                    $adminDailyUsersStats['peak_date'] !== ''
                                        ? t('admin.chart.peak_date', ['date' => (string)$adminDailyUsersStats['peak_date']])
                                        : t('admin.chart.no_peak'),
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>
                            </small>
                        </div>
                    </div>

                    <p class="admin-chart-meta">
                        <?php echo t('admin.chart.range', ['start' => (string)$adminDailyUsersStats['range_start'], 'end' => (string)$adminDailyUsersStats['range_end'], 'timezone' => (string)$adminDailyUsersStats['timezone']]); ?>
                    </p>
                    <p class="admin-chart-note"><?php echo t('admin.chart.note'); ?></p>

                    <div class="admin-chart-frame">
                        <svg
                            class="admin-chart"
                            viewBox="0 0 <?php echo (int)$adminDailyUsersChart['width']; ?> <?php echo (int)$adminDailyUsersChart['height']; ?>"
                            role="img"
                            aria-labelledby="admin-daily-users-chart-title">
                            <title id="admin-daily-users-chart-title"><?php echo htmlspecialchars(t('admin.chart.title'), ENT_QUOTES, 'UTF-8'); ?></title>

                            <?php foreach ($adminDailyUsersChart['grid_lines'] as $gridLine): ?>
                                <line
                                    class="admin-chart__grid"
                                    x1="52"
                                    y1="<?php echo htmlspecialchars((string)$gridLine['y'], ENT_QUOTES, 'UTF-8'); ?>"
                                    x2="742"
                                    y2="<?php echo htmlspecialchars((string)$gridLine['y'], ENT_QUOTES, 'UTF-8'); ?>"></line>
                                <text
                                    class="admin-chart__axis"
                                    x="44"
                                    y="<?php echo htmlspecialchars((string)($gridLine['y'] + 4), ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo (int)$gridLine['value']; ?>
                                </text>
                            <?php endforeach; ?>

                            <?php foreach ($adminDailyUsersChart['bars'] as $bar): ?>
                                <rect
                                    class="admin-chart__bar"
                                    x="<?php echo htmlspecialchars((string)$bar['x'], ENT_QUOTES, 'UTF-8'); ?>"
                                    y="<?php echo htmlspecialchars((string)$bar['y'], ENT_QUOTES, 'UTF-8'); ?>"
                                    width="<?php echo htmlspecialchars((string)$bar['width'], ENT_QUOTES, 'UTF-8'); ?>"
                                    height="<?php echo htmlspecialchars((string)$bar['height'], ENT_QUOTES, 'UTF-8'); ?>"
                                    rx="2">
                                    <title><?php echo htmlspecialchars($bar['date'] . ': ' . number_format((int)$bar['count']) . ' ' . t('admin.chart.users_label'), ENT_QUOTES, 'UTF-8'); ?></title>
                                </rect>
                            <?php endforeach; ?>

                            <?php foreach ($adminDailyUsersChart['x_labels'] as $label): ?>
                                <text
                                    class="admin-chart__label"
                                    x="<?php echo htmlspecialchars((string)$label['x'], ENT_QUOTES, 'UTF-8'); ?>"
                                    y="254">
                                    <?php echo htmlspecialchars((string)$label['label'], ENT_QUOTES, 'UTF-8'); ?>
                                </text>
                            <?php endforeach; ?>
                        </svg>
                    </div>
                <?php endif; ?>
            </section>

            <section class="card">
                <h3><?php echo t('admin.ban.title'); ?></h3>
                <p><?php echo t('admin.ban.desc'); ?></p>

                <form method="POST" class="admin-ban-search">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf-token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <label for="admin_ban_search_term"><?php echo t('admin.ban.search_label'); ?></label>
                    <div class="admin-ban-search__row">
                        <input
                            type="text"
                            id="admin_ban_search_term"
                            name="admin_ban_search_term"
                            value="<?php echo htmlspecialchars($adminBanSearchTerm, ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="<?php echo htmlspecialchars(t('admin.ban.search_ph'), ENT_QUOTES, 'UTF-8'); ?>"
                            minlength="2"
                            maxlength="255">
                        <button type="submit" name="search_admin_ban_user"><?php echo t('admin.ban.search_submit'); ?></button>
                    </div>
                </form>

                <?php if ($adminBanSearchTerm !== ''): ?>
                    <h4><?php echo t('admin.ban.results_title'); ?></h4>

                    <?php if (empty($adminBanMatches)): ?>
                        <p><em><?php echo t('admin.ban.no_results'); ?></em></p>
                    <?php else: ?>
                        <?php foreach ($adminBanMatches as $match): ?>
                            <div class="admin-ban-match">
                                <div class="admin-ban-match__details">
                                    <p class="admin-ban-match__title"><?php echo htmlspecialchars($match['display_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <p>
                                        <strong><?php echo t('admin.ban.match_characters'); ?></strong>
                                        <?php echo htmlspecialchars(implode(', ', $match['matched_characters']), ENT_QUOTES, 'UTF-8'); ?>
                                    </p>
                                    <p>
                                        <strong><?php echo t('admin.ban.username'); ?></strong>
                                        <?php echo htmlspecialchars($match['username'], ENT_QUOTES, 'UTF-8'); ?>
                                    </p>
                                    <p>
                                        <strong><?php echo t('admin.ban.user_id'); ?></strong>
                                        <?php echo number_format((int)$match['user_id']); ?>
                                    </p>
                                    <p>
                                        <strong><?php echo t('admin.ban.status'); ?></strong>
                                        <span class="admin-status-pill"><?php echo htmlspecialchars($match['status_label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </p>
                                    <p>
                                        <strong><?php echo t('admin.ban.last_seen'); ?></strong>
                                        <?php echo htmlspecialchars($match['last_seen'] !== null ? (string)$match['last_seen'] : t('admin.ban.never'), ENT_QUOTES, 'UTF-8'); ?>
                                    </p>
                                </div>

                                <form method="POST" class="admin-ban-match__actions">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf-token'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="admin_ban_search_term" value="<?php echo htmlspecialchars($adminBanSearchTerm, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="target_user_id" value="<?php echo (int)$match['user_id']; ?>">
                                    <input type="hidden" name="target_character_name" value="<?php echo htmlspecialchars($match['target_character_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <button
                                        type="submit"
                                        name="ban_admin_user"
                                        class="contrast"
                                        <?php echo (int)$match['status'] === \Delight\Auth\Status::BANNED ? 'disabled' : ''; ?>>
                                        <?php echo (int)$match['status'] === \Delight\Auth\Status::BANNED ? t('admin.ban.button_disabled') : t('admin.ban.button'); ?>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </section>

            <div class="admin-grid">
                <section class="card">
                    <h3><?php echo t('admin.session_title'); ?></h3>
                    <p><?php echo t('admin.session_desc'); ?></p>
                    <dl class="admin-kv">
                        <div>
                            <dt><?php echo t('admin.label.user_id'); ?></dt>
                            <dd><?php echo number_format((int)$adminSession['user_id']); ?></dd>
                        </div>
                        <div>
                            <dt><?php echo t('admin.label.username'); ?></dt>
                            <dd><?php echo htmlspecialchars($adminSession['username'], ENT_QUOTES, 'UTF-8'); ?></dd>
                        </div>
                        <div>
                            <dt><?php echo t('admin.label.email'); ?></dt>
                            <dd><?php echo htmlspecialchars($adminSession['email'], ENT_QUOTES, 'UTF-8'); ?></dd>
                        </div>
                        <div>
                            <dt><?php echo t('admin.label.roles'); ?></dt>
                            <dd><?php echo htmlspecialchars($adminRoleNames !== [] ? implode(', ', $adminRoleNames) : t('admin.value.none'), ENT_QUOTES, 'UTF-8'); ?></dd>
                        </div>
                        <div>
                            <dt><?php echo t('admin.label.role_mask'); ?></dt>
                            <dd><?php echo (int)$adminSession['roles_mask']; ?></dd>
                        </div>
                    </dl>
                </section>

                <section class="card">
                    <h3><?php echo t('admin.summary_title'); ?></h3>
                    <p><?php echo t('admin.summary_desc'); ?></p>
                    <div class="admin-metrics">
                        <div class="admin-metric">
                            <span class="admin-metric__label"><?php echo t('admin.summary.total_users'); ?></span>
                            <strong class="admin-metric__value"><?php echo number_format((int)$adminSummary['total_users']); ?></strong>
                        </div>
                        <div class="admin-metric">
                            <span class="admin-metric__label"><?php echo t('admin.summary.verified_users'); ?></span>
                            <strong class="admin-metric__value"><?php echo number_format((int)$adminSummary['verified_users']); ?></strong>
                        </div>
                        <div class="admin-metric">
                            <span class="admin-metric__label"><?php echo t('admin.summary.active_characters'); ?></span>
                            <strong class="admin-metric__value"><?php echo number_format((int)$adminSummary['active_characters']); ?></strong>
                        </div>
                        <div class="admin-metric">
                            <span class="admin-metric__label"><?php echo t('admin.summary.admin_accounts'); ?></span>
                            <strong class="admin-metric__value"><?php echo number_format((int)$adminSummary['admin_accounts']); ?></strong>
                        </div>
                    </div>
                </section>
            </div>
        <?php endif; ?>

    </article>
    </div>
    </div>
</main>
