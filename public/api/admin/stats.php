<?php
// public/api/admin/stats.php
// Angka ringkas dashboard admin — semuanya dihitung dari database.
//
// Menggantikan lima kartu yang nilainya ditulis keras di
// public/js/pages/admin.js:13-19 (1248 pengguna, 48 keterampilan, dst.)
// dan dua feed aktivitas palsu di baris 37-77.
//
//   GET -> { users, activeUsers, suspendedUsers, platformAdmins,
//           skills, careers, mappings, publishedContent, content,
//           recent[], accounts[] }
//
// recent[] digabung dari beberapa tabel master: tidak ada log admin khusus,
// jadi yang ditampilkan adalah baris terbaru yang benar-benar dibuat.
// accounts[] adalah baris akun admin terakhir (status + updated_at),
// dipakai panel "Aktivitas administrasi".

require_once __DIR__ . '/../../../includes/api/bootstrap.php';

if (api_method() !== 'GET') {
    api_error('Method tidak didukung.', 405);
}

api_guard('admin');
$pdo = api_pdo();

try {
    $scalar = function (string $sql) use ($pdo): int {
        return (int) $pdo->query($sql)->fetchColumn();
    };

    $stats = [
        'users'            => $scalar("SELECT COUNT(*) FROM users WHERE role = 'user'"),
        'activeUsers'      => $scalar("SELECT COUNT(*) FROM users WHERE role = 'user' AND status = 'active'"),
        'suspendedUsers'   => $scalar("SELECT COUNT(*) FROM users WHERE role = 'user' AND status <> 'active'"),
        'platformAdmins'   => $scalar("SELECT COUNT(*) FROM users WHERE role = 'admin'"),
        'skills'           => $scalar('SELECT COUNT(*) FROM skills'),
        'careers'          => $scalar('SELECT COUNT(*) FROM careers'),
        'mappings'         => $scalar('SELECT COUNT(*) FROM career_skill_requirements'),
        'publishedContent' => $scalar("SELECT COUNT(*) FROM content_items WHERE status = 'published'"),
        'content'          => $scalar('SELECT COUNT(*) FROM content_items'),
    ];

    $recent = $pdo->query(
        "(SELECT 'Pengguna' AS cat, name || ' dibuat' AS text, created_at FROM users ORDER BY created_at DESC LIMIT 6)
         UNION ALL
         (SELECT 'Keterampilan', name || ' ditambahkan ke katalog', created_at FROM skills ORDER BY created_at DESC LIMIT 6)
         UNION ALL
         (SELECT 'Target karier', name || ' dibuat', created_at FROM careers ORDER BY created_at DESC LIMIT 6)
         ORDER BY created_at DESC NULLS LAST LIMIT 8"
    )->fetchAll();

    $rows = [];
    foreach ($recent as $r) {
        $rows[] = [
            'cat'  => $r['cat'],
            'text' => $r['text'],
            'time' => $r['created_at'],
        ];
    }

    // Panel "Aktivitas administrasi": aplikasi belum mencatat log tindakan
    // admin, jadi yang bisa ditampilkan apa adanya adalah baris akun
    // admin terakhir beserta status dan waktu perubahannya.
    $accounts = $pdo->query(
        "SELECT name, status, updated_at
         FROM users
         WHERE role = 'admin'
         ORDER BY updated_at DESC
         LIMIT 5"
    )->fetchAll();

    $accountRows = [];
    foreach ($accounts as $a) {
        $accountRows[] = [
            'actor'  => $a['name'] . ' · Admin',
            'detail' => $a['status'] === 'active' ? 'Akun aktif' : 'Status akun: ' . $a['status'],
            'time'   => $a['updated_at'],
        ];
    }

    api_out(['ok' => true] + $stats + ['recent' => $rows, 'accounts' => $accountRows]);
} catch (PDOException $e) {
    error_log('api/admin/stats.php: ' . $e->getMessage());
    api_error('Gagal memuat statistik admin.', 500);
}
