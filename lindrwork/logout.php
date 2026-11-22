<?php
<<<<<<< HEAD
require __DIR__ . '/config/database.php';
=======
require __DIR__.'/config/database.php';
>>>>>>> 56925097664ae51b0b11dd31b29189b4488ae373
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
    }
    session_destroy();
}
header('Location: index.php');
exit;
