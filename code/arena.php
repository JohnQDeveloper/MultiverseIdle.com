<?php
#print_r($_POST);die();
$simulated_results = [];

# Load active potion bonuses
$potion = new Potion();
$potion_bonuses = $potion->GetActivePotionBonuses($Character->Data['id']);
$active_potion = $potion->GetActivePotion($Character->Data['id']);

if(isset($_POST['update_floor'])) {
    $new_floor = intval($_POST['new_floor']);
    if($new_floor >= 1 && $new_floor <= 1000000) {
        $Character->Data['arena_floor'] = $new_floor;
    }
}

if(isset($_POST['simulate_floor'])) {
    $new_floor = intval($_POST['new_floor']);

    $Battle = new Battle();

    $simulated_results = $Battle->SimulateArenaFloor($Character, $new_floor);

    #print_r($results);die();
}
