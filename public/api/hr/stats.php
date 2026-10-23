<?php
// public/api/hr/stats.php
// Kartu statistik dashboard perusahaan (semuanya dihitung dari DB).
//
//   GET -> { ok, teamSize, activeRecruiters, openRoles, totalPositions,
//            pipelineCandidates, mappedSkills }
//
// 'Skill Terpetakan' dulu angka "15" hardcoded di recruiter.js; kini jumlah
// skill unik yang benar-benar dipakai di role perusahaan ini.

require_once __DIR__ . '/../../../includes/api/bootstrap.php';

api_guard('hr');
$pdo = api_pdo();
$cid = (int) api_hr_company(api_user_id())['company_id'];

try {
    // Placeholder berbeda per kemunculan: dengan prepared statement native
    // (DB_EMULATE_PREPARES != 1) PDO tidak mengizinkan nama dipakai ulang.
    $stmt = $pdo->prepare(
        "SELECT
            (SELECT COUNT(*) FROM company_recruiters WHERE company_id = :c1) AS team_size,
            (SELECT COUNT(*) FROM company_recruiters WHERE company_id = :c2 AND status = 'active') AS active_recruiters,
            (SELECT COUNT(*) FROM vacancies WHERE company_id = :c3 AND status = 'open') AS open_roles,
            (SELECT COALESCE(SUM(positions), 0) FROM vacancies WHERE company_id = :c4 AND status = 'open') AS total_positions,
            (SELECT COUNT(DISTINCT user_id) FROM candidate_reviews WHERE company_id = :c5) AS pipeline_candidates,
            (SELECT COUNT(DISTINCT vs.skill_id)
               FROM vacancy_skills vs
               JOIN vacancies v ON v.id = vs.vacancy_id
              WHERE v.company_id = :c6) AS mapped_skills"
    );
    $stmt->execute([
        ':c1' => $cid, ':c2' => $cid, ':c3' => $cid,
        ':c4' => $cid, ':c5' => $cid, ':c6' => $cid,
    ]);
    $row = $stmt->fetch();

    api_out([
        'ok'                 => true,
        'teamSize'           => (int) $row['team_size'],
        'activeRecruiters'   => (int) $row['active_recruiters'],
        'openRoles'          => (int) $row['open_roles'],
        'totalPositions'     => (int) $row['total_positions'],
        'pipelineCandidates' => (int) $row['pipeline_candidates'],
        'mappedSkills'       => (int) $row['mapped_skills'],
    ]);
} catch (PDOException $e) {
    error_log('api/hr/stats.php: ' . $e->getMessage());
    api_error('Gagal memuat statistik perusahaan.', 500);
}
