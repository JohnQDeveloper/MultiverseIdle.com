    <main class="container">
        <div class="wrapper">
            <article class="main">
                <h2>Forgot Password</h2>

                <?php if ($alert_success !== ''): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($alert_success); ?></div>
                <?php endif; ?>
                <?php if ($alert_danger !== ''): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($alert_danger); ?></div>
                <?php endif; ?>

                <?php if ($alert_success === ''): ?>
                <p>Enter your account email address and we'll send you a link to reset your password.</p>
                <form method="POST" action="/forgot-password">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">

                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>

                    <button type="submit">Send Reset Link</button>
                    <p><small><a href="/login">Back to Login</a></small></p>
                </form>
                <?php else: ?>
                    <p><a href="/login">Back to Login</a></p>
                <?php endif; ?>
            </article>
        </div>
    </main>
