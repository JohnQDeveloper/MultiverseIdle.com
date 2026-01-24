<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1>Workers Management</h1>

        <p>
        Here you can manage your workers. Assign them to different tasks, upgrade their skills, and monitor their progress.
        Efficient worker management is key to maximizing your resource production and overall game success.
        </p>

        <form method="POST">

        <b>Yield</b>:<BR />
        <?php
            $resource = $Character->Data['worker_json']['resource'];
            $skill_level = $Character->Data['worker_json']['skills'][$resource];
            $num_workers = $Character->Data['worker_json']['workers'];
            $speed_upgrades = $Character->Data['worker_json']['speed_upgrade_percent'];
            $intelligence_upgrades = $Character->Data['worker_json']['intelligence_upgrade_percent'];

            # Get the specific potion bonus for this resource type
            $potion_bonus_key = $resource . '_worker_yield';
            $potion_bonus = isset($potion_bonuses[$potion_bonus_key]) ? $potion_bonuses[$potion_bonus_key] : 0;

            $harvests = 10;
            $total = worker_yield($harvests, $speed_upgrades, $skill_level, $num_workers, $potion_bonus);
            echo human_num($total) . ' ' . htmlspecialchars(ucfirst($resource)) . ' every minute.';

            if ($potion_bonus > 0) {
                echo ' <span class="text--success">(+' . $potion_bonus . '% from potion)</span>';
            }
        ?>
        <br />
        <?php display_worker_yield_formula(10, $speed_upgrades, $skill_level, $num_workers, $potion_bonus); ?>
        <br /><br />

        <?php if ($active_potion): ?>
            <div class="potion-effect-box">
                <b class="potion-effect-box__title">Active Potion Effects:</b>
                <ul class="potion-effect-box__list">
                    <?php
                        # Potion definitions for display
                        $potion_prefix_definitions = [
                            'herb_worker_yield' => ['name' => 'Herb Worker Yield', 'per_level' => 1],
                            'gold_worker_yield' => ['name' => 'Gold Worker Yield', 'per_level' => 1],
                            'iron_worker_yield' => ['name' => 'Iron Worker Yield', 'per_level' => 1],
                            'gems_worker_yield' => ['name' => 'Gems Worker Yield', 'per_level' => 1],
                            'arena_resource_drops' => ['name' => 'Arena Resource Drops', 'per_level' => 1],
                            'rift_drops' => ['name' => 'Rift Drops', 'per_level' => 1],
                        ];

                        $potion_suffix_definitions = [
                            'arena_xp' => ['name' => 'Arena XP', 'per_level' => 1],
                            'arena_stat_gains' => ['name' => 'Arena Stat Gains', 'per_level' => 1],
                            'rift_xp' => ['name' => 'Rift XP', 'per_level' => 1],
                            'rift_stat_gains' => ['name' => 'Rift Stat Gains', 'per_level' => 1],
                            'world_boss_xp' => ['name' => 'World Boss XP', 'per_level' => 100],
                        ];

                        $prefix_value = $active_potion['level'] * $potion_prefix_definitions[$active_potion['prefix']]['per_level'];
                        $suffix_value = $active_potion['level'] * $potion_suffix_definitions[$active_potion['suffix']]['per_level'];

                        $time_remaining = strtotime($active_potion['expire_time']) - time();
                        $hours_remaining = floor($time_remaining / 3600);
                        $minutes_remaining = floor(($time_remaining % 3600) / 60);
                    ?>
                    <li>+<?php echo $prefix_value; ?>% <?php echo htmlspecialchars($potion_prefix_definitions[$active_potion['prefix']]['name']); ?></li>
                    <li>+<?php echo $suffix_value; ?>% <?php echo htmlspecialchars($potion_suffix_definitions[$active_potion['suffix']]['name']); ?></li>
                </ul>
                <small>Expires in: <?php echo $hours_remaining; ?>h <?php echo $minutes_remaining; ?>m</small>
            </div>
        <?php endif; ?>
        <br />

        <b>Workers:</b>
        <?php echo $Character->Data['worker_json']['workers']; ?><br />  <br />

        <b>Worker Speed:</b>
        <?php echo $Character->Data['worker_json']['speed_upgrade_percent']; ?>%<br />  <br />
        <b>Worker Intelligence:</b>
        <?php echo $Character->Data['worker_json']['intelligence_upgrade_percent']; ?>%<br />  <br />

        <b>Worker Skill Level</b><br />
        <?php
        echo '<div class="worker-skill-level">';
        foreach (RESOURCES as $resource) {
            echo '' . htmlspecialchars($resource) . ': '.
            $Character->Data['worker_json']['skills'][strtolower($resource)].'<BR />';
        }
        echo '</div>';
        ?>
        <br />

        <b>Select Resource to Assign Workers:</b><br />
        <?php echo Controls::ResourceSelectBox(); ?>
        <input type="submit" role="button" class="contrast" name="change_resource" value="Change Resource"><br /><br />

        <div class="grid">
            <div>
                <b>#% of Speed Upgrades:</b><br />
                <input type="text" name="worker_speed" value="1">
                <input type="submit" role="button" name="upgrade_speed"
                class="<?php echo affordable_button($Character->Data['gold'], $next_speed_upgrade_cost); ?>"
                value="Upgrade Speed for <?php echo human_num($next_speed_upgrade_cost); ?> gold">
            </div>
            <div>
                <b>#% of XP Gain Upgrades aka Worker Intelligence:</b><br />
                <input type="text" name="worker_intelligence" value="1">
                <input type="submit" role="button" name="upgrade_intelligence"
                class="<?php echo affordable_button($Character->Data['gold'], $next_intelligence_upgrade_cost); ?>"
                value="Upgrade Intelligence for <?php echo human_num($next_intelligence_upgrade_cost); ?> gold">
            </div>
        </div>


        <input type="submit" role="button" name="hire_workers"
        class="<?php echo affordable_button($Character->Data['gold'], $new_worker_cost); ?>"
        value="Hire +1 Worker for <?php echo human_num($new_worker_cost); ?> gold"><br /><br />

        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
        </form>

    </article>
    </div>
    </div>
</main>
