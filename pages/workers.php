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
        <input type="submit" role="button" name="change_resource" value="Change Resource"><br /><br />

        <div class="grid">
            <div>
                <b>#% of Speed Upgrades:</b><br />
                <input type="text" name="worker_speed" value="1">
                <input type="submit" role="button" name="upgrade_speed"
                value="Upgrade Speed for <?php echo human_num($next_speed_upgrade_cost); ?> gold">
            </div>
            <div>
                <b>#% of XP Gain Upgrades aka Worker Intelligence:</b><br />
                <input type="text" name="worker_intelligence" value="1">
                <input type="submit" role="button" name="upgrade_intelligence"
                value="Upgrade Intelligence for <?php echo human_num($next_intelligence_upgrade_cost); ?> gold">
            </div>
        </div>


        <input type="submit" role="button" name="hire_workers"
        value="Hire +1 Worker for <?php echo human_num($new_worker_cost); ?> gold"><br /><br />

        </form>

    </article>
    </div>
    </div>
</main>
