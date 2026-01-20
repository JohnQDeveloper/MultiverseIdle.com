<?php
#print_r($_POST);die();
$simulated_results = [];
if(isset($_POST['simulate_floor'])) {
    $new_floor = intval($_POST['new_floor']);

    $Battle = new Battle();

    $simulated_results = $Battle->SimulateArenaFloor($Character, $new_floor);

    #print_r($results);die();
}
