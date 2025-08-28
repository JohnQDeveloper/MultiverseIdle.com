<?php
if($_POST != [] && isset($fail_message) && $fail_message !== "") {
    echo '
    <dialog open id="messageModal">
        <article>
            <header>
            <button aria-label="Close" rel="prev" id="closeMessageModal"></button>
            <p>
                <strong>Registration Error!</strong>
            </p>
            </header>
            ' . $fail_message . '
        </article>
    </dialog>';
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
