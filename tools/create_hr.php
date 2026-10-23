<?php
// tools/create_hr.php
// Membuat akun login role 'hr' dan menautkannya ke sebuah perusahaan
// (baris company_recruiters). Hanya untuk CLI.
//
//   php tools/create_hr.php hr@contoh.id "Nama Recruiter" "PT Tech Indonesia"
//   (password dibaca dari variabel HR_PASSWORD, atau ditanyakan di terminal)
//
// Perusahaan harus sudah ada. Kalau belum, buat lebih dulu:
//   INSERT INTO companies (name, status) VALUES ('PT ...', 'verified');

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/config/database.php';

$email   = strtolower(trim($argv[1] ?? ''));
$name    = trim($argv[2] ?? '');
$company = trim($argv[3] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $name === '' || $company === '') {
    fwrite(STDERR, "Pemakaian: php tools/create_hr.php email@contoh.id \"Nama\" \"Nama Perusahaan\"\n");
    exit(1);
}

$password = getenv('HR_PASSWORD') ?: '';
if ($password === '') {
    fwrite(STDERR, 'Password HR (min. 8 karakter): ');
    @shell_exec('stty -echo 2>/dev/null');
    $password = rtrim((string) fgets(STDIN), "\r\n");
    @shell_exec('stty echo 2>/dev/null');
    fwrite(STDERR, "\n");
}
if (strlen($password) < 8 || strlen($password) > 72) {
    fwrite(STDERR, "Password harus 8-72 karakter.\n");
    exit(1);
}

$pdo = get_db_connection();
if (!$pdo) {
    fwrite(STDERR, "Tidak bisa terhubung ke database. Periksa DB_* di .env / environment.\n");
    exit(1);
}

$companyRow = $pdo->prepare("SELECT id FROM companies WHERE name = :name");
$companyRow->execute([':name' => $company]);
$companyId = $companyRow->fetchColumn();
if (!$companyId) {
    fwrite(STDERR, "Perusahaan '{$company}' tidak ada di tabel companies.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$pdo->beginTransaction();
try {
    $upsertUser = $pdo->prepare(
        "INSERT INTO users (name, email, password_hash, role)
         VALUES (:name, :email, :hash, 'hr')
         ON CONFLICT (email) DO UPDATE
           SET role = 'hr', password_hash = EXCLUDED.password_hash, name = EXCLUDED.name
         RETURNING id"
    );
    $upsertUser->execute([':name' => $name, ':email' => $email, ':hash' => $hash]);
    $userId = (int) $upsertUser->fetchColumn();

    // Satu akun HR hanya menempati satu perusahaan (UNIQUE user_id),
    // jadi pindahkan tautan lama alih-alih menabrak constraint.
    $already = $pdo->prepare('SELECT company_id FROM company_recruiters WHERE user_id = :user');
    $already->execute([':user' => $userId]);
    $existingCompany = $already->fetchColumn();
    if ($existingCompany !== false && (int) $existingCompany !== (int) $companyId) {
        throw new RuntimeException(sprintf(
            'Akun %s sudah tertaut ke perusahaan id %d. Hapus keanggotaan itu lebih dulu '
            . 'atau pakai email lain.', $email, (int) $existingCompany
        ));
    }

    // Anggota catatan (user_id NULL) yang emailnya sama diklaim lebih dulu,
    // supaya tidak menabrak UNIQUE (company_id, email).
    $claim = $pdo->prepare(
        "UPDATE company_recruiters
            SET user_id = :user, name = :name, status = 'active'
          WHERE company_id = :company AND email = :email AND user_id IS NULL"
    );
    $claim->execute([
        ':user'    => $userId,
        ':name'    => $name,
        ':company' => $companyId,
        ':email'   => $email,
    ]);

    if ($claim->rowCount() === 0) {
        // Satu akun HR hanya boleh menempati satu perusahaan.
        $link = $pdo->prepare(
            "INSERT INTO company_recruiters (company_id, user_id, name, email, role, role_label, status)
             VALUES (:company, :user, :name, :email, 'recruiter', 'Recruiter', 'active')
             ON CONFLICT (user_id) DO UPDATE
               SET company_id = EXCLUDED.company_id, name = EXCLUDED.name, status = 'active'"
        );
        $link->execute([
            ':company' => $companyId,
            ':user'    => $userId,
            ':name'    => $name,
            ':email'   => $email,
        ]);
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Gagal: " . $e->getMessage() . "\n");
    exit(1);
}

echo "Akun HR siap: {$email} (perusahaan: {$company}, user_id: {$userId})\n";
