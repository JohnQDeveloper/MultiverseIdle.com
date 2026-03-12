<?php

declare(strict_types=1);

$alert_success = '';
$alert_danger = '';

if (isset($_POST['email'])) {
    $email = trim($_POST['email']);

    try {
        $auth->forgotPassword($email, function (string $selector, string $token) use ($email): void {
            $resend = Resend::client(RESEND_API_KEY);

            $resend->emails->send([
                'from' => 'John Q Developer Automation <noreply@resend.johnqdeveloper.com>',
                'to'   => [$email],
                'subject' => 'Reset Your Password - Multiverse Idle',
                'html' => '<p>You requested a password reset for your Multiverse Idle account.</p>
                           <p>Click the link below to choose a new password. This link expires in 6 hours.</p>
                           <p><a href="' . BASE_URL . '/reset-password?selector=' . \urlencode($selector) .
                           '&token=' . \urlencode($token) . '">Reset Password</a></p>
                           <p>If you did not request this, please ignore this email.</p>',
            ]);
        });

        // Generic message to prevent user enumeration
        $alert_success = 'If an account with that email exists, a password reset link has been sent.';
    } catch (\Delight\Auth\InvalidEmailException $e) {
        $alert_danger = 'Please enter a valid email address.';
    } catch (\Delight\Auth\EmailNotVerifiedException $e) {
        $alert_danger = 'This account has not been verified yet. Please check your inbox for the verification email.';
    } catch (\Delight\Auth\ResetDisabledException $e) {
        $alert_danger = 'Password reset is not available for this account.';
    } catch (\Delight\Auth\TooManyRequestsException $e) {
        $alert_danger = 'Too many requests. Please wait before trying again.';
    }
}
