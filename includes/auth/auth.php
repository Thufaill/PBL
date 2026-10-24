<?php
// includes/auth/auth.php
// Autentikasi dan sesi untuk dua role aplikasi (mahasiswa, admin).
//
// Role di database (enum user_role): 'user' | 'admin'.
// Role di aplikasi (folder & sesi):  'mahasiswa' | 'admin'.
// 'user' (DB) <-> 'mahasiswa' (aplikasi) dipetakan oleh dua fungsi di bawah.

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

/** Mode demo: hanya aktif jika APP_DEMO=1. Default MATI. */
function demo_mode() {
    return getenv('APP_DEMO') === '1';
}

function db_role_to_app_role($role) {
    return $role === 'user' ? 'mahasiswa' : (string) $role;
}

function app_role_to_db_role($role) {
    return $role === 'mahasiswa' ? 'user' : (string) $role;
}

/** Role aplikasi yang masih dikenal. Dipakai guard dan login. */
function auth_role_is_valid($role): bool {
    return in_array($role, ['mahasiswa', 'admin'], true);
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function is_logged_in() {
    return !empty($_SESSION['user']);
}

function get_user_role() {
    return $_SESSION['user']['role'] ?? null;
}

function make_initials($name) {
    $parts = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
    if (!$parts) {
        return 'SB';
    }
    $first = mb_strtoupper(mb_substr($parts[0], 0, 1));
    $second = isset($parts[1])
        ? mb_strtoupper(mb_substr($parts[1], 0, 1))
        : mb_strtoupper(mb_substr($parts[0], 1, 1));
    return $first . $second;
}

function role_label($role) {
    switch ($role) {
        case 'admin': return 'Administrator';
        default:      return 'Student';
    }
}

/**
 * Guard halaman. WAJIB login; jika $required_role diberikan, role harus cocok.
 * - Belum login            -> redirect ke login.php
 * - Role tidak sesuai      -> redirect ke dashboard role miliknya sendiri
 * Parameter $redirect_on_fail dipertahankan demi kompatibilitas, tidak lagi dipakai.
 * Mode demo (APP_DEMO=1) tetap memakai perilaku lama untuk keperluan pengujian UI.
 */
function check_auth($required_role = null, $redirect_on_fail = true) {
    header('Cache-Control: no-store');

    if ($required_role !== null && !auth_role_is_valid($required_role)) {
        logout();
        header('Location: ' . base_url('login.php'));
        exit;
    }

    if (demo_mode()) {
        if (!is_logged_in() && $required_role) {
            login_demo_user($required_role);
        } elseif ($required_role && get_user_role() !== $required_role) {
            login_demo_user($required_role);
        }
        if (is_logged_in() && !auth_role_is_valid(get_user_role())) {
            logout();
            header('Location: ' . base_url('login.php'));
            exit;
        }
        return true;
    }

    if (!is_logged_in()) {
        header('Location: ' . base_url('login.php'));
        exit;
    }

    if (!auth_role_is_valid(get_user_role())) {
        logout();
        header('Location: ' . base_url('login.php'));
        exit;
    }

    if ($required_role && get_user_role() !== $required_role) {
        redirect_to_dashboard(get_user_role());
    }

    return true;
}

/** Sesi demo. Hanya boleh dipanggil saat demo_mode() aktif. */
function login_demo_user($role = 'mahasiswa') {
    if (!demo_mode()) {
        return null;
    }
    $demo_users = [
        'mahasiswa' => [
            'id' => 1, 'name' => 'Jhon Doe', 'email' => 'jhondoe@gmail.com',
            'role' => 'mahasiswa', 'role_label' => 'Student', 'initials' => 'JD',
        ],
        'admin' => [
            'id' => 3, 'name' => 'Aditya Pratama', 'email' => 'admin@skillbridge.id',
            'role' => 'admin', 'role_label' => 'Admin Utama · Platform Owner', 'initials' => 'AP',
        ],
    ];
    if (!isset($demo_users[$role])) {
        $role = 'mahasiswa';
    }
    $_SESSION['user'] = $demo_users[$role];
    return $_SESSION['user'];
}

/**
 * Login. Hasil: ['success' => bool, 'role' => string, 'error' => 'invalid'|'unavailable'].
 * Pesan error ke pengguna sengaja generik agar tidak membocorkan apakah email terdaftar.
 */
function attempt_login($email, $password) {
    $email = strtolower(trim((string) $email));
    $password = (string) $password;
    $pdo = get_db_connection();

    if ($pdo) {
        try {
            $stmt = $pdo->prepare(
                'SELECT id, name, email, password_hash, role FROM users WHERE email = :email LIMIT 1'
            );
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            // Selalu jalankan password_verify agar waktu respons tidak membedakan email ada/tidak.
            static $dummy_hash = null;
            $dummy_hash = $dummy_hash ?? password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
            $valid = password_verify($password, $user['password_hash'] ?? $dummy_hash);

            if ($user && $valid) {
                session_regenerate_id(true);
                $role = db_role_to_app_role($user['role']);
                if (!auth_role_is_valid($role)) {
                    return ['success' => false, 'error' => 'invalid'];
                }
                $_SESSION['user'] = [
                    'id'         => (int) $user['id'],
                    'name'       => $user['name'],
                    'email'      => $user['email'],
                    'role'       => $role,
                    'role_label' => role_label($role),
                    'initials'   => make_initials($user['name']),
                ];
                return ['success' => true, 'role' => $role];
            }
            return ['success' => false, 'error' => 'invalid'];
        } catch (Exception $e) {
            error_log('DB login error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'unavailable'];
        }
    }

    // Tanpa database: hanya akun demo yang eksplisit, dan hanya saat APP_DEMO=1.
    if (demo_mode()) {
        $demo_creds = [
            'admin@skillbridge.id'      => 'admin',
            'jhondoe@gmail.com'         => 'mahasiswa',
            'mahasiswa@skillbridge.id'  => 'mahasiswa',
        ];
        if (isset($demo_creds[$email]) && $password === 'password123') {
            session_regenerate_id(true);
            login_demo_user($demo_creds[$email]);
            return ['success' => true, 'role' => $demo_creds[$email]];
        }
        return ['success' => false, 'error' => 'invalid'];
    }

    return ['success' => false, 'error' => 'unavailable'];
}

function redirect_to_dashboard($role, $path_prefix = '') {
    if (!auth_role_is_valid($role)) {
        header('Location: ' . base_url('login.php'));
        exit;
    }
    $target = $role === 'admin' ? 'admin/index.php' : 'mahasiswa/dashboard.php';
    header('Location: ' . base_url($target));
    exit;
}

function logout() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}
