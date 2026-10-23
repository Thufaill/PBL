<?php
// includes/auth/csrf.php
// Generator & Validator CSRF Token

require_once __DIR__ . '/session.php';

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Alias lama agar kode yang masih memanggil get_csrf_token() tetap jalan.
function get_csrf_token() {
    return csrf_token();
}

function validate_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token) || !is_string($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Ambil token dari field form `csrf_token` atau header `X-CSRF-Token` (untuk fetch()).
function csrf_request_token() {
    return $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
}

// True jika request POST membawa token yang valid.
function csrf_verify() {
    return validate_csrf_token(csrf_request_token());
}
