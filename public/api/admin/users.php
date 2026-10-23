<?php
// public/api/admin/users.php
// Daftar akun mahasiswa untuk konsol admin.
//
//   GET    -> [{ id, name, email, role:'student', status, career_target,
//                skills_count, evidence_count, created_at }]
//   PATCH  -> { id, status }  dengan status active | suspended | inactive
//   DELETE -> { id }
//
// Ini pengganti data/admin-users.json. File itu berisi nama dan email dan
// bisa diambil tanpa login dari docroot (temuan P0-5); lewat endpoint ini
// datanya hanya keluar untuk sesi admin, dan tulisannya benar-benar mengubah
// baris users — bukan localStorage admin seperti sebelumnya.

require_once __DIR__ . '/../../../includes/api/bootstrap.php';

const USER_STATUSES = ['active', 'suspended', 'inactive'];

$method = api_method();
$pdo    = api_pdo();

try {
    switch ($method) {
        case 'GET': {
            api_guard('admin');
            $rows = $pdo->query(
                "SELECT u.id, u.name, u.email, u.status, u.created_at,
                        c.name AS career_target,
                        (SELECT COUNT(*) FROM user_skills us WHERE us.user_id = u.id) AS skills_count,
                        (SELECT COUNT(*) FROM evidence ev WHERE ev.user_id = u.id)    AS evidence_count
                 FROM users u
                 LEFT JOIN student_profiles sp ON sp.user_id = u.id
                 LEFT JOIN careers c ON c.id = sp.target_career_id
                 WHERE u.role = 'user'
                 ORDER BY u.id"
            )->fetchAll();

            foreach ($rows as &$r) {
                $r['id']             = (int) $r['id'];
                $r['role']           = 'student';
                $r['skills_count']   = (int) $r['skills_count'];
                $r['evidence_count'] = (int) $r['evidence_count'];
            }
            unset($r);

            api_out(['ok' => true, 'users' => $rows]);
            break;
        }

        case 'PATCH': {
            api_write_guard('admin');
            $body   = api_body();
            $id     = api_int($body, 'id');
            $status = api_str($body, 'status', 20);
            if ($id === null || $status === null) {
                api_error('Butuh id dan status.', 422);
            }
            if (!in_array($status, USER_STATUSES, true)) {
                api_error('Status tidak dikenal.', 422, ['allowed' => USER_STATUSES]);
            }
            if ($id === api_user_id()) {
                api_error('Tidak bisa mengubah status akun sendiri.', 422);
            }

            $stmt = $pdo->prepare("UPDATE users SET status = :s WHERE id = :id AND role = 'user'");
            $stmt->execute([':s' => $status, ':id' => $id]);
            if ($stmt->rowCount() === 0) {
                api_error('Akun mahasiswa itu tidak ada.', 404);
            }
            api_out(['ok' => true, 'id' => $id, 'status' => $status]);
            break;
        }

        case 'DELETE': {
            api_write_guard('admin');
            $body = api_body();
            $id   = api_int($body, 'id') ?? api_int($_GET, 'id');
            if ($id === null) {
                api_error('Butuh id.', 422);
            }
            if ($id === api_user_id()) {
                api_error('Tidak bisa menghapus akun sendiri.', 422);
            }
            // ON DELETE CASCADE merapikan profil, skill, dan bukti milik user.
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'user'");
            $stmt->execute([':id' => $id]);
            if ($stmt->rowCount() === 0) {
                api_error('Akun mahasiswa itu tidak ada.', 404);
            }
            api_out(['ok' => true, 'id' => $id]);
            break;
        }

        default:
            api_error('Method tidak didukung.', 405);
    }
} catch (PDOException $e) {
    error_log('api/admin/users.php: ' . $e->getMessage());
    api_error('Gagal memuat data pengguna.', 500);
}
