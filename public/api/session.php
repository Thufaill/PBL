<?php
// public/api/session.php
// GET snapshot sekali jalan untuk seluruh halaman mahasiswa.
//
// Kenapa satu endpoint: gateway klien (public/js/api.js) punya fungsi baca
// SINCHRON (getMySkills(), getEvidence(), getTargetId()) yang dipakai
// dashboard.php, skills.php, career.php dan profile.php tanpa
// await. Snapshot ini diisi oleh panggilan async pertama di tiap halaman,
// sehingga fungsi sinkron itu tetap sah tanpa menyimpan apa pun di browser.
//
// Semua baris difilter user_id dari sesi.

require_once __DIR__ . '/../../includes/api/bootstrap.php';

if (api_method() !== 'GET') {
    api_error('Method tidak didukung.', 405);
}

$user = api_guard('mahasiswa');
$uid  = api_user_id();
$pdo  = api_pdo();

try {
    $profile = $pdo->prepare(
        'SELECT sp.target_career_id, sp.institution, sp.program, sp.semester,
                sp.location, sp.bio, sp.photo_path, sp.is_public, sp.public_token
         FROM student_profiles sp
         WHERE sp.user_id = :u'
    );
    $profile->execute([':u' => $uid]);
    $profile = $profile->fetch() ?: [
        'target_career_id' => null, 'institution' => null, 'program' => null,
        'semester' => null, 'location' => null, 'bio' => null,
        'photo_path' => null, 'is_public' => false, 'public_token' => null,
    ];

    $skillRows = api_user_skills($pdo, $uid);

    $evidenceRows = api_fetch_evidence($pdo, $uid);

    api_out([
        'ok'               => true,
        'user'             => $user,
        'target_career_id' => $profile['target_career_id'] === null ? null : (int) $profile['target_career_id'],
        'skills'           => $skillRows,
        'evidence'         => array_values($evidenceRows),
        'profile'          => [
            'name'         => $user['name'] ?? null,
            'email'        => $user['email'] ?? null,
            'role_label'   => $user['role_label'] ?? 'Student',
            'initials'     => $user['initials'] ?? 'SB',
            'institution'  => $profile['institution'],
            'program'      => $profile['program'],
            'semester'     => $profile['semester'],
            'location'     => $profile['location'],
            'bio'          => $profile['bio'],
            // URL, bukan photo_path mentah: path storage/ ada di luar docroot
            // dan tidak boleh bocor ke klien.
            'photo'        => $profile['photo_path'] ? api_photo_url($uid) : null,
            'is_public'    => (bool) $profile['is_public'],
            'public_token' => $profile['public_token'],
        ],
    ]);
} catch (PDOException $e) {
    error_log('api/session.php: ' . $e->getMessage());
    api_error('Gagal memuat data profil.', 500);
}
