<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$fail_message = "";
if(isset($_POST['username']) && isset($_POST['email']) && isset($_POST['password'])) {
    try {

        if($_POST['password'] != $_POST['confirm_password']) {
            $fail_message = 'Passwords do not match';
        }

        if (\preg_match('/[\x00-\x1f\x7f\/:\\\\]/', $_POST['username']) === 0) {
            $userId = $auth->register($_POST['email'], $_POST['password'], $_POST['username'], function ($selector, $token) {

                $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();                                            //Send using SMTP
                    $mail->Host       = 'smtp.resend.com';                     //Set the SMTP server to send through
                    $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
                    $mail->Username   = 'resend';                     //SMTP username
                    $mail->Password   = getenv("RESEND_API_KEY");                               //SMTP password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
                    $mail->Port       = 465;
                    $mail->setFrom('noreply@resend.johnqdeveloper.com', 'MultiverseIdle');
                    $mail->addAddress($_POST['email'], $_POST['username']);
                    $mail->isHTML(true);                                  //Set email format to HTML
                    $mail->Subject = 'E-Mail Verification for MultiverseIdle.com';
                    $mail->Body    = "Click the link below to verify your email address: <BR />".
                    URL.'verify-email?selector=' . \urlencode($selector) . '&token=' . \urlencode($token);

                    $mail->AltBody = 'Click the link below to verify your email address: \n'.
                     URL.'verify-email?selector=' . \urlencode($selector) . '&token=' . \urlencode($token);

                    $mail->send();
                    #echo 'Message has been sent';
                } catch (Exception $e) {
                    #echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }
            });
            die("Please check your email to verify your account.
            The source e-mail address is noreply@resend.johnqdeveloper.com. Be sure to check spam.");
        }
        else {
            $fail_message = 'Username exists or contains special characters';
        }
    }
    catch (\Delight\Auth\InvalidEmailException $e) {
        $fail_message = 'Invalid email address';
    }
    catch (\Delight\Auth\InvalidPasswordException $e) {
        $fail_message = 'Invalid password';
    }
    catch (\Delight\Auth\UserAlreadyExistsException $e) {
        $fail_message = 'User already exists';
    }
    catch (\Delight\Auth\TooManyRequestsException $e) {
        $fail_message = 'Too many requests';
    }
    catch (Exception $e) {
        if(DEBUG)
            die("Exception: " . $e->getMessage());
    }
}
else if($_POST != []) {
    $fail_message = 'Please fill in all fields';
}
?>
