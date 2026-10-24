<?php
// public/api/public-photo.php
// Foto profil publik berbasis token. Tidak perlu login.
//
//   GET ?token=<uuid> -> bytes gambar
//
// Hanya menyalurkan foto bila token ada di v_public_profiles dan file
// benar-benar ada di storage/. Path file diturunkan dari user id hasil
// validasi token, tidak pernah dari input klien.

require_once __DIR__ . '/../../includes/api/bootstrap.php';

if (api_method() !== 'GET') {
    api_error('Method tidak didukung.', 405);
}

$token = trim((string) ($_GET['token'] ?? ''));
if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $token)) {
    api_error('Foto tidak ditemukan.', 404);
}

$pdo = api_pdo();

try {
    $stmt = $pdo->prepare('SELECT id FROM v_public_profiles WHERE public_token = :t LIMIT 1');
    $stmt->execute([':t' => $token]);
    $uid = $stmt->fetchColumn();
    if ($uid === false) {
        api_error('Foto tidak ditemukan.', 404);
    }

    $path = api_photo_path((int) $uid);
    if ($path === '') {
        api_error('Foto tidak ditemukan.', 404);
    }

    $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($path);
    if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) {
        api_error('Foto tidak ditemukan.', 404);
    }

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
} catch (PDOException $e) {
    error_log('api/public-photo.php: ' . $e->getMessage());
    api_error('Gagal memuat foto.', 500);
}
