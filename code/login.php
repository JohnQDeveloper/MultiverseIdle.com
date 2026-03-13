<?php

if (isset($_POST['email'])) {
    try {
        $auth->login($_POST['email'], $_POST['password']);

        // Clear guest session on login - guest progress is discarded when logging into an existing account
        unset($_SESSION['guest_mode'], $_SESSION['guest_character']);

        header('Location: /');
    }
    catch (\Delight\Auth\InvalidEmailException $e) {
        die('Wrong email address');
    }
    catch (\Delight\Auth\InvalidPasswordException $e) {
        die('Wrong password');
    }
    catch (\Delight\Auth\EmailNotVerifiedException $e) {
        die('Email not verified');
    }
    catch (\Delight\Auth\TooManyRequestsException $e) {
        die('Too many requests');
    }
}
