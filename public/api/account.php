<?php
// public/api/account.php
// Identitas akun milik pemanggil sendiri, untuk semua role.
//
//   GET   -> { id, name, email, role, role_label, initials }
//   PATCH -> { name?, email? }
//
// Dipakai layar Pengaturan admin yang sebelumnya menulis ke
// localStorage 'sb.admin.profile' — perubahan di situ tidak pernah sampai
// ke server, jadi nama di header tetap nama lama setelah "disimpan".
//
// Password TIDAK dilayani di sini: gunakan api/password.php (butuh sandi
// saat ini dan melewati password_verify terhadap hash asli).

require_once __DIR__ . '/../../includes/api/bootstrap.php';

$method = api_method();
$pdo    = api_pdo();

function account_row(PDO $pdo, int $uid): array {
    $stmt = $pdo->prepare('SELECT id, name, email, role, status FROM users WHERE id = :u');
    $stmt->execute([':u' => $uid]);
    $row = $stmt->fetch();
    if (!$row) {
        api_error('Akun tidak ditemukan.', 404);
    }
    $row['id'] = (int) $row['id'];
    $row['role_label'] = role_label((string) $row['role']);
    $row['initials']   = make_initials((string) $row['name']);
    return $row;
}

try {
    switch ($method) {
        case 'GET': {
            api_guard();
            api_out(account_row($pdo, api_user_id()) + ['ok' => true]);
            break;
        }

        case 'PATCH': {
            api_write_guard();
            $uid  = api_user_id();
            $body = api_body();
            $name  = array_key_exists('name', $body) ? api_str($body, 'name', 100) : null;
            $email = array_key_exists('email', $body) ? api_str($body, 'email', 150) : null;

            if ($name === null && $email === null) {
                api_error('Tidak ada perubahan yang dikirim.', 422);
            }

            $set  = [];
            $args = [':u' => $uid];
            if ($name !== null) {
                $set['name'] = $name;
            }
            if ($email !== null) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    api_error('Format email tidak valid.', 422);
                }
                $taken = $pdo->prepare('SELECT id FROM users WHERE lower(email) = lower(:e) AND id <> :u');
                $taken->execute([':e' => $email, ':u' => $uid]);
                if ($taken->fetchColumn()) {
                    api_error('Email itu sudah dipakai akun lain.', 409);
                }
                $set['email'] = $email;
            }
            $cols = implode(', ', array_map(fn($k) => "{$k} = :{$k}", array_keys($set)));
            $pdo->prepare("UPDATE users SET {$cols} WHERE id = :u")->execute($set + $args);

            $row = account_row($pdo, $uid);
            // Header membaca dari sesi, jadi sesi disinkronkan dengansatu
            // permintaan tanpa memaksa user logout.
            $_SESSION['user']['name']     = $row['name'];
            $_SESSION['user']['email']    = $row['email'];
            $_SESSION['user']['initials'] = $row['initials'];
            $_SESSION['user']['role_label'] = $row['role_label'];
            api_out($row);
            break;
        }

        default:
            api_error('Method tidak didukung.', 405);
    }
} catch (PDOException $e) {
    error_log('api/account.php: ' . $e->getMessage());
    api_error('Gagal menyimpan data akun.', 500);
}
