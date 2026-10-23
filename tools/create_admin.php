<?php
// tools/create_admin.php
// Membuat / mempromosikan akun admin. Hanya untuk CLI.
//
//   php tools/create_admin.php admin@contoh.id "Nama Admin"
//   (password dibaca dari variabel ADMIN_PASSWORD, atau ditanyakan di terminal)

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/config/database.php';

$email = strtolower(trim($argv[1] ?? ''));
$name  = trim($argv[2] ?? 'Administrator');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Pemakaian: php tools/create_admin.php email@contoh.id \"Nama\"\n");
    exit(1);
}

$password = getenv('ADMIN_PASSWORD') ?: '';
if ($password === '') {
    fwrite(STDERR, 'Password admin (min. 8 karakter): ');
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

$stmt = $pdo->prepare(
    "INSERT INTO users (name, email, password_hash, role)
     VALUES (:name, :email, :hash, 'admin')
     ON CONFLICT (email) DO UPDATE
       SET role = 'admin', password_hash = EXCLUDED.password_hash"
);
$stmt->execute([
    ':name'  => $name,
    ':email' => $email,
    ':hash'  => password_hash($password, PASSWORD_DEFAULT),
]);

echo "Admin siap: {$email}\n";
