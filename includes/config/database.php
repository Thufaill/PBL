<?php
// includes/config/database.php
// Konfigurasi koneksi PostgreSQL (Supabase / lokal) lewat environment variable.
//
// Variabel yang dibaca (lihat .env.example):
//   DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
//   DB_SSLMODE          disable | prefer (default) | require   -> Supabase: require
//   DB_EMULATE_PREPARES 1 jika memakai pooler transaction mode (port 6543)
//   APP_DEMO            1 untuk mengaktifkan akun/sesi demo (JANGAN di produksi)

// Loader .env sederhana (opsional, untuk lokal/shared hosting). Tidak menimpa env yang sudah ada.
(function () {
    $file = __DIR__ . '/../../.env';
    if (!is_readable($file)) {
        return;
    }
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");
        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
        }
    }
})();

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'postgres');
define('DB_USER', getenv('DB_USER') ?: 'postgres');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_SSLMODE', getenv('DB_SSLMODE') ?: 'prefer');

function get_db_connection() {
    static $pdo = null;
    static $failed = false;
    if ($pdo !== null) {
        return $pdo;
    }
    if ($failed) {
        return null;
    }

    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
        DB_HOST, DB_PORT, DB_NAME, DB_SSLMODE
    );
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 5,
            // Pooler transaction mode (port 6543) tidak mendukung prepared statement server-side.
            PDO::ATTR_EMULATE_PREPARES   => getenv('DB_EMULATE_PREPARES') === '1',
        ]);
        return $pdo;
    } catch (PDOException $e) {
        $failed = true;
        error_log('Database connection error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Nilai pengaturan platform dari tabel system_settings, dibaca dari konfigurasi
 * yang diketik "Konfigurasi Platform" di Admin Console.
 *
 * Dipakai oleh halaman PHP (bukan hanya API) supaya pengaturan admin benar-benar
 * mengubah perilaku aplikasi. Nilai di-cache sekali per request; ketika database
 * belum siap atau kunci belum pernah disimpan, $default yang dipakai sehingga
 * halaman publik tetap bisa tampil.
 */
function app_setting(string $key, $default = null) {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        $pdo = get_db_connection();
        if ($pdo) {
            try {
                foreach ($pdo->query('SELECT key, value FROM system_settings') as $row) {
                    $cache[$row['key']] = json_decode((string) $row['value'], true);
                }
            } catch (PDOException $e) {
                error_log('app_setting: ' . $e->getMessage());
            }
        }
    }
    return array_key_exists($key, $cache) ? $cache[$key] : $default;
}
