<?php
// includes/api/bootstrap.php
// Fondasi seluruh endpoint JSON di public/api/.
// Sengaja berada di luar docroot (public/) supaya tidak pernah terlayani web.
//
// Aturan yang berlaku untuk SEMUA endpoint di folder ini:
//   1. Data domain selalu difilter dengan user_id / company_id dari SESI,
//      tidak pernah dari body request.
//   2. Semua tulis (POST/PATCH/DELETE) wajib lolos csrf_verify().
//   3. Respons selalu JSON, tidak pernah redirect. Halaman yang sudah login
//      memanggil endpoint ini lewat fetch(); sesi hilang = 401, bukan
//      lemparan ke login.php.
//   4. Prepared statement mutlak. Tidak ada interpolasi nilai ke SQL.
//
// Mode demo (APP_DEMO=1) mengikuti perilaku check_auth(): sesi demo dibuat
// otomatis. Saat mode demo mati, guard bersikap ketat.

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../config/app.php';

/** Keluar dengan body JSON dan hentikan eksekusi. */
function api_out($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_error(string $message, int $status, array $extra = []): void {
    api_out(['ok' => false, 'error' => $message] + $extra, $status);
}

/** Koneksi DB atau keluar 503. */
function api_pdo(): PDO {
    $pdo = get_db_connection();
    if (!$pdo) {
        api_error('Database tidak tersedia.', 503);
    }
    return $pdo;
}

/** Method request, mematuhi tunneling X-HTTP-Method-Override. */
function api_method(): string {
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if ($method === 'POST') {
        $override = strtoupper((string) ($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? ''));
        if (in_array($override, ['PATCH', 'PUT', 'DELETE'], true)) {
            return $override;
        }
    }
    return $method;
}

/**
 * Wajib login (+ role bila diminta). Tidak pernah redirect.
 * Mengembalikan sesi user: ['id' => int, 'role' => 'mahasiswa|admin|hr', ...]
 */
function api_guard(?string $role = null): array {
    header('Cache-Control: no-store');

    if (demo_mode()) {
        if (!is_logged_in() && $role) {
            login_demo_user($role);
        } elseif ($role && get_user_role() !== $role) {
            login_demo_user($role);
        }
        if (!is_logged_in()) {
            login_demo_user('mahasiswa');
        }
        return current_user();
    }

    if (!is_logged_in()) {
        api_error('Sesi tidak ada. Silakan login.', 401);
    }
    $user = current_user();
    if ($role && (get_user_role() !== $role)) {
        api_error('Akun ini tidak punya hak akses tersebut.', 403);
    }
    return $user;
}

/** id user dari sesi, selalu integer. */
function api_user_id(): int {
    return (int) (current_user()['id'] ?? 0);
}

/** Body JSON (atau form) untuk tulis; keluar 400 bila tidak bisa dibaca. */
function api_body(): array {
    $raw = file_get_contents('php://input');
    if ($raw !== false && trim($raw) !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        api_error('Body request bukan JSON yang valid.', 400);
    }
    return $_POST;
}

/** Guard penuh untuk operasi tulis: login + role + CSRF. */
function api_write_guard(?string $role = null): array {
    $user = api_guard($role);
    if (!csrf_verify()) {
        api_error('Token CSRF tidak valid. Muat ulang halaman.', 403);
    }
    return $user;
}

/** Baca string yang sudah dibatasi panjangnya; null bila kosong. */
function api_str(array $data, string $key, int $maxLen = 500): ?string {
    if (!array_key_exists($key, $data) || $data[$key] === null) {
        return null;
    }
    $value = trim((string) $data[$key]);
    if ($value === '') {
        return null;
    }
    if (mb_strlen($value) > $maxLen) {
        api_error("Kolom '{$key}' terlalu panjang (maks {$maxLen} karakter).", 422);
    }
    return $value;
}

function api_int(array $data, string $key): ?int {
    if (!array_key_exists($key, $data) || $data[$key] === '' || $data[$key] === null) {
        return null;
    }
    if (!is_numeric($data[$key])) {
        api_error("Kolom '{$key}' harus angka.", 422);
    }
    return (int) $data[$key];
}

/** Daftar integer dari JSON array; dipakai untuk skill_ids. */
function api_int_list(array $data, string $key): array {
    $raw = $data[$key] ?? [];
    if (!is_array($raw)) {
        api_error("Kolom '{$key}' harus berupa daftar.", 422);
    }
    $out = [];
    foreach ($raw as $v) {
        if (is_numeric($v)) {
            $out[] = (int) $v;
        }
    }
    return array_values(array_unique($out));
}

/**
 * URL foto profil. Selalu dibangun dari id sesi, tidak pernah dari path
 * yang dikirim klien.
 */
function api_photo_url(int $userId): string {
    // BASE_URL buatan includes/config/app.php dihitung dari SCRIPT_NAME, jadi
    // di dalam endpoint nilainya "<basis app>/api[/admin|hr]", sementara klien
    // memakai BASE_URL halaman ("<basis app>"). Potong segmen '/api'-nya dulu.
    $base = rtrim(preg_replace('#/api(/.*)?$#', '', BASE_URL), '/');
    return $base . '/api/profile-photo.php?user=' . $userId;
}

/**
 * Skill milik satu mahasiswa, memakai v_user_skill_evidence sehingga
 * evidence_count hanya menghitung bukti milik user itu sendiri.
 * label 'Strong' pada dua bukti atau lebih: kontrak lama gateway klien.
 */
function api_user_skills(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare(
        'SELECT skill_id, skill_name, skill_category, source, status, evidence_count
         FROM v_user_skill_evidence
         WHERE user_id = :u
         ORDER BY skill_name'
    );
    $stmt->execute([':u' => $userId]);
    $rows = [];
    foreach ($stmt as $row) {
        $count = (int) $row['evidence_count'];
        $rows[] = [
            'skill_id'       => (int) $row['skill_id'],
            'skill_name'     => $row['skill_name'],
            'skill_category' => $row['skill_category'],
            'source'         => $row['source'],
            'status'         => $row['status'],
            'evidence_count' => $count,
            'label'          => $count >= 2 ? 'Strong' : 'Growing',
        ];
    }
    return $rows;
}

/**
 * Seluruh evidence milik satu user, sudah digabung dengan tautan skillnya.
 * Bentuk keluaran identik dengan data/evidence.json lama supaya gateway
 * klien tidak perlu berubah. Dipakai session.php dan evidence.php.
 */
function api_fetch_evidence(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare(
        'SELECT id, source_type, title, description, file_url, metadata, created_at, updated_at
         FROM evidence
         WHERE user_id = :u
         ORDER BY id'
    );
    $stmt->execute([':u' => $userId]);

    $rows = [];
    foreach ($stmt as $row) {
        $meta = json_decode((string) ($row['metadata'] ?? '{}'), true);
        $rows[(int) $row['id']] = [
            'id'          => (int) $row['id'],
            'source_type' => $row['source_type'],
            'title'       => $row['title'],
            'description' => $row['description'],
            'file_url'    => $row['file_url'],
            // stdClass memaksa json_encode menghasilkan {} saat metadata
            // kosong; array kosong akan keluar sebagai [] dan membuat
            // ev.metadata.date di sisi JS membaca indeks, bukan properti.
            'metadata'    => (is_array($meta) && $meta !== []) ? $meta : new stdClass(),
            'created_at'  => $row['created_at'],
            'updated_at'  => $row['updated_at'],
            'skill_ids'   => [],
            'skills'      => [],
        ];
    }
    if (!$rows) {
        return [];
    }

    $ids = array_map('intval', array_keys($rows));
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $links = $pdo->prepare(
        "SELECT es.evidence_id, es.skill_id, s.name
         FROM evidence_skills es
         JOIN skills s ON s.id = es.skill_id
         WHERE es.evidence_id IN ({$in})
         ORDER BY es.skill_id"
    );
    // Placeholder posisional: isinya integer dari database, bukan input user.
    foreach ($ids as $i => $id) {
        $links->bindValue($i + 1, $id, PDO::PARAM_INT);
    }
    $links->execute();
    foreach ($links as $link) {
        $eid = (int) $link['evidence_id'];
        $rows[$eid]['skill_ids'][] = (int) $link['skill_id'];
        $rows[$eid]['skills'][]    = $link['name'];
    }

    return array_values($rows);
}

/** Nilai sebuah pengaturan platform (tabel system_settings). */
function api_setting($key, $default = null, ?PDO $pdo = null) {
    $pdo  = $pdo ?? api_pdo();
    $stmt = $pdo->prepare('SELECT value FROM system_settings WHERE key = :k');
    $stmt->execute([':k' => $key]);
    $raw = $stmt->fetchColumn();
    if ($raw === false || $raw === null) {
        return $default;
    }
    $decoded = json_decode((string) $raw, true);
    return $decoded === null ? $default : $decoded;
}

/**
 * Satu baris log aktivitas konsol HR. Nama aktor selalu diambil dari sesi,
 * bukan dari body, supaya log tidak bisa dipalsukan oleh klien.
 */
function api_log_activity(PDO $pdo, int $companyId, string $action, string $text): void {
    $actor = (string) (current_user()['name'] ?? 'Recruiter');
    $stmt = $pdo->prepare(
        'INSERT INTO activity_log (company_id, actor_user_id, actor, action, text)
         VALUES (:c, :u, :a, :ac, :t)'
    );
    $stmt->execute([
        ':c'  => $companyId,
        ':u'  => api_user_id(),
        ':a'  => $actor,
        ':ac' => $action,
        ':t'  => $text,
    ]);
}

/**
 * company_id milik akun HR yang sedang login.
 * Tabel company_recruiters adalah satu-satunya sumber keanggotaan, jadi
 * perusahaan tidak pernah diambil dari request.
 */
function api_hr_company(int $userId): array {
    $pdo = api_pdo();
    $stmt = $pdo->prepare(
        'SELECT cr.company_id, cr.role_label, cr.status, c.name, c.tagline,
                c.email_domain, c.location, c.description, c.logo_initial, c.status AS company_status
         FROM company_recruiters cr
         JOIN companies c ON c.id = cr.company_id
         WHERE cr.user_id = :u
         LIMIT 1'
    );
    $stmt->execute([':u' => $userId]);
    $row = $stmt->fetch();
    if (!$row) {
        api_error('Akun ini belum ditautkan ke perusahaan mana pun.', 403, [
            'hint' => 'Jalankan php tools/create_hr.php email@perusahaan "Nama" "Nama Perusahaan"',
        ]);
    }
    return $row;
}

