<?php
if(isset($failure_message)) {
    echo '
    <dialog open id="messageModal">
        <article>
            <header>
            <button aria-label="Close" rel="prev" id="closeMessageModal"></button>
            <p>
                <strong>Insufficient Energy!</strong>
            </p>
            </header>
            Training consumes one energy per attempt, please reduce your attempts accordingly.
        </article>
    </dialog>';
}

if(isset($success_message)) {
    echo '
    <dialog open id="messageModal">
        <article>
            <header>
            <button aria-label="Close" rel="prev" id="closeMessageModal"></button>
            <p>
                <strong>Training Complete!</strong>
            </p>
            </header>
            ' . $success_message . '
        </article>
    </dialog>';
}
?>

<div>
    <h2>Gym</h2>
    <p>
    Stats are critical to character growth.
    as they determine your success rate in delves and combat. Even the quality of your crafting attempts are governed by such things.
    A player with higher total stats will usually beat a weaker player in a fight but gear and skill selection also play a role.
    </p>
    <p>
    You use 1 energy per training attempt but gains are not guaranteed.
    </p>
    <p>
    Welcome to Adventurer's Gym! Here you can train your character to improve their stats.
    </p>
</div>


    <div><h3><?php echo $_SESSION['agility']; ?> Agility</h3>
    <form action="/play-now/gym" method="POST">
        <input type="hidden" name="stat" value="agility">
        <input type="number" name="amount" min="1" placeholder="Amount">
        <button type="submit">Train</button>
        <?php SecurityTools::AddFormCSRFToken('gym'); ?>
    </form>
    </div>
    <div><h3><?php echo $_SESSION['dexterity']; ?> Dexterity</h3>
    <form action="/play-now/gym" method="POST">
        <input type="hidden" name="stat" value="dexterity">
        <input type="number" name="amount" min="1" placeholder="Amount">
        <button type="submit">Train</button>
        <?php SecurityTools::AddFormCSRFToken('gym'); ?>
    </form>
    </div>
    <div><h3><?php echo $_SESSION['constitution']; ?> Constitution</h3>
    <form action="/play-now/gym" method="POST">
        <input type="hidden" name="stat" value="constitution">
        <input type="number" name="amount" min="1" placeholder="Amount">
        <button type="submit">Train</button>
        <?php SecurityTools::AddFormCSRFToken('gym'); ?>
    </form>
    </div>
    <div><h3><?php echo $_SESSION['strength']; ?> Strength</h3>
    <form action="/play-now/gym" method="POST">
        <input type="hidden" name="stat" value="strength">
        <input type="number" name="amount" min="1" placeholder="Amount">
        <button type="submit">Train</button>
        <?php SecurityTools::AddFormCSRFToken('gym'); ?>
    </form>
    </div>
    <div><h3><?php echo $_SESSION['intelligence']; ?> Intelligence</h3>
    <form action="/play-now/gym" method="POST">
        <input type="hidden" name="stat" value="intelligence">
        <input type="number" name="amount" min="1" placeholder="Amount">
        <button type="submit">Train</button>
        <?php SecurityTools::AddFormCSRFToken('gym'); ?>
    </form>
    </div>
