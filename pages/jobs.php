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
            Performing a job consumes 5 energy or nerve per attempt, you currently do not have enough of either.
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
    <h2>Jobs</h2>
    <p>
    Stats are critical to character growth and jobs provide a passive income when your Nerve or Energy goes over the cap. They are also
    an easy source of early game cash.
    </p>
    <?php
    if($guest_mode) {
        echo "<p>For the tutorial, this means little as you only get your allotted starting energy,
        you should only perform a job for early cash. Demand is artificial during the tutorial always printing $100.</p>";
    }
    ?>

    <p>
    Jobs consume 5 energy or 5 nerve per task. So when idle, this defaults to 6 attempts.
    You roll 2 stat gains and 2 skill gains each time you perform a job so it is less efficient for stats than the gym.
    The last job you trained will be yours until you change.
    </p>
    <p>
    Welcome to the Merchant Quarter! Here you can work to earn money while improving stats and skills.
    </p>
    <p>
    Each demand type is global across the entire game divided among the number of people performing the job that tick. There is also a
    baseline level of demand based on daily active users for all jobs. One DAU = $1 of baseline demand for each job type.

    Basically, you want to serve jobs that are in high demand but have low supply. This mechanic is meant to balance high value pvp
    stats with other less valuable but economically important stats. Basically, you'll have to decide if you want money or pvp more.
    </p>
</div>

<div class="grid">
    <div>
    <form action="/play-now/jobs" method="POST">
        <input type="hidden" name="job" value="healer">
        <button type="submit">Work as a Healer</button>
    </form>
    <h3>Healer - Intelligence/Healing</h3>
    <p>Healers train Healing, improving Healing Effectiveness, while also training Intelligence.
        Demand is determined by overall healing. </p>
    <p>1 Life = $1 of Demand.</p>
    </div>

    <div>
    <form action="/play-now/jobs" method="POST">
        <input type="hidden" name="job" value="runeforger">
        <button type="submit">Work as a Runeforger</button>
    </form>
    <h3>Runeforger - Intelligence/Runemastery</h3>
    <p>Runeforgers train Runemastery, improving Rune Effectiveness, while also training Intelligence that doubles down
        on economic activity.
        Demand is determined by overall crafting. </p>
    <p>1 Minor Rune = $1 of Demand. 1 Major Rune = $5 of Demand.</p>
    </div>

    <div>
    <form action="/play-now/jobs" method="POST">
        <input type="hidden" name="job" value="armorer">
        <button type="submit">Work as a Armorer</button>
    </form>
    <h3>Armorer - Strength/Forging</h3>
    <p>Armorers also train crafting but focus on building valuable base items. The results are based on strength as it
        is more a question of working with rare metals and forcing them into shape.
        Demand is determined by crafting of quality items.
    </p>
    <p>1 Mithral = $1 of Demand.</p>
    </div>

    <div>
    <form action="/play-now/jobs" method="POST">
        <input type="hidden" name="job" value="alchemist">
        <button type="submit">Work as a Alchemist</button>
    </form>
    <h3>Alchemist - Agility/Dexterity</h3>
    <p>Consumables are always in demand and producing these valuable potions will provide dividends.
    The results are a mixture of dexterity in preparing the ingredients and skill at the craft.
    It should be noted that you train two stats with this one but no skills.</p>
    <p>Each consumable that is produced is $1 of Demand.</p>
    </div>

</div>
