<?php
// public/api/hr/activity.php
// Log aktivitas konsol HR (pengganti data/recruiter-activity.json).
//
//   GET -> [{ id, time, actor, text, type, type_label, created_at }]
//
// Baris ditulis oleh endpoint hr/* lain lewat api_log_activity(), memakai
// nama aktor dari sesi. Tidak ada endpoint tulis di sini: log bukan tempat
// yang bisa dikarang klien.

require_once __DIR__ . '/../../../includes/api/bootstrap.php';

const ACTION_LABELS = [
    'interview' => 'Interview',
    'role'      => 'Target Role',
    'review'    => 'Review',
];

/** Label waktu akrab sehari-hari, sesuai tampilan lama konsol HR. */
function activity_time(string $iso): string {
    $ts = strtotime($iso);
    if ($ts === false) {
        return $iso;
    }
    $days = (int) floor((time() - $ts) / 86400);
    if ($days <= 0) {
        return date('H:i', $ts);
    }
    if ($days === 1) {
        return 'Kemarin';
    }
    if ($days < 7) {
        return "{$days} hari lalu";
    }
    if ($days < 30) {
        $weeks = intdiv($days, 7);
        return "{$weeks} minggu lalu";
    }
    return date('j F Y', $ts);
}

$method = api_method();
$pdo    = api_pdo();

try {
    switch ($method) {
        case 'GET': {
            api_guard('hr');
            $link = api_hr_company(api_user_id());
            $stmt = $pdo->prepare(
                'SELECT id, actor, action, text, created_at
                 FROM activity_log
                 WHERE company_id = :c
                 ORDER BY created_at DESC, id DESC
                 LIMIT 100'
            );
            $stmt->execute([':c' => (int) $link['company_id']]);

            $rows = [];
            foreach ($stmt as $r) {
                $action = (string) $r['action'];
                $rows[] = [
                    'id'         => (int) $r['id'],
                    'time'       => activity_time((string) $r['created_at']),
                    'actor'      => $r['actor'],
                    'text'       => $r['text'],
                    'type'       => array_key_exists($action, ACTION_LABELS) ? $action : 'review',
                    'type_label' => ACTION_LABELS[$action] ?? 'Review',
                    'created_at' => $r['created_at'],
                ];
            }
            api_out(['ok' => true, 'activity' => $rows]);
            break;
        }

        default:
            api_error('Method tidak didukung.', 405);
    }
} catch (PDOException $e) {
    error_log('api/hr/activity.php: ' . $e->getMessage());
    api_error('Gagal memuat aktivitas.', 500);
}
