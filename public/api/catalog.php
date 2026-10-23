<?php
// public/api/catalog.php
// GET katalog master: target karier + skill + daftar kategori.
//
// Membentuk ulang bentuk data/careers.json dan data/skills.json persis,
// supaya getCareers() dan getSkillCatalog() di gateway klien tidak perlu
// diubah pemanggilnya. skills di setiap karier adalah DAFTAR NAMA, sama
// seperti JSON lama, karena career.js dan admin-careers.js mencocokkan
// berdasarkan nama.
//
// Endpoint ini butuh login (role apa pun); halaman publik tidak memakai
// api.js sehingga tidak ada data katalog yang terlayani tanpa sesi.

require_once __DIR__ . '/../../includes/api/bootstrap.php';

if (api_method() !== 'GET') {
    api_error('Method tidak didukung.', 405);
}

api_guard();
$pdo = api_pdo();

try {
    $careers = $pdo->query(
        'SELECT id, name, description FROM careers ORDER BY id'
    )->fetchAll();

    $req = $pdo->query(
        'SELECT csr.career_id, s.name
         FROM career_skill_requirements csr
         JOIN skills s ON s.id = csr.skill_id
         ORDER BY csr.career_id, s.name'
    )->fetchAll();

    $byCareer = [];
    foreach ($req as $row) {
        $byCareer[(int) $row['career_id']][] = $row['name'];
    }
    foreach ($careers as &$career) {
        $career['id'] = (int) $career['id'];
        $career['skills'] = $byCareer[(int) $career['id']] ?? [];
    }
    unset($career);

    $skills = $pdo->query(
        'SELECT id, name, category, description FROM skills ORDER BY id'
    )->fetchAll();
    foreach ($skills as &$skill) {
        $skill['id'] = (int) $skill['id'];
    }
    unset($skill);

    // Kategori diambil dari data, bukan daftar hardcoded di JS.
    $categories = $pdo->query(
        "SELECT DISTINCT category FROM skills
         WHERE category IS NOT NULL AND category <> ''
         ORDER BY category"
    )->fetchAll(PDO::FETCH_COLUMN);

    api_out([
        'ok'         => true,
        'careers'    => $careers,
        'skills'     => $skills,
        'categories' => $categories,
    ]);
} catch (PDOException $e) {
    error_log('api/catalog.php: ' . $e->getMessage());
    api_error('Gagal memuat katalog.', 500);
}
