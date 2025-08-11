<?php
if(isset($_POST['email']) && isset($_POST['password'])) {
    try {
        $auth->login($_POST['email'], $_POST['password']);

        echo 'User is logged in';
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
    catch (Exception $e) {
        if(DEBUG)
            die("Exception: " . $e->getMessage());
    }
}
?>

<form method="POST" action="/login">
  <fieldset>
    <label>
      Email
      <input type="email" name="email" placeholder="Email" autocomplete="email" />
    </label>
    <label>
      Password
      <input type="password" name="password" placeholder="Password" autocomplete="new-password" />
    </label>
  </fieldset>

  <input type="submit" value="Login" />
</form>
