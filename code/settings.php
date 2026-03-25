<?php

declare(strict_types=1);

$alert_success = '';
$alert_danger = '';

// Referral data (registered users only)
$referral_code  = '';
$referral_count = 0;
if (!$Character->IsGuest() && isset($_SESSION['auth_user_id']) && $_SESSION['auth_user_id'] > 0) {
    $referral_code  = get_or_create_referral_code((int)$_SESSION['auth_user_id']);
    $referral_count = count_referrals((int)$_SESSION['auth_user_id']);
}

// Handle email change
if (isset($_POST['change_email']) && isset($_POST['email'], $_POST['email_password'])) {
    $new_email = trim($_POST['email']);
    $password = $_POST['email_password'];

    // Validate email
    if (!Controls::validateEmail($new_email)) {
        $alert_danger = 'Invalid email address';
    } else {
        try {
            // Reconfirm password before changing email
            if ($auth->reconfirmPassword($password)) {
                $auth->changeEmail($new_email, function ($selector, $token) use ($new_email) {
                    // Send verification email to the new address
                    $resend = Resend::client(RESEND_API_KEY);

                    $resend->emails->send([
                        'from' => 'John Q Developer Automation <noreply@resend.johnqdeveloper.com>',
                        'to' => [$new_email],
                        'subject' => 'Verify Your New Email Address - Multiverse Idle',
                        'html' => '<p>You have requested to change your email address.</p>
                                   <p>Please verify your new email address by clicking the link below:</p>
                                   <a href="' . BASE_URL . '/verify-email?selector=' . \urlencode($selector) .
                                   '&token=' . \urlencode($token) . '">Verify New Email Address</a>
                                   <p>If you did not request this change, please ignore this email.</p>'
                    ]);
                });
                $alert_success = 'Verification email sent to your new address. Please check your email to complete the change.';
            }
        } catch (\Delight\Auth\InvalidPasswordException $e) {
            $alert_danger = 'Incorrect password';
        } catch (\Delight\Auth\InvalidEmailException $e) {
            $alert_danger = 'Invalid email address';
        } catch (\Delight\Auth\UserAlreadyExistsException $e) {
            $alert_danger = 'This email is already in use';
        } catch (\Delight\Auth\EmailNotVerifiedException $e) {
            $alert_danger = 'Your current email is not verified';
        } catch (\Delight\Auth\NotLoggedInException $e) {
            $alert_danger = 'Not logged in';
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            $alert_danger = 'Too many requests. Please try again later';
        }
    }
}

// Handle password change
if (isset($_POST['change_password']) && isset($_POST['current_password'], $_POST['new_password'], $_POST['confirm_new_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    // Validate passwords match
    if ($new_password !== $confirm_new_password) {
        $alert_danger = 'New passwords do not match';
    } elseif (strlen($new_password) < 8) {
        $alert_danger = 'Password must be at least 8 characters long';
    } else {
        try {
            $auth->changePassword($current_password, $new_password);
            $alert_success = 'Password updated successfully';
        } catch (\Delight\Auth\NotLoggedInException $e) {
            $alert_danger = 'Not logged in';
        } catch (\Delight\Auth\InvalidPasswordException $e) {
            $alert_danger = 'Current password is incorrect';
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            $alert_danger = 'Too many requests. Please try again later';
        }
    }
}
