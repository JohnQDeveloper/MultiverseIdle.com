    <main class="container">
        <div class="wrapper">
            <article class="main">
                <h2><?php echo t('auth.forgot.title'); ?></h2>

                <?php if ($alert_success !== ''): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($alert_success); ?></div>
                <?php endif; ?>
                <?php if ($alert_danger !== ''): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($alert_danger); ?></div>
                <?php endif; ?>

                <?php if ($alert_success === ''): ?>
                <p><?php echo t('auth.forgot.intro'); ?></p>
                <form method="POST" action="/forgot-password">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">

                    <label for="email"><?php echo t('auth.forgot.email'); ?></label>
                    <input type="email" id="email" name="email" required>

                    <button type="submit"><?php echo t('auth.forgot.submit'); ?></button>
                    <p><small><a href="/login"><?php echo t('auth.forgot.back'); ?></a></small></p>
                </form>
                <?php else: ?>
                    <p><a href="/login"><?php echo t('auth.forgot.back'); ?></a></p>
                <?php endif; ?>
            </article>
        </div>
    </main>
