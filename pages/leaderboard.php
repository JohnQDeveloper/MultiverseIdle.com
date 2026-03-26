<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1><?php echo t('lb.title'); ?></h1>
        <p><?php echo t('lb.desc'); ?></p>

        <!-- Mode Toggle (shown when a season is active) -->
        <?php if ($active_season): ?>
        <div class="tab-nav" style="margin-bottom:0.5rem;">
            <a href="?mode=perpetual&tab=<?php echo htmlspecialchars($_GET['tab'] ?? 'combined'); ?>"
               class="tab-nav-item <?php echo $lb_mode !== 'season' ? 'active' : ''; ?>"><?php echo t('lb.tab.perpetual'); ?></a>
            <a href="?mode=season&tab=<?php echo htmlspecialchars($_GET['tab'] ?? 'combined'); ?>"
               class="tab-nav-item <?php echo $lb_mode === 'season' ? 'active' : ''; ?>">
                &#9733; <?php echo htmlspecialchars($active_season['name']); ?>
            </a>
        </div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <div class="tab-nav">
            <a href="?mode=<?php echo $lb_mode; ?>&tab=combined" class="tab-nav-item <?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'combined') ? 'active' : ''; ?>"><?php echo t('lb.tab.combined'); ?></a>
            <a href="?mode=<?php echo $lb_mode; ?>&tab=strength" class="tab-nav-item <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'strength') ? 'active' : ''; ?>"><?php echo t('lb.tab.strength'); ?></a>
            <a href="?mode=<?php echo $lb_mode; ?>&tab=dexterity" class="tab-nav-item <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'dexterity') ? 'active' : ''; ?>"><?php echo t('lb.tab.dexterity'); ?></a>
            <a href="?mode=<?php echo $lb_mode; ?>&tab=health" class="tab-nav-item <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'health') ? 'active' : ''; ?>"><?php echo t('lb.tab.health'); ?></a>
            <a href="?mode=<?php echo $lb_mode; ?>&tab=wisdom" class="tab-nav-item <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'wisdom') ? 'active' : ''; ?>"><?php echo t('lb.tab.wisdom'); ?></a>
            <a href="?mode=<?php echo $lb_mode; ?>&tab=arena" class="tab-nav-item <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'arena') ? 'active' : ''; ?>"><?php echo t('lb.tab.arena'); ?></a>
            <a href="?mode=<?php echo $lb_mode; ?>&tab=rift" class="tab-nav-item <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'rift') ? 'active' : ''; ?>"><?php echo t('lb.tab.rift'); ?></a>
        </div>

        <?php
        $active_tab = $_GET['tab'] ?? 'combined';
        ?>

        <!-- Combined Stats Leaderboard -->
        <?php if ($active_tab === 'combined'): ?>
            <h2><?php echo t('lb.combined.title'); ?></h2>
            <p><?php echo t('lb.combined.desc'); ?></p>

            <?php if (empty($combined_stats_leaderboard)): ?>
                <p><em><?php echo t('common.no_data'); ?></em></p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th><?php echo t('common.rank'); ?></th>
                            <th><?php echo t('common.player'); ?></th>
                            <th><?php echo t('lb.combined.total'); ?></th>
                            <th><?php echo t('lb.combined.frontline'); ?></th>
                            <th><?php echo t('lb.combined.backline'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1; foreach ($combined_stats_leaderboard as $entry): ?>
                            <tr <?php echo ($entry['user_id'] == $current_user_id) ? 'class="card--active"' : ''; ?>>
                                <td><b><?php echo $rank; ?></b></td>
                                <td><?php echo htmlspecialchars($entry['name']); ?></td>
                                <td><b><?php echo number_format((int)$entry['total_stats']); ?></b></td>
                                <td>
                                    <small>
                                        STR: <?php echo $entry['fl_str']; ?> |
                                        DEX: <?php echo $entry['fl_dex']; ?> |
                                        HP: <?php echo $entry['fl_hp']; ?> |
                                        WIS: <?php echo $entry['fl_wis']; ?>
                                    </small>
                                </td>
                                <td>
                                    <small>
                                        STR: <?php echo $entry['bl_str']; ?> |
                                        DEX: <?php echo $entry['bl_dex']; ?> |
                                        HP: <?php echo $entry['bl_hp']; ?> |
                                        WIS: <?php echo $entry['bl_wis']; ?>
                                    </small>
                                </td>
                            </tr>
                        <?php $rank++; endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Strength Leaderboard -->
        <?php if ($active_tab === 'strength'): ?>
            <h2><?php echo t('lb.strength.title'); ?></h2>
            <p><?php echo t('lb.strength.desc'); ?></p>

            <?php if (empty($strength_leaderboard)): ?>
                <p><em><?php echo t('common.no_data'); ?></em></p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th><?php echo t('common.rank'); ?></th>
                            <th><?php echo t('common.player'); ?></th>
                            <th><?php echo t('lb.strength.col'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1; foreach ($strength_leaderboard as $entry): ?>
                            <tr <?php echo ($entry['user_id'] == $current_user_id) ? 'class="card--active"' : ''; ?>>
                                <td><b><?php echo $rank; ?></b></td>
                                <td><?php echo htmlspecialchars($entry['name']); ?></td>
                                <td><b><?php echo number_format((int)$entry['max_strength']); ?></b></td>
                            </tr>
                        <?php $rank++; endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Dexterity Leaderboard -->
        <?php if ($active_tab === 'dexterity'): ?>
            <h2><?php echo t('lb.dexterity.title'); ?></h2>
            <p><?php echo t('lb.dexterity.desc'); ?></p>

            <?php if (empty($dexterity_leaderboard)): ?>
                <p><em><?php echo t('common.no_data'); ?></em></p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th><?php echo t('common.rank'); ?></th>
                            <th><?php echo t('common.player'); ?></th>
                            <th><?php echo t('lb.dexterity.col'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1; foreach ($dexterity_leaderboard as $entry): ?>
                            <tr <?php echo ($entry['user_id'] == $current_user_id) ? 'class="card--active"' : ''; ?>>
                                <td><b><?php echo $rank; ?></b></td>
                                <td><?php echo htmlspecialchars($entry['name']); ?></td>
                                <td><b><?php echo number_format((int)$entry['max_dexterity']); ?></b></td>
                            </tr>
                        <?php $rank++; endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Health Leaderboard -->
        <?php if ($active_tab === 'health'): ?>
            <h2><?php echo t('lb.health.title'); ?></h2>
            <p><?php echo t('lb.health.desc'); ?></p>

            <?php if (empty($health_leaderboard)): ?>
                <p><em><?php echo t('common.no_data'); ?></em></p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th><?php echo t('common.rank'); ?></th>
                            <th><?php echo t('common.player'); ?></th>
                            <th><?php echo t('lb.health.col'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1; foreach ($health_leaderboard as $entry): ?>
                            <tr <?php echo ($entry['user_id'] == $current_user_id) ? 'class="card--active"' : ''; ?>>
                                <td><b><?php echo $rank; ?></b></td>
                                <td><?php echo htmlspecialchars($entry['name']); ?></td>
                                <td><b><?php echo number_format((int)$entry['max_health']); ?></b></td>
                            </tr>
                        <?php $rank++; endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Wisdom Leaderboard -->
        <?php if ($active_tab === 'wisdom'): ?>
            <h2><?php echo t('lb.wisdom.title'); ?></h2>
            <p><?php echo t('lb.wisdom.desc'); ?></p>

            <?php if (empty($wisdom_leaderboard)): ?>
                <p><em><?php echo t('common.no_data'); ?></em></p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th><?php echo t('common.rank'); ?></th>
                            <th><?php echo t('common.player'); ?></th>
                            <th><?php echo t('lb.wisdom.col'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1; foreach ($wisdom_leaderboard as $entry): ?>
                            <tr <?php echo ($entry['user_id'] == $current_user_id) ? 'class="card--active"' : ''; ?>>
                                <td><b><?php echo $rank; ?></b></td>
                                <td><?php echo htmlspecialchars($entry['name']); ?></td>
                                <td><b><?php echo number_format((int)$entry['max_wisdom']); ?></b></td>
                            </tr>
                        <?php $rank++; endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Arena Floor Leaderboard -->
        <?php if ($active_tab === 'arena'): ?>
            <h2><?php echo t('lb.arena.title'); ?></h2>
            <p><?php echo t('lb.arena.desc'); ?></p>

            <?php if (empty($arena_leaderboard)): ?>
                <p><em><?php echo t('common.no_data'); ?></em></p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th><?php echo t('common.rank'); ?></th>
                            <th><?php echo t('common.player'); ?></th>
                            <th><?php echo t('lb.arena.col'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1; foreach ($arena_leaderboard as $entry): ?>
                            <tr <?php echo ($entry['user_id'] == $current_user_id) ? 'class="card--active"' : ''; ?>>
                                <td><b><?php echo $rank; ?></b></td>
                                <td><?php echo htmlspecialchars($entry['name']); ?></td>
                                <td><b><?php echo number_format((int)$entry['arena_floor']); ?></b></td>
                            </tr>
                        <?php $rank++; endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Highest Rift Leaderboard -->
        <?php if ($active_tab === 'rift'): ?>
            <h2><?php echo t('lb.rift.title'); ?></h2>
            <p><?php echo t('lb.rift.desc'); ?></p>

            <?php if (empty($rift_leaderboard)): ?>
                <p><em><?php echo t('common.no_data'); ?></em></p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th><?php echo t('common.rank'); ?></th>
                            <th><?php echo t('common.player'); ?></th>
                            <th><?php echo t('lb.rift.col'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1; foreach ($rift_leaderboard as $entry): ?>
                            <tr <?php echo ($entry['user_id'] == $current_user_id) ? 'class="card--active"' : ''; ?>>
                                <td><b><?php echo $rank; ?></b></td>
                                <td><?php echo htmlspecialchars($entry['name']); ?></td>
                                <td><b><?php echo number_format((int)$entry['highest_rift_level']); ?></b></td>
                            </tr>
                        <?php $rank++; endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>

    </article>
    </div>
    </div>
</main>
