<?php

if(isset($_POST['email'], $_POST['password'], $_POST['username'])) {
    try {
        $userId = $auth->register($_POST['email'], $_POST['password'], $_POST['username'], function ($selector, $token) {
            #echo 'Send ' . $selector . ' and ' . $token . ' to the user (e.g. via email)';
            #echo '  For emails, consider using the mail(...) function, Symfony Mailer, Swiftmailer, PHPMailer, etc.';
            #echo '  For SMS, consider using a third-party service and a compatible SDK';

            $resend = Resend::client(RESEND_API_KEY);

            $resend->emails->send([
            'from' => 'John Q Developer Automation <noreply@resend.johnqdeveloper.com>',
            'to' => [$_POST['email']],
            'subject' => 'E-Mail Verification for Multiverse Idle',
            'html' => '<p>Thank you for registering at Multiverse Idle!</p>
                       <p>Please verify your e-mail address by clicking the link below:</p>
                       <a href="' . BASE_URL . '/verify-email?selector=' . \urlencode($selector) .
                       '&token=' . \urlencode($token) . '">Verify E-Mail Address</a>
                       <p>If you did not register, please ignore this email.</p>'
            ]);

        });

        echo 'Please check your e-mail to complete the registration and click here to <a href="/">login</a>.';
    }
    catch (\Delight\Auth\InvalidEmailException $e) {
        die('Invalid email address');
    }
    catch (\Delight\Auth\InvalidPasswordException $e) {
        die('Invalid password');
    }
    catch (\Delight\Auth\UserAlreadyExistsException $e) {
        die('User already exists');
    }
    catch (\Delight\Auth\TooManyRequestsException $e) {
        die('Too many requests');
    }
}
else {

}
