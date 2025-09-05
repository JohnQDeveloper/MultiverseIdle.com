<table>
    <tr><th colspan="5">Raw Materials</th></tr>
    <tr>
        <td>Mithral: <?php echo $_SESSION['mithral']; ?></td>
        <td>Minor Runes: <?php echo $_SESSION['minor runes']; ?></td>
        <td>Major Runes: <?php echo $_SESSION['major runes']; ?></td>
        <td>Stat Books: <?php echo $_SESSION['stat books']; ?></td>
        <td>Potion Ingredients: <?php echo $_SESSION['potion ingredients']; ?></td>
    </tr>
    <tr>
        <td><?php foreach($_SESSION['inventory'] as $key => $value) {
            #echo "<pre>";
            #print_r($value);
            #echo "</pre>";
            Items::displayItem($value);
        } ?></td>
    </tr>
</table>
