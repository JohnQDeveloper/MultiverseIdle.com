    <main class="container">
        <div class="wrapper">
            <article class="main">
                <form method="POST" action="/login">
                    <h2><?php echo t('auth.login.title'); ?></h2>
                    <?php if (!empty($alert_danger)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($alert_danger, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                    <label for="email"><?php echo t('auth.login.email'); ?></label>
                    <input type="email" id="email" name="email" required>

                    <label for="password"><?php echo t('auth.login.password'); ?></label>
                    <input type="password" id="password" name="password" required>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">

                    <button type="submit"><?php echo t('auth.login.submit'); ?></button>
                    <p><small><a href="/forgot-password"><?php echo t('auth.login.forgot'); ?></a></small></p>
                </form>
            </article>
        </div>
    </main>
