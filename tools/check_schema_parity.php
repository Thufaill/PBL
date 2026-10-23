<?php
// tools/check_schema_parity.php
// Membandingkan STRUKTUR dua database PostgreSQL: hasil instalasi baru
// lewat db/schema.sql vs hasil migrasi aditif db/migrate.sql
// di atas skema lama.
//
// Hanya untuk CLI. Tidak mengubah apa pun (SELECT pg_catalog saja).
//
//   php tools/check_schema_parity.php skillbridge_fresh skillbridge_migrated
//   php tools/check_schema_parity.php            # pakai DB_NAME vs DB_NAME_COMPARE
//
// Keluar: 0 = struktur sama, 1 = ada beda, 2 = pemakaian/koneksi salah.
//
// Yang sengaja TIDAK dibandingkan:
//   - urutan kolom (ordinal_position). ALTER TABLE ADD COLUMN selalu
//     menempel di akhir, sedangkan skema baru bisa mendefinisikannya di
//     tengah. Ini perbedaan kosmetik, bukan perbedaan struktur data.
//   - isi tabel. Yang dijamin sama hanyalah bentuknya.
//
// Yang MEMANG dibandingkan: nama constraint dan nama index. Kedua jalur
// install harus menghasilkan nama objek yang sama, supaya DROP CONSTRAINT
// atau ON CONFLICT di kemudian hari berperilaku identik di database baru
// maupun database hasil migrasi.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(2);
}

require_once __DIR__ . '/../includes/config/database.php';

$dbA = $argv[1] ?? DB_NAME;
$dbB = $argv[2] ?? (getenv('DB_NAME_COMPARE') ?: '');
if ($dbB === '') {
    fwrite(STDERR, "Pemakaian: php tools/check_schema_parity.php <db_baru> <db_hasil_migrasi>\n");
    fwrite(STDERR, "Atau set DB_NAME dan DB_NAME_COMPARE.\n");
    exit(2);
}
if ($dbA === $dbB) {
    fwrite(STDERR, "Kedua nama database sama ({$dbA}). Tidak ada yang bisa dibandingkan.\n");
    exit(2);
}

function connect_as(string $dbname): ?PDO {
    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=%s', DB_HOST, DB_PORT, $dbname, DB_SSLMODE);
    try {
        return new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 8,
        ]);
    } catch (PDOException $e) {
        fwrite(STDERR, "Gagal koneksi ke {$dbname}: " . $e->getMessage() . "\n");
        return null;
    }
}

// pg_dump-pola: buang nama objek dari definisi index supaya rename
// tidak terbaca sebagai perbedaan struktur.
function normalize(string $s): string {
    $s = preg_replace('/\s+/', ' ', $s);
    return trim((string) $s);
}

function dequote(string $s): string {
    return preg_replace('/\b(?:public)\./', '', normalize($s));
}

function collect(PDO $pdo): array {
    $out = ['columns' => [], 'constraints' => [], 'indexes' => [], 'views' => [], 'tables' => []];

    foreach ($pdo->query("
        SELECT table_name, column_name, data_type, is_nullable,
               COALESCE(character_maximum_length::text, '') AS maxlen,
               COALESCE(column_default, '') AS coldefault
        FROM information_schema.columns
        WHERE table_schema = 'public'
        ORDER BY table_name, column_name") as $r) {
        $out['columns']["{$r['table_name']}.{$r['column_name']}"] = sprintf(
            'type=%s null=%s len=%s default=%s',
            $r['data_type'], $r['is_nullable'], $r['maxlen'], dequote($r['coldefault'])
        );
    }

    foreach ($pdo->query("
        SELECT n.nspname AS schema, c.relname AS table_name,
               con.conname AS constraint_name,
               pg_get_constraintdef(con.oid) AS def
        FROM pg_constraint con
        JOIN pg_class      c ON c.oid = con.conrelid
        JOIN pg_namespace  n ON n.oid = c.relnamespace
        WHERE n.nspname = 'public'
        ORDER BY c.relname, con.conname") as $r) {
        // Key: tabel + definisi ternormalisasi. Nilai: namanya, dan nama
        // itulah yang ikut dibandingkan di bawah.
        $out['constraints']["{$r['table_name']} :: " . dequote($r['def'])] = $r['constraint_name'];
    }

    foreach ($pdo->query("
        SELECT indexname, indexdef
        FROM pg_indexes
        WHERE schemaname = 'public'
        ORDER BY indexname") as $r) {
        $stripped = preg_replace('/^CREATE (UNIQUE )?INDEX \S+ /', 'CREATE ${1}INDEX ', $r['indexdef']);
        $out['indexes'][dequote($stripped)] = $r['indexname'];
    }

    foreach ($pdo->query("
        SELECT table_name FROM information_schema.tables
        WHERE table_schema = 'public' AND table_type = 'BASE TABLE'") as $r) {
        $out['tables'][$r['table_name']] = true;
    }

    foreach ($pdo->query("
        SELECT table_name AS v, view_definition AS def
        FROM information_schema.views
        WHERE table_schema = 'public'") as $r) {
        $out['views'][$r['v']] = dequote($r['def']);
    }

    return $out;
}

$a = connect_as($dbA);
$b = connect_as($dbB);
if (!$a || !$b) {
    exit(2);
}

$schemaA = collect($a);
$schemaB = collect($b);

printf("A (instalasi baru) : %s\nB (hasil migrasi)  : %s\n\n", $dbA, $dbB);

$problems = 0;
foreach (['tables', 'columns', 'constraints', 'indexes', 'views'] as $section) {
    $setA = $schemaA[$section];
    $setB = $schemaB[$section];
    $missingB = array_diff_key($setA, $setB);
    $extraB   = array_diff_key($setB, $setA);
    $diffVal  = [];
    foreach (array_intersect_key($setA, $setB) as $k => $v) {
        if ($setB[$k] !== $v) {
            $diffVal[$k] = [$v, $setB[$k]];
        }
    }

    $total = max(count($setA), count($setB));
    printf("%-12s %d item\n", $section . ':', $total);
    foreach ($missingB as $k => $v) {
        printf("   HILANG di B   : %s\n", $k);
        $problems++;
    }
    foreach ($extraB as $k => $v) {
        printf("   ASING di B    : %s\n", $k);
        $problems++;
    }
    foreach ($diffVal as $k => $pair) {
        printf("   BEDA            : %s\n     A: %s\n     B: %s\n", $k, $pair[0], $pair[1]);
        $problems++;
    }
}

echo "\n";
if ($problems === 0) {
    echo "PARITAS OK: struktur kedua database identik.\n";
    exit(0);
}
printf("PARITAS GAGAL: %d perbedaan.\n", $problems);
exit(1);
