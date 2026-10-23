<?php
// public/api/profile-photo.php
// Foto profil: unggah, tayang, hapus.
//
//   GET    ?user={id}  -> menyalurkan bytes gambar milik user itu saja
//   POST   multipart (field 'photo') -> simpan gambar baru
//   DELETE -> hapus gambar
//
// Menggantikan data URL di localStorage kunci sb.profile yang dulu membuat
// foto hanya ada di satu browser dan membuat ukuran profil membengkak.
//
// Penyimpanan: folder storage/ di LUAR docroot (public/), nama file
// selalu "{user_id}.{ext}" sehingga id sesi satu-satunya penentu path.
// Tidak ada path dari klien yang pernah dipakai untuk membaca file.
// Catatan deployment: sistem file container sementara; bila butuh tahan
// ulang, mount storage/ ke volume atau pindah ke Supabase Storage.

require_once __DIR__ . '/../../includes/api/bootstrap.php';

const PHOTO_MAX_BYTES = 2 * 1024 * 1024;

$method = api_method();
$pdo    = api_pdo();

$allowedTypes = [
    'image/png'  => 'png',
    'image/jpeg' => 'jpg',
    'image/webp' => 'webp',
];

/** Path absolut foto user. Nama file diturunkan dari id, bukan dari input. */
function api_photo_path(int $uid): string {
    $dir = dirname(__DIR__, 2) . '/storage';
    foreach (['png', 'jpg', 'webp'] as $ext) {
        $candidate = "{$dir}/avatar_{$uid}.{$ext}";
        if (is_file($candidate)) {
            return $candidate;
        }
    }
    return '';
}

try {
    switch ($method) {
        case 'GET': {
            $user = api_guard();
            $uid  = (int) ($_GET['user'] ?? 0);
            if ($uid !== (int) ($user['id'] ?? 0)) {
                api_error('Hanya foto milik Anda sendiri.', 403);
            }
            $path = api_photo_path($uid);
            if ($path === '') {
                api_error('Foto belum diunggah.', 404);
            }
            $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($path);
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . filesize($path));
            header('Cache-Control: private, max-age=300');
            header('X-Content-Type-Options: nosniff');
            readfile($path);
            exit;
        }

        case 'POST': {
            api_write_guard('mahasiswa');
            $uid = api_user_id();

            $file = $_FILES['photo'] ?? null;
            if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                api_error('Tidak ada berkas gambar yang diunggah.', 422);
            }
            if ($file['size'] > PHOTO_MAX_BYTES) {
                api_error('Ukuran gambar maksimal 2 MB.', 422);
            }
            $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $ext  = $allowedTypes[$mime] ?? null;
            if ($ext === null) {
                api_error('Format gambar harus PNG, JPG, atau WEBP.', 422);
            }
            // getimagesize menolak file yang kebetulan ber-awalan magic byte
            // tapi bukan gambar utuh.
            if (getimagesize($file['tmp_name']) === false) {
                api_error('Berkas bukan gambar yang valid.', 422);
            }

            $dir = dirname(__DIR__, 2) . '/storage';
            if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                api_error('Folder penyimpanan tidak dapat dibuat.', 500);
            }
            // Bersihkan varian ekstensi lama milik user yang sama.
            foreach (['png', 'jpg', 'webp'] as $old) {
                $stale = "{$dir}/avatar_{$uid}.{$old}";
                if (is_file($stale)) {
                    unlink($stale);
                }
            }

            $target = "{$dir}/avatar_{$uid}.{$ext}";
            if (!move_uploaded_file($file['tmp_name'], $target)) {
                api_error('Gagal menyimpan gambar.', 500);
            }
            chmod($target, 0644);

            $pdo->prepare('INSERT INTO student_profiles (user_id, photo_path)
                            VALUES (:u, :p)
                            ON CONFLICT (user_id) DO UPDATE SET photo_path = EXCLUDED.photo_path')
                ->execute([':u' => $uid, ':p' => 'storage/avatar_' . $uid . '.' . $ext]);

            api_out(['ok' => true, 'photo' => api_photo_url($uid)], 201);
            break;
        }

        case 'DELETE': {
            api_write_guard('mahasiswa');
            $uid = api_user_id();
            $path = api_photo_path($uid);
            if ($path !== '') {
                unlink($path);
            }
            $pdo->prepare('UPDATE student_profiles SET photo_path = NULL WHERE user_id = :u')
                ->execute([':u' => $uid]);
            api_out(['ok' => true, 'photo' => null]);
            break;
        }

        default:
            api_error('Method tidak didukung.', 405);
    }
} catch (PDOException $e) {
    error_log('api/profile-photo.php: ' . $e->getMessage());
    api_error('Gagal memproses foto.', 500);
}
