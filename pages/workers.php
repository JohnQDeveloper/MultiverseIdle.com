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
            $harvests = 10;
            $harvests = worker_yield($harvests, $speed_upgrades, $skill_level, $num_workers);
            echo human_num($harvests) . ' ' . htmlspecialchars(ucfirst($resource)) . ' every 10 minutes.';
        ?>
        <br />
        <?php display_worker_yield_formula(); ?>
        <br /><br />

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
