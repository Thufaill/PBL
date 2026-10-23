<?php
// public/api/evidence.php
// Bukti (evidence) milik mahasiswa: daftar, tambah, hapus.
//
//   GET    -> semua bukti milik user, lengkap dengan skill_ids dan namanya
//   POST   -> { source_type, title, description, file_url, skill_ids[], metadata }
//   DELETE -> { id }
//
// Ini endpoint yang mengganti fungsi addEvidence()/removeEvidence() yang
// sebelumnya hanya menulis ke localStorage. Relasi bukti-skill ditulis ke
// evidence_skills, jadi evidence_count di halaman Skills dan Skill Gap
// akhirnya punya angka nyata (sebelumnya selalu 0).
//
// Batas jumlah bukti dibaca dari pengaturan admin (system_settings
// 'max_evidence'), bukan dari hardcoded JS.

require_once __DIR__ . '/../../includes/api/bootstrap.php';

const EVIDENCE_MAX = 6;

$method = api_method();
$pdo    = api_pdo();

try {
    switch ($method) {
        case 'GET': {
            api_guard('mahasiswa');
            api_out(['ok' => true, 'evidence' => api_fetch_evidence($pdo, api_user_id())]);
            break;
        }

        case 'POST': {
            api_write_guard('mahasiswa');
            $uid  = api_user_id();
            $body = api_body();

            $source  = api_str($body, 'source_type', 20) ?? 'project';
            $allowed = ['cv', 'github', 'project', 'portfolio', 'certificate', 'assessment'];
            if (!in_array($source, $allowed, true)) {
                api_error('Jenis bukti tidak dikenal.', 422, ['allowed' => $allowed]);
            }

            $title = api_str($body, 'title', 150);
            if ($title === null) {
                api_error('Judul bukti wajib diisi.', 422);
            }
            $description = api_str($body, 'description', 5000);

            $fileUrl = api_str($body, 'file_url', 500);
            if ($fileUrl !== null) {
                $scheme = strtolower((string) parse_url($fileUrl, PHP_URL_SCHEME));
                if ($scheme !== '' && !in_array($scheme, ['http', 'https'], true)) {
                    // Menolak javascript:, data:, dll. yang dulu bisa lolos
                    // karena nilainya cuma singgah di localStorage.
                    api_error('Tautan hanya boleh memakai http atau https.', 422);
                }
                if ($scheme === '' && !preg_match('#^/#', $fileUrl)) {
                    api_error('Tautan tidak valid.', 422);
                }
            }

            $skillIds = api_int_list($body, 'skill_ids');
            if (count($skillIds) === 0) {
                api_error('Pilih 1-6 skill untuk bukti ini.', 422);
            }
            if (count($skillIds) > EVIDENCE_MAX) {
                api_error('Satu bukti maksimal membuktikan 6 skill.', 422);
            }

            $limit = (int) api_setting('max_evidence', 12);
            $count = $pdo->prepare('SELECT COUNT(*) FROM evidence WHERE user_id = :u');
            $count->execute([':u' => $uid]);
            if ($limit > 0 && (int) $count->fetchColumn() >= $limit) {
                api_error("Profil Anda sudah memuat {$limit} bukti. Hapus salah satu dulu.", 422);
            }

            // Hanya boleh menunjuk skill yang benar-benar ada di katalog.
            $in = implode(',', array_fill(0, count($skillIds), '?'));
            $check = $pdo->prepare("SELECT id FROM skills WHERE id IN ({$in})");
            foreach ($skillIds as $i => $id) {
                $check->bindValue($i + 1, $id, PDO::PARAM_INT);
            }
            $check->execute();
            $validIds = array_map('intval', $check->fetchAll(PDO::FETCH_COLUMN));
            if (count($validIds) !== count($skillIds)) {
                api_error('Ada skill yang tidak ada di katalog.', 422);
            }

            $meta = [];
            if (isset($body['metadata']) && is_array($body['metadata'])) {
                $date  = api_str($body['metadata'], 'date', 10);
                $level = api_str($body['metadata'], 'level', 40);
                if ($date !== null) {
                    $meta['date'] = $date;
                }
                if ($level !== null) {
                    $meta['level'] = $level;
                }
            }

            $pdo->beginTransaction();
            $insert = $pdo->prepare(
                'INSERT INTO evidence (user_id, source_type, title, description, file_url, metadata)
                 VALUES (:u, :source, :title, :desc, :url, CAST(:meta AS jsonb))
                 RETURNING id'
            );
            $insert->execute([
                ':u'     => $uid,
                ':source'=> $source,
                ':title' => $title,
                ':desc'  => $description,
                ':url'   => $fileUrl,
                ':meta'  => json_encode((object) $meta, JSON_UNESCAPED_UNICODE),
            ]);
            $newId = (int) $insert->fetchColumn();

            $link = $pdo->prepare('INSERT INTO evidence_skills (evidence_id, skill_id) VALUES (:e, :s)');
            foreach ($validIds as $sid) {
                $link->execute([':e' => $newId, ':s' => $sid]);
            }
            $pdo->commit();

            api_out(['ok' => true, 'id' => $newId, 'evidence' => api_fetch_evidence($pdo, $uid)], 201);
            break;
        }

        case 'DELETE': {
            api_write_guard('mahasiswa');
            $uid  = api_user_id();
            $body = api_body();
            $id   = api_int($body, 'id') ?? api_int($_GET, 'id');
            if ($id === null) {
                api_error('Butuh id bukti.', 422);
            }
            // user_id ikut kondisi WHERE: bukti milik akun lain tidak bisa
            // dihapus walau id-nya ditebak.
            $stmt = $pdo->prepare('DELETE FROM evidence WHERE id = :id AND user_id = :u');
            $stmt->execute([':id' => $id, ':u' => $uid]);
            if ($stmt->rowCount() === 0) {
                api_error('Bukti itu tidak ada di profil Anda.', 404);
            }
            api_out(['ok' => true, 'evidence' => api_fetch_evidence($pdo, $uid)]);
            break;
        }

        default:
            api_error('Method tidak didukung.', 405);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('api/evidence.php: ' . $e->getMessage());
    api_error('Gagal menyimpan bukti.', 500);
}
