    <main class="container">
        <div class="wrapper">
            <article class="main">
                <form method="POST" action="/login">
                    <h2>Login</h2>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>

                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf-token']; ?>">

                    <button type="submit">Login</button>
                    <p><small><a href="/forgot-password">Forgot your password?</a></small></p>
                </form>
            </article>
        </div>
    </main>
