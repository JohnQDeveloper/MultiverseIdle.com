<?php

    # Worker Resource Assignment
    if(isset($_POST['change_resource'])) {
        $selected_resource = strtolower($_POST['resource'] ?? '');
        $valid_resources = array_map('strtolower', RESOURCES);
        if (in_array($selected_resource, $valid_resources, true)) {
            $Character->Data['worker_json']['resource'] = $selected_resource;
            $alert_success = t('workers.alert.assigned', ['resource' => t('res.' . strtolower($selected_resource))]);
        } else {
            $alert_danger = t('workers.alert.invalid_res');
        }
    }

    # Worker Upgrades
    $current_workers = $Character->Data['worker_json']['workers'];

    $current_speed = isset($Character->Data['worker_json']['speed_upgrades'])
    ? $Character->Data['worker_json']['speed_upgrades'] : 0;

    $current_intelligence = isset($Character->Data['worker_json']['intelligence_upgrades'])
    ? $Character->Data['worker_json']['intelligence_upgrades'] : 0;

    $new_worker_cost = calculate_worker_cost(1000, 10, $current_workers);
    $next_intelligence_upgrade_cost = calculate_worker_cost(500, 1.4, $current_intelligence);
    $next_speed_upgrade_cost = calculate_worker_cost(500, 1.4, $current_speed);

    # INCREASE INTELLIGENCE
    if(isset($_POST['upgrade_intelligence'])) {
        $increased_intelligence = 0;
        $total_cost = 0;

        # chain upgrades until you run out of gold/upgrades requested
        while(
            $Character->Data['gold'] >= $next_intelligence_upgrade_cost
            && $increased_intelligence < intval($_POST['worker_intelligence'])
        ){
            $increased_intelligence += 1; # increment loop counter

            $Character->Data['gold'] -= $next_intelligence_upgrade_cost;
            $Character->Data['worker_json']['intelligence_upgrades'] += 1;
            $total_cost += $next_intelligence_upgrade_cost;

            # Recalculate cost for next upgrade
            $current_intelligence = $Character->Data['worker_json']['intelligence_upgrades'];
            $next_intelligence_upgrade_cost = calculate_worker_cost(500, 1.4, $current_intelligence);

            $alert_success = t('workers.alert.upgraded_int', ['pct' => $increased_intelligence, 'cost' => human_num($total_cost)]);
        }

        if($increased_intelligence == 0) {
            $alert_danger = t('workers.alert.no_gold_int');
        }

    }

    # INCREASE SPEED
    if(isset($_POST['upgrade_speed'])) {
        $increased_speed = 0;
        $total_cost = 0;

        # chain upgrades until you run out of gold/upgrades requested
        while(
            $Character->Data['gold'] >= $next_speed_upgrade_cost
            && $increased_speed < intval($_POST['worker_speed'])
        ){
            $increased_speed += 1; # increment loop counter

            $Character->Data['gold'] -= $next_speed_upgrade_cost;
            $Character->Data['worker_json']['speed_upgrades'] += 1;
            $total_cost += $next_speed_upgrade_cost;

            # Recalculate cost for next upgrade
            $current_speed = $Character->Data['worker_json']['speed_upgrades'];
            $next_speed_upgrade_cost = calculate_worker_cost(500, 1.4, $current_speed);

            $alert_success = t('workers.alert.upgraded_spd', ['pct' => $increased_speed, 'cost' => human_num($total_cost)]);
        }

        if($increased_speed == 0) {
            $alert_danger = t('workers.alert.no_gold_spd');
        }
    }


    # HIRE WORKERS
    if(isset($_POST['hire_workers'])) {
        if($Character->Data['gold'] >= $new_worker_cost) {
            $Character->Data['gold'] -= $new_worker_cost;
            $Character->Data['worker_json']['workers'] += 1;
            $alert_success = t('workers.alert.hired', ['cost' => human_num($new_worker_cost)]);
        }
        else {
            $alert_danger = t('workers.alert.no_gold_hire');
        }
    }
    #echo $alert_success;die();

    # recalculate costs after potential upgrades/hiring
    $current_workers = $Character->Data['worker_json']['workers'];

    $current_speed = isset($Character->Data['worker_json']['speed_upgrades'])
    ? $Character->Data['worker_json']['speed_upgrades'] : 0;

    $current_intelligence = isset($Character->Data['worker_json']['intelligence_upgrades'])
    ? $Character->Data['worker_json']['intelligence_upgrades'] : 0;

    $new_worker_cost = calculate_worker_cost(1000, 10, $current_workers);
    $next_intelligence_upgrade_cost = calculate_worker_cost(500, 1.4, $current_intelligence);
    $next_speed_upgrade_cost = calculate_worker_cost(500, 1.4, $current_speed);

    # Load active potion bonuses
    $potion = new Potion();
    $potion_bonuses = $potion->GetActivePotionBonuses($Character->Data['id']);
    $active_potion = $potion->GetActivePotion($Character->Data['id']);
