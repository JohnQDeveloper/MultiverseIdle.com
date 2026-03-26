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
        $alert_success = t('auth.forgot.alert.sent');
    } catch (\Delight\Auth\InvalidEmailException $e) {
        $alert_danger = t('auth.forgot.alert.invalid_email');
    } catch (\Delight\Auth\EmailNotVerifiedException $e) {
        $alert_danger = t('auth.forgot.alert.not_verified');
    } catch (\Delight\Auth\ResetDisabledException $e) {
        $alert_danger = t('auth.forgot.alert.reset_disabled');
    } catch (\Delight\Auth\TooManyRequestsException $e) {
        $alert_danger = t('auth.forgot.alert.too_many');
    }
}
