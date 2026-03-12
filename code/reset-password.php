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
        $alert_danger = 'This reset link is invalid.';
    } catch (\Delight\Auth\TokenExpiredException $e) {
        $alert_danger = 'This reset link has expired. Please request a new one.';
    } catch (\Delight\Auth\ResetDisabledException $e) {
        $alert_danger = 'Password reset is not available for this account.';
    } catch (\Delight\Auth\TooManyRequestsException $e) {
        $alert_danger = 'Too many requests. Please wait before trying again.';
    }
} else {
    $alert_danger = 'Invalid reset link.';
}

if (isset($_POST['new_password'], $_POST['confirm_password']) && $valid_token) {
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $alert_danger = 'Passwords do not match.';
    } elseif (strlen($new_password) < 8) {
        $alert_danger = 'Password must be at least 8 characters long.';
    } else {
        try {
            $auth->resetPassword($selector, $token, $new_password);
            $alert_success = 'Your password has been reset. You can now log in with your new password.';
            $valid_token = false; // Hide the form after success
        } catch (\Delight\Auth\InvalidSelectorTokenPairException $e) {
            $alert_danger = 'This reset link is invalid.';
        } catch (\Delight\Auth\TokenExpiredException $e) {
            $alert_danger = 'This reset link has expired. Please request a new one.';
        } catch (\Delight\Auth\ResetDisabledException $e) {
            $alert_danger = 'Password reset is not available for this account.';
        } catch (\Delight\Auth\InvalidPasswordException $e) {
            $alert_danger = 'Invalid password. Please choose a stronger password.';
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            $alert_danger = 'Too many requests. Please wait before trying again.';
        }
    }
}
