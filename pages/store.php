<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1><?php echo t('store.title'); ?></h1>

        <?php
        $has_active_sub = !empty($Character->Data['subscription_expires'])
            && strtotime($Character->Data['subscription_expires']) > time();
        ?>

        <!-- Current Credits Balance -->
        <div class="info-box">
            <strong><?php echo t('store.your_credits'); ?></strong>
            <span class="store-credits-balance"><?php echo human_num((int)($Character->Data['credits'] ?? 0)); ?></span>
            <?php if ($has_active_sub): ?>
                &nbsp;&nbsp;|&nbsp;&nbsp;
                <span class="text--success"><?php echo t('store.sub_active'); ?></span>
                <?php echo t('store.sub_expires', ['date' => date('Y-m-d', strtotime($Character->Data['subscription_expires']))]); ?>
            <?php endif; ?>
        </div>

        <!-- Free Credits (Dev/QA only) -->
        <?php if ($free_credits_enabled): ?>
        <h2><?php echo t('store.free_credits.title'); ?></h2>
        <div class="card">
            <div class="grid">
                <div>
                    <h3 class="heading--no-top-margin"><?php echo t('store.free_credits.title'); ?></h3>
                    <p><?php echo t('store.free_credits.desc'); ?></p>
                    <?php if (!$can_claim): ?>
                        <p><small class="text--warning"><?php echo t('store.free_credits.next', ['time' => date('Y-m-d H:i:s', $next_claim_time)]); ?></small></p>
                    <?php endif; ?>
                </div>
                <div>
                    <form method="POST" action="/store">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                        <input type="submit" name="claim_free_credits" value="<?php echo t('store.free_credits.submit'); ?>" class="success-button"
                            <?php echo $can_claim ? '' : 'disabled'; ?>>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Buy Credits (Demo) -->
        <h2><?php echo t('store.buy_credits.title'); ?></h2>
        <div class="card store-card--demo">
            <div class="grid">
                <div>
                    <h3 class="heading--no-top-margin"><?php echo t('store.buy_credits.item'); ?></h3>
                    <p><?php echo t('store.buy_credits.desc'); ?></p>
                    <p><small class="text--warning"><?php echo t('store.buy_credits.demo'); ?></small></p>
                </div>
                <div>
                    <button disabled class="store-btn--demo"><?php echo t('store.buy_credits.btn'); ?></button>
                </div>
            </div>
        </div>

        <!-- QoL Subscription -->
        <h2><?php echo t('store.qol.title'); ?></h2>
        <p><?php echo t('store.qol.desc'); ?></p>
        <ul>
            <li><?php echo t('store.qol.li_rift'); ?></li>
            <li><?php echo t('store.qol.li_simulate'); ?></li>
            <li><?php echo t('store.qol.li_more'); ?></li>
        </ul>

        <div class="store-plans">
            <?php foreach ($subscription_plans as $months => $plan): ?>
                <div class="card <?php echo ($has_active_sub && $months === 1) ? '' : ''; ?>">
                    <div class="grid">
                        <div>
                            <h3 class="heading--no-top-margin"><?php echo htmlspecialchars($plan['label']); ?></h3>
                            <p class="store-plan-price">
                                <?php echo t('store.qol.credits', ['credits' => human_num($plan['credits'])]); ?>
                            </p>
                            <?php if ($months >= 3): ?>
                                <p><small class="text--success"><?php echo t('store.qol.save', ['pct' => round((1 - ($plan['credits'] / $months) / 100) * 100)]); ?></small></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <form method="POST" action="/store">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <input type="hidden" name="duration_months" value="<?php echo $months; ?>">
                                <input type="submit" name="buy_subscription" value="<?php echo t('store.qol.subscribe'); ?>"
                                    <?php echo ((int)($Character->Data['credits'] ?? 0) < $plan['credits']) ? 'disabled' : ''; ?>>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </article>
    </div>
    </div>
</main>
