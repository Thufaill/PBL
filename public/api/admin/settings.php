<?php
// public/api/admin/settings.php
// Konfigurasi platform (pengganti localStorage sb.admin.config).
//
//   GET   -> { settings: { site_name, max_evidence, default_visibility, registration_open } }
//   PATCH -> { settings: { ... } }   hanya kunci yang dikenal yang ditulis
//
// Endpoint domain lain MEMBACA nilai ini: api/evidence.php memakai
// max_evidence untuk membatasi bukti per mahasiswa, jadi perubahan di layar
// ini benar-benar mengubah perilaku aplikasi — bukan hanya preview.

require_once __DIR__ . '/../../../includes/api/bootstrap.php';

const KNOWN_SETTINGS = [
    'site_name'          => ['type' => 'string',  'max' => 120],
    'max_evidence'       => ['type' => 'int',     'min' => 1,  'max' => 100],
    'default_visibility' => ['type' => 'string',  'allowed' => ['Public', 'Private']],
    'registration_open'  => ['type' => 'bool'],
];

$method = api_method();
$pdo    = api_pdo();

try {
    switch ($method) {
        case 'GET': {
            api_guard('admin');
            $defaults = [
                'site_name'          => 'SkillBridge',
                'max_evidence'       => 12,
                'default_visibility' => 'Public',
                'registration_open'  => true,
            ];
            $settings = [];
            foreach ($defaults as $key => $fallback) {
                $value = api_setting($key, $fallback, $pdo);
                $settings[$key] = $value === null ? $fallback : $value;
            }
            api_out(['ok' => true, 'settings' => $settings]);
            break;
        }

        case 'PATCH': {
            api_write_guard('admin');
            $body = api_body();
            $incoming = $body['settings'] ?? $body;
            if (!is_array($incoming)) {
                api_error('settings harus berupa objek.', 422);
            }

            $uid  = api_user_id();
            $done = [];
            $skipped = [];
            $stmt = $pdo->prepare(
                'INSERT INTO system_settings (key, value, updated_by)
                 VALUES (:k, CAST(:v AS jsonb), :u)
                 ON CONFLICT (key) DO UPDATE
                    SET value = EXCLUDED.value,
                        updated_by = EXCLUDED.updated_by,
                        updated_at = CURRENT_TIMESTAMP'
            );
            foreach ($incoming as $key => $value) {
                if (!array_key_exists($key, KNOWN_SETTINGS)) {
                    $skipped[] = $key;
                    continue;
                }
                $rule = KNOWN_SETTINGS[$key];
                if ($rule['type'] === 'int') {
                    if (!is_numeric($value) || (int) $value < $rule['min'] || (int) $value > $rule['max']) {
                        api_error("{$key} harus angka antara {$rule['min']} dan {$rule['max']}.", 422);
                    }
                    $value = (int) $value;
                } elseif ($rule['type'] === 'bool') {
                    $value = (bool) $value;
                } else {
                    $value = trim((string) $value);
                    if ($value === '') {
                        api_error("{$key} tidak boleh kosong.", 422);
                    }
                    if (mb_strlen($value) > (int) ($rule['max'] ?? 500)) {
                        api_error("{$key} terlalu panjang.", 422);
                    }
                    if (isset($rule['allowed']) && !in_array($value, $rule['allowed'], true)) {
                        api_error("{$key} hanya boleh: " . implode(', ', $rule['allowed']) . '.', 422);
                    }
                }
                $stmt->execute([':k' => $key, ':v' => json_encode($value), ':u' => $uid]);
                $done[$key] = $value;
            }
            if (!$done) {
                api_error('Tidak ada pengaturan yang dikenal untuk disimpan.', 422, ['skipped' => $skipped]);
            }
            api_out(['ok' => true, 'saved' => $done, 'ignored' => $skipped]);
            break;
        }

        default:
            api_error('Method tidak didukung.', 405);
    }
} catch (PDOException $e) {
    error_log('api/admin/settings.php: ' . $e->getMessage());
    api_error('Gagal memproses pengaturan.', 500);
}
