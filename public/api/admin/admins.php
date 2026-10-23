<?php
// public/api/admin/admins.php
// Daftar administrator: Admin Job (akun role 'hr' yang ditautkan ke
// perusahaan) dan Admin SkillBridge (akun role 'admin').
//
//   GET    -> [{ id, name, email, role:'job_admin'|'skillbridge_admin',
//                company, active_vacancies, status, created_at }]
//   PATCH  -> { id, status }
//   DELETE -> { id }
//
// Pengganti data/admin-jobs.json. Label role dijaga sama dengan
// ROLE_LABEL di public/js/pages/admin-admins.js:6-10 supaya filter dan
// badge di tabel tidak perlu diubah.

require_once __DIR__ . '/../../../includes/api/bootstrap.php';

const ADMIN_STATUSES = ['active', 'suspended', 'inactive'];

$method = api_method();
$pdo    = api_pdo();

try {
    switch ($method) {
        case 'GET': {
            api_guard('admin');
            $rows = $pdo->query(
                "SELECT u.id, u.name, u.email, u.role AS db_role, u.status, u.created_at,
                        c.name AS company,
                        (SELECT COUNT(*) FROM vacancies v
                          WHERE v.company_id = c.id AND v.status = 'open') AS active_vacancies
                 FROM users u
                 LEFT JOIN company_recruiters cr ON cr.user_id = u.id
                 LEFT JOIN companies c ON c.id = cr.company_id
                 WHERE u.role IN ('admin', 'hr')
                 ORDER BY u.id"
            )->fetchAll();

            foreach ($rows as &$r) {
                $r['id']           = (int) $r['id'];
                // Pemetaan nama role ke label yang dipakai UI.
                $r['role']         = $r['db_role'] === 'hr' ? 'job_admin' : 'skillbridge_admin';
                $r['active_vacancies'] = (int) $r['active_vacancies'];
                unset($r['db_role']);
            }
            unset($r);

            api_out(['ok' => true, 'admins' => $rows]);
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
            if (!in_array($status, ADMIN_STATUSES, true)) {
                api_error('Status tidak dikenal.', 422, ['allowed' => ADMIN_STATUSES]);
            }
            if ($id === api_user_id()) {
                api_error('Tidak bisa mengubah status akun sendiri.', 422);
            }
            $stmt = $pdo->prepare("UPDATE users SET status = :s WHERE id = :id AND role IN ('admin','hr')");
            $stmt->execute([':s' => $status, ':id' => $id]);
            if ($stmt->rowCount() === 0) {
                api_error('Akun administrator itu tidak ada.', 404);
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
            $pdo->beginTransaction();
            // Akun HR dihapus bersama tautan perusahaannya, tapi lowongan
            // yang dibuatnya dipertahankan (created_by ON DELETE SET NULL).
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role IN ('admin','hr')");
            $stmt->execute([':id' => $id]);
            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                api_error('Akun administrator itu tidak ada.', 404);
            }
            $pdo->commit();
            api_out(['ok' => true, 'id' => $id]);
            break;
        }

        default:
            api_error('Method tidak didukung.', 405);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('api/admin/admins.php: ' . $e->getMessage());
    api_error('Gagal memuat data administrator.', 500);
}
