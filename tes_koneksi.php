<?php
// tes_koneksi.php
// Skrip untuk menguji koneksi PDO ke database PostgreSQL / Supabase.
// Mendukung eksekusi via CLI (terminal) maupun Web Browser.

$configFile = __DIR__ . '/includes/config/database.php';
if (!file_exists($configFile)) {
    $configFile = __DIR__ . '/../includes/config/database.php';
}
require_once $configFile;

$isCli = (PHP_SAPI === 'cli');

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tes Koneksi Database Supabase</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; padding: 2rem; margin: 0; }
        .card { background: #1e293b; border-radius: 10px; padding: 1.75rem; max-width: 650px; margin: 0 auto; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.3); border: 1px solid #334155; }
        h1 { margin-top: 0; font-size: 1.35rem; display: flex; align-items: center; gap: 0.5rem; }
        .status-badge { display: inline-block; padding: 0.25rem 0.6rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 600; }
        .badge-success { background: #064e3b; color: #34d399; }
        .badge-error { background: #7f1d1d; color: #f87171; }
        .info-row { margin: 0.4rem 0; color: #94a3b8; font-size: 0.95rem; }
        .info-row strong { color: #e2e8f0; }
        .divider { border-top: 1px solid #334155; margin: 1.25rem 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 0.75rem; }
        th, td { text-align: left; padding: 0.45rem 0.6rem; border-bottom: 1px solid #334155; font-size: 0.875rem; }
        th { color: #94a3b8; font-weight: 600; }
        td:last-child { text-align: right; }
        th:last-child { text-align: right; }
        .error-msg { background: #450a0a; border: 1px solid #dc2626; color: #fca5a5; padding: 0.75rem; border-radius: 6px; font-size: 0.9rem; margin-top: 1rem; word-break: break-word; }
    </style>
</head>
<body>
<div class="card">';
}

function print_line(string $text, string $status = 'info'): void {
    global $isCli;
    if ($isCli) {
        $prefix = match ($status) {
            'success' => '[OK] ',
            'error'   => '[GAGAL] ',
            default   => '[INFO] ',
        };
        echo $prefix . strip_tags($text) . PHP_EOL;
    } else {
        $color = match ($status) {
            'success' => 'style="color:#34d399;font-weight:600;"',
            'error'   => 'style="color:#f87171;font-weight:600;"',
            default   => 'class="info-row"',
        };
        echo "<div {$color}>{$text}</div>";
    }
}

print_line("Memeriksa koneksi database PostgreSQL / Supabase...", "info");
print_line("Host: <strong>" . htmlspecialchars(DB_HOST) . "</strong> (Port: " . htmlspecialchars(DB_PORT) . ")", "info");
print_line("Database: <strong>" . htmlspecialchars(DB_NAME) . "</strong> | User: <strong>" . htmlspecialchars(DB_USER) . "</strong>", "info");
print_line("SSL Mode: <strong>" . htmlspecialchars(DB_SSLMODE) . "</strong>", "info");

if (!$isCli) {
    echo '<div class="divider"></div>';
}

$dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=%s', DB_HOST, DB_PORT, DB_NAME, DB_SSLMODE);

try {
    $startTime = microtime(true);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 5,
        PDO::ATTR_EMULATE_PREPARES   => getenv('DB_EMULATE_PREPARES') === '1',
    ]);
    $latency = round((microtime(true) - $startTime) * 1000, 2);

    print_line("Status: Koneksi BERHASIL! (Latency: {$latency} ms)", "success");

    $serverInfo = $pdo->query("SELECT NOW() AS server_time, version() AS pg_version")->fetch();
    if ($serverInfo) {
        print_line("Waktu Server Supabase: <strong>" . htmlspecialchars((string)$serverInfo['server_time']) . "</strong>", "info");
    }

    if (!$isCli) {
        echo '<div class="divider"></div>';
        echo '<div style="font-weight:600; margin-bottom:0.5rem;">Tabel Database & Jumlah Baris:</div>';
    } else {
        echo PHP_EOL . "Tabel database yang terdeteksi:" . PHP_EOL;
    }

    $tablesStmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name;");
    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        print_line("Belum ada tabel yang terdeteksi di schema public. Pastikan schema.sql sudah dijalankan.", "error");
    } else {
        if (!$isCli) {
            echo '<table><thead><tr><th>Nama Tabel</th><th>Jumlah Data</th></tr></thead><tbody>';
        }
        foreach ($tables as $t) {
            try {
                $count = $pdo->query("SELECT COUNT(*) FROM \"{$t}\"")->fetchColumn();
            } catch (Exception) {
                $count = '-';
            }
            if ($isCli) {
                echo sprintf("  - %-30s : %s baris\n", $t, $count);
            } else {
                echo "<tr><td>" . htmlspecialchars($t) . "</td><td>" . htmlspecialchars((string)$count) . "</td></tr>";
            }
        }
        if (!$isCli) {
            echo '</tbody></table>';
        }
    }

} catch (PDOException $e) {
    print_line("Status: Koneksi GAGAL!", "error");
    if ($isCli) {
        echo "Pesan Error: " . $e->getMessage() . PHP_EOL;
    } else {
        echo '<div class="error-msg"><strong>Detail Error:</strong><br>' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

if (!$isCli) {
    echo '</div></body></html>';
}
