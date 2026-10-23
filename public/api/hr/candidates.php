<?php
// public/api/hr/candidates.php
// Pipeline kandidat untuk perusahaan yang sedang login.
//
//   GET   -> [{ id, name, email, target_role, institution, location,
//              skills:[nama], matched_skills:[], gap_skills:[],
//              evidence_count, review_status, note, vacancy_id }]
//   GET ?role={vacancy_id}  mempersempit dan mengisi matched/gap per role
//   PATCH -> { id, status?, note?, vacancy_id? }  (upsert candidate_reviews)
//
// Sumber kandidat adalah v_public_candidates: HANYA profil mahasiswa yang
// menekan "Public" di layarnya sendiri. Tidak ada daftar PII yang bisa
// diambil seperti berkas recruiter-candidates.json dulu.
//
// Status review memakai nilai candidate_reviews (new/reviewing/interview/
// offered/rejected/hired), bukan localStorage.

require_once __DIR__ . '/../../../includes/api/bootstrap.php';

const REVIEW_STATUSES = ['new', 'reviewing', 'interview', 'offered', 'rejected', 'hired'];

$method = api_method();
$pdo    = api_pdo();

try {
    switch ($method) {
        case 'GET': {
            api_guard('hr');
            $link  = api_hr_company(api_user_id());
            $cid   = (int) $link['company_id'];
            $roleParam = api_int($_GET, 'role') ?? api_int($_GET, 'vacancy_id');

            $sql =
                "SELECT pc.id, pc.name, pc.email, pc.target_role, pc.institution, pc.location,
                        COALESCE(
                            (SELECT json_agg(s.name ORDER BY s.name)
                               FROM user_skills us
                               JOIN skills s ON s.id = us.skill_id
                              WHERE us.user_id = pc.id AND us.status = 'confirmed'),
                            '[]'::json) AS skills,
                        (SELECT COUNT(*) FROM evidence ev WHERE ev.user_id = pc.id) AS evidence_count
                 FROM v_public_candidates pc
                 ORDER BY pc.name";
            $rows = $pdo->query($sql)->fetchAll();

            // Skill milik tiap kandidat sebagai himpunan, untuk matched/gap.
            $byUser = [];
            foreach ($rows as $r) {
                $byUser[(int) $r['id']] = [
                    'skill_names' => json_decode((string) $r['skills'], true) ?: [],
                ];
            }

            $roleSkills = [];
            if ($roleParam !== null) {
                $check = $pdo->prepare('SELECT title FROM vacancies WHERE id = :v AND company_id = :c');
                $check->execute([':v' => $roleParam, ':c' => $cid]);
                if ($check->fetchColumn() === false) {
                    api_error('Role itu bukan milik perusahaan Anda.', 404);
                }
                $stmt = $pdo->prepare(
                    'SELECT s.id, s.name FROM vacancy_skills vs
                     JOIN skills s ON s.id = vs.skill_id
                     WHERE vs.vacancy_id = :v ORDER BY s.name'
                );
                $stmt->execute([':v' => $roleParam]);
                $roleSkills = $stmt->fetchAll();
            }

            // Status review tersimpan perusahaan ini (untuk role yang diminta,
            // atau lintas role bila parameter role tidak dikirim).
            $reviews = [];
            if ($roleParam !== null) {
                $stmt = $pdo->prepare(
                    'SELECT user_id, status, note, vacancy_id, updated_at
                     FROM candidate_reviews WHERE company_id = :c AND vacancy_id = :v'
                );
                $stmt->execute([':c' => $cid, ':v' => $roleParam]);
            } else {
                $stmt = $pdo->prepare(
                    'SELECT user_id, status, note, vacancy_id, updated_at
                     FROM candidate_reviews WHERE company_id = :c'
                );
                $stmt->execute([':c' => $cid]);
            }
            foreach ($stmt as $rev) {
                $reviews[(int) $rev['user_id']] = $rev;
            }

            $roleSkillNames = array_column($roleSkills, 'name');
            $out = [];
            foreach ($rows as $r) {
                $uid = (int) $r['id'];
                $owned = $byUser[$uid]['skill_names'];
                if ($roleParam !== null) {
                    $matched = array_values(array_intersect($roleSkillNames, $owned));
                    $gap     = array_values(array_diff($roleSkillNames, $owned));
                } else {
                    $matched = $owned;
                    $gap     = [];
                }
                $rev = $reviews[$uid] ?? [];
                $revVacancy = $rev['vacancy_id'] ?? null;
                $out[] = [
                    'id'             => $uid,
                    'name'           => $r['name'],
                    'email'          => $r['email'],
                    'target_role'    => $r['target_role'],
                    'institution'    => $r['institution'],
                    'location'       => $r['location'],
                    'skills'         => $owned,
                    'matched_skills' => $matched,
                    'gap_skills'     => $gap,
                    'evidence_count' => (int) $r['evidence_count'],
                    'review_status'  => $rev['status'] ?? 'new',
                    'note'           => $rev['note'] ?? null,
                    'vacancy_id'     => $revVacancy === null ? null : (int) $revVacancy,
                    'reviewed_at'    => $rev['updated_at'] ?? null,
                ];
            }

            api_out([
                'ok'         => true,
                'candidates' => $out,
                'role_id'    => $roleParam,
                'role_skills'=> $roleSkillNames,
                // Konsisten dengan kontrak lama: tanpa opt-in publik, daftar kosong.
                'source'     => 'v_public_candidates',
            ]);
            break;
        }

        case 'PATCH': {
            api_write_guard('hr');
            $link = api_hr_company(api_user_id());
            $cid  = (int) $link['company_id'];
            $body = api_body();
            $id   = api_int($body, 'id');
            if ($id === null) {
                api_error('Butuh id kandidat.', 422);
            }
            $status = api_str($body, 'status', 20);
            if ($status === null) {
                api_error('Butuh status review.', 422);
            }
            if (!in_array($status, REVIEW_STATUSES, true)) {
                api_error('Status review tidak dikenal.', 422, ['allowed' => REVIEW_STATUSES]);
            }
            $note = array_key_exists('note', $body) ? api_str($body, 'note', 2000) : null;
            $vacancyId = api_int($body, 'vacancy_id');

            // Kandidat harus memang muncul sebagai profil publik; tanpa ini,
            // recruiter bisa mencatat review untuk akun mana pun asal id.
            $public = $pdo->prepare('SELECT name FROM v_public_candidates WHERE id = :u');
            $public->execute([':u' => $id]);
            $candName = $public->fetchColumn();
            if ($candName === false) {
                api_error('Kandidat itu tidak ada di daftar profil publik.', 404);
            }

            if ($vacancyId !== null) {
                $check = $pdo->prepare('SELECT 1 FROM vacancies WHERE id = :v AND company_id = :c');
                $check->execute([':v' => $vacancyId, ':c' => $cid]);
                if (!$check->fetchColumn()) {
                    api_error('Role itu bukan milik perusahaan Anda.', 404);
                }
            }

            $stmt = $pdo->prepare(
                'INSERT INTO candidate_reviews (company_id, user_id, vacancy_id, status, note, updated_by)
                 VALUES (:c, :u, :v, :s, :n, :by)
                 ON CONFLICT (company_id, user_id, COALESCE(vacancy_id, 0))
                 DO UPDATE SET status = EXCLUDED.status,
                               note = EXCLUDED.note,
                               updated_by = EXCLUDED.updated_by,
                               updated_at = CURRENT_TIMESTAMP'
            );
            $stmt->execute([
                ':c' => $cid,
                ':u' => $id,
                ':v' => $vacancyId,
                ':s' => $status,
                ':n' => $note,
                ':by'=> api_user_id(),
            ]);

            api_log_activity($pdo, $cid, 'interview', "memindahkan {$candName} ke status {$status}");
            api_out(['ok' => true, 'id' => $id, 'status' => $status]);
            break;
        }

        default:
            api_error('Method tidak didukung.', 405);
    }
} catch (PDOException $e) {
    error_log('api/hr/candidates.php: ' . $e->getMessage());
    api_error('Gagal memproses kandidat.', 500);
}
