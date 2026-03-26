<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1><?php echo t('arena.title'); ?></h1>
        <p><?php echo t('arena.desc'); ?></p>
        <?php if(!empty($simulated_results)) {
            $class = $simulated_results['won'] > $simulated_results['lost'] ? 'alert-success' : 'alert-danger';
        ?>
        <div class="alert <?php echo $class; ?>">
            <b><?php echo t('arena.sim_title', ['floor' => intval($_POST['new_floor'])]); ?></b> <BR />
            <?php echo t('arena.wins'); ?>: <?php echo $simulated_results['won']; ?> | <?php echo t('arena.losses'); ?>: <?php echo $simulated_results['lost']; ?> | <?php echo t('arena.total_battles'); ?>: <?php echo $simulated_results['total']; ?>
        </div>
        <?php } ?>

        <?php if ($active_potion): ?>
            <?php
                # Check if potion has arena-related bonuses
                $has_arena_bonus = false;
                $arena_bonuses = [];

                if (isset($potion_bonuses['arena_xp']) && $potion_bonuses['arena_xp'] > 0) {
                    $arena_bonuses[] = t('arena.xp_bonus', ['pct' => $potion_bonuses['arena_xp']]);
                    $has_arena_bonus = true;
                }
                if (isset($potion_bonuses['arena_stat_gains']) && $potion_bonuses['arena_stat_gains'] > 0) {
                    $arena_bonuses[] = t('arena.stat_bonus', ['pct' => $potion_bonuses['arena_stat_gains']]);
                    $has_arena_bonus = true;
                }
                if (isset($potion_bonuses['arena_resource_drops']) && $potion_bonuses['arena_resource_drops'] > 0) {
                    $arena_bonuses[] = t('arena.resource_bonus', ['pct' => $potion_bonuses['arena_resource_drops']]);
                    $has_arena_bonus = true;
                }
            ?>
            <?php if ($has_arena_bonus): ?>
                <div class="potion-effect-box">
                    <b class="potion-effect-box__title"><?php echo t('common.active_effects'); ?></b>
                    <ul class="potion-effect-box__list">
                        <?php foreach ($arena_bonuses as $bonus): ?>
                            <li><?php echo $bonus; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php
                        $time_remaining = strtotime($active_potion['expire_time']) - time();
                        $hours_remaining = floor($time_remaining / 3600);
                        $minutes_remaining = floor(($time_remaining % 3600) / 60);
                    ?>
                    <small><?php echo t('common.expires_in', ['h' => $hours_remaining, 'm' => $minutes_remaining]); ?></small>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="grid">
            <form METHOD="POST" action="/arena">
                <div>
                    <?php echo t('arena.current_floor'); ?> <?php echo $Character->Data['arena_floor']; ?> <br />
                    <input type="number" name="new_floor" value="<?php echo $Character->Data['arena_floor']; ?>"
                    min="1" max="1000000" />
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                    <input type="submit" value="<?php echo t('arena.update_floor'); ?>" name="update_floor" />
                    <input type="submit" value="<?php echo t('arena.simulate_floor'); ?>" name="simulate_floor"
                        <?php echo $has_active_sub ? '' : 'disabled title="' . t('arena.requires_sub') . '"'; ?> />
                </div>
            </form>
            <div>
                <div><?php echo t('arena.last_battle_at'); ?>
                <?php echo $Character->Data['last_arena_time']; ?>
                </div>
                <div>
                <h3><?php echo t('arena.battle_log'); ?></h3>
                <div><?php echo localize_battle_log((string)$Character->Data['last_arena_log']); ?></div>
            </div>
    </article>
    </div>
    </div>
</main>
