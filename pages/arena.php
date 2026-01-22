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
            $class = $simulated_results['won'] > $simulated_results['lost'] ? 'alert-success' : 'alert-danger';
        ?>
        <div class="alert <?php echo $class; ?>">
            <b>Simulation Results for Floor <?php echo intval($_POST['new_floor']); ?></b> <BR />
            Wins: <?php echo $simulated_results['won']; ?> | Losses: <?php echo $simulated_results['lost']; ?> | Total Battles: <?php echo $simulated_results['total']; ?>
        </div>
        <?php } ?>

        <?php if ($active_potion): ?>
            <?php
                # Check if potion has arena-related bonuses
                $has_arena_bonus = false;
                $arena_bonuses = [];

                if (isset($potion_bonuses['arena_xp']) && $potion_bonuses['arena_xp'] > 0) {
                    $arena_bonuses[] = '+' . $potion_bonuses['arena_xp'] . '% Arena XP';
                    $has_arena_bonus = true;
                }
                if (isset($potion_bonuses['arena_stat_gains']) && $potion_bonuses['arena_stat_gains'] > 0) {
                    $arena_bonuses[] = '+' . $potion_bonuses['arena_stat_gains'] . '% chance for bonus stat';
                    $has_arena_bonus = true;
                }
                if (isset($potion_bonuses['arena_resource_drops']) && $potion_bonuses['arena_resource_drops'] > 0) {
                    $arena_bonuses[] = '+' . $potion_bonuses['arena_resource_drops'] . '% Arena Resource Drops';
                    $has_arena_bonus = true;
                }
            ?>
            <?php if ($has_arena_bonus): ?>
                <div style="border: 2px solid #28a745; padding: 10px; margin-bottom: 15px; border-radius: 5px; background-color: rgba(40, 167, 69, 0.1);">
                    <b style="color: #28a745;">Active Potion Effects:</b>
                    <ul style="margin: 5px 0;">
                        <?php foreach ($arena_bonuses as $bonus): ?>
                            <li><?php echo $bonus; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php
                        $time_remaining = strtotime($active_potion['expire_time']) - time();
                        $hours_remaining = floor($time_remaining / 3600);
                        $minutes_remaining = floor(($time_remaining % 3600) / 60);
                    ?>
                    <small>Expires in: <?php echo $hours_remaining; ?>h <?php echo $minutes_remaining; ?>m</small>
                </div>
            <?php endif; ?>
        <?php endif; ?>

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
