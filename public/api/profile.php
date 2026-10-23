<?php
// public/api/profile.php
// Profil mahasiswa: identitas, visibilitas portofolio, dan ganti password.
//
//   GET   -> { name, email, institution, ..., member_since, visibility,
//              public_url, skills[], evidence[] }
//   PATCH -> { name?, email?, institution?, program?, semester?, location?,
//              bio?, visibility? 'Public'|'Private' }
//
// Menggantikan kunci localStorage sb.profile dan sb.portfolioVisibility,
// sekaligus menghapus default palsu "Jhon Doe / Politeknik Negeri Malang"
// yang dulu membuat semua akun terlihat seperti orang yang sama.
//
// Nama dan email ditulis ke tabel users; sisanya ke student_profiles.

require_once __DIR__ . '/../../includes/api/bootstrap.php';

$method = api_method();
$pdo    = api_pdo();

function api_profile_row(PDO $pdo, int $uid): array {
    $stmt = $pdo->prepare(
        'SELECT u.name, u.email, u.created_at,
                sp.target_career_id, sp.institution, sp.program, sp.semester,
                sp.location, sp.bio, sp.photo_path, sp.is_public, sp.public_token
         FROM users u
         LEFT JOIN student_profiles sp ON sp.user_id = u.id
         WHERE u.id = :u'
    );
    $stmt->execute([':u' => $uid]);
    $row = $stmt->fetch();
    if (!$row) {
        api_error('Akun tidak ditemukan.', 404);
    }
    $public = (bool) ($row['is_public'] ?? false);
    return [
        'ok'          => true,
        'name'        => $row['name'],
        'email'       => $row['email'],
        'institution' => $row['institution'],
        'program'     => $row['program'],
        'semester'    => $row['semester'],
        'location'    => $row['location'],
        'bio'         => $row['bio'],
        'photo'       => $row['photo_path'] ? api_photo_url($uid) : null,
        // memberSince versi lama adalah teks display, bukan tanggal mentah.
        'member_since'=> $row['created_at'],
        'is_public'   => $public,
        'visibility'  => $public ? 'Public' : 'Private',
        'public_token'=> $row['public_token'],
        'target_career_id' => $row['target_career_id'] === null ? null : (int) $row['target_career_id'],
    ];
}

try {
    switch ($method) {
        case 'GET': {
            api_guard('mahasiswa');
            $data = api_profile_row($pdo, api_user_id());
            $data['skills']   = api_user_skills($pdo, api_user_id());
            $data['evidence'] = api_fetch_evidence($pdo, api_user_id());
            api_out($data);
            break;
        }

        case 'PATCH': {
            api_write_guard('mahasiswa');
            $uid  = api_user_id();
            $body = api_body();

            $name     = array_key_exists('name', $body) ? api_str($body, 'name', 100) : null;
            $email    = array_key_exists('email', $body) ? api_str($body, 'email', 150) : null;
            $bio      = array_key_exists('bio', $body) ? api_str($body, 'bio', 2000) : null;
            $fields   = [
                'institution' => array_key_exists('institution', $body) ? api_str($body, 'institution', 120) : null,
                'program'     => array_key_exists('program', $body) ? api_str($body, 'program', 120) : null,
                'semester'    => array_key_exists('semester', $body) ? api_str($body, 'semester', 20) : null,
                'location'    => array_key_exists('location', $body) ? api_str($body, 'location', 120) : null,
            ];
            $visibility = api_str($body, 'visibility', 10);
            if ($visibility !== null && !in_array($visibility, ['Public', 'Private'], true)) {
                api_error("Visibilitas hanya 'Public' atau 'Private'.", 422);
            }

            if ($name === null && $email === null && $bio === null
                && !array_filter($fields) && $visibility === null) {
                api_error('Tidak ada perubahan yang dikirim.', 422);
            }

            $pdo->beginTransaction();

            if ($name !== null || $email !== null) {
                if ($name === null) {
                    $current = $pdo->prepare('SELECT name FROM users WHERE id = :u');
                    $current->execute([':u' => $uid]);
                    $name = $current->fetchColumn();
                }
                $set = ['name' => $name];
                if ($email !== null) {
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        api_error('Format email tidak valid.', 422);
                    }
                    $taken = $pdo->prepare('SELECT id FROM users WHERE lower(email) = lower(:e) AND id <> :u');
                    $taken->execute([':e' => $email, ':u' => $uid]);
                    if ($taken->fetchColumn()) {
                        api_error('Email itu sudah dipakai akun lain.', 409);
                    }
                    $set['email'] = $email;
                }
                $cols = implode(', ', array_map(fn($k) => "{$k} = :{$k}", array_keys($set)));
                $upd = $pdo->prepare("UPDATE users SET {$cols} WHERE id = :u");
                $upd->execute($set + [':u' => $uid]);
            }

            // Baris profil bisa belum ada untuk akun lama.
            $pdo->prepare('INSERT INTO student_profiles (user_id) VALUES (:u) ON CONFLICT (user_id) DO NOTHING')
                ->execute([':u' => $uid]);

            $changes = array_filter($fields, fn($v) => $v !== null);
            if ($bio !== null) {
                $changes['bio'] = $bio;
            }
            if ($changes) {
                $cols = implode(', ', array_map(fn($k) => "{$k} = :{$k}", array_keys($changes)));
                $upd = $pdo->prepare("UPDATE student_profiles SET {$cols} WHERE user_id = :u");
                $upd->execute($changes + [':u' => $uid]);
            }

            if ($visibility !== null) {
                $isPublic = $visibility === 'Public';
                if ($isPublic) {
                    // CHECK chk_public_needs_token mewajibkan token; dibuat
                    // sekali lalu dipertahankan agar tautan lama tidak putus.
                    $upd = $pdo->prepare(
                        "UPDATE student_profiles
                            SET is_public = TRUE,
                                public_token = COALESCE(public_token, gen_random_uuid())
                          WHERE user_id = :u"
                    );
                } else {
                    $upd = $pdo->prepare('UPDATE student_profiles SET is_public = FALSE WHERE user_id = :u');
                }
                $upd->execute([':u' => $uid]);
            }

            $pdo->commit();

            $row = api_profile_row($pdo, $uid);
            // Nama/initials di header berasal dari sesi, jadi sesi ikut
            // diperbarui supaya tampilan konsisten tanpa logout.
            $_SESSION['user']['name']  = $row['name'];
            $_SESSION['user']['email'] = $row['email'];
            $_SESSION['user']['initials'] = make_initials($row['name']);
            api_out($row);
        }

        default:
            api_error('Method tidak didukung.', 405);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('api/profile.php: ' . $e->getMessage());
    api_error('Gagal menyimpan profil.', 500);
}
