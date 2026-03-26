<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1><?php echo t('settings.title'); ?></h1>

        <?php if ($alert_success !== ''): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($alert_success); ?></div>
        <?php endif; ?>
        <?php if ($alert_danger !== ''): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($alert_danger); ?></div>
        <?php endif; ?>

        <form method="POST">
        <h3><?php echo t('settings.language'); ?></h3>
        <h5><?php echo t('settings.language_select'); ?></h5>
        <select name="language">
            <?php foreach (supported_languages() as $langCode => $langName): ?>
            <option value="<?php echo htmlspecialchars($langCode, ENT_QUOTES, 'UTF-8'); ?>" <?php echo get_language() === $langCode ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($langName, ENT_QUOTES, 'UTF-8'); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf-token']); ?>" />
        <input type="submit" name="change_language" value="<?php echo t('settings.language_save'); ?>" />
        </form>

        <?php if (!$Character->IsGuest() && $referral_code !== ''): ?>
        <h3><?php echo t('settings.referral.title'); ?></h3>
        <p><?php echo t('settings.referral.desc'); ?></p>
        <h5><?php echo t('settings.referral.code'); ?></h5>
        <div style="display:flex;gap:.5rem;align-items:center;">
            <input type="text" id="referral_code_display" value="<?php echo htmlspecialchars($referral_code); ?>" readonly style="width:12rem;font-family:monospace;" />
            <button type="button" onclick="copyReferralLink()"><?php echo t('settings.referral.copy'); ?></button>
        </div>
        <p><small><?php echo t('settings.referral.count', ['count' => $referral_count]); ?></small></p>
        <script>
        function copyReferralLink() {
            var code = document.getElementById('referral_code_display').value;
            var link = window.location.origin + '/register?ref=' + code;
            navigator.clipboard.writeText(link).then(function() {
                alert(<?php echo json_encode(t('settings.referral.copied')); ?>);
            });
        }
        </script>
        <?php endif; ?>

        <form method="POST">
        <h3><?php echo t('settings.email.title'); ?></h3>
        <h5><?php echo t('settings.email.new'); ?></h5>
        <input type="email" name="email" value="<?php echo htmlspecialchars($auth->getEmail() ?? ''); ?>" required />
        <h5><?php echo t('settings.email.password'); ?></h5>
        <input type="password" name="email_password" value="" autocomplete="current-password" required />
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf-token']); ?>" />
        <input type="submit" name="change_email" value="<?php echo t('settings.email.submit'); ?>" />
        </form>

        <form method="POST">
        <h3><?php echo t('settings.password.title'); ?></h3>
        <h5><?php echo t('settings.password.current'); ?></h5>
        <input type="password" name="current_password" value="" autocomplete="current-password" required />
        <h5><?php echo t('settings.password.new'); ?></h5>
        <input type="password" name="new_password" value="" autocomplete="new-password" required />
        <h5><?php echo t('settings.password.confirm'); ?></h5>
        <input type="password" name="confirm_new_password" value="" autocomplete="new-password" required />
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf-token']); ?>" />
        <input type="submit" name="change_password" value="<?php echo t('settings.password.submit'); ?>" />
        </form>

    </article>
    </div>
    </div>
</main>
