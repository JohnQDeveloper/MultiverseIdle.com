<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1>Store</h1>

        <?php
        $has_active_sub = !empty($Character->Data['subscription_expires'])
            && strtotime($Character->Data['subscription_expires']) > time();
        ?>

        <!-- Current Credits Balance -->
        <div class="info-box">
            <strong>Your Credits:</strong>
            <span class="store-credits-balance"><?php echo human_num((int)($Character->Data['credits'] ?? 0)); ?></span>
            <?php if ($has_active_sub): ?>
                &nbsp;&nbsp;|&nbsp;&nbsp;
                <span class="text--success">QoL Subscription Active</span>
                &mdash; expires <?php echo date('F j, Y', strtotime($Character->Data['subscription_expires'])); ?>
            <?php endif; ?>
        </div>

        <!-- Free Credits (Dev/QA only) -->
        <?php if ($free_credits_enabled): ?>
        <h2>Free Credits</h2>
        <div class="card">
            <div class="grid">
                <div>
                    <h3 class="heading--no-top-margin">Daily Free Credits</h3>
                    <p>Claim <strong>100 free credits</strong> once every 24 hours.</p>
                    <?php if (!$can_claim): ?>
                        <p><small class="text--warning">Next claim available: <?php echo date('Y-m-d H:i:s', $next_claim_time); ?></small></p>
                    <?php endif; ?>
                </div>
                <div>
                    <form method="POST" action="/store">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                        <input type="submit" name="claim_free_credits" value="Claim 100 Free Credits" class="success-button"
                            <?php echo $can_claim ? '' : 'disabled'; ?>>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Buy Credits (Demo) -->
        <h2>Buy Credits</h2>
        <div class="card store-card--demo">
            <div class="grid">
                <div>
                    <h3 class="heading--no-top-margin">100 Credits &mdash; $5.00</h3>
                    <p>Purchase credits to spend on subscriptions and future store items.</p>
                    <p><small class="text--warning">Demo only &mdash; payment processing coming soon.</small></p>
                </div>
                <div>
                    <button disabled class="store-btn--demo">Buy for $5.00</button>
                </div>
            </div>
        </div>

        <!-- QoL Subscription -->
        <h2>Quality of Life Subscription</h2>
        <p>Unlock quality of life improvements to enhance your experience.</p>
        <ul>
            <li>Increased Rift Queue from 2 to 8 slots</li>
            <li>Ability to simulate Arena floors before battling</li>
            <li>Additional QoL features coming soon!</li>
        </ul>

        <div class="store-plans">
            <?php foreach ($subscription_plans as $months => $plan): ?>
                <div class="card <?php echo ($has_active_sub && $months === 1) ? '' : ''; ?>">
                    <div class="grid">
                        <div>
                            <h3 class="heading--no-top-margin"><?php echo htmlspecialchars($plan['label']); ?></h3>
                            <p class="store-plan-price">
                                <span class="store-credits-cost"><?php echo human_num($plan['credits']); ?></span> credits
                            </p>
                            <?php if ($months >= 3): ?>
                                <p><small class="text--success">Save <?php echo round((1 - ($plan['credits'] / $months) / 100) * 100); ?>% vs monthly</small></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <form method="POST" action="/store">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <input type="hidden" name="duration_months" value="<?php echo $months; ?>">
                                <input type="submit" name="buy_subscription" value="Subscribe"
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
