<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1>Settings</h1>

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
