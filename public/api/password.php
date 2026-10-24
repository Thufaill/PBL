<?php
// public/api/password.php
// Ganti password akun yang sedang login (mahasiswa maupun admin).
//
//   POST -> { current_password, new_password }
//
// Selama ini tombol "Change Password" di profil hanya menampilkan toast
// "Password updated" tanpa menulis ke mana pun (laporannya: P2-8).
// Endpoint ini memverifikasi password lama dengan password_verify terhadap
// hash asli di users, lalu menyimpan hash baru.

require_once __DIR__ . '/../../includes/api/bootstrap.php';

const PASSWORD_MIN_LENGTH = 8;

if (api_method() !== 'POST') {
    api_error('Method tidak didukung.', 405);
}

api_write_guard();
$uid = api_user_id();
$pdo = api_pdo();

try {
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :u');
    $stmt->execute([':u' => $uid]);
    $hash = $stmt->fetchColumn();
    if (!$hash) {
        api_error('Akun ini tidak punya kredensial di database.', 404);
    }

    $body = api_body();
    $current = (string) ($body['current_password'] ?? '');
    $new     = (string) ($body['new_password'] ?? '');

    if (!password_verify($current, (string) $hash)) {
        // Pesan generik: tidak membedakan "email tidak ada" dan "password salah".
        api_error('Password lama tidak cocok.', 403);
    }
    if (strlen($new) < PASSWORD_MIN_LENGTH || strlen($new) > 72) {
        api_error('Password baru minimal 8 karakter.', 422);
    }
    if (password_verify($new, (string) $hash)) {
        api_error('Password baru harus berbeda dari yang lama.', 422);
    }

    $update = $pdo->prepare('UPDATE users SET password_hash = :h WHERE id = :u');
    $update->execute([':h' => password_hash($new, PASSWORD_DEFAULT), ':u' => $uid]);

    // Sidik jari sesi lama dibuang setelah kredensial berubah.
    session_regenerate_id(true);

    api_out(['ok' => true]);
} catch (PDOException $e) {
    error_log('api/password.php: ' . $e->getMessage());
    api_error('Gagal mengganti password.', 500);
}
