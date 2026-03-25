<?php require_once('../templates/game-header.php'); ?>
    <div class="wrapper">
    <article class="main">
        <h1>Guild Bank</h1>

        <?php if ($user_guild_id === null): ?>
            <div class="info-box">
                <h3 class="heading--no-top-margin">You are not in a guild</h3>
                <p>Join a guild to access the guild bank.</p>
            </div>
        <?php else: ?>
            <p style="margin-top: 0;">
                <a href="/guilds">&larr; Back to <?php echo htmlspecialchars($guild_data['name']); ?></a>
            </p>

            <!-- Balances -->
            <div class="card">
                <h2 class="heading--no-top-margin">Balances</h2>
                <div class="grid" style="grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 10px;">
                    <?php foreach (['gold' => 'Gold', 'iron' => 'Iron', 'herbs' => 'Herbs', 'gems' => 'Gems'] as $key => $label): ?>
                        <div style="text-align: center; padding: 10px; border: 1px solid #444;">
                            <div style="font-size: 0.85em; color: #999;"><?php echo $label; ?></div>
                            <div style="font-size: 1.2em; font-weight: bold;"><?php echo number_format($guild_bank[$key]); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Donate -->
            <div class="card">
                <h2 class="heading--no-top-margin">Donate</h2>
                <form method="post" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                    <select name="commodity" style="padding: 6px;">
                        <option value="gold">Gold</option>
                        <option value="iron">Iron</option>
                        <option value="herbs">Herbs</option>
                        <option value="gems">Gems</option>
                    </select>
                    <input type="number" name="amount" min="1" placeholder="Amount" style="width: 120px; padding: 6px;">
                    <button type="submit" name="donate_to_bank" class="button button--primary">Donate</button>
                </form>
            </div>

            <!-- Tax Rate (officers/master only) -->
            <?php if ($user_role === 'guild_master' || $user_role === 'officer'): ?>
                <div class="card">
                    <h2 class="heading--no-top-margin">Tax Rate</h2>
                    <p style="margin-top: 0; color: #999;">
                        Current rate: <b style="color: inherit;"><?php echo $guild_tax_rate; ?>%</b>
                        — flat % automatically taken from each member's arena, worker, rift, and world boss income.
                    </p>
                    <form method="post" style="display: flex; align-items: center; gap: 10px;">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                        <label for="tax_rate"><b>Set Tax Rate:</b></label>
                        <input type="number" id="tax_rate" name="tax_rate" min="0" max="20" value="<?php echo $guild_tax_rate; ?>" style="width: 70px; padding: 6px; text-align: center;">
                        <span style="color: #999;">% (0–20)</span>
                        <button type="submit" name="set_tax_rate" class="button button--primary">Save</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="info-box">
                    <p style="margin: 0;">Tax Rate: <b><?php echo $guild_tax_rate; ?>%</b>
                        <small style="color: #999;"> — set by guild master or officers</small>
                    </p>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </article>
    </div>
<?php require_once('../templates/footer.php'); ?>
