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
        <?php if(!empty($simulated_results)) {
            $class = $simulated_results['won'] > $simulated_results['lost'] ? 'alert-success' : 'alert-error';
        ?>
        <div class="alert <?php echo $class; ?>">
            <b>Simulation Results for Floor <?php echo intval($_POST['new_floor']); ?></b> <BR />
            Wins: <?php echo $simulated_results['won']; ?> | Losses: <?php echo $simulated_results['lost']; ?> | Total Battles: <?php echo $simulated_results['total']; ?>
        </div>
        <?php } ?>

        <div class="grid">
            <form METHOD="POST" action="/arena">
                <div>
                    Current Floor: <?php echo $Character->Data['arena_floor']; ?> <br />
                    <input type="number" name="new_floor" value="<?php echo $Character->Data['arena_floor']; ?>"
                    min="1" max="1000000" />
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">
                    <input type="submit" value="Update Floor" name="update_floor" />
                    <input type="submit" value="Simulate Floor" name="simulate_floor" />
                </div>
            </form>
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
