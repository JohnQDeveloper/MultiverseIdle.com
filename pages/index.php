    <main class="container">
        <div class="wrapper">
        <article class="main">
          <form method="POST" class="language-picker">
            <label for="landing-language"><?php echo t('settings.language_select'); ?></label>
            <div class="language-picker__controls">
              <select id="landing-language" name="language">
                <?php foreach (supported_languages() as $langCode => $langName): ?>
                <option value="<?php echo htmlspecialchars($langCode, ENT_QUOTES, 'UTF-8'); ?>" <?php echo get_language() === $langCode ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($langName, ENT_QUOTES, 'UTF-8'); ?>
                </option>
                <?php endforeach; ?>
              </select>
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf-token']); ?>" />
              <button type="submit" name="change_language"><?php echo t('settings.language_save'); ?></button>
            </div>
          </form>
          <h1><?php echo t('index.title'); ?></h1>
            <p><?php echo t('index.welcome'); ?></p>
            <p><?php echo t('index.feedback'); ?></p>

            <a href="/register" class="button"><?php echo t('index.create_acct'); ?></a>
            <BR />
            <a href="/login" class="button"><?php echo t('index.login'); ?></a>
            <BR />
            <a href="/guest" class="button outline"><?php echo t('index.guest'); ?></a>
            <p><small><?php echo t('index.guest_note', ['register_link' => '<a href="/register">' . t('index.register') . '</a>']); ?></small></p>

        </article>
        </div>
      </div>
    </main>
