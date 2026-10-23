<?php
// public/api/hr/company.php
// Profil perusahaan milik akun HR yang sedang login.
//
//   GET   -> { id, name, tagline, status, logo_initial, email_domain,
//              location, description, team_size }
//   PATCH -> { name?, tagline?, email_domain?, location?, description? }
//
// company_id SELALU diambil dari baris company_recruiters milik sesi, tidak
// pernah dari body. Pengaturan HR yang dulu menulis ke localStorage
// 'sb.rec.company.edit' kini mengubah baris companies yang dibaca seluruh
// layar konsol.

require_once __DIR__ . '/../../../includes/api/bootstrap.php';

$method = api_method();
$pdo    = api_pdo();

try {
    switch ($method) {
        case 'GET': {
            api_guard('hr');
            $uid  = api_user_id();
            $link = api_hr_company($uid);

            $count = $pdo->prepare('SELECT COUNT(*) FROM company_recruiters WHERE company_id = :c');
            $count->execute([':c' => (int) $link['company_id']]);

            api_out([
                'ok'           => true,
                'id'           => (int) $link['company_id'],
                'name'         => $link['name'],
                'tagline'      => $link['tagline'],
                'status'       => $link['company_status'],
                'logo_initial' => $link['logo_initial'],
                'email_domain' => $link['email_domain'],
                'location'     => $link['location'],
                'description'  => $link['description'],
                'team_size'    => (int) $count->fetchColumn(),
                'my_role'      => $link['role_label'],
            ]);
            break;
        }

        case 'PATCH': {
            api_write_guard('hr');
            $link  = api_hr_company(api_user_id());
            $cid   = (int) $link['company_id'];
            $body  = api_body();
            $max   = ['name' => 150, 'tagline' => 150, 'email_domain' => 120, 'location' => 120, 'description' => 2000];

            $sets = [];
            $args = [':c' => $cid];
            foreach ($max as $col => $len) {
                if (array_key_exists($col, $body)) {
                    $value = api_str($body, $col, $len);
                    if ($col === 'email_domain' && $value !== null
                        && !preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $value)) {
                        api_error('Domain email harus seperti "perusahaan.com" tanpa @ atau protocol.', 422);
                    }
                    $sets[] = "{$col} = :{$col}";
                    $args[":{$col}"] = $value;
                }
            }
            if (!$sets) {
                api_error('Tidak ada kolom yang diubah.', 422);
            }

            $stmt = $pdo->prepare('UPDATE companies SET ' . implode(', ', $sets) . ' WHERE id = :c');
            $stmt->execute($args);

            // Logo: inisial selalu ikut nama supaya avatar tidak basi.
            if (isset($args[':name'])) {
                $initial = mb_strtoupper(mb_substr((string) $args[':name'], 0, 1));
                $pdo->prepare('UPDATE companies SET logo_initial = :i WHERE id = :c')
                    ->execute([':i' => $initial, ':c' => $cid]);
            }

            api_log_activity($pdo, $cid, 'review', 'memperbarui profil perusahaan');
            api_out(['ok' => true, 'id' => $cid]);
            break;
        }

        default:
            api_error('Method tidak didukung.', 405);
    }
} catch (PDOException $e) {
    error_log('api/hr/company.php: ' . $e->getMessage());
    api_error('Gagal memproses profil perusahaan.', 500);
}
