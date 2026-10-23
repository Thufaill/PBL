<?php
// public/api/admin/mapping.php
// Relasi karier <-> keterampilan (pengganti relasi di data/careers.json).
//
//   GET    -> [{ career_id, career_name, skill_id, skill_name, skill_category, is_mandatory }]
//   POST   -> { career_id, skill_id }  ATAU  { career_name, skill_name }
//             skill_name yang belum ada di katalog dibuat otomatis (kategori null).
//   PATCH  -> { career_id, skill_id, is_mandatory }
//   DELETE -> { career_id, skill_id }
//
// Halaman sebelumnya menampilkan toast "(dummy)" dan memakai empat nama
// karier hardcoded di form; kini keduanya berasal dari database.

require_once __DIR__ . '/../../../includes/api/bootstrap.php';

$method = api_method();
$pdo    = api_pdo();

try {
    switch ($method) {
        case 'GET': {
            api_guard('admin');
            $rows = $pdo->query(
                'SELECT csr.career_id, c.name AS career_name,
                        csr.skill_id, s.name AS skill_name, s.category AS skill_category,
                        csr.is_mandatory
                 FROM career_skill_requirements csr
                 JOIN careers c ON c.id = csr.career_id
                 JOIN skills s  ON s.id = csr.skill_id
                 ORDER BY c.name, s.name'
            )->fetchAll();
            foreach ($rows as &$r) {
                $r['career_id']    = (int) $r['career_id'];
                $r['skill_id']     = (int) $r['skill_id'];
                $r['is_mandatory'] = (bool) $r['is_mandatory'];
            }
            unset($r);
            api_out(['ok' => true, 'mapping' => $rows]);
            break;
        }

        case 'POST': {
            api_write_guard('admin');
            $body      = api_body();
            $careerId  = api_int($body, 'career_id');
            $skillId   = api_int($body, 'skill_id');
            $mandatory = array_key_exists('is_mandatory', $body) ? (bool) $body['is_mandatory'] : true;

            if ($careerId === null && ($careerName = api_str($body, 'career_name', 100)) !== null) {
                $stmt = $pdo->prepare('SELECT id FROM careers WHERE LOWER(name) = LOWER(:n)');
                $stmt->execute([':n' => $careerName]);
                $found = $stmt->fetchColumn();
                $careerId = $found === false ? null : (int) $found;
            }
            if ($careerId === null) {
                api_error('Butuh career_id atau career_name yang dikenal.', 422);
            }

            if ($skillId === null && ($skillName = api_str($body, 'skill_name', 100)) !== null) {
                $stmt = $pdo->prepare('SELECT id FROM skills WHERE LOWER(name) = LOWER(:n)');
                $stmt->execute([':n' => $skillName]);
                $found = $stmt->fetchColumn();
                if ($found) {
                    $skillId = (int) $found;
                } else {
                    $ins = $pdo->prepare("INSERT INTO skills (name, category) VALUES (:n, 'Programming') RETURNING id");
                    $ins->execute([':n' => $skillName]);
                    $skillId = (int) $ins->fetchColumn();
                }
            }
            if ($skillId === null) {
                api_error('Butuh skill_id atau skill_name.', 422);
            }

            $check = $pdo->prepare('SELECT 1 FROM careers WHERE id = :c');
            $check->execute([':c' => $careerId]);
            if (!$check->fetchColumn()) {
                api_error('Karier itu tidak ada.', 404);
            }

            // is_mandatory selalu diikat lewat bindValue(PARAM_BOOL).
            // PDO memperlakukan nilai array execute() sebagai string, dan
            // false menjadi "" yang ditolak Postgres sebagai boolean.
            $stmt = $pdo->prepare(
                'INSERT INTO career_skill_requirements (career_id, skill_id, is_mandatory)
                 VALUES (:c, :s, :m)
                 ON CONFLICT (career_id, skill_id) DO UPDATE SET is_mandatory = EXCLUDED.is_mandatory'
            );
            $stmt->bindValue(':c', $careerId, PDO::PARAM_INT);
            $stmt->bindValue(':s', $skillId, PDO::PARAM_INT);
            $stmt->bindValue(':m', $mandatory, PDO::PARAM_BOOL);
            $stmt->execute();
            api_out(['ok' => true, 'career_id' => $careerId, 'skill_id' => $skillId], 201);
            break;
        }

        case 'PATCH': {
            api_write_guard('admin');
            $body = api_body();
            $careerId = api_int($body, 'career_id');
            $skillId  = api_int($body, 'skill_id');
            if ($careerId === null || $skillId === null) {
                api_error('Butuh career_id dan skill_id.', 422);
            }
            $stmt = $pdo->prepare(
                'UPDATE career_skill_requirements SET is_mandatory = :m
                 WHERE career_id = :c AND skill_id = :s'
            );
            $stmt->bindValue(':m', (bool) ($body['is_mandatory'] ?? true), PDO::PARAM_BOOL);
            $stmt->bindValue(':c', $careerId, PDO::PARAM_INT);
            $stmt->bindValue(':s', $skillId, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                api_error('Relasi itu tidak ada.', 404);
            }
            api_out(['ok' => true]);
            break;
        }

        case 'DELETE': {
            api_write_guard('admin');
            $body     = api_body();
            $careerId = api_int($body, 'career_id') ?? api_int($_GET, 'career_id');
            $skillId  = api_int($body, 'skill_id') ?? api_int($_GET, 'skill_id');
            if ($careerId === null || $skillId === null) {
                api_error('Butuh career_id dan skill_id.', 422);
            }
            $stmt = $pdo->prepare('DELETE FROM career_skill_requirements WHERE career_id = :c AND skill_id = :s');
            $stmt->execute([':c' => $careerId, ':s' => $skillId]);
            if ($stmt->rowCount() === 0) {
                api_error('Relasi itu tidak ada.', 404);
            }
            api_out(['ok' => true]);
            break;
        }

        default:
            api_error('Method tidak didukung.', 405);
    }
} catch (PDOException $e) {
    error_log('api/admin/mapping.php: ' . $e->getMessage());
    api_error('Gagal memproses relasi karier.', 500);
}
