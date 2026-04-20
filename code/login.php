<?php

declare(strict_types=1);

$alert_danger = $alert_danger ?? '';

if (isset($_POST['email'])) {
    try {
        $auth->login(
            trim((string)$_POST['email']),
            (string)($_POST['password'] ?? '')
        );

        $currentAuthStatus = (int)($auth->getStatus() ?? \Delight\Auth\Status::NORMAL);
        if (isBlockedAuthStatus($currentAuthStatus)) {
            $auth->logOut();
            $alert_danger = getBlockedAuthStatusMessage($currentAuthStatus);
        } else {
            // Clear guest session on login - guest progress is discarded when logging into an existing account
            unset($_SESSION['guest_mode'], $_SESSION['guest_character']);

            header('Location: /');
            exit;
        }
    } catch (\Delight\Auth\InvalidEmailException $e) {
        $alert_danger = t('auth.login.alert.invalid_email');
    } catch (\Delight\Auth\InvalidPasswordException $e) {
        $alert_danger = t('auth.login.alert.invalid_password');
    } catch (\Delight\Auth\EmailNotVerifiedException $e) {
        $alert_danger = t('auth.login.alert.not_verified');
    } catch (\Delight\Auth\TooManyRequestsException $e) {
        $alert_danger = t('auth.login.alert.too_many');
    }
}
