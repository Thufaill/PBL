<?php
// public/api/hr/roles.php
// Target role perusahaan + mapping skill (pengganti data/recruiter-roles.json
// dan localStorage 'sb.rec.mapping.{role_id}').
//
//   GET    -> [{ id, name, category, positions, status, skills:[nama],
//                skill_ids:[...], matches }]
//   POST   -> { name, category?, positions?, status?, skill_ids? }
//   PATCH  -> { id, name?, category?, positions?, status?, skill_ids? }
//             skill_ids mengganti SELURUH mapping role itu (replace, bukan append)
//   DELETE -> { id }
//
// 'matches' dihitung dari profil mahasiswa yang sengaja publik dan punya
// minimal satu skill role — bukan angka statis hasil seed.
// Company_id selalu dari sesi.

require_once __DIR__ . '/../../../includes/api/bootstrap.php';

const ROLE_STATUSES = ['open', 'closed', 'draft'];

$method = api_method();
$pdo    = api_pdo();

/**
 * Tolak request lebih dulu bila ada skill_id yang tidak ada di katalog.
 * Tanpa ini, skill karangan klien hanya hilang diam-diam dan layar
 * melaporkan "tersimpan" padahal mapping-nya kosong.
 */
function roles_assert_skills(PDO $pdo, array $skillIds): void {
    if (!$skillIds) {
        return;
    }
    $in = implode(',', array_fill(0, count($skillIds), '?'));
    $valid = $pdo->prepare("SELECT id FROM skills WHERE id IN ({$in})");
    foreach ($skillIds as $i => $sid) {
        $valid->bindValue($i + 1, (int) $sid, PDO::PARAM_INT);
    }
    $valid->execute();
    if (count($valid->fetchAll()) !== count($skillIds)) {
        api_error('Ada skill yang tidak ada di katalog.', 422);
    }
}

/**
 * Ganti isi vacancy_skills untuk satu role.
 * Dipanggil POST dan PATCH; selalu di dalam transaksi pemanggilnya.
 * $skillIds sudah lolos roles_assert_skills().
 */
function roles_replace_skills(PDO $pdo, int $vacancyId, array $skillIds): int {
    $pdo->prepare('DELETE FROM vacancy_skills WHERE vacancy_id = :v')->execute([':v' => $vacancyId]);
    if (!$skillIds) {
        return 0;
    }

    $ins = $pdo->prepare('INSERT INTO vacancy_skills (vacancy_id, skill_id) VALUES (:v, :s) ON CONFLICT DO NOTHING');
    foreach ($skillIds as $sid) {
        $ins->execute([':v' => $vacancyId, ':s' => (int) $sid]);
    }
    return count($skillIds);
}

try {
    switch ($method) {
        case 'GET': {
            api_guard('hr');
            $link = api_hr_company(api_user_id());
            $cid  = (int) $link['company_id'];

            $roles = $pdo->prepare(
                'SELECT id, title, category, positions, status, created_at
                 FROM vacancies WHERE company_id = :c ORDER BY id'
            );
            $roles->execute([':c' => $cid]);
            $list = [];
            foreach ($roles as $r) {
                $list[(int) $r['id']] = [
                    'id'        => (int) $r['id'],
                    'name'      => $r['title'],
                    'category'  => $r['category'],
                    'positions' => (int) $r['positions'],
                    'status'    => $r['status'],
                    'created_at'=> $r['created_at'],
                    'skills'    => [],
                    'skill_ids' => [],
                    'matches'   => 0,
                ];
            }

            if ($list) {
                $ids = array_keys($list);
                $in  = implode(',', array_fill(0, count($ids), '?'));
                $skills = $pdo->prepare(
                    "SELECT vs.vacancy_id, s.id AS skill_id, s.name
                     FROM vacancy_skills vs
                     JOIN skills s ON s.id = vs.skill_id
                     WHERE vs.vacancy_id IN ({$in})
                     ORDER BY s.name"
                );
                foreach ($ids as $i => $id) {
                    $skills->bindValue($i + 1, $id, PDO::PARAM_INT);
                }
                $skills->execute();
                foreach ($skills as $row) {
                    $vid = (int) $row['vacancy_id'];
                    $list[$vid]['skills'][]    = $row['name'];
                    $list[$vid]['skill_ids'][] = (int) $row['skill_id'];
                }

                // Kandidat cocok = profil publik yang punya >=1 skill role ini.
                $matches = $pdo->prepare(
                    "SELECT vs.vacancy_id, COUNT(DISTINCT pc.id) AS n
                     FROM vacancy_skills vs
                     JOIN user_skills us ON us.skill_id = vs.skill_id AND us.status = 'confirmed'
                     JOIN v_public_candidates pc ON pc.id = us.user_id
                     WHERE vs.vacancy_id IN ({$in})
                     GROUP BY vs.vacancy_id"
                );
                foreach ($ids as $i => $id) {
                    $matches->bindValue($i + 1, $id, PDO::PARAM_INT);
                }
                $matches->execute();
                foreach ($matches as $row) {
                    $list[(int) $row['vacancy_id']]['matches'] = (int) $row['n'];
                }
            }

            api_out(['ok' => true, 'roles' => array_values($list)]);
            break;
        }

        case 'POST': {
            api_write_guard('hr');
            $link = api_hr_company(api_user_id());
            $cid  = (int) $link['company_id'];
            $body = api_body();

            $name = api_str($body, 'name', 120) ?? api_str($body, 'title', 120);
            if ($name === null) {
                api_error('Nama role wajib diisi.', 422);
            }
            $status = api_str($body, 'status', 20) ?? 'open';
            if (!in_array($status, ROLE_STATUSES, true)) {
                api_error('Status tidak dikenal.', 422, ['allowed' => ROLE_STATUSES]);
            }
            $positions = api_int($body, 'positions') ?? 1;
            if ($positions < 1) {
                api_error('Posisi minimal 1.', 422);
            }
            $skillIds = api_int_list($body, 'skill_ids');
            roles_assert_skills($pdo, $skillIds);

            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'INSERT INTO vacancies (company_id, title, category, type, status, positions, created_by)
                 VALUES (:c, :t, :cat, :ty, :s, :p, :by) RETURNING id'
            );
            $stmt->execute([
                ':c'   => $cid,
                ':t'   => $name,
                ':cat' => api_str($body, 'category', 60),
                ':ty'  => api_str($body, 'type', 40) ?? 'Full-time',
                ':s'   => $status,
                ':p'   => $positions,
                ':by'  => api_user_id(),
            ]);
            $vid = (int) $stmt->fetchColumn();
            $linked = roles_replace_skills($pdo, $vid, $skillIds);
            api_log_activity($pdo, $cid, 'role', "menambahkan target role \"{$name}\"");
            $pdo->commit();

            api_out(['ok' => true, 'id' => $vid, 'skills_linked' => $linked], 201);
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

            $own = $pdo->prepare('SELECT title FROM vacancies WHERE id = :id AND company_id = :c');
            $own->execute([':id' => $id, ':c' => $cid]);
            $title = $own->fetchColumn();
            if ($title === false) {
                api_error('Role itu bukan milik perusahaan Anda.', 404);
            }

            $replaceSkills = array_key_exists('skill_ids', $body);
            $skillIds = $replaceSkills ? api_int_list($body, 'skill_ids') : [];
            roles_assert_skills($pdo, $skillIds);

            $pdo->beginTransaction();

            $sets = [];
            $args = [':id' => $id, ':c' => $cid];
            $newName = api_str($body, 'name', 120) ?? api_str($body, 'title', 120);
            if ($newName !== null) {
                $sets[] = 'title = :title';
                $args[':title'] = $newName;
            }
            foreach (['category' => 60, 'type' => 40] as $col => $len) {
                if (array_key_exists($col, $body)) {
                    $sets[] = "{$col} = :{$col}";
                    $args[":{$col}"] = api_str($body, $col, $len);
                }
            }
            if (array_key_exists('status', $body)) {
                $status = api_str($body, 'status', 20);
                if (!in_array($status, ROLE_STATUSES, true)) {
                    api_error('Status tidak dikenal.', 422, ['allowed' => ROLE_STATUSES]);
                }
                $sets[] = 'status = :status';
                $args[':status'] = $status;
            }
            if (array_key_exists('positions', $body)) {
                $positions = api_int($body, 'positions');
                if ($positions === null || $positions < 1) {
                    api_error('Posisi minimal 1.', 422);
                }
                $sets[] = 'positions = :positions';
                $args[':positions'] = $positions;
            }
            if ($sets) {
                $pdo->prepare('UPDATE vacancies SET ' . implode(', ', $sets) . ' WHERE id = :id AND company_id = :c')
                    ->execute($args);
            }

            $linked = null;
            if ($replaceSkills) {
                $linked = roles_replace_skills($pdo, $id, $skillIds);
            }

            if (!$sets && $linked === null) {
                api_error('Tidak ada perubahan yang dikirim.', 422);
            }

            if ($linked !== null) {
                api_log_activity($pdo, $cid, 'role', "memperbarui kebutuhan skill {$title}");
            } elseif ($sets) {
                api_log_activity($pdo, $cid, 'role', "memperbarui target role {$title}");
            }
            $pdo->commit();

            api_out(['ok' => true, 'id' => $id, 'skills_linked' => $linked]);
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

            $pdo->beginTransaction();
            $own = $pdo->prepare('SELECT title FROM vacancies WHERE id = :id AND company_id = :c');
            $own->execute([':id' => $id, ':c' => $cid]);
            $title = $own->fetchColumn();
            if ($title === false) {
                api_error('Role itu bukan milik perusahaan Anda.', 404);
            }
            $reviews = $pdo->prepare('SELECT COUNT(*) FROM candidate_reviews WHERE vacancy_id = :v');
            $reviews->execute([':v' => $id]);
            $reviewCount = (int) $reviews->fetchColumn();

            $stmt = $pdo->prepare('DELETE FROM vacancies WHERE id = :id AND company_id = :c');
            $stmt->execute([':id' => $id, ':c' => $cid]);
            api_log_activity($pdo, $cid, 'role', "menghapus target role {$title}");
            $pdo->commit();

            api_out(['ok' => true, 'id' => $id, 'reviews_removed' => $reviewCount]);
            break;
        }

        default:
            api_error('Method tidak didukung.', 405);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('api/hr/roles.php: ' . $e->getMessage());
    api_error('Gagal memproses target role.', 500);
}
