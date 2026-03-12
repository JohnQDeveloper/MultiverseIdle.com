    <main class="container">
        <div class="wrapper">
            <article class="main">
                <h2>Reset Password</h2>

                <?php if ($alert_success !== ''): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($alert_success); ?></div>
                    <p><a href="/login">Click here to log in</a></p>
                <?php endif; ?>
                <?php if ($alert_danger !== ''): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($alert_danger); ?></div>
                <?php endif; ?>

                <?php if ($valid_token): ?>
                <form method="POST" action="/reset-password?selector=<?php echo urlencode($selector); ?>&token=<?php echo urlencode($token); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">

                    <label for="new_password">New Password:</label>
                    <input type="password" id="new_password" name="new_password" minlength="8" required>
                    <small>Password must be at least 8 characters long</small>

                    <label for="confirm_password">Confirm New Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" minlength="8" required>

                    <button type="submit">Reset Password</button>
                </form>
                <?php elseif ($alert_success === ''): ?>
                    <p><a href="/forgot-password">Request a new reset link</a></p>
                <?php endif; ?>
            </article>
        </div>
    </main>
