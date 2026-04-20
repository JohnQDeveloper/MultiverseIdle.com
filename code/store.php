<?php

declare(strict_types=1);

$alert_success = '';
$alert_danger = '';

$subscription_plans = [
    1  => ['months' => 1,  'credits' => 100,  'label' => t('store.plan.1_month')],
    3  => ['months' => 3,  'credits' => 250,  'label' => t('store.plan.3_months')],
    6  => ['months' => 6,  'credits' => 450,  'label' => t('store.plan.6_months')],
    12 => ['months' => 12, 'credits' => 800,  'label' => t('store.plan.12_months')],
];

$vip_plans = [
    1  => ['months' => 1,  'credits' => 100,  'label' => t('store.plan.1_month')],
    3  => ['months' => 3,  'credits' => 250,  'label' => t('store.plan.3_months')],
    6  => ['months' => 6,  'credits' => 450,  'label' => t('store.plan.6_months')],
    12 => ['months' => 12, 'credits' => 800,  'label' => t('store.plan.12_months')],
];

$is_seasonal = isset($_SESSION['active_season_id']);

$free_credits_enabled = in_array(ENVIRONMENT, ['Dev', 'QA'], true);

// Determine free credits cooldown state
$last_claim = $Character->Data['last_free_credits_claim'] ?? null;
$next_claim_time = (!empty($last_claim)) ? strtotime($last_claim) + 86400 : 0;
$can_claim = $free_credits_enabled && $next_claim_time <= time();

// Handle free credits claim
if ($free_credits_enabled && isset($_POST['claim_free_credits'])) {
    if ($next_claim_time > time()) {
        $alert_danger = t('store.alert.already_claimed', ['time' => date('Y-m-d H:i:s', $next_claim_time)]);
    } else {
        $Character->Data['credits'] = ((int)($Character->Data['credits'] ?? 0)) + 100;
        $Character->Data['last_free_credits_claim'] = date('Y-m-d H:i:s');
        $can_claim = false;
        $next_claim_time = time() + 86400;
        $alert_success = t('store.alert.claimed');
    }
}

// Handle VIP subscription purchase
if (isset($_POST['buy_vip'])) {
    if ($is_seasonal) {
        $alert_danger = t('store.alert.vip_seasonal');
    } else {
        $duration = (int)$_POST['vip_duration_months'];

        if (!isset($vip_plans[$duration])) {
            $alert_danger = t('store.alert.invalid_plan');
        } else {
            $plan = $vip_plans[$duration];
            $current_credits = (int)($Character->Data['credits'] ?? 0);

            if ($current_credits < $plan['credits']) {
                $alert_danger = t('store.alert.no_credits', ['need' => $plan['credits'], 'have' => $current_credits]);
            } else {
                $Character->Data['credits'] = $current_credits - $plan['credits'];

                $current_expiry = $Character->Data['vip_expires'] ?? null;
                if (!empty($current_expiry) && strtotime($current_expiry) > time()) {
                    $base = new DateTime($current_expiry);
                } else {
                    $base = new DateTime();
                }
                $base->modify('+' . $plan['months'] . ' months');
                $Character->Data['vip_expires'] = $base->format('Y-m-d H:i:s');

                $alert_success = t('store.alert.vip_subscribed', ['label' => $plan['label'], 'date' => date('Y-m-d', strtotime($Character->Data['vip_expires']))]);
            }
        }
    }
}

// Handle subscription purchase
if (isset($_POST['buy_subscription'])) {
    $duration = (int)$_POST['duration_months'];

    if (!isset($subscription_plans[$duration])) {
        $alert_danger = t('store.alert.invalid_plan');
    } else {
        $plan = $subscription_plans[$duration];
        $current_credits = (int)($Character->Data['credits'] ?? 0);

        if ($current_credits < $plan['credits']) {
            $alert_danger = t('store.alert.no_credits', ['need' => $plan['credits'], 'have' => $current_credits]);
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

            $alert_success = t('store.alert.subscribed', ['label' => $plan['label'], 'date' => date('Y-m-d', strtotime($Character->Data['subscription_expires']))]);
        }
    }
}
