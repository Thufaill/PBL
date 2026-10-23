<?php
// public/api/target.php
// Target karier aktif mahasiswa.
//
//   GET  -> { target_career_id, career }
//   POST -> { career_id } lalu profil diarahkan ke karier itu
//
// Dulu nilainya hanya hidup di localStorage kunci sb.targetCareer, jadi
// pindah browser berarti target hilang dan halaman Skill Gap kosong.

require_once __DIR__ . '/../../includes/api/bootstrap.php';

$method = api_method();
$pdo    = api_pdo();

/** Pastikan baris profil ada; mahasiswa lama bisa belum punya row. */
function api_ensure_profile(PDO $pdo, int $uid): void {
    $stmt = $pdo->prepare(
        'INSERT INTO student_profiles (user_id) VALUES (:u) ON CONFLICT (user_id) DO NOTHING'
    );
    $stmt->execute([':u' => $uid]);
}

function api_career_shape(PDO $pdo, int $careerId): ?array {
    $stmt = $pdo->prepare('SELECT id, name, description FROM careers WHERE id = :id');
    $stmt->execute([':id' => $careerId]);
    $career = $stmt->fetch();
    if (!$career) {
        return null;
    }
    $skills = $pdo->prepare(
        'SELECT s.name FROM career_skill_requirements csr
         JOIN skills s ON s.id = csr.skill_id
         WHERE csr.career_id = :id ORDER BY s.name'
    );
    $skills->execute([':id' => $careerId]);
    $career['id']     = (int) $career['id'];
    $career['skills'] = $skills->fetchAll(PDO::FETCH_COLUMN);
    return $career;
}

try {
    switch ($method) {
        case 'GET': {
            api_guard('mahasiswa');
            $uid = api_user_id();
            $stmt = $pdo->prepare('SELECT target_career_id FROM student_profiles WHERE user_id = :u');
            $stmt->execute([':u' => $uid]);
            $targetId = $stmt->fetchColumn();
            $career = $targetId ? api_career_shape($pdo, (int) $targetId) : null;
            api_out([
                'ok'               => true,
                'target_career_id' => $targetId ? (int) $targetId : null,
                'career'           => $career,
            ]);
            break;
        }

        case 'POST': {
            api_write_guard('mahasiswa');
            $uid  = api_user_id();
            $body = api_body();
            $careerId = api_int($body, 'career_id') ?? api_int($body, 'target');
            if ($careerId === null) {
                api_error('Butuh career_id.', 422);
            }
            $career = api_career_shape($pdo, $careerId);
            if (!$career) {
                api_error('Karier itu tidak ada.', 404);
            }

            api_ensure_profile($pdo, $uid);
            $stmt = $pdo->prepare(
                'UPDATE student_profiles SET target_career_id = :c WHERE user_id = :u'
            );
            $stmt->execute([':c' => $careerId, ':u' => $uid]);

            api_out(['ok' => true, 'target_career_id' => $careerId, 'career' => $career]);
            break;
        }

        default:
            api_error('Method tidak didukung.', 405);
    }
} catch (PDOException $e) {
    error_log('api/target.php: ' . $e->getMessage());
    api_error('Gagal menyimpan target karier.', 500);
}
