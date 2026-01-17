<?php

    # Worker Resource Assignment
    if(isset($_POST['change_resource'])) {
        $selected_resource = $_POST['resource'];
        $Character->Data['worker_json']['resource'] = strtolower($selected_resource);
        $alert_success = 'Workers have been assigned to gather ' . htmlspecialchars($selected_resource) . '.';
    }

    # Worker Upgrades
    $current_workers = $Character->Data['worker_json']['workers'];

    $current_speed = isset($Character->Data['worker_json']['speed_upgrade_percent'])
    ? $Character->Data['worker_json']['speed_upgrade_percent'] : 0;

    $current_intelligence = isset($Character->Data['worker_json']['intelligence_upgrade_percent'])
    ? $Character->Data['worker_json']['intelligence_upgrade_percent'] : 0;

    $new_worker_cost = calculate_worker_cost(1000, 10, $current_workers);
    $next_intelligence_upgrade_cost = calculate_worker_cost(500, 1.4, $current_intelligence);
    $next_speed_upgrade_cost = calculate_worker_cost(500, 1.4, $current_speed);

    #print_r($_POST);

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
            $Character->Data['worker_json']['intelligence_upgrade_percent'] += 1;
            $total_cost += $next_intelligence_upgrade_cost;

            $alert_success = 'You have upgraded worker intelligence by ' . $increased_intelligence . '% for ' .
            human_num($total_cost) . ' gold!';
        }

        if($increased_intelligence == 0) {
            $alert_danger = 'You do not have enough gold to upgrade worker intelligence.';
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
            $Character->Data['worker_json']['speed_upgrade_percent'] += 1;
            $total_cost += $next_speed_upgrade_cost;

            $alert_success = 'You have upgraded worker speed by ' . $increased_speed . '% for ' .
            human_num($total_cost) . ' gold!';
        }

        if($increased_speed == 0) {
            $alert_danger = 'You do not have enough gold to upgrade worker speed.';
        }
    }


    # HIRE WORKERS
    if(isset($_POST['hire_workers'])) {
        if($Character->Data['gold'] >= $new_worker_cost) {
            $Character->Data['gold'] -= $new_worker_cost;
            $Character->Data['worker_json']['workers'] += 1;
            $alert_success = 'You have hired a new worker for ' . human_num($new_worker_cost) . ' gold!';
        }
        else {
            $alert_danger = 'You do not have enough gold to hire a new worker.';
        }
    }
    #echo $alert_success;die();

    # recalculate costs after potential upgrades/hiring
    $current_workers = $Character->Data['worker_json']['workers'];

    $current_speed = isset($Character->Data['worker_json']['speed_upgrade_percent'])
    ? $Character->Data['worker_json']['speed_upgrade_percent'] : 0;

    $current_intelligence = isset($Character->Data['worker_json']['intelligence_upgrade_percent'])
    ? $Character->Data['worker_json']['intelligence_upgrade_percent'] : 0;

    $new_worker_cost = calculate_worker_cost(1000, 10, $current_workers);
    $next_intelligence_upgrade_cost = calculate_worker_cost(500, 1.4, $current_intelligence);
    $next_speed_upgrade_cost = calculate_worker_cost(500, 1.4, $current_speed);
