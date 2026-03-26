    <main class="container">
        <div class="wrapper">
            <article class="main">
                <h2><?php echo t('auth.reset.title'); ?></h2>

                <?php if ($alert_success !== ''): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($alert_success); ?></div>
                    <p><a href="/login"><?php echo t('auth.reset.login'); ?></a></p>
                <?php endif; ?>
                <?php if ($alert_danger !== ''): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($alert_danger); ?></div>
                <?php endif; ?>

                <?php if ($valid_token): ?>
                <form method="POST" action="/reset-password?selector=<?php echo urlencode($selector); ?>&token=<?php echo urlencode($token); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">

                    <label for="new_password"><?php echo t('auth.reset.new_password'); ?></label>
                    <input type="password" id="new_password" name="new_password" minlength="8" required>
                    <small><?php echo t('auth.reset.password_hint'); ?></small>

                    <label for="confirm_password"><?php echo t('auth.reset.confirm'); ?></label>
                    <input type="password" id="confirm_password" name="confirm_password" minlength="8" required>

                    <button type="submit"><?php echo t('auth.reset.submit'); ?></button>
                </form>
                <?php elseif ($alert_success === ''): ?>
                    <p><a href="/forgot-password"><?php echo t('auth.reset.request_new'); ?></a></p>
                <?php endif; ?>
            </article>
        </div>
    </main>
