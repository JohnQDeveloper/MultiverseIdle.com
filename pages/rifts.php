<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1>Rift Delves</h1>
        <p>Queue up to <?php echo $rift_queue_max; ?> rift stones to run challenging 10-battle Rift Delves. Each rift runs automatically every 4 hours.</p>

        <div class="info-box">
            <h3 class="heading--no-top-margin">How Rift Delves Work</h3>
            <ul>
                <li><b>Queue rifts:</b> Add rift stones to your queue (max <?php echo $rift_queue_max; ?><?php echo $has_active_sub ? '' : ' &mdash; <a href="/store">upgrade to QoL</a> for up to 8'; ?>)</li>
                <li><b>Automatic processing:</b> Rifts run every 4 hours</li>
                <li><b>10 consecutive battles:</b> Fight through all 10 to earn rewards</li>
                <li><b>Full heal between battles:</b> You start each fight at full health</li>
                <li><b>All-or-nothing rewards:</b> Only get rewards if you complete all 10 battles</li>
            </ul>
        </div>

        <!-- Rift Queue Section -->
        <h2>Rift Queue (<?php echo count($queued_rifts); ?>/<?php echo $rift_queue_max; ?>)</h2>

        <?php if ($has_active_sub && !empty($queued_rifts)): ?>
            <form method="POST" action="/rifts" class="form--inline" style="margin-bottom: 16px;">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                <input type="submit" role="button" name="simulate_queue" value="Simulate Queue (300x each)">
            </form>
        <?php endif; ?>

        <?php if (empty($queued_rifts)): ?>
            <p><em>No rifts currently queued. Add rift stones from your available rifts below.</em></p>
        <?php else: ?>
            <div style="margin-bottom: 30px;">
                <?php foreach ($queued_rifts as $rift): ?>
                    <div class="card card--active">
                        <div class="grid">
                            <div>
                                <h3 class="heading--no-top-margin">
                                    <span class="badge">
                                        POSITION <?php echo $rift['queue_position']; ?>
                                    </span>
                                    <?php echo htmlspecialchars($rift['name']); ?>
                                </h3>
                                <p><b>Rift Level:</b> <?php echo $rift['level']; ?></p>

                                <p><b>Reward Implicit:</b></p>
                                <ul class="list--compact">
                                    <li class="list-item--positive">
                                        <?php echo htmlspecialchars($rift_stone_implicit_definitions[$rift['implicit']]['description']); ?>
                                    </li>
                                </ul>

                                <p><b>Difficulty Affixes:</b></p>
                                <ul class="list--compact">
                                    <?php foreach ($rift['affixes'] as $affix_key): ?>
                                        <li class="list-item--negative">
                                            <?php echo htmlspecialchars($rift_stone_affix_definitions[$affix_key]['description']); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div>
                                <form method="POST" action="/rifts" class="form--inline" onsubmit="return confirm('Remove this rift from the queue?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                    <input type="hidden" name="rift_stone_id" value="<?php echo $rift['id']; ?>">
                                    <input type="submit" role="button" name="remove_rift" value="Remove from Queue" class="secondary">
                                </form>
                                <?php if (!empty($rift_simulation_results[$rift['id']])): ?>
                                    <?php
                                        $sim = $rift_simulation_results[$rift['id']];
                                        $sim_pct = round(($sim['won'] / $sim['total']) * 100, 1);
                                        if ($sim_pct >= 90) {
                                            $sim_class = 'list-item--success';
                                        } else {
                                            $sim_class = 'list-item--negative';
                                        }
                                    ?>
                                    <p><b>Simulation (300x):</b> <span class="<?php echo $sim_class; ?>"><?php echo $sim_pct; ?>% success rate</span>
                                    <small>(<?php echo $sim['won']; ?> wins / <?php echo $sim['lost']; ?> losses)</small></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Available Rift Stones Section -->
        <h2>Available Rift Stones</h2>

        <?php if (count($queued_rifts) >= $rift_queue_max): ?>
            <p class="text--warning"><em>Queue is full (<?php echo $rift_queue_max; ?>/<?php echo $rift_queue_max; ?>). Remove a rift from the queue to add more.</em></p>
        <?php endif; ?>

        <?php if (empty($available_rift_stones)): ?>
            <p><em>No available rift stones. All your rifts are either queued or you need to <a href="/craft?tab=rift_stones">craft more</a>.</em></p>
        <?php else: ?>
            <p><b>Total Available:</b> <?php echo count($available_rift_stones); ?></p>

            <?php foreach ($available_rift_stones as $rift_stone): ?>
                <div class="card">
                    <div class="grid">
                        <div>
                            <h3 class="heading--no-top-margin"><?php echo htmlspecialchars($rift_stone['name']); ?></h3>
                            <p><b>Rift Level:</b> <?php echo $rift_stone['level']; ?></p>

                            <p><b>Reward Implicit:</b></p>
                            <ul class="list--compact">
                                <li class="list-item--positive">
                                    <?php echo htmlspecialchars($rift_stone_implicit_definitions[$rift_stone['implicit']]['description']); ?>
                                </li>
                            </ul>

                            <p><b>Difficulty Affixes:</b></p>
                            <ul class="list--compact">
                                <?php foreach ($rift_stone['affixes'] as $affix_key): ?>
                                    <li class="list-item--negative">
                                        <?php echo htmlspecialchars($rift_stone_affix_definitions[$affix_key]['description']); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <p><small>Crafted at Party Level: <?php echo $rift_stone['party_level_at_craft'] ?? 'N/A'; ?></small></p>
                        </div>
                        <div>
                            <form method="POST" action="/rifts" class="form--inline">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <input type="hidden" name="rift_stone_id" value="<?php echo $rift_stone['id']; ?>">
                                <input type="submit" role="button" name="queue_rift" value="Add to Queue" <?php echo count($queued_rifts) >= $rift_queue_max ? 'disabled' : ''; ?>>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Rift Battle Log -->
        <?php if (!empty($Character->Data['last_rift_log'])): ?>
            <h2>Last Rift Delve Result</h2>
            <p><small>Last completed: <?php echo $Character->Data['last_rift_time'] ?? 'Never'; ?></small></p>
            <div class="log-box">
                <?php echo $Character->Data['last_rift_log']; ?>
            </div>
        <?php endif; ?>

    </article>
    </div>
    </div>
</main>
