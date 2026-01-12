<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1>Workers Management</h1>

        <p>
        Here you can manage your workers. Assign them to different tasks, upgrade their skills, and monitor their progress.
        Efficient worker management is key to maximizing your resource production and overall game success.
        </p>

        <b>Select Resource to Assign Workers:</b><br />
        <?php echo Controls::ResourceSelectBox(); ?>

        <b>Workers:</b> 1<br />  <br />

        <b>Worker Skill Level</b><br />
        <?php
        echo '<div class="worker-skill-level">';
        foreach (RESOURCES as $resource) {
            echo '' . htmlspecialchars($resource) . ': 365<BR />';
        }
        echo '</div>';
        ?>
        <br />
        <a href="#" role="button">Hire More Workers</a><br /><br />

    </article>
    </div>
    </div>
</main>
