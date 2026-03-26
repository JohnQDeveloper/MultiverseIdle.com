<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1><?php echo t('workers.title'); ?></h1>

        <p><?php echo t('workers.desc'); ?></p>

        <form method="POST">

        <b><?php echo t('workers.yield'); ?></b>:<BR />
        <?php
            $resource = $Character->Data['worker_json']['resource'];
            $skill_level = $Character->Data['worker_json']['skills'][$resource];
            $num_workers = $Character->Data['worker_json']['workers'];
            $speed_upgrades = $Character->Data['worker_json']['speed_upgrades'] ?? 0;
            $intelligence_upgrades = $Character->Data['worker_json']['intelligence_upgrades'] ?? 0;

            # Get the specific potion bonus for this resource type
            # Note: the potion system uses 'herb' (singular) for the herbs resource
            $potion_resource_key = ($resource === 'herbs') ? 'herb' : $resource;
            $potion_bonus_key = $potion_resource_key . '_worker_yield';
            $potion_bonus = isset($potion_bonuses[$potion_bonus_key]) ? $potion_bonuses[$potion_bonus_key] : 0;

            $harvests = 10;
            $total = worker_yield($harvests, $speed_upgrades, $skill_level, $num_workers, $potion_bonus);
            echo t('workers.every_minute', ['amount' => human_num($total), 'resource' => htmlspecialchars(t('res.' . strtolower($resource)))]);

            if ($potion_bonus > 0) {
                echo ' <span class="text--success">' . t('workers.from_potion', ['pct' => $potion_bonus]) . '</span>';
            }
        ?>
        <br />
        <?php display_worker_yield_formula(10, $speed_upgrades, $skill_level, $num_workers, $potion_bonus); ?>
        <br /><br />

        <?php if ($active_potion): ?>
            <div class="potion-effect-box">
                <b class="potion-effect-box__title"><?php echo t('common.active_effects'); ?></b>
                <ul class="potion-effect-box__list">
                    <?php
                        # Potion definitions for display
                        $potion_prefix_definitions = Potion::getPrefixDefinitions();
                        $potion_suffix_definitions = Potion::getSuffixDefinitions();

                        $prefix_value = $active_potion['level'] * $potion_prefix_definitions[$active_potion['prefix']]['per_level'];
                        $suffix_value = $active_potion['level'] * $potion_suffix_definitions[$active_potion['suffix']]['per_level'];

                        $time_remaining = strtotime($active_potion['expire_time']) - time();
                        $hours_remaining = floor($time_remaining / 3600);
                        $minutes_remaining = floor(($time_remaining % 3600) / 60);
                    ?>
                    <li>+<?php echo $prefix_value; ?>% <?php echo htmlspecialchars(t('potion.affix.' . $active_potion['prefix'])); ?></li>
                    <li>+<?php echo $suffix_value; ?>% <?php echo htmlspecialchars(t('potion.affix.' . $active_potion['suffix'])); ?></li>
                </ul>
                <small><?php echo t('common.expires_in', ['h' => $hours_remaining, 'm' => $minutes_remaining]); ?></small>
            </div>
        <?php endif; ?>
        <br />

        <b><?php echo t('workers.count'); ?></b>
        <?php echo $Character->Data['worker_json']['workers']; ?><br />  <br />

        <b><?php echo t('workers.speed'); ?></b>
        <?php echo $Character->Data['worker_json']['speed_upgrades'] ?? 0; ?>%<br />  <br />
        <b><?php echo t('workers.intelligence'); ?></b>
        <?php echo $Character->Data['worker_json']['intelligence_upgrades'] ?? 0; ?>%<br />  <br />

        <b><?php echo t('workers.skill_level'); ?></b><br />
        <?php
        echo '<div class="worker-skill-level">';
        foreach (RESOURCES as $resource) {
            echo '' . htmlspecialchars(t('res.' . strtolower($resource))) . ': '.
            $Character->Data['worker_json']['skills'][strtolower($resource)].'<BR />';
        }
        echo '</div>';
        ?>
        <br />

        <b><?php echo t('workers.select_resource'); ?></b><br />
        <?php echo Controls::ResourceSelectBox($Character->Data['worker_json']['resource']); ?>
        <input type="submit" role="button" class="contrast" name="change_resource" value="<?php echo t('workers.change_resource'); ?>"><br /><br />

        <div class="grid">
            <div>
                <b><?php echo t('workers.speed_upgrades'); ?></b><br />
                <input type="text" name="worker_speed" value="1">
                <input type="submit" role="button" name="upgrade_speed"
                class="<?php echo affordable_button($Character->Data['gold'], $next_speed_upgrade_cost); ?>"
                value="<?php echo t('workers.upgrade_speed', ['cost' => human_num($next_speed_upgrade_cost)]); ?>">
            </div>
            <div>
                <b><?php echo t('workers.int_upgrades'); ?></b><br />
                <input type="text" name="worker_intelligence" value="1">
                <input type="submit" role="button" name="upgrade_intelligence"
                class="<?php echo affordable_button($Character->Data['gold'], $next_intelligence_upgrade_cost); ?>"
                value="<?php echo t('workers.upgrade_int', ['cost' => human_num($next_intelligence_upgrade_cost)]); ?>">
            </div>
        </div>


        <input type="submit" role="button" name="hire_workers"
        class="<?php echo affordable_button($Character->Data['gold'], $new_worker_cost); ?>"
        value="<?php echo t('workers.hire', ['cost' => human_num($new_worker_cost)]); ?>"><br /><br />

        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
        </form>

    </article>
    </div>
    </div>
</main>
