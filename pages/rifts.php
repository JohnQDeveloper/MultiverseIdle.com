<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1>Rift Delves</h1>
        <p>Queue up to 6 rift stones to run challenging 10-battle Rift Delves. Each rift runs automatically every 4 hours.</p>

        <div class="info-box">
            <h3 class="heading--no-top-margin">How Rift Delves Work</h3>
            <ul>
                <li><b>Queue rifts:</b> Add rift stones to your queue (max 6)</li>
                <li><b>Automatic processing:</b> Rifts run every 4 hours</li>
                <li><b>10 consecutive battles:</b> Fight through all 10 to earn rewards</li>
                <li><b>Full heal between battles:</b> You start each fight at full health</li>
                <li><b>All-or-nothing rewards:</b> Only get rewards if you complete all 10 battles</li>
            </ul>
        </div>

        <!-- Rift Queue Section -->
        <h2>Rift Queue (<?php echo count($queued_rifts); ?>/6)</h2>

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
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Available Rift Stones Section -->
        <h2>Available Rift Stones</h2>

        <?php if (count($queued_rifts) >= 6): ?>
            <p class="text--warning"><em>Queue is full (6/6). Remove a rift from the queue to add more.</em></p>
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
                                <input type="submit" role="button" name="queue_rift" value="Add to Queue" <?php echo count($queued_rifts) >= 6 ? 'disabled' : ''; ?>>
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
            <div style="background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                <?php echo $Character->Data['last_rift_log']; ?>
            </div>
        <?php endif; ?>

    </article>
    </div>
    </div>
</main>
