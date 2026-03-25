    <main class="container">
        <div class="wrapper">
            <article class="main">
                <?php if (isset($_SESSION['guest_mode']) && $_SESSION['guest_mode'] === true): ?>
                <div class="alert alert-warning">You are converting your guest account. Your current progress will be saved to your new account.</div>
                <?php endif; ?>
                <form method="POST" action="/register">
                    <h2>Register</h2>
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>

                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>

                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" minlength="8" required>
                    <small>Password must be at least 8 characters long</small>

                    <label for="referral_code">Referral Code (optional):</label>
                    <input type="text" id="referral_code" name="referral_code" maxlength="8"
                           value="<?php echo htmlspecialchars(strtoupper($_GET['ref'] ?? '')); ?>"
                           placeholder="e.g. ABCD1234" style="text-transform:uppercase;" />

                    <button type="submit">Register</button>
                </form>
            </article>
        </div>
    </main>
