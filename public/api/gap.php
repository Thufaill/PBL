<?php
// public/api/gap.php
// Skill gap deskriptif untuk target karier aktif.
//
// Membaca v_skill_gap: satu baris per skill yang dibutuhkan karier,
// dengan has_skill TRUE bila user sudah memilikinya (status confirmed).
// Bentuk keluaran disengaja sama dengan getSkillGap() lama di api.js:
// { career, have[], explore[] } — dua daftar, tanpa skor atau persentase
// kesiapan (Keputusan G01 di Laporan/PEMETAAN_PROYEK.md).

require_once __DIR__ . '/../../includes/api/bootstrap.php';

if (api_method() !== 'GET') {
    api_error('Method tidak didukung.', 405);
}

api_guard('mahasiswa');
$uid = api_user_id();
$pdo = api_pdo();

try {
    $stmt = $pdo->prepare(
        'SELECT g.career_id, c.name AS career_name, c.description AS career_description,
                g.skill_id, g.skill_name, s.category, g.is_mandatory, g.has_skill
         FROM v_skill_gap g
         JOIN careers c ON c.id = g.career_id
         LEFT JOIN skills s ON s.id = g.skill_id
         WHERE g.user_id = :u
         ORDER BY g.has_skill DESC, g.skill_name'
    );
    $stmt->execute([':u' => $uid]);
    $rows = $stmt->fetchAll();

    if (!$rows) {
        api_out(['ok' => true, 'career' => null, 'have' => [], 'explore' => []]);
    }

    $careerId = (int) $rows[0]['career_id'];
    $counts   = [];
    foreach (api_user_skills($pdo, $uid) as $owned) {
        $counts[$owned['skill_id']] = $owned['evidence_count'];
    }

    $have    = [];
    $explore = [];
    foreach ($rows as $row) {
        $skill = [
            'id'       => (int) $row['skill_id'],
            'name'     => $row['skill_name'],
            'category' => $row['category'],
        ];
        if ($row['has_skill']) {
            $skill['evidence_count'] = $counts[(int) $row['skill_id']] ?? 0;
            $have[] = $skill;
        } else {
            $explore[] = $skill;
        }
    }

    api_out([
        'ok'     => true,
        'career' => [
            'id'          => $careerId,
            'name'        => $rows[0]['career_name'],
            'description' => $rows[0]['career_description'],
        ],
        'have'    => $have,
        'explore' => $explore,
    ]);
} catch (PDOException $e) {
    error_log('api/gap.php: ' . $e->getMessage());
    api_error('Gagal menghitung skill gap.', 500);
}
