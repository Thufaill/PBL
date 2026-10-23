<?php
// public/api/admin/careers.php
// Katalog target karier master (pengganti data/careers.json).
//
//   GET    -> [{ id, name, description, skills:[nama], skills_count, created_at }]
//   POST   -> { name, description? }
//   PATCH  -> { id, name?, description? }
//   DELETE -> { id }
//
// DELETE melepas seluruh career_skill_requirements milik karier (CASCADE).

require_once __DIR__ . '/../../../includes/api/bootstrap.php';

$method = api_method();
$pdo    = api_pdo();

function admin_careers_list(PDO $pdo): array {
    $careers = $pdo->query(
        "SELECT c.id, c.name, c.description, c.created_at
         FROM careers c
         ORDER BY c.name"
    )->fetchAll();

    $links = $pdo->query(
        'SELECT csr.career_id, s.name
         FROM career_skill_requirements csr
         JOIN skills s ON s.id = csr.skill_id
         ORDER BY s.name'
    )->fetchAll();

    $byCareer = [];
    foreach ($links as $l) {
        $byCareer[(int) $l['career_id']][] = $l['name'];
    }

    foreach ($careers as &$c) {
        $cid = (int) $c['id'];
        $c['id']           = $cid;
        $c['skills']       = $byCareer[$cid] ?? [];
        $c['skills_count'] = count($c['skills']);
    }
    unset($c);
    return $careers;
}

try {
    switch ($method) {
        case 'GET': {
            api_guard('admin');
            api_out(['ok' => true, 'careers' => admin_careers_list($pdo)]);
            break;
        }

        case 'POST': {
            api_write_guard('admin');
            $body = api_body();
            $name = api_str($body, 'name', 100);
            $desc = api_str($body, 'description', 2000);
            if ($name === null) {
                api_error('Nama karier wajib diisi.', 422);
            }
            $exists = $pdo->prepare('SELECT 1 FROM careers WHERE LOWER(name) = LOWER(:n)');
            $exists->execute([':n' => $name]);
            if ($exists->fetchColumn()) {
                api_error('Karier dengan nama itu sudah ada.', 422);
            }
            $stmt = $pdo->prepare('INSERT INTO careers (name, description) VALUES (:n, :d) RETURNING id');
            $stmt->execute([':n' => $name, ':d' => $desc]);
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
            $name = api_str($body, 'name', 100);
            $sets = [];
            $args = [':id' => $id];
            if ($name !== null) {
                $dup = $pdo->prepare('SELECT 1 FROM careers WHERE LOWER(name) = LOWER(:n) AND id <> :id');
                $dup->execute([':n' => $name, ':id' => $id]);
                if ($dup->fetchColumn()) {
                    api_error('Nama itu sudah dipakai karier lain.', 422);
                }
                $sets[] = 'name = :name';
                $args[':name'] = $name;
            }
            if (array_key_exists('description', $body)) {
                $sets[] = 'description = :desc';
                $args[':desc'] = api_str($body, 'description', 2000);
            }
            if (!$sets) {
                api_error('Tidak ada kolom yang diubah.', 422);
            }
            $stmt = $pdo->prepare('UPDATE careers SET ' . implode(', ', $sets) . ' WHERE id = :id');
            $stmt->execute($args);
            if ($stmt->rowCount() === 0) {
                api_error('Karier itu tidak ada.', 404);
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
            // Profil mahasiswa yang menunjuk karier ini dilepas lebih dulu
            // (ON DELETE SET NULL), jadi target karier mereka kembali kosong.
            $unlinked = $pdo->prepare('SELECT COUNT(*) FROM student_profiles WHERE target_career_id = :id');
            $unlinked->execute([':id' => $id]);
            $profiles = (int) $unlinked->fetchColumn();

            $req = $pdo->prepare('SELECT COUNT(*) FROM career_skill_requirements WHERE career_id = :id');
            $req->execute([':id' => $id]);
            $relations = (int) $req->fetchColumn();

            $del = $pdo->prepare('DELETE FROM careers WHERE id = :id');
            $del->execute([':id' => $id]);
            if ($del->rowCount() === 0) {
                api_error('Karier itu tidak ada.', 404);
            }
            api_out([
                'ok'               => true,
                'id'               => $id,
                'relations_removed'=> $relations,
                'profiles_reset'   => $profiles,
            ]);
            break;
        }

        default:
            api_error('Method tidak didukung.', 405);
    }
} catch (PDOException $e) {
    error_log('api/admin/careers.php: ' . $e->getMessage());
    api_error('Gagal memproses target karier master.', 500);
}
