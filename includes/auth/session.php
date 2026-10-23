<?php
// includes/auth/session.php
// Manajemen Session PHP Native (dengan hardening cookie)

if (session_status() === PHP_SESSION_NONE) {
    // Di balik reverse proxy (Render, dsb.) HTTPS diteruskan lewat X-Forwarded-Proto.
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $is_https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function session_get($key, $default = null) {
    return $_SESSION[$key] ?? $default;
}

function session_set($key, $value) {
    $_SESSION[$key] = $value;
}

function session_remove($key) {
    unset($_SESSION[$key]);
}
