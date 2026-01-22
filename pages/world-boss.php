<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1>World Bosses</h1>

        <p>The World Boss spawns every 24 hours and all queued players participate in the battle.</p>

        <div class="boss-timer">
            <h3>Next Boss Reset:</h3>
            <div id="countdown" style="font-size: 1.5em; font-weight: bold; color: #ff6b6b;">
                Calculating...
            </div>
        </div>

        <hr>

        <div class="queue-status">
            <h3>Queue Status</h3>
            <p>Players in Queue: <strong><?php echo number_format($total_in_queue); ?></strong></p>

            <?php if($Character->Data['world_boss_queued'] == 1): ?>
                <p style="color: green; font-weight: bold;">✓ You are currently in the queue!</p>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                    <input type="submit" name="leave_queue" value="Leave Queue" class="button-danger" />
                </form>
            <?php else: ?>
                <p>You are not in the queue.</p>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                    <input type="submit" name="join_queue" value="Join Queue" class="button-success" />
                </form>
            <?php endif; ?>
        </div>

        <hr>

        <div class="boss-info">
            <h3>How it Works</h3>
            <ul>
                <li>Join the queue to participate in the next World Boss battle</li>
                <li>The boss resets at midnight UTC every day</li>
                <li>All queued players automatically participate</li>
                <li>Rewards are distributed based on participation</li>
            </ul>
        </div>

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
        document.getElementById('countdown').textContent = 'Resetting...';
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
</script>
