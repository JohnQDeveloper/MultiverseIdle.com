<?php require_once('../templates/game-header.php'); ?>
    <div class="wrapper">
    <article class="main">
        <h1><?php echo t('guild_buildings.title'); ?></h1>

        <?php if ($user_guild_id === null): ?>
            <div class="info-box">
                <h3 class="heading--no-top-margin"><?php echo t('guild_buildings.not_in_guild'); ?></h3>
                <p><?php echo t('guild_buildings.not_in_desc'); ?></p>
            </div>
        <?php else: ?>
            <p style="margin-top: 0;">
                <a href="/guilds"><?php echo t('guild_buildings.back', ['name' => htmlspecialchars($guild_data['name'])]); ?></a>
            </p>

            <div class="info-box">
                <p style="margin: 0;">
                    <?php echo t('guild_buildings.info'); ?>
                    <br><small style="color: #999;"><?php echo t('guild_buildings.cost_note', ['gold' => number_format($guild_bank['gold']), 'iron' => number_format($guild_bank['iron']), 'herbs' => number_format($guild_bank['herbs']), 'gems' => number_format($guild_bank['gems'])]); ?></small>
                </p>
            </div>

            <?php
            $building_defs = [
                'farm'      => ['label' => t('guild_buildings.farm'),      'effect' => t('guild_buildings.effect.farm')],
                'iron_mine' => ['label' => t('guild_buildings.iron_mine'),  'effect' => t('guild_buildings.effect.iron_mine')],
                'gem_mine'  => ['label' => t('guild_buildings.gem_mine'),   'effect' => t('guild_buildings.effect.gem_mine')],
                'market'    => ['label' => t('guild_buildings.market'),     'effect' => t('guild_buildings.effect.market')],
                'gym'       => ['label' => t('guild_buildings.gym'),        'effect' => t('guild_buildings.effect.gym')],
                'tavern'    => ['label' => t('guild_buildings.tavern'),     'effect' => t('guild_buildings.effect.tavern')],
            ];

            $can_upgrade = in_array($user_role, ['guild_master', 'officer'], true);
            ?>

            <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px;">
                <?php foreach ($building_defs as $key => $def): ?>
                    <?php
                    $level      = $building_levels[$key];
                    $next_cost  = 10000 * $member_count * ($level + 1);
                    $resource   = $building_resources[$key] ?? 'gold';
                    $resource_label = t('res.' . strtolower($resource));
                    $can_afford = $guild_bank[$resource] >= $next_cost;
                    ?>
                    <div class="card">
                        <h3 class="heading--no-top-margin"><?php echo $def['label']; ?></h3>
                        <p style="margin: 5px 0;">
                            <span class="badge"><?php echo t('common.level'); ?> <?php echo $level; ?></span>
                            <span style="margin-left: 8px; color: #8EFAD5;">+<?php echo $level; ?>% <?php echo $def['effect']; ?></span>
                        </p>
                        <p style="margin: 5px 0; color: #999; font-size: 0.85em;">
                            <?php echo t('guild_buildings.next_upgrade'); ?> <b style="color: <?php echo $can_afford ? '#8EFAD5' : '#ff6b6b'; ?>;"><?php echo number_format($next_cost); ?> <?php echo htmlspecialchars($resource_label); ?></b>
                        </p>

                        <?php if ($can_upgrade): ?>
                            <form method="post" style="margin-top: 10px;">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <input type="hidden" name="building" value="<?php echo $key; ?>">
                                <button
                                    type="submit"
                                    name="upgrade_building"
                                    class="button button--primary button--small"
                                    <?php echo !$can_afford ? 'disabled title="' . t('guild_buildings.not_enough', ['resource' => htmlspecialchars($resource_label)]) . '"' : ''; ?>
                                ><?php echo t('guild_buildings.upgrade'); ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!$can_upgrade): ?>
                <p style="color: #999; margin-top: 15px;"><small><?php echo t('guild_buildings.no_perm'); ?></small></p>
            <?php endif; ?>

        <?php endif; ?>
    </article>
    </div>
<?php require_once('../templates/footer.php'); ?>
