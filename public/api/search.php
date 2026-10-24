<?php
// public/api/search.php
// Pencarian profil publik untuk semua user yang login.
//
//   GET ?q=<query> -> { query, total, truncated, profiles[] }
//
// Hanya membaca v_public_profiles (aktif + Public). Respons tidak pernah
// memuat alamat surel, id pengguna, atau path file.

require_once __DIR__ . '/../../includes/api/bootstrap.php';

if (api_method() !== 'GET') {
    api_error('Method tidak didukung.', 405);
}

api_guard();
$pdo = api_pdo();

$query = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($query) < 2) {
    api_error('Kata kunci minimal 2 karakter.', 422);
}
if (mb_strlen($query) > 100) {
    api_error('Kata kunci maksimal 100 karakter.', 422);
}

try {
    // Escape wildcard agar input pengguna diperlakukan literal.
    $needle = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query);
    $like   = '%' . $needle . '%';

    $sql = "SELECT p.id, p.public_token, p.name, p.institution, p.program,
                   p.semester, p.location, p.target_role, p.photo_path,
                   COUNT(*) OVER() AS total_count
            FROM v_public_profiles p
            WHERE p.name ILIKE :q1 ESCAPE '\\'
               OR p.institution ILIKE :q2 ESCAPE '\\'
               OR p.program ILIKE :q3 ESCAPE '\\'
               OR p.location ILIKE :q4 ESCAPE '\\'
               OR p.target_role ILIKE :q5 ESCAPE '\\'
               OR EXISTS (
                    SELECT 1
                    FROM user_skills us
                    JOIN skills s ON s.id = us.skill_id
                    WHERE us.user_id = p.id
                      AND us.status = 'confirmed'
                      AND s.name ILIKE :q6 ESCAPE '\\'
               )
            ORDER BY p.name
            LIMIT 30";
    $stmt = $pdo->prepare($sql);
    for ($i = 1; $i <= 6; $i++) {
        $stmt->bindValue(':q' . $i, $like, PDO::PARAM_STR);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $total = $rows ? (int) $rows[0]['total_count'] : 0;
    $ids = [];
    $profiles = [];
    foreach ($rows as $row) {
        $token = (string) $row['public_token'];
        $uid   = (int) $row['id'];
        $ids[] = $uid;
        $profiles[] = [
            'token'          => $token,
            'name'           => $row['name'],
            'initials'       => make_initials($row['name']),
            'photo_url'      => $row['photo_path'] !== null && api_photo_path($uid) !== ''
                ? api_public_photo_url($token) : null,
            'profile_url'    => api_public_profile_url($token),
            'institution'    => $row['institution'],
            'program'        => $row['program'],
            'semester'       => $row['semester'],
            'location'       => $row['location'],
            'target_role'    => $row['target_role'],
            'skills'         => [],
            'evidence_count' => 0,
        ];
    }

    if ($ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));

        $skillStmt = $pdo->prepare(
            "SELECT us.user_id, s.name
             FROM user_skills us
             JOIN skills s ON s.id = us.skill_id
             WHERE us.user_id IN ({$in})
               AND us.status = 'confirmed'
             ORDER BY s.name"
        );
        foreach ($ids as $i => $uid) {
            $skillStmt->bindValue($i + 1, $uid, PDO::PARAM_INT);
        }
        $skillStmt->execute();
        $skillsByUser = [];
        foreach ($skillStmt as $skill) {
            $skillsByUser[(int) $skill['user_id']][] = $skill['name'];
        }

        $evidenceStmt = $pdo->prepare(
            "SELECT user_id, COUNT(*) AS total
             FROM evidence
             WHERE user_id IN ({$in})
             GROUP BY user_id"
        );
        foreach ($ids as $i => $uid) {
            $evidenceStmt->bindValue($i + 1, $uid, PDO::PARAM_INT);
        }
        $evidenceStmt->execute();
        $evidenceByUser = [];
        foreach ($evidenceStmt as $item) {
            $evidenceByUser[(int) $item['user_id']] = (int) $item['total'];
        }

        foreach ($profiles as $i => $profile) {
            $uid = $ids[$i];
            $profiles[$i]['skills'] = $skillsByUser[$uid] ?? [];
            $profiles[$i]['evidence_count'] = $evidenceByUser[$uid] ?? 0;
        }
    }

    api_out([
        'ok'        => true,
        'query'     => $query,
        'total'     => $total,
        'truncated' => $total > 30,
        'profiles'  => $profiles,
    ]);
} catch (PDOException $e) {
    error_log('api/search.php: ' . $e->getMessage());
    api_error('Gagal mencari profil.', 500);
}
