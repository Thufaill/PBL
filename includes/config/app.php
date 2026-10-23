<?php
// includes/config/app.php
// Konfigurasi URL, Base Path, dan Helper Sanitasi Output

if (!defined('BASE_URL')) {
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    $script_dir = str_replace('\\', '/', dirname($script_name));
    // Normalisasi: jika dipanggil dari subfolder role (/mahasiswa, /hr, /admin), hapus akhiran tersebut
    $clean_base = preg_replace('#/(mahasiswa|hr|admin)$#', '', $script_dir);
    $clean_base = rtrim($clean_base, '/');
    define('BASE_URL', $clean_base);
}

function base_url($path = '') {
    $path = ltrim($path, '/');
    $base = BASE_URL;
    if ($base === '' || $base === '/') {
        return '/' . $path;
    }
    return $base . '/' . $path;
}

function asset($path = '') {
    return base_url($path);
}

function e($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}
