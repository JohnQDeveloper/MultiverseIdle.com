    <main class="container">
        <div class="wrapper">
            <article class="main">
                <form method="POST" action="/register">
                    <h2>Register</h2>
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>

                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>

                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" minlength="8" required>
                    <small>Password must be at least 8 characters long</small>

                    <button type="submit">Register</button>
                </form>
            </article>
        </div>
    </main>
