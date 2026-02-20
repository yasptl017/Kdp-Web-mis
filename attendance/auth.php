<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    $oneYearInSeconds = 60 * 60 * 24 * 365;
    ini_set('session.gc_maxlifetime', (string)$oneYearInSeconds);

    $cookieParams = session_get_cookie_params();
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    );

    session_set_cookie_params([
        'lifetime' => $oneYearInSeconds,
        'path' => $cookieParams['path'] ?: '/',
        'domain' => $cookieParams['domain'] ?? '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), session_id(), [
            'expires' => time() + $oneYearInSeconds,
            'path' => $cookieParams['path'] ?: '/',
            'domain' => $cookieParams['domain'] ?? '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

if (!function_exists('require_login')) {
    function require_login($redirect = 'index.php') {
        $name = trim((string)($_SESSION['Name'] ?? ''));
        if ($name === '') {
            header('Location: ' . $redirect);
            exit();
        }
    }
}
?>
