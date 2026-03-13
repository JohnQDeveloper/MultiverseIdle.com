<?php require_once('../templates/game-header.php'); ?>
    <div class="wrapper">
    <article class="main">
        <h1>Game Mode</h1>
        <p>Choose between the permanent <strong>Perpetual</strong> game or the time-limited <strong>Season</strong>. Your progress in each is separate.</p>

        <?php if ($current_season_id === null): ?>
            <p>You are currently playing in <strong>Perpetual</strong> mode.</p>
        <?php else: ?>
            <p>You are currently playing in <strong>Season mode</strong>
            <?php if ($current_season): ?>
                — <?php echo htmlspecialchars($current_season['name']); ?>
                (ends <?php echo htmlspecialchars(date('M j, Y', strtotime($current_season['end_date']))); ?>).
            <?php endif; ?>
            </p>
        <?php endif; ?>

        <div class="grid">

            <!-- Perpetual Mode Card -->
            <div class="card">
                <h2>Perpetual</h2>
                <p>The main game — no end date, no resets. Progress here is permanent.</p>
                <ul>
                    <li>Runs forever</li>
                    <li>Stats and commodities received from ended seasons are added here</li>
                    <li>Separate market from Season players</li>
                </ul>
                <?php if ($current_season_id === null): ?>
                    <p><em>Currently active</em></p>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf-token']); ?>">
                        <button type="submit" name="play_perpetual">Switch to Perpetual</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Season Mode Card -->
            <div class="card">
                <?php if ($active_season): ?>
                    <h2><?php echo htmlspecialchars($active_season['name']); ?></h2>
                    <p>A fresh start that runs until <?php echo htmlspecialchars(date('M j, Y', strtotime($active_season['end_date']))); ?>. When the season ends, your stats and commodities are transferred to your Perpetual character.</p>
                    <ul>
                        <li>Fresh start — all players begin from the same point</li>
                        <li>Isolated market — trade only with other season players</li>
                        <li>At season end: stats and commodities merge into your Perpetual character</li>
                    </ul>
                    <?php
                    $days_remaining = max(0, (int)ceil((strtotime($active_season['end_date']) - time()) / 86400));
                    ?>
                    <p><strong><?php echo $days_remaining; ?></strong> days remaining</p>

                    <?php if ($current_season_id === (int)$active_season['id']): ?>
                        <p><em>Currently active</em></p>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf-token']); ?>">
                            <input type="hidden" name="season_id" value="<?php echo (int)$active_season['id']; ?>">
                            <button type="submit" name="play_season">
                                <?php echo $has_season_character ? 'Switch to Season' : 'Join Season'; ?>
                            </button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <h2>Season</h2>
                    <p>No season is currently active. Check back later for the next season launch.</p>
                <?php endif; ?>
            </div>

        </div>
    </article>
    </div>
    </div>
</main>
