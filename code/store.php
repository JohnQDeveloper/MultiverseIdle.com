<?php

declare(strict_types=1);

$alert_success = '';
$alert_danger = '';

$subscription_plans = [
    1  => ['months' => 1,  'credits' => 100,  'label' => '1 Month'],
    3  => ['months' => 3,  'credits' => 250,  'label' => '3 Months'],
    6  => ['months' => 6,  'credits' => 450,  'label' => '6 Months'],
    12 => ['months' => 12, 'credits' => 800,  'label' => '12 Months'],
];

// Determine free credits cooldown state
$last_claim = $Character->Data['last_free_credits_claim'] ?? null;
$next_claim_time = (!empty($last_claim)) ? strtotime($last_claim) + 86400 : 0;
$can_claim = $next_claim_time <= time();

// Handle free credits claim
if (isset($_POST['claim_free_credits'])) {
    if (!$can_claim) {
        $alert_danger = 'You already claimed your free credits today. Next claim available at ' . date('Y-m-d H:i:s', $next_claim_time) . '.';
    } else {
        $Character->Data['credits'] = ((int)($Character->Data['credits'] ?? 0)) + 100;
        $Character->Data['last_free_credits_claim'] = date('Y-m-d H:i:s');
        $can_claim = false;
        $next_claim_time = time() + 86400;
        $alert_success = 'You claimed 100 free credits!';
    }
}

// Handle subscription purchase
if (isset($_POST['buy_subscription'])) {
    $duration = (int)$_POST['duration_months'];

    if (!isset($subscription_plans[$duration])) {
        $alert_danger = 'Invalid subscription option.';
    } else {
        $plan = $subscription_plans[$duration];
        $current_credits = (int)($Character->Data['credits'] ?? 0);

        if ($current_credits < $plan['credits']) {
            $alert_danger = 'Not enough credits. You need ' . $plan['credits'] . ' credits but only have ' . $current_credits . '.';
        } else {
            $Character->Data['credits'] = $current_credits - $plan['credits'];

            // Extend from current expiry if active, otherwise from now
            $current_expiry = $Character->Data['subscription_expires'] ?? null;
            if (!empty($current_expiry) && strtotime($current_expiry) > time()) {
                $base = new DateTime($current_expiry);
            } else {
                $base = new DateTime();
            }
            $base->modify('+' . $plan['months'] . ' months');
            $Character->Data['subscription_expires'] = $base->format('Y-m-d H:i:s');

            $alert_success = 'QoL subscription activated for ' . $plan['label'] . '! Expires: ' . $Character->Data['subscription_expires'];
        }
    }
}
