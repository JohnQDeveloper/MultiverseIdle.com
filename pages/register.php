    <main class="container">
        <div class="wrapper">
            <article class="main">
                <?php if (isset($_SESSION['guest_mode']) && $_SESSION['guest_mode'] === true): ?>
                <div class="alert alert-warning"><?php echo t('auth.register.guest_banner'); ?></div>
                <?php endif; ?>
                <form method="POST" action="/register">
                    <h2><?php echo t('auth.register.title'); ?></h2>
                    <label for="username"><?php echo t('auth.register.username'); ?></label>
                    <input type="text" id="username" name="username" required>

                    <label for="email"><?php echo t('auth.register.email'); ?></label>
                    <input type="email" id="email" name="email" required>

                    <label for="password"><?php echo t('auth.register.password'); ?></label>
                    <input type="password" id="password" name="password" minlength="8" required>
                    <small><?php echo t('auth.register.password_hint'); ?></small>

                    <label for="referral_code"><?php echo t('auth.register.referral_code'); ?></label>
                    <input type="text" id="referral_code" name="referral_code" maxlength="8"
                           value="<?php echo htmlspecialchars(strtoupper($_GET['ref'] ?? '')); ?>"
                           placeholder="<?php echo htmlspecialchars(t('auth.register.referral_ph'), ENT_QUOTES, 'UTF-8'); ?>" style="text-transform:uppercase;" />

                    <button type="submit"><?php echo t('auth.register.submit'); ?></button>
                </form>
            </article>
        </div>
    </main>
