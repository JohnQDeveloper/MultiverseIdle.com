<?php

declare(strict_types=1);

$alert_success = '';
$alert_danger = '';

$selector = isset($_GET['selector']) ? trim($_GET['selector']) : '';
$token    = isset($_GET['token'])    ? trim($_GET['token'])    : '';

// Validate that we have a selector/token before showing the form
$valid_token = false;
if ($selector !== '' && $token !== '') {
    try {
        $auth->canResetPasswordOrThrow($selector, $token);
        $valid_token = true;
    } catch (\Delight\Auth\InvalidSelectorTokenPairException $e) {
        $alert_danger = t('auth.reset.alert.invalid_link');
    } catch (\Delight\Auth\TokenExpiredException $e) {
        $alert_danger = t('auth.reset.alert.expired');
    } catch (\Delight\Auth\ResetDisabledException $e) {
        $alert_danger = t('auth.reset.alert.reset_disabled');
    } catch (\Delight\Auth\TooManyRequestsException $e) {
        $alert_danger = t('auth.reset.alert.too_many');
    }
} else {
    $alert_danger = t('auth.reset.alert.invalid_request');
}

if (isset($_POST['new_password'], $_POST['confirm_password']) && $valid_token) {
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $alert_danger = t('auth.reset.alert.mismatch');
    } elseif (strlen($new_password) < 8) {
        $alert_danger = t('auth.reset.alert.short_password');
    } else {
        try {
            $auth->resetPassword($selector, $token, $new_password);
            $alert_success = t('auth.reset.alert.success');
            $valid_token = false; // Hide the form after success
        } catch (\Delight\Auth\InvalidSelectorTokenPairException $e) {
            $alert_danger = t('auth.reset.alert.invalid_link');
        } catch (\Delight\Auth\TokenExpiredException $e) {
            $alert_danger = t('auth.reset.alert.expired');
        } catch (\Delight\Auth\ResetDisabledException $e) {
            $alert_danger = t('auth.reset.alert.reset_disabled');
        } catch (\Delight\Auth\InvalidPasswordException $e) {
            $alert_danger = t('auth.reset.alert.invalid_password');
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            $alert_danger = t('auth.reset.alert.too_many');
        }
    }
}
