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
        'initials'    => make_initials($row['name']),
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

/**
 * String profil: null = tidak dikirim, '' = dikirim kosong (hapus nilai).
 * api_str() tidak dipakai di sini karena mengubah '' menjadi null, sehingga
 * kolom opsional tidak pernah bisa dikosongkan lewat modal edit.
 */
function api_profile_str(array $body, string $key, int $maxLen): ?string {
    if (!array_key_exists($key, $body) || $body[$key] === null) {
        return null;
    }
    $value = trim((string) $body[$key]);
    if (mb_strlen($value) > $maxLen) {
        api_error("Kolom '{$key}' terlalu panjang (maks {$maxLen} karakter).", 422);
    }
    return $value;
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

            $name  = api_profile_str($body, 'name', 100);
            $email = api_profile_str($body, 'email', 150);
            $bio   = api_profile_str($body, 'bio', 2000);

            // Nama dan email wajib; kolom lain boleh dikosongkan.
            if ($name === '') {
                api_error('Nama wajib diisi.', 422);
            }
            if ($email === '') {
                api_error('Email wajib diisi.', 422);
            }

            $fields = [
                'institution' => api_profile_str($body, 'institution', 120),
                'program'     => api_profile_str($body, 'program', 120),
                'semester'    => api_profile_str($body, 'semester', 20),
                'location'    => api_profile_str($body, 'location', 120),
            ];
            $visibility = api_str($body, 'visibility', 10);
            if ($visibility !== null && !in_array($visibility, ['Public', 'Private'], true)) {
                api_error("Visibilitas hanya 'Public' atau 'Private'.", 422);
            }

            if ($name === null && $email === null && $bio === null
                && !array_filter($fields, fn($v) => $v !== null) && $visibility === null) {
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

                $currentStmt = $pdo->prepare('SELECT is_public FROM student_profiles WHERE user_id = :u');
                $currentStmt->execute([':u' => $uid]);
                $wasPublic = (bool) $currentStmt->fetchColumn();

                if ($isPublic && !$wasPublic) {
                    // Private -> Public: rotasi token agar tautan lama mati.
                    $upd = $pdo->prepare(
                        'UPDATE student_profiles
                            SET is_public = TRUE,
                                public_token = gen_random_uuid()
                          WHERE user_id = :u'
                    );
                } elseif ($isPublic) {
                    $upd = $pdo->prepare(
                        'UPDATE student_profiles
                            SET is_public = TRUE,
                                public_token = COALESCE(public_token, gen_random_uuid())
                          WHERE user_id = :u'
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
            $_SESSION['user']['initials'] = $row['initials'];
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
