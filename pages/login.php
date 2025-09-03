<?php
if(isset($_POST['email']) && isset($_POST['password'])) {

    if(SecurityTools::VerifyCSRFToken('login')) {
      #die("Passed");
      // DO nothing
    }
    else {
      die("CSRF Token Verification Failed");
    }

    try {
        $auth->login($_POST['email'], $_POST['password'], (60 * 60 * 24 * 30)); # 30 day cookie

        #print_r($_SESSION);
        header('Location: '.URL."");
        die();
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
    <?php SecurityTools::AddFormCSRFToken('login'); ?>
  </fieldset>

  <input type="submit" value="Login" />
</form>
