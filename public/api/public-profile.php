<?php
// public/api/public-profile.php
// Profil publik berbasis token. Tidak perlu login.
//
//   GET ?token=<uuid> -> { profile }
//
// Token invalid, profil private, akun nonaktif, dan profil yang tidak ada
// semuanya menghasilkan 404 yang sama. Respons tidak memuat surel, id,
// token, status akun, atau path file.

require_once __DIR__ . '/../../includes/api/bootstrap.php';

if (api_method() !== 'GET') {
    api_error('Method tidak didukung.', 405);
}

$token = trim((string) ($_GET['token'] ?? ''));
if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $token)) {
    api_error('Profil tidak ditemukan atau tidak lagi publik.', 404);
}

$pdo = api_pdo();

try {
    $stmt = $pdo->prepare(
        'SELECT id, name, public_token, institution, program, semester,
                location, bio, photo_path, member_since, target_role
         FROM v_public_profiles
         WHERE public_token = :t
         LIMIT 1'
    );
    $stmt->execute([':t' => $token]);
    $row = $stmt->fetch();
    if (!$row) {
        api_error('Profil tidak ditemukan atau tidak lagi publik.', 404);
    }

    $uid = (int) $row['id'];

    $skillStmt = $pdo->prepare(
        "SELECT skill_name, skill_category, evidence_count
         FROM v_user_skill_evidence
         WHERE user_id = :u AND status = 'confirmed'
         ORDER BY skill_category NULLS LAST, skill_name"
    );
    $skillStmt->execute([':u' => $uid]);
    $skills = [];
    foreach ($skillStmt as $skill) {
        $skills[] = [
            'name'           => $skill['skill_name'],
            'category'       => $skill['skill_category'],
            'evidence_count' => (int) $skill['evidence_count'],
        ];
    }

    $evidenceStmt = $pdo->prepare('SELECT COUNT(*) FROM evidence WHERE user_id = :u');
    $evidenceStmt->execute([':u' => $uid]);

    $tokenValue = (string) $row['public_token'];
    api_out([
        'ok' => true,
        'profile' => [
            'name'           => $row['name'],
            'initials'       => make_initials($row['name']),
            'photo_url'      => $row['photo_path'] !== null && api_photo_path($uid) !== ''
                ? api_public_photo_url($tokenValue) : null,
            'institution'    => $row['institution'],
            'program'        => $row['program'],
            'semester'       => $row['semester'],
            'location'       => $row['location'],
            'bio'            => $row['bio'],
            'member_since'   => $row['member_since'],
            'target_role'    => $row['target_role'],
            'skills'         => $skills,
            'evidence_count' => (int) $evidenceStmt->fetchColumn(),
        ],
    ]);
} catch (PDOException $e) {
    error_log('api/public-profile.php: ' . $e->getMessage());
    api_error('Gagal memuat profil publik.', 500);
}
