<?php
// includes/auth/middleware.php
// Middleware pelindung rute & otorisasi role (mahasiswa, hr, admin)

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/auth.php';

/** Wajib login (role apa pun). */
function require_login() {
    check_auth(null);
    return current_user();
}

/** Wajib login dengan role tertentu; role lain diarahkan ke dashboard miliknya. */
function require_role($role) {
    check_auth($role);
    return current_user();
}
