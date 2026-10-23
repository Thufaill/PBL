<?php
// public/api/admin/skills.php
// Katalog keterampilan master (pengganti data/skills.json + handler "dummy").
//
//   GET    -> [{ id, name, category, description, users_count, careers_count }]
//   POST   -> { name, category?, description? }
//   PATCH  -> { id, name?, category?, description? }
//   DELETE -> { id }
//
// Sebelum ditulis, halaman ini hanya menampilkan toast "(dummy)" dan
// menghapus dari state lokal: tidak ada yang berubah di server.
// DELETE pada skills CASCADE ke user_skills, evidence_skills, dan
// career_skill_requirements, jadi respons ikut melaporkan berapa tautan
// yang terhapus supaya efeknya tidak diam-diam.

require_once __DIR__ . '/../../../includes/api/bootstrap.php';

const SKILL_CATEGORIES = ['Programming', 'Database', 'Web', 'Tools', 'Data', 'Design'];

$method = api_method();
$pdo    = api_pdo();

function admin_skills_list(PDO $pdo): array {
    $rows = $pdo->query(
        "SELECT s.id, s.name, s.category, s.description,
                (SELECT COUNT(*) FROM user_skills us WHERE us.skill_id = s.id) AS users_count,
                (SELECT COUNT(*) FROM career_skill_requirements csr WHERE csr.skill_id = s.id) AS careers_count
         FROM skills s
         ORDER BY s.category NULLS LAST, s.name"
    )->fetchAll();
    foreach ($rows as &$r) {
        $r['id']           = (int) $r['id'];
        $r['users_count']  = (int) $r['users_count'];
        $r['careers_count']= (int) $r['careers_count'];
    }
    unset($r);
    return $rows;
}

/** Kategori harus salah satu enam nilai yang dipakai UI (ikon & picker). */
function admin_check_category(?string $category): ?string {
    if ($category === null) {
        return null;
    }
    if (!in_array($category, SKILL_CATEGORIES, true)) {
        api_error('Kategori tidak dikenal.', 422, ['allowed' => SKILL_CATEGORIES]);
    }
    return $category;
}

try {
    switch ($method) {
        case 'GET': {
            api_guard('admin');
            api_out([
                'ok'         => true,
                'skills'     => admin_skills_list($pdo),
                // Daftar kategori yang diterima POST/PATCH, supaya form admin
                // menawarkan pilihan yang sama dengan validasi server.
                'categories' => SKILL_CATEGORIES,
            ]);
            break;
        }

        case 'POST': {
            api_write_guard('admin');
            $body     = api_body();
            $name     = api_str($body, 'name', 100);
            $category = admin_check_category(api_str($body, 'category', 50));
            $desc     = api_str($body, 'description', 2000);
            if ($name === null) {
                api_error('Nama keterampilan wajib diisi.', 422);
            }

            $exists = $pdo->prepare('SELECT id FROM skills WHERE LOWER(name) = LOWER(:n)');
            $exists->execute([':n' => $name]);
            if ($exists->fetchColumn()) {
                api_error('Keterampilan dengan nama itu sudah ada.', 422);
            }

            $stmt = $pdo->prepare('INSERT INTO skills (name, category, description) VALUES (:n, :c, :d) RETURNING id');
            $stmt->execute([':n' => $name, ':c' => $category, ':d' => $desc]);
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
            $desc = array_key_exists('description', $body) ? api_str($body, 'description', 2000) : null;

            $sets = [];
            $args = [':id' => $id];
            if ($name !== null) {
                $dup = $pdo->prepare('SELECT 1 FROM skills WHERE LOWER(name) = LOWER(:n) AND id <> :id');
                $dup->execute([':n' => $name, ':id' => $id]);
                if ($dup->fetchColumn()) {
                    api_error('Nama itu sudah dipakai keterampilan lain.', 422);
                }
                $sets[] = 'name = :name';
                $args[':name'] = $name;
            }
            if (array_key_exists('category', $body)) {
                $sets[] = 'category = :cat';
                $args[':cat'] = admin_check_category(api_str($body, 'category', 50));
            }
            if (array_key_exists('description', $body)) {
                $sets[] = 'description = :desc';
                $args[':desc'] = $desc;
            }
            if (!$sets) {
                api_error('Tidak ada kolom yang diubah.', 422);
            }

            $stmt = $pdo->prepare('UPDATE skills SET ' . implode(', ', $sets) . ' WHERE id = :id');
            $stmt->execute($args);
            if ($stmt->rowCount() === 0) {
                api_error('Keterampilan itu tidak ada.', 404);
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

            $count = $pdo->prepare(
                'SELECT (SELECT COUNT(*) FROM user_skills WHERE skill_id = :id1) AS us,
                        (SELECT COUNT(*) FROM career_skill_requirements WHERE skill_id = :id2) AS csr'
            );
            $count->execute([':id1' => $id, ':id2' => $id]);
            $usage = $count->fetch();

            $del = $pdo->prepare('DELETE FROM skills WHERE id = :id');
            $del->execute([':id' => $id]);
            if ($del->rowCount() === 0) {
                api_error('Keterampilan itu tidak ada.', 404);
            }
            api_out([
                'ok'           => true,
                'id'           => $id,
                'users_unlinked'   => (int) ($usage['us'] ?? 0),
                'careers_unlinked' => (int) ($usage['csr'] ?? 0),
            ]);
            break;
        }

        default:
            api_error('Method tidak didukung.', 405);
    }
} catch (PDOException $e) {
    error_log('api/admin/skills.php: ' . $e->getMessage());
    api_error('Gagal memproses keterampilan master.', 500);
}
