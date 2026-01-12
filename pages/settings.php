<?php require_once('../templates/game-header.php'); ?>
    <!-- Page Details -->
    <div class="wrapper">
    <article class="main">
        <h1>Settings</h1>

        <form>
        <h3>Change Email:</h3>
        <input type="text" name="email" value="example@gmail.com" />
        <input type="submit" value="Update Email" />
        </form>

        <form>
        <h3>Change Password:</h3>
        <h5>Current Password:</h5>
        <input type="password" name="current_password" value="" autocomplete="current-password" />
        <h5>New Password:</h5>
        <input type="password" name="new_password" value="" autocomplete="new-password" />
        <h5>Confirm New Password:</h5>
        <input type="password" name="confirm_new_password" value="" autocomplete="new-password" />
        <input type="submit" value="Update Password" />
        </form>

    </article>
    </div>
    </div>
</main>
