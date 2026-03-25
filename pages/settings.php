<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1>Settings</h1>

        <?php if ($alert_success !== ''): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($alert_success); ?></div>
        <?php endif; ?>
        <?php if ($alert_danger !== ''): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($alert_danger); ?></div>
        <?php endif; ?>

        <?php if (!$Character->IsGuest() && $referral_code !== ''): ?>
        <h3>Referral Program</h3>
        <p>Share your referral link to earn <strong>7 days of worker production in gold</strong> for each friend who joins. Your friend receives <strong>100,000 gold</strong> to get started.</p>
        <h5>Your Referral Code:</h5>
        <div style="display:flex;gap:.5rem;align-items:center;">
            <input type="text" id="referral_code_display" value="<?php echo htmlspecialchars($referral_code); ?>" readonly style="width:12rem;font-family:monospace;" />
            <button type="button" onclick="copyReferralLink()">Copy Link</button>
        </div>
        <p><small>Successful referrals: <?php echo $referral_count; ?></small></p>
        <script>
        function copyReferralLink() {
            var code = document.getElementById('referral_code_display').value;
            var link = window.location.origin + '/register?ref=' + code;
            navigator.clipboard.writeText(link).then(function() {
                alert('Referral link copied!');
            });
        }
        </script>
        <?php endif; ?>

        <form method="POST">
        <h3>Change Email:</h3>
        <h5>New Email Address:</h5>
        <input type="email" name="email" value="<?php echo htmlspecialchars($auth->getEmail() ?? ''); ?>" required />
        <h5>Current Password (to confirm):</h5>
        <input type="password" name="email_password" value="" autocomplete="current-password" required />
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf-token']); ?>" />
        <input type="submit" name="change_email" value="Update Email" />
        </form>

        <form method="POST">
        <h3>Change Password:</h3>
        <h5>Current Password:</h5>
        <input type="password" name="current_password" value="" autocomplete="current-password" required />
        <h5>New Password:</h5>
        <input type="password" name="new_password" value="" autocomplete="new-password" required />
        <h5>Confirm New Password:</h5>
        <input type="password" name="confirm_new_password" value="" autocomplete="new-password" required />
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf-token']); ?>" />
        <input type="submit" name="change_password" value="Update Password" />
        </form>

    </article>
    </div>
    </div>
</main>
