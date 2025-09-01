<div>
    <h2>Crafting - Forging/Strength</h2>
    <p>
    Crafting is the art of combining materials to craft gear for combat and delving.

    A minimum of 5 mithral is required to attempt crafting an item. Using more will increase the quality of the product.

    However, the bonuses for each craft are random which means maximizing a single stat might require several attempts.
    </p>
</div>
<h3>Strength: <?php echo $_SESSION['strength'] ?></h3>
<h3>Forging: <?php echo $_SESSION['forging'] ?></h3>
<h3>Mithral: <?php echo $_SESSION['mithral'] ?></h3>
<h3>Bonus: <?php echo round(($_SESSION['forging']/2)+($_SESSION['strength']/20))*2 ?>%</h3>
<div class="grid">
    <div>
    <form action="/play-now/crafting" method="POST">
        <input type="hidden" name="craft" value="craft">
        <p>Mithral per Attempt</p>
        <input type="number" name="mithral" min="5" max="100" value="5" placeholder="Mithral per Attempt">
        <p>Attempts</p>
        <input type="number" name="attempts" min="1" max="100" value="1" placeholder="Attempts">
        <?php
        echo '<p>Item Slot</p>';
        echo '<select name="gear-slot" aria-label="Select a gear slot..." required>';
        echo '<option selected disabled value="">
            Select a gear slot...
            </option>';
        foreach(GEAR_SLOTS as $slot) {
            echo '<option value="' . $slot . '">' . ucfirst($slot) . '</option>';
        }
        echo '</select>';
        ?>

        <button type="submit">Craft!</button>
    </form>
    </div>
