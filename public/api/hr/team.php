<?php
// public/api/hr/team.php
// Anggota tim recruiter satu perusahaan (pengganti data/recruiter-team.json).
//
//   GET    -> [{ id, name, email, role, role_label, status, created_at }]
//   POST   -> { name, email, role?, status? }
//   PATCH  -> { id, status? | role? | name? | email? }
//   DELETE -> { id }
//
// Batas keamanan:
//   - company_id dari sesi, jadi recruiter tidak bisa menambah anggota ke
//     perusahaan tetangga.
//   - status 'pending' di UI disimpan sebagai 'invited' di DB; pemetaan
//     dilakukan di sini agar CHECK chk_recruiter_status tetap satu sumber.
//   - akun login milik sendiri (user_id = sesi) tidak bisa dihapus lewat
//     layar tim; itu tugas admin di layar Administrator.

require_once __DIR__ . '/../../../includes/api/bootstrap.php';

const TEAM_ROLES = [
    'recruiter'      => 'Recruiter',
    'lead'           => 'Lead Recruiter',
    'hiring_manager' => 'Hiring Manager',
];

const UI_TO_DB_STATUS = ['active' => 'active', 'inactive' => 'inactive', 'pending' => 'invited'];
const DB_TO_UI_STATUS = ['active' => 'active', 'inactive' => 'inactive', 'invited' => 'pending'];

$method = api_method();
$pdo    = api_pdo();

function team_status_to_ui(?string $status): string {
    return DB_TO_UI_STATUS[$status ?? ''] ?? 'inactive';
}

try {
    switch ($method) {
        case 'GET': {
            api_guard('hr');
            $link = api_hr_company(api_user_id());
            $rows = $pdo->prepare(
                'SELECT id, name, email, role, role_label, status, created_at, user_id
                 FROM company_recruiters WHERE company_id = :c ORDER BY id'
            );
            $rows->execute([':c' => (int) $link['company_id']]);
            $team = [];
            foreach ($rows as $r) {
                $team[] = [
                    'id'         => (int) $r['id'],
                    'name'       => $r['name'],
                    'email'      => $r['email'],
                    'role'       => $r['role'],
                    'role_label' => $r['role_label'] ?? (TEAM_ROLES[$r['role']] ?? 'Recruiter'),
                    'status'     => team_status_to_ui($r['status']),
                    'created_at' => $r['created_at'],
                    'has_account'=> $r['user_id'] !== null,
                ];
            }
            api_out(['ok' => true, 'team' => $team]);
            break;
        }

        case 'POST': {
            api_write_guard('hr');
            $link = api_hr_company(api_user_id());
            $cid  = (int) $link['company_id'];
            $body = api_body();

            $name  = api_str($body, 'name', 100);
            $email = api_str($body, 'email', 150);
            if ($name === null || $email === null) {
                api_error('Nama dan email wajib diisi.', 422);
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                api_error('Format email tidak valid.', 422);
            }
            $role = api_str($body, 'role', 30) ?? 'recruiter';
            if (!array_key_exists($role, TEAM_ROLES)) {
                api_error('Role tidak dikenal.', 422, ['allowed' => array_keys(TEAM_ROLES)]);
            }
            $uiStatus = api_str($body, 'status', 20) ?? 'pending';
            if (!array_key_exists($uiStatus, UI_TO_DB_STATUS)) {
                api_error('Status tidak dikenal.', 422, ['allowed' => array_keys(UI_TO_DB_STATUS)]);
            }

            $dup = $pdo->prepare('SELECT id FROM company_recruiters WHERE company_id = :c AND lower(email) = lower(:e)');
            $dup->execute([':c' => $cid, ':e' => $email]);
            if ($dup->fetchColumn()) {
                api_error('Email itu sudah menjadi anggota perusahaan ini.', 422);
            }

            // Kalau sudah ada akun login untuk email itu, tautkan — jangan
            // buat baris catatan duplikat yang membuat UNIQUE(user_id) jebol.
            $acct = $pdo->prepare("SELECT id FROM users WHERE lower(email) = lower(:e) AND role = 'hr'");
            $acct->execute([':e' => $email]);
            $foundUser = $acct->fetchColumn();
            $userId = $foundUser === false ? null : (int) $foundUser;

            $stmt = $pdo->prepare(
                'INSERT INTO company_recruiters (company_id, user_id, name, email, role, role_label, status)
                 VALUES (:c, :u, :n, :e, :r, :l, :s) RETURNING id'
            );
            $stmt->execute([
                ':c' => $cid,
                ':u' => $userId,
                ':n' => $name,
                ':e' => $email,
                ':r' => $role,
                ':l' => TEAM_ROLES[$role],
                ':s' => UI_TO_DB_STATUS[$uiStatus],
            ]);
            $newId = (int) $stmt->fetchColumn();

            api_log_activity($pdo, $cid, 'role', "menambahkan {$name} sebagai " . TEAM_ROLES[$role]);
            api_out(['ok' => true, 'id' => $newId, 'linked_user_id' => $userId], 201);
            break;
        }

        case 'PATCH': {
            api_write_guard('hr');
            $link = api_hr_company(api_user_id());
            $cid  = (int) $link['company_id'];
            $body = api_body();
            $id   = api_int($body, 'id');
            if ($id === null) {
                api_error('Butuh id.', 422);
            }

            $own = $pdo->prepare(
                'SELECT user_id, name, status FROM company_recruiters
                 WHERE id = :id AND company_id = :c'
            );
            $own->execute([':id' => $id, ':c' => $cid]);
            $row = $own->fetch();
            if (!$row) {
                api_error('Anggota tim itu tidak ada di perusahaan Anda.', 404);
            }
            if ($row['user_id'] !== null && (int) $row['user_id'] === api_user_id()) {
                api_error('Tidak bisa mengubah keanggotaan akun sendiri.', 422);
            }

            $sets = [];
            $args = [':id' => $id, ':c' => $cid];
            if (array_key_exists('status', $body)) {
                $ui = api_str($body, 'status', 20);
                if (!array_key_exists((string) $ui, UI_TO_DB_STATUS)) {
                    api_error('Status tidak dikenal.', 422, ['allowed' => array_keys(UI_TO_DB_STATUS)]);
                }
                $sets[] = 'status = :status';
                $args[':status'] = UI_TO_DB_STATUS[$ui];
            }
            if (array_key_exists('role', $body)) {
                $role = api_str($body, 'role', 30);
                if (!array_key_exists((string) $role, TEAM_ROLES)) {
                    api_error('Role tidak dikenal.', 422, ['allowed' => array_keys(TEAM_ROLES)]);
                }
                $sets[] = 'role = :role';
                $sets[] = 'role_label = :role_label';
                $args[':role'] = $role;
                $args[':role_label'] = TEAM_ROLES[$role];
            }
            foreach (['name' => 100, 'email' => 150] as $col => $len) {
                if (array_key_exists($col, $body)) {
                    $value = api_str($body, $col, $len);
                    if ($col === 'email' && $value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        api_error('Format email tidak valid.', 422);
                    }
                    $sets[] = "{$col} = :{$col}";
                    $args[":{$col}"] = $value;
                }
            }
            if (!$sets) {
                api_error('Tidak ada kolom yang diubah.', 422);
            }

            $stmt = $pdo->prepare(
                'UPDATE company_recruiters SET ' . implode(', ', $sets) . '
                 WHERE id = :id AND company_id = :c'
            );
            $stmt->execute($args);

            // Status akun login ikut berubah supaya akun nonaktif tidak bisa
            // terus masuk ke konsol.
            if (isset($args[':status']) && $row['user_id'] !== null && $args[':status'] !== 'active') {
                $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = :u AND role = 'hr'")
                    ->execute([':u' => (int) $row['user_id']]);
            }

            api_log_activity($pdo, $cid, 'role', 'memperbarui keanggotaan ' . $row['name']);
            api_out(['ok' => true, 'id' => $id]);
            break;
        }

        case 'DELETE': {
            api_write_guard('hr');
            $link = api_hr_company(api_user_id());
            $cid  = (int) $link['company_id'];
            $body = api_body();
            $id   = api_int($body, 'id') ?? api_int($_GET, 'id');
            if ($id === null) {
                api_error('Butuh id.', 422);
            }
            $own = $pdo->prepare('SELECT user_id, name FROM company_recruiters WHERE id = :id AND company_id = :c');
            $own->execute([':id' => $id, ':c' => $cid]);
            $row = $own->fetch();
            if (!$row) {
                api_error('Anggota tim itu tidak ada di perusahaan Anda.', 404);
            }
            if ($row['user_id'] !== null && (int) $row['user_id'] === api_user_id()) {
                api_error('Tidak bisa menghapus akun sendiri dari tim.', 422);
            }

            $stmt = $pdo->prepare('DELETE FROM company_recruiters WHERE id = :id AND company_id = :c');
            $stmt->execute([':id' => $id, ':c' => $cid]);
            if ($stmt->rowCount() === 0) {
                api_error('Anggota tim itu tidak ada di perusahaan Anda.', 404);
            }

            api_log_activity($pdo, $cid, 'role', 'menghapus keanggotaan ' . $row['name']);
            api_out(['ok' => true, 'id' => $id]);
            break;
        }

        default:
            api_error('Method tidak didukung.', 405);
    }
} catch (PDOException $e) {
    error_log('api/hr/team.php: ' . $e->getMessage());
    api_error('Gagal memproses tim recruiter.', 500);
}
