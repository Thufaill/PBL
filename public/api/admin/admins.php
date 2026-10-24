<?php
// public/api/admin/admins.php
// Daftar administrator SkillBridge (akun role 'admin').
//
//   GET    -> [{ id, name, email, role:'skillbridge_admin', status, created_at }]
//   PATCH  -> { id, status }
//   DELETE -> { id }

require_once __DIR__ . '/../../../includes/api/bootstrap.php';

const ADMIN_STATUSES = ['active', 'suspended', 'inactive'];

$method = api_method();
$pdo    = api_pdo();

try {
    switch ($method) {
        case 'GET': {
            api_guard('admin');
            $rows = $pdo->query(
                "SELECT id, name, email, status, created_at
                 FROM users
                 WHERE role = 'admin'
                 ORDER BY id"
            )->fetchAll();

            foreach ($rows as &$r) {
                $r['id']   = (int) $r['id'];
                $r['role'] = 'skillbridge_admin';
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
            $stmt = $pdo->prepare("UPDATE users SET status = :s WHERE id = :id AND role = 'admin'");
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
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'admin'");
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
