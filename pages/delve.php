<?php
if(isset($failure_message)) {
    echo '
    <dialog open id="messageModal">
        <article>
            <header>
            <button aria-label="Close" rel="prev" id="closeMessageModal"></button>
            <p>
                <strong>Insufficient Nerve!</strong>
            </p>
            </header>
            Performing a delve consumes 5 nerve per attempt, you currently do not have enough.
            You must also have 5+ life to make the attempt or its simply suicidal.
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
                <strong>Delve Completed!</strong>
            </p>
            </header>
            ' . $success_message . '
        </article>
    </dialog>';
}
?>

<div>
    <h2>Delve</h2>
    <p>Do you have what it takes to risk it all for riches?</p>
    <p>
    Delving is a risky but rewarding activity that allows you to explore hidden realms and uncover valuable resources.
    It can also put you in the hospital for an hour, costing you valuable energy and nerve.
    </p>
</div>

<div class="grid">
    <div>
    <form action="/play-now/delve" method="POST">
        <input type="hidden" name="delve" value="mining">
        <input type="number" name="risk" min="1" max="100" value="5" placeholder="% Risk Tolerance">
        <button type="submit">Dig for riches</button>
    </form>
    <h3>Mining - Strength/Mining</h3>
    <p>Mining yields valuable Mithral, Runes, and Money. </p>
    <p>But beware the cave in...</p>
    </div>

    <div>
    <form action="/play-now/delve" method="POST">
        <input type="hidden" name="delve" value="puzzles">
        <input type="number" name="risk" min="1" max="100" value="5" placeholder="% Risk Tolerance">
        <button type="submit">Solve puzzles</button>
    </form>
    <h3>Deadly Puzzles - Intelligence/Puzzles</h3>
    <p>Behind every puzzle is a stat book, potion, or cash.</p>
    <p>But beware a wrong answer...</p>
    </div>

    <div>
    <form action="/play-now/delve" method="POST">
        <input type="hidden" name="delve" value="traps">
        <input type="number" name="risk" min="1" max="100" value="5" placeholder="% Risk Tolerance">
        <button type="submit">Disarm traps</button>
    </form>
    <h3>Disarm Traps - Dexterity/Agility</h3>
    <p>Behind every trap is a stat book, potion, or cash.</p>
    <p>But beware the trigger wire...</p>
    </div>

    <div>
    <form action="/play-now/delve" method="POST">
        <input type="hidden" name="delve" value="hunt">
        <input type="number" name="risk" min="1" max="100" value="5" placeholder="% Risk Tolerance">
        <button type="submit">Hunt monsters!</button>
    </form>
    <h3>Monster Hunting - Combat (PvE)</h3>
    <p>200% of normal yields of all loot drops.</p>
    <p>Monsters scale with battle power so this is never 100% safe...</p>
    </div>


</div>
