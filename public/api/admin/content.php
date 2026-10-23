<?php
// public/api/admin/content.php
// Konten editorial (pengganti data/admin-content.json).
//
//   GET    -> [{ id, title, type, category, status, author, views, updated_at, slug }]
//   POST   -> { title, type?, category?, excerpt?, body?, status?, slug? }
//   PATCH  -> { id, ...field, status? }
//   DELETE -> { id }
//
// slug dibuat dari judul bila tidak dikirim; tab dan spasi tidak boleh masuk
// karena slug dipakai di URL halaman publik.

require_once __DIR__ . '/../../../includes/api/bootstrap.php';

const CONTENT_STATUSES = ['published', 'draft', 'archived'];
const CONTENT_TYPES    = ['article', 'guide', 'news'];

$method = api_method();
$pdo    = api_pdo();

function admin_slug(string $text): string {
    $slug = strtolower(trim($text));
    $slug = preg_replace('/[^a-z0-9]+/u', '-', $slug);
    $slug = trim($slug, '-');
    return $slug === '' ? 'tanpa-judul' : substr($slug, 0, 160);
}

function admin_unique_slug(PDO $pdo, string $base, int $ignoreId = 0): string {
    $slug = $base;
    $i    = 2;
    while (true) {
        $stmt = $pdo->prepare('SELECT id FROM content_items WHERE slug = :s AND id <> :i');
        $stmt->execute([':s' => $slug, ':i' => $ignoreId]);
        if (!$stmt->fetchColumn()) {
            return $slug;
        }
        $slug = $base . '-' . $i;
        $i++;
    }
}

try {
    switch ($method) {
        case 'GET': {
            // Publik boleh membaca konten yang sudah terbit; draf hanya admin.
            $publicOnly = !is_logged_in() || get_user_role() !== 'admin';
            $sql = 'SELECT id, slug, title, type, category, status, author, views, updated_at, excerpt
                    FROM content_items';
            if ($publicOnly) {
                $sql .= " WHERE status = 'published'";
            }
            $sql .= ' ORDER BY updated_at DESC, id DESC';
            $rows = $pdo->query($sql)->fetchAll();
            foreach ($rows as &$r) {
                $r['id']    = (int) $r['id'];
                $r['views'] = (int) $r['views'];
            }
            unset($r);
            api_out(['ok' => true, 'content' => $rows, 'scoped_to_published' => $publicOnly]);
            break;
        }

        case 'POST': {
            api_write_guard('admin');
            $body  = api_body();
            $title = api_str($body, 'title', 150);
            if ($title === null) {
                api_error('Judul wajib diisi.', 422);
            }
            $type   = api_str($body, 'type', 30) ?? 'article';
            $status = api_str($body, 'status', 20) ?? 'draft';
            if (!in_array($type, CONTENT_TYPES, true)) {
                api_error('Tipe konten tidak dikenal.', 422, ['allowed' => CONTENT_TYPES]);
            }
            if (!in_array($status, CONTENT_STATUSES, true)) {
                api_error('Status tidak dikenal.', 422, ['allowed' => CONTENT_STATUSES]);
            }

            $slug = admin_unique_slug($pdo, admin_slug(api_str($body, 'slug', 160) ?? $title));
            $stmt = $pdo->prepare(
                'INSERT INTO content_items (slug, title, type, category, status, author, excerpt, body)
                 VALUES (:slug, :title, :type, :cat, :status, :author, :excerpt, :body) RETURNING id'
            );
            $stmt->execute([
                ':slug'   => $slug,
                ':title'  => $title,
                ':type'   => $type,
                ':cat'    => api_str($body, 'category', 50),
                ':status' => $status,
                ':author' => api_str($body, 'author', 100) ?? (current_user()['name'] ?? null),
                ':excerpt'=> api_str($body, 'excerpt', 500),
                ':body'   => api_str($body, 'body', 20000),
            ]);
            api_out(['ok' => true, 'id' => (int) $stmt->fetchColumn(), 'slug' => $slug], 201);
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
            $max  = ['title' => 150, 'category' => 50, 'author' => 100, 'excerpt' => 500, 'body' => 20000];

            if (array_key_exists('status', $body)) {
                $status = api_str($body, 'status', 20);
                if (!in_array($status, CONTENT_STATUSES, true)) {
                    api_error('Status tidak dikenal.', 422, ['allowed' => CONTENT_STATUSES]);
                }
                $sets[] = 'status = :status';
                $args[':status'] = $status;
            }
            if (array_key_exists('type', $body)) {
                $type = api_str($body, 'type', 30);
                if (!in_array($type, CONTENT_TYPES, true)) {
                    api_error('Tipe konten tidak dikenal.', 422, ['allowed' => CONTENT_TYPES]);
                }
                $sets[] = 'type = :type';
                $args[':type'] = $type;
            }
            foreach ($max as $col => $len) {
                if (array_key_exists($col, $body)) {
                    $sets[] = "{$col} = :{$col}";
                    $args[":{$col}"] = api_str($body, $col, $len);
                }
            }
            if (!$sets) {
                api_error('Tidak ada kolom yang diubah.', 422);
            }

            $stmt = $pdo->prepare('UPDATE content_items SET ' . implode(', ', $sets) . ' WHERE id = :id');
            $stmt->execute($args);
            if ($stmt->rowCount() === 0) {
                api_error('Konten itu tidak ada.', 404);
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
            $stmt = $pdo->prepare('DELETE FROM content_items WHERE id = :id');
            $stmt->execute([':id' => $id]);
            if ($stmt->rowCount() === 0) {
                api_error('Konten itu tidak ada.', 404);
            }
            api_out(['ok' => true, 'id' => $id]);
            break;
        }

        default:
            api_error('Method tidak didukung.', 405);
    }
} catch (PDOException $e) {
    error_log('api/admin/content.php: ' . $e->getMessage());
    api_error('Gagal memproses konten.', 500);
}
