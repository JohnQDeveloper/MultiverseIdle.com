<?php require_once('../templates/game-header.php'); ?>
    <div class="wrapper">
    <article class="main">
        <h1>Guild Buildings</h1>

        <?php if ($user_guild_id === null): ?>
            <div class="info-box">
                <h3 class="heading--no-top-margin">You are not in a guild</h3>
                <p>Join a guild to access guild buildings.</p>
            </div>
        <?php else: ?>
            <p style="margin-top: 0;">
                <a href="/guilds">&larr; Back to <?php echo htmlspecialchars($guild_data['name']); ?></a>
            </p>

            <div class="info-box">
                <p style="margin: 0;">
                    Guild buildings provide passive bonuses to all members. Each upgrade requires a randomly assigned resource from the guild bank &mdash; the resource is fixed until the upgrade is purchased.
                    <br><small style="color: #999;">Cost = 10,000 &times; members &times; upgrade number &mdash; bank: <b><?php echo number_format($guild_bank['gold']); ?> gold</b> / <b><?php echo number_format($guild_bank['iron']); ?> iron</b> / <b><?php echo number_format($guild_bank['herbs']); ?> herbs</b> / <b><?php echo number_format($guild_bank['gems']); ?> gems</b></small>
                </p>
            </div>

            <?php
            $building_defs = [
                'farm'      => ['label' => 'Farm',      'effect' => 'Herb gain from all sources'],
                'iron_mine' => ['label' => 'Iron Mine',  'effect' => 'Iron gain from all sources'],
                'gem_mine'  => ['label' => 'Gem Mine',   'effect' => 'Gem gain from all sources'],
                'market'    => ['label' => 'Market',     'effect' => 'Gold gain from all sources'],
                'gym'       => ['label' => 'Gym',        'effect' => 'Stat drops from all sources'],
                'tavern'    => ['label' => 'Tavern',     'effect' => 'Experience gain from all sources'],
            ];

            $can_upgrade = in_array($user_role, ['guild_master', 'officer'], true);
            ?>

            <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px;">
                <?php foreach ($building_defs as $key => $def): ?>
                    <?php
                    $level      = $building_levels[$key];
                    $next_cost  = 10000 * $member_count * ($level + 1);
                    $resource   = $building_resources[$key] ?? 'gold';
                    $can_afford = $guild_bank[$resource] >= $next_cost;
                    ?>
                    <div class="card">
                        <h3 class="heading--no-top-margin"><?php echo $def['label']; ?></h3>
                        <p style="margin: 5px 0;">
                            <span class="badge">Level <?php echo $level; ?></span>
                            <span style="margin-left: 8px; color: #8EFAD5;">+<?php echo $level; ?>% <?php echo $def['effect']; ?></span>
                        </p>
                        <p style="margin: 5px 0; color: #999; font-size: 0.85em;">
                            Next upgrade: <b style="color: <?php echo $can_afford ? '#8EFAD5' : '#ff6b6b'; ?>;"><?php echo number_format($next_cost); ?> <?php echo htmlspecialchars($resource); ?></b>
                        </p>

                        <?php if ($can_upgrade): ?>
                            <form method="post" style="margin-top: 10px;">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <input type="hidden" name="building" value="<?php echo $key; ?>">
                                <button
                                    type="submit"
                                    name="upgrade_building"
                                    class="button button--primary button--small"
                                    <?php echo !$can_afford ? 'disabled title="Not enough ' . htmlspecialchars($resource) . ' in guild bank"' : ''; ?>
                                >Upgrade</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!$can_upgrade): ?>
                <p style="color: #999; margin-top: 15px;"><small>Only guild masters and officers can upgrade buildings.</small></p>
            <?php endif; ?>

        <?php endif; ?>
    </article>
    </div>
<?php require_once('../templates/footer.php'); ?>
