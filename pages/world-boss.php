<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1><?php echo t('world_boss.title'); ?></h1>

        <p><?php echo t('world_boss.desc'); ?></p>

        <div class="boss-timer">
            <h3><?php echo t('world_boss.next_reset'); ?></h3>
            <div id="countdown" class="countdown">
                <?php echo t('world_boss.calculating'); ?>
            </div>
        </div>
        <?php if (!empty($Character->Data['world_boss_log'])): ?>
        <BR />
        <div class="boss-log">
            <h3><?php echo t('world_boss.history'); ?></h3>
            <div class="log-entries">
                <p class="success"><?php echo htmlspecialchars(trim(localize_world_boss_log((string)$Character->Data['world_boss_log']))); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <div class="queue-status">
            <h3><?php echo t('world_boss.queue_title'); ?></h3>
            <p><?php echo t('world_boss.players_queued', ['count' => number_format((int)$total_in_queue)]); ?></p>

            <?php if($Character->Data['world_boss_queued'] == 1): ?>
                <p class="text--success"><?php echo t('world_boss.in_queue'); ?></p>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                    <input type="submit" name="leave_queue" value="<?php echo t('world_boss.leave_queue'); ?>" class="button-danger" />
                </form>
            <?php else: ?>
                <p><?php echo t('world_boss.not_in_queue'); ?></p>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                    <input type="submit" name="join_queue" value="<?php echo t('world_boss.join_queue'); ?>" class="button-success" />
                </form>
            <?php endif; ?>
        </div>

        <hr>

        <div class="boss-info">
            <h3><?php echo t('world_boss.how_title'); ?></h3>
            <ul>
                <li><?php echo t('world_boss.li_join'); ?></li>
                <li><?php echo t('world_boss.li_reset'); ?></li>
                <li><?php echo t('world_boss.li_auto'); ?></li>
                <li><?php echo t('world_boss.li_rewards'); ?></li>
            </ul>
        </div>

        <?php if (CORRUPTION_SEASON_ENABLED && (CORRUPTION_SEASON_PERPETUAL || ($Character->Data['season_id'] ?? null) !== null)): ?>
        <hr>

        <h2><?php echo t('corruption.avatar.title'); ?></h2>
        <p><?php echo t('corruption.avatar.desc'); ?></p>

        <div class="boss-timer">
            <h3><?php echo t('world_boss.next_reset'); ?></h3>
            <div id="corruption-countdown" class="countdown">
                <?php echo t('world_boss.calculating'); ?>
            </div>
        </div>

        <?php if (!empty($Character->Data['avatar_corruption_log'])): ?>
        <br>
        <div class="boss-log">
            <h3><?php echo t('corruption.avatar.history'); ?></h3>
            <div class="log-entries">
                <p class="success"><?php echo nl2br(htmlspecialchars(trim((string)$Character->Data['avatar_corruption_log']))); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <div class="queue-status">
            <h3><?php echo t('world_boss.queue_title'); ?></h3>
            <p><?php echo t('corruption.avatar.players_queued', ['count' => number_format((int)$avatar_corruption_queue_count)]); ?></p>

            <?php if (($Character->Data['avatar_corruption_queued'] ?? 0) == 1): ?>
                <p class="text--success"><?php echo t('world_boss.in_queue'); ?></p>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                    <input type="submit" name="leave_corruption_queue" value="<?php echo t('world_boss.leave_queue'); ?>" class="button-danger" />
                </form>
            <?php else: ?>
                <p><?php echo t('world_boss.not_in_queue'); ?></p>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                    <input type="submit" name="join_corruption_queue" value="<?php echo t('corruption.avatar.join_queue'); ?>" class="button-success" />
                </form>
            <?php endif; ?>
        </div>

        <div class="boss-info">
            <h3><?php echo t('world_boss.how_title'); ?></h3>
            <ul>
                <li><?php echo t('corruption.avatar.li_orbs'); ?></li>
                <li><?php echo t('world_boss.li_reset'); ?></li>
                <li><?php echo t('world_boss.li_auto'); ?></li>
            </ul>
        </div>
        <?php endif; ?>

    </article>
    </div>
    </div>
</main>

<script>
function updateCountdown() {
    const now = new Date();
    const utcNow = new Date(now.toUTCString());

    // Calculate midnight UTC
    const midnight = new Date(utcNow);
    midnight.setUTCHours(24, 0, 0, 0);

    // Calculate difference
    const diff = midnight - utcNow;

    if (diff <= 0) {
        document.getElementById('countdown').textContent = '<?php echo t('world_boss.resetting'); ?>';
        setTimeout(() => location.reload(), 2000);
        return;
    }

    // Calculate hours, minutes, seconds
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);

    // Format with leading zeros
    const formattedTime =
        String(hours).padStart(2, '0') + ':' +
        String(minutes).padStart(2, '0') + ':' +
        String(seconds).padStart(2, '0');

    document.getElementById('countdown').textContent = formattedTime;
}

// Update countdown immediately and then every second
updateCountdown();
setInterval(updateCountdown, 1000);

<?php if (CORRUPTION_SEASON_ENABLED && (CORRUPTION_SEASON_PERPETUAL || ($Character->Data['season_id'] ?? null) !== null)): ?>
const corruptionCountdownEl = document.getElementById('corruption-countdown');
if (corruptionCountdownEl) {
    function updateCorruptionCountdown() {
        const now = new Date();
        const utcNow = new Date(now.toUTCString());
        const midnight = new Date(utcNow);
        midnight.setUTCHours(24, 0, 0, 0);
        const diff = midnight - utcNow;
        if (diff <= 0) {
            corruptionCountdownEl.textContent = '<?php echo t('world_boss.resetting'); ?>';
            setTimeout(() => location.reload(), 2000);
            return;
        }
        const h = Math.floor(diff / (1000 * 60 * 60));
        const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const s = Math.floor((diff % (1000 * 60)) / 1000);
        corruptionCountdownEl.textContent =
            String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    }
    updateCorruptionCountdown();
    setInterval(updateCorruptionCountdown, 1000);
}
<?php endif; ?>
</script>
