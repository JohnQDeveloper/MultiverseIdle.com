<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1>2v2 Arena</h1>
        <p>You battle a pair of monsters or other gladiators at your level every minute.
            They drop gold, experience, resources, and stats.
            Keep in mind your class influences your stat drops.
            (i.e. healer = lucky wisdom drops, warrior = lucky strength drops)
        </p>
        <div class="grid">
            <div>
                Current Floor: <?php echo $Character->Data['arena_floor']; ?> <br />
                <input type="number" name="new_floor" value="<?php echo $Character->Data['arena_floor']; ?>"
                 min="1" max="1000000" />
                <input type="button" value="Update Floor" />
            </div>
            <div>
                <div>Last Arena Battle Was At
                <?php echo $Character->Data['last_arena_time']; ?>
                </div>
                <div>
                <h3>Battle Log</h3>
                <div><?php echo $Character->Data['last_arena_log']; ?></div>
            </div>
    </article>
    </div>
    </div>
</main>
