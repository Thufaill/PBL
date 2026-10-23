<?php
// public/api/admin/vacancies.php
// Moderasi lowongan kerja lintas perusahaan (pengganti data/admin-vacancies.json).
//
//   GET    -> [{ id, title, company, location, type, status, applicants,
//                posted_by, positions, created_at }]
//   POST   -> { title, company | company_id, location?, type?, positions?, status? }
//   PATCH  -> { id, status? }  atau  { id, title?, location?, type?, positions? }
//   DELETE -> { id }
//
// Status yang boleh diatur admin sengaja lebih luas dari tombol "ubah status"
// di UI (open<->closed) supaya moderasi draft/pending/rejected bisa lewat API.

require_once __DIR__ . '/../../../includes/api/bootstrap.php';

const VACANCY_STATUSES = ['open', 'closed', 'draft', 'pending', 'rejected'];

$method = api_method();
$pdo    = api_pdo();

try {
    switch ($method) {
        case 'GET': {
            api_guard('admin');
            $rows = $pdo->query(
                'SELECT v.id, v.title, v.location, v.type, v.status, v.positions, v.created_at,
                        c.name AS company, c.id AS company_id,
                        u.name AS posted_by,
                        (SELECT COUNT(*) FROM candidate_reviews cr WHERE cr.vacancy_id = v.id) AS applicants
                 FROM vacancies v
                 JOIN companies c ON c.id = v.company_id
                 LEFT JOIN users u ON u.id = v.created_by
                 ORDER BY v.id'
            )->fetchAll();
            foreach ($rows as &$r) {
                $r['id']         = (int) $r['id'];
                $r['company_id'] = (int) $r['company_id'];
                $r['positions']  = (int) $r['positions'];
                $r['applicants'] = (int) $r['applicants'];
            }
            unset($r);
            api_out(['ok' => true, 'vacancies' => $rows]);
            break;
        }

        case 'POST': {
            api_write_guard('admin');
            $body  = api_body();
            $title = api_str($body, 'title', 120);
            if ($title === null) {
                api_error('Judul lowongan wajib diisi.', 422);
            }
            $companyId = api_int($body, 'company_id');
            if ($companyId === null) {
                $companyName = api_str($body, 'company', 150);
                if ($companyName === null) {
                    api_error('Butuh company_id atau nama perusahaan.', 422);
                }
                $stmt = $pdo->prepare('SELECT id FROM companies WHERE LOWER(name) = LOWER(:n)');
                $stmt->execute([':n' => $companyName]);
                $found = $stmt->fetchColumn();
                if (!$found) {
                    $ins = $pdo->prepare(
                        "INSERT INTO companies (name, status) VALUES (:n, 'pending') RETURNING id"
                    );
                    $ins->execute([':n' => $companyName]);
                    $companyId = (int) $ins->fetchColumn();
                } else {
                    $companyId = (int) $found;
                }
            } else {
                $check = $pdo->prepare('SELECT 1 FROM companies WHERE id = :id');
                $check->execute([':id' => $companyId]);
                if (!$check->fetchColumn()) {
                    api_error('Perusahaan itu tidak ada.', 404);
                }
            }

            $status = api_str($body, 'status', 20) ?? 'draft';
            if (!in_array($status, VACANCY_STATUSES, true)) {
                api_error('Status tidak dikenal.', 422, ['allowed' => VACANCY_STATUSES]);
            }
            $positions = api_int($body, 'positions') ?? 1;
            if ($positions < 1) {
                api_error('Posisi minimal 1.', 422);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO vacancies (company_id, title, location, type, status, positions, created_by)
                 VALUES (:c, :t, :l, :ty, :s, :p, :by) RETURNING id'
            );
            $stmt->execute([
                ':c'  => $companyId,
                ':t'  => $title,
                ':l'  => api_str($body, 'location', 120),
                ':ty' => api_str($body, 'type', 40) ?? 'Full-time',
                ':s'  => $status,
                ':p'  => $positions,
                ':by' => api_user_id(),
            ]);
            api_out(['ok' => true, 'id' => (int) $stmt->fetchColumn()], 201);
            break;
        }

        case 'PATCH': {
            api_write_guard('admin');
            $body = api_body();
            $id   = api_int($body, 'id');
            if ($id === null) {
                api_error('Butuh id.', 422);
            }
            $sets = [];
            $args = [':id' => $id];

            if (array_key_exists('status', $body)) {
                $status = api_str($body, 'status', 20);
                if (!in_array($status, VACANCY_STATUSES, true)) {
                    api_error('Status tidak dikenal.', 422, ['allowed' => VACANCY_STATUSES]);
                }
                $sets[] = 'status = :status';
                $args[':status'] = $status;
            }
            foreach (['title' => 120, 'location' => 120, 'type' => 40] as $col => $max) {
                if (array_key_exists($col, $body)) {
                    $sets[] = "{$col} = :{$col}";
                    $args[":{$col}"] = api_str($body, $col, $max);
                }
            }
            if (array_key_exists('positions', $body)) {
                $positions = api_int($body, 'positions');
                if ($positions === null || $positions < 1) {
                    api_error('Posisi minimal 1.', 422);
                }
                $sets[] = 'positions = :positions';
                $args[':positions'] = $positions;
            }
            if (!$sets) {
                api_error('Tidak ada kolom yang diubah.', 422);
            }

            $stmt = $pdo->prepare('UPDATE vacancies SET ' . implode(', ', $sets) . ' WHERE id = :id');
            $stmt->execute($args);
            if ($stmt->rowCount() === 0) {
                api_error('Lowongan itu tidak ada.', 404);
            }
            api_out(['ok' => true, 'id' => $id]);
            break;
        }

        case 'DELETE': {
            api_write_guard('admin');
            $body = api_body();
            $id   = api_int($body, 'id') ?? api_int($_GET, 'id');
            if ($id === null) {
                api_error('Butuh id.', 422);
            }
            $stmt = $pdo->prepare('DELETE FROM vacancies WHERE id = :id');
            $stmt->execute([':id' => $id]);
            if ($stmt->rowCount() === 0) {
                api_error('Lowongan itu tidak ada.', 404);
            }
            api_out(['ok' => true, 'id' => $id]);
            break;
        }

        default:
            api_error('Method tidak didukung.', 405);
    }
} catch (PDOException $e) {
    error_log('api/admin/vacancies.php: ' . $e->getMessage());
    api_error('Gagal memproses lowongan.', 500);
}
