    <main class="container">
        <div class="wrapper">
        <article class="main">
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
