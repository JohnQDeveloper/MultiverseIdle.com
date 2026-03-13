<?php

declare(strict_types=1);

// Only allow guest mode for unauthenticated users
if (!isset($_SESSION['auth_logged_in']) || $_SESSION['auth_logged_in'] !== 1) {
    $_SESSION['guest_mode'] = true;
}

header('Location: /');
exit;
