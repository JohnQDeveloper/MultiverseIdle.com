<?php if(!$guest_mode) { ?>
<h3>Status</h3>
$<?php echo $_SESSION['money'] ?>
<BR />
<a href="#" data-tooltip="Recovers 15 on the hour" data-placement="right">Energy:</a>
<?php echo $_SESSION['energy'] . "/" . $_SESSION['max_energy']; ?><br />
<a href="#" data-tooltip="Recovers 15 on the hour" data-placement="right">Nerve:</a>
<?php echo $_SESSION['nerve'] . "/" . $_SESSION['max_nerve']; ?><br />
<a href="#" data-tooltip="Recovers 100% on the hour" data-placement="right">Life:</a>
<?php echo $_SESSION['life'] . "/" . $_SESSION['max_life']; ?><br />
<a href="#" data-tooltip="Drops 10% on the hour" data-placement="right">Toxicity:</a>
<?php echo $_SESSION['toxicity'] . "%"; ?><br />
<br />
<h3>Locations</h3>
<a href="/play-now/auctions">Auctions</a><br />
<a href="/play-now/character-sheet">Char Sheet</a><br />
<a href="/play-now/crafting">Crafting</a><br />
<a href="/play-now/delve">Delves</a><br />
<a href="/play-now/guilds">Guilds</a><br />
<a href="/play-now/gym">Gym</a><br />
<a href="/play-now/inventory">Inventory</a><br />
<a href="/play-now/jobs">Jobs</a><br />
<a href="/play-now/pets">Pets</a><br />
<a href="/play-now/turf-wars">Turf Wars</a><br />
<a href="/play-now/logs">Logs</a><br />
<a href="/logout">Log Out</a><br />
<?php } else { ?>
<h3>Status</h3>
$<?php echo $_SESSION['money'] ?>
<BR />
<a href="#" data-tooltip="Recovers 15 on the hour" data-placement="right">Energy:</a>
<?php echo $_SESSION['energy'] . "/" . $_SESSION['max_energy']; ?><br />
<a href="#" data-tooltip="Recovers 15 on the hour" data-placement="right">Nerve:</a>
<?php echo $_SESSION['nerve'] . "/" . $_SESSION['max_nerve']; ?><br />
<a href="#" data-tooltip="Recovers 100% on the hour" data-placement="right">Life:</a>
<?php echo $_SESSION['life'] . "/" . $_SESSION['max_life']; ?><br />
<a href="#" data-tooltip="Drops 10% on the hour" data-placement="right">Toxicity:</a>
<?php echo $_SESSION['toxicity'] . "%"; ?><br />
<br />
<h3>Locations</h3>
<a href="#">🔒 Auctions</a><br />
<a href="/play-now/character-sheet">Char Sheet</a><br />
<a href="#">🔒 Crafting</a><br />
<a href="/play-now/delve">Delves</a><br />
<a href="#">🔒 Guilds</a><br />
<a href="/play-now/gym">Gym</a><br />
<a href="/play-now/inventory">Inventory</a><br />
<a href="/play-now/jobs">Jobs</a><br />
<a href="#">🔒 Pets</a><br />
<a href="#">🔒 Turf Wars</a><br />
<a href="/play-now/logs">Logs</a><br />
<a href="/logout">Log Out</a><br />
<?php } ?>
