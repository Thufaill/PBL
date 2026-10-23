<?php
// public/api/skills.php
// Skill milik mahasiswa + pembuatan skill baru di katalog.
//
//   GET    -> daftar skill milik user, dengan evidence_count dan label
//   POST   -> { skill_id }            : tambahkan skill katalog ke profil
//             { name, category }      : buat skill baru lalu tambahkan
//   PATCH  -> { skill_id, status }    : konfirmasi skill hasil ekstraksi CV
//   DELETE-> { skill_id }            : lepas skill dari profil
//
// user_id selalu dari sesi. Skill yang belum ada di tabel skills dibuat
// lebih dulu karena user_skills.skill_id adalah foreign key ke katalog
// master — inilah alasan addCustomSkill() di JS dulu hanya hidup di
// localStorage.

require_once __DIR__ . '/../../includes/api/bootstrap.php';

$method = api_method();
$pdo    = api_pdo();

try {
    switch ($method) {
        case 'GET': {
            api_guard('mahasiswa');
            api_out(['ok' => true, 'skills' => api_user_skills($pdo, api_user_id())]);
            break;
        }

        case 'POST': {
            api_write_guard('mahasiswa');
            $uid   = api_user_id();
            $body  = api_body();
            $skillId = api_int($body, 'skill_id');

            if ($skillId === null) {
                $name = api_str($body, 'name', 100);
                if ($name === null) {
                    api_error('Kirim skill_id atau name.', 422);
                }
                $category = api_str($body, 'category', 50) ?? 'Programming';

                // Cari lebih dulu dengan pembandingan case-insensitive:
                // UNIQUE(name) di PostgreSQL peka huruf besar-kecil, jadi
                // tanpa langkah ini "php" dan "PHP" jadi dua baris.
                $find = $pdo->prepare('SELECT id, name, category FROM skills WHERE lower(name) = lower(:n) LIMIT 1');
                $find->execute([':n' => $name]);
                $skill = $find->fetch();

                if (!$skill) {
                    $insert = $pdo->prepare(
                        'INSERT INTO skills (name, category) VALUES (:n, :c)
                         ON CONFLICT (name) DO UPDATE SET name = EXCLUDED.name
                         RETURNING id, name, category'
                    );
                    $insert->execute([':n' => $name, ':c' => $category]);
                    $skill = $insert->fetch();
                }
                $skillId = (int) $skill['id'];
            } else {
                $check = $pdo->prepare('SELECT id, name, category FROM skills WHERE id = :id');
                $check->execute([':id' => $skillId]);
                $skill = $check->fetch();
                if (!$skill) {
                    api_error('Skill tidak ada di katalog.', 404);
                }
            }

            $own = $pdo->prepare(
                'INSERT INTO user_skills (user_id, skill_id, source, status)
                 VALUES (:u, :s, :source, :status)
                 ON CONFLICT (user_id, skill_id) DO UPDATE
                   SET status = :status2'
            );
            // Skill hasil CV tetap 'candidate' sampai user mengonfirmasi;
            // skill yang dipilih manual langsung 'confirmed'.
            $source = ($body['source'] ?? 'manual') === 'cv_extracted' ? 'cv_extracted' : 'manual';
            $status = $source === 'cv_extracted' ? 'candidate' : 'confirmed';
            $own->execute([
                ':u' => $uid, ':s' => $skillId,
                ':source' => $source, ':status' => $status, ':status2' => $status,
            ]);

            api_out(['ok' => true, 'skill' => $skill, 'skills' => api_user_skills($pdo, $uid)], 201);
        }

        case 'PATCH': {
            api_write_guard('mahasiswa');
            $uid   = api_user_id();
            $body  = api_body();
            $skillId = api_int($body, 'skill_id');
            $status  = api_str($body, 'status', 20);
            if ($skillId === null || $status === null) {
                api_error('Butuh skill_id dan status.', 422);
            }
            if (!in_array($status, ['candidate', 'confirmed'], true)) {
                api_error('Status tidak dikenal.', 422);
            }
            $stmt = $pdo->prepare(
                'UPDATE user_skills SET status = :status
                 WHERE user_id = :u AND skill_id = :s'
            );
            $stmt->execute([':status' => $status, ':u' => $uid, ':s' => $skillId]);
            if ($stmt->rowCount() === 0) {
                api_error('Skill itu tidak ada di profil Anda.', 404);
            }
            api_out(['ok' => true, 'skills' => api_user_skills($pdo, $uid)]);
            break;
        }

        case 'DELETE': {
            api_write_guard('mahasiswa');
            $uid  = api_user_id();
            $body = api_body();
            $skillId = api_int($body, 'skill_id');
            if ($skillId === null) {
                $skillId = api_int($_GET, 'skill_id');
            }
            if ($skillId === null) {
                api_error('Butuh skill_id.', 422);
            }
            $pdo->beginTransaction();
            // Lepas juga tautan bukti ke skill ini supaya tidak ada
            // evidence_skills yang menunjuk skill di luar profil user.
            $unlink = $pdo->prepare(
                'DELETE FROM evidence_skills
                 WHERE skill_id = :s
                   AND evidence_id IN (SELECT id FROM evidence WHERE user_id = :u)'
            );
            $unlink->execute([':s' => $skillId, ':u' => $uid]);
            $drop = $pdo->prepare('DELETE FROM user_skills WHERE user_id = :u AND skill_id = :s');
            $drop->execute([':u' => $uid, ':s' => $skillId]);
            $pdo->commit();
            api_out(['ok' => true, 'skills' => api_user_skills($pdo, $uid)]);
            break;
        }

        default:
            api_error('Method tidak didukung.', 405);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('api/skills.php: ' . $e->getMessage());
    api_error('Gagal menyimpan skill.', 500);
}
