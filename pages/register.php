<?php
if(isset($_POST['username']) && isset($_POST['email']) && isset($_POST['password'])) {
    try {

        if($_POST['password'] != $_POST['confirm_password']) {
            die('Passwords do not match');
        }

        if (\preg_match('/[\x00-\x1f\x7f\/:@\\\\]/', $_POST['username']) === 0) {
            $userId = $auth->register($_POST['email'], $_POST['password'], $_POST['username'], function ($selector, $token) {
                echo 'Send this link: ' .
                'https://example.com/confirm?selector=' . \urlencode($selector) . '&token=' . \urlencode($token);
            });
            echo 'We have signed up a new user with the ID ' . $userId;
        }
        else {
            die('Invalid username');
        }
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
    catch (Exception $e) {
        if(DEBUG)
            die("Exception: " . $e->getMessage());
    }
}
?>

<form method="POST" action="/register">
  <fieldset>
    <label>
      Username
      <input name="username" placeholder="Username" autocomplete="username" />
    </label>
    <label>
      Email
      <input type="email" name="email" placeholder="Email" autocomplete="email" />
    </label>
    <label>
      Password
      <input type="password" name="password" placeholder="Password" autocomplete="new-password" />
    </label>
    <label>
      Confirm Password
      <input type="password" name="confirm_password" placeholder="Confirm Password" autocomplete="new-password" />
    </label>
  </fieldset>

  <input type="submit" value="Register" />
</form>
