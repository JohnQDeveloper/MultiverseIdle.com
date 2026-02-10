<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1>Leaderboards</h1>
        <p>Compete with other players across multiple categories and climb to the top!</p>

        <!-- Tab Navigation -->
        <div class="tab-nav">
            <a href="?tab=combined" class="tab-nav-item <?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'combined') ? 'active' : ''; ?>">Combined Stats</a>
            <a href="?tab=strength" class="tab-nav-item <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'strength') ? 'active' : ''; ?>">Strength</a>
            <a href="?tab=dexterity" class="tab-nav-item <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'dexterity') ? 'active' : ''; ?>">Dexterity</a>
            <a href="?tab=health" class="tab-nav-item <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'health') ? 'active' : ''; ?>">Health</a>
            <a href="?tab=wisdom" class="tab-nav-item <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'wisdom') ? 'active' : ''; ?>">Wisdom</a>
            <a href="?tab=arena" class="tab-nav-item <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'arena') ? 'active' : ''; ?>">Arena Floor</a>
            <a href="?tab=rift" class="tab-nav-item <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'rift') ? 'active' : ''; ?>">Highest Rift</a>
        </div>

        <?php
        $active_tab = $_GET['tab'] ?? 'combined';
        ?>

        <!-- Combined Stats Leaderboard -->
        <?php if ($active_tab === 'combined'): ?>
            <h2>Combined Stats Leaderboard</h2>
            <p>Ranking by total stats across both party members (STR + DEX + HP + WIS).</p>

            <?php if (empty($combined_stats_leaderboard)): ?>
                <p><em>No data available yet.</em></p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Player</th>
                            <th>Total Stats</th>
                            <th>Frontline</th>
                            <th>Backline</th>
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
            <h2>Strength Leaderboard</h2>
            <p>Ranking by highest strength value across party members.</p>

            <?php if (empty($strength_leaderboard)): ?>
                <p><em>No data available yet.</em></p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Player</th>
                            <th>Max Strength</th>
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
            <h2>Dexterity Leaderboard</h2>
            <p>Ranking by highest dexterity value across party members.</p>

            <?php if (empty($dexterity_leaderboard)): ?>
                <p><em>No data available yet.</em></p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Player</th>
                            <th>Max Dexterity</th>
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
            <h2>Health Leaderboard</h2>
            <p>Ranking by highest health value across party members.</p>

            <?php if (empty($health_leaderboard)): ?>
                <p><em>No data available yet.</em></p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Player</th>
                            <th>Max Health</th>
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
            <h2>Wisdom Leaderboard</h2>
            <p>Ranking by highest wisdom value across party members.</p>

            <?php if (empty($wisdom_leaderboard)): ?>
                <p><em>No data available yet.</em></p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Player</th>
                            <th>Max Wisdom</th>
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
            <h2>Arena Floor Leaderboard</h2>
            <p>Ranking by highest arena floor reached.</p>

            <?php if (empty($arena_leaderboard)): ?>
                <p><em>No data available yet.</em></p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Player</th>
                            <th>Arena Floor</th>
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
            <h2>Highest Rift Leaderboard</h2>
            <p>Ranking by highest rift level completed.</p>

            <?php if (empty($rift_leaderboard)): ?>
                <p><em>No data available yet.</em></p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Player</th>
                            <th>Rift Level</th>
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
