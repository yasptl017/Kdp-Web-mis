<?php
require_once __DIR__ . '/auth.php';

session_unset();
session_destroy();

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'] ?? '/',
        $params['domain'] ?? '',
        !empty($params['secure']),
        !empty($params['httponly'])
    );
}

header("Location: index.php"); // Redirect to login page after logout
exit();
?>
