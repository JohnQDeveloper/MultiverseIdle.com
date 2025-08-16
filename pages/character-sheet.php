<table>
    <tr><th colspan="5">Character Stats</th></tr>
    <tr>
        <td>Str: <?php echo $_SESSION['strength']; ?></td>
        <td>Agi: <?php echo $_SESSION['agility']; ?></td>
        <td>Dex: <?php echo $_SESSION['dexterity']; ?></td>
        <td>Con: <?php echo $_SESSION['constitution']; ?></td>
        <td>Int: <?php echo $_SESSION['intelligence']; ?></td>
    </tr>
    <tr><th colspan="5">Skills</th></tr>
    <tr>
        <td>Healer: <?php echo $_SESSION['healing']; ?></td>
        <td>Runeforger: <?php echo $_SESSION['runemastery']; ?></td>
        <td>Armorer: <?php echo $_SESSION['forging']; ?></td>
    </tr>
</table>
