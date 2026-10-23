<?php
// public/register.php
// Halaman Registrasi Mahasiswa Baru
require_once __DIR__ . '/../includes/auth/auth.php';

if (is_logged_in()) {
    redirect_to_dashboard(get_user_role());
}

$page_title = 'Register · SkillBridge';
$role = 'public';
$path_prefix = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!csrf_verify()) {
        $error = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif ($name === '' || $email === '' || $password === '') {
        $error = 'Semua field wajib diisi.';
    } elseif (mb_strlen($name) > 100 || strlen($email) > 150) {
        $error = 'Nama maksimal 100 karakter dan email maksimal 150 karakter.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 8) {
        $error = 'Kata sandi minimal harus 8 karakter.';
    } elseif (strlen($password) > 72) {
        // bcrypt hanya memakai 72 byte pertama
        $error = 'Kata sandi maksimal 72 karakter.';
    } else {
        $pdo = get_db_connection();
        if (!$pdo) {
            $error = 'Layanan pendaftaran sedang tidak tersedia. Coba lagi beberapa saat lagi.';
        } else {
            try {
                $pdo->beginTransaction();
                $ins = $pdo->prepare(
                    'INSERT INTO users (name, email, password_hash, role)
                     VALUES (:name, :email, :hash, :role) RETURNING id'
                );
                $ins->execute([
                    ':name'  => $name,
                    ':email' => $email,
                    ':hash'  => password_hash($password, PASSWORD_DEFAULT),
                    ':role'  => app_role_to_db_role('mahasiswa'), // 'user'
                ]);
                $user_id = (int) $ins->fetchColumn();

                // Setiap mahasiswa punya satu baris profil (target karier diisi kemudian).
                $pdo->prepare('INSERT INTO student_profiles (user_id) VALUES (:id)')
                    ->execute([':id' => $user_id]);
                $pdo->commit();

                $result = attempt_login($email, $password);
                if ($result['success']) {
                    redirect_to_dashboard($result['role'], $path_prefix);
                }
                header('Location: ' . base_url('login.php'));
                exit;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if ($e->getCode() === '23505') { // unique_violation (email)
                    $error = 'Email ini sudah terdaftar. Silakan login.';
                } else {
                    error_log('Register error: ' . $e->getMessage());
                    $error = 'Terjadi kesalahan sistem saat mendaftar.';
                }
            }
        }
    }
}

require_once __DIR__ . '/../includes/layouts/head.php';
?>
    <main class="auth-main">
      <article class="auth-card card">
        <div class="auth-logo">
          <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--primary); color: #fff; display: grid; place-items: center; font-size: 18px; font-weight: 800">S</div>
          <span>SkillBridge</span>
        </div>

        <h1 style="font-size: 28px; font-weight: 700; text-align: center; margin-bottom: 8px">Create account</h1>
        <p style="text-align: center; color: var(--text-muted); font-size: 14px; margin-bottom: 24px">Start tracking your skills and building evidence.</p>

        <?php if (!empty($error)): ?>
          <div style="background: #FEF2F2; color: #991B1B; border: 1px solid #F87171; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px;">
            <?= e($error) ?>
          </div>
        <?php endif; ?>

        <form id="register-form" method="POST" action="<?= e(base_url('register.php')) ?>" style="display: grid; gap: 16px">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />

          <div class="field">
            <label for="name">Full name</label>
            <input type="text" id="name" name="name" placeholder="Jhon Doe" required maxlength="100" autocomplete="name" value="<?= e($_POST['name'] ?? '') ?>" />
          </div>

          <div class="field">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" placeholder="you@example.com" required maxlength="150" autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>" />
          </div>

          <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="At least 8 characters" minlength="8" maxlength="72" required autocomplete="new-password" />
          </div>

          <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; height: 44px">Create Account</button>
        </form>

        <p style="text-align: center; font-size: 14px; color: var(--text-muted); margin-top: 20px">
          Already have an account?
          <a href="login.php" style="color: var(--primary); font-weight: 700; text-decoration: none">Sign in</a>
        </p>
      </article>
    </main>

    <script src="<?= e(asset('js/ui.js')) ?>"></script>
    <script src="<?= e(asset('js/api.js')) ?>"></script>
    <script src="<?= e(asset('js/pages/register.js')) ?>"></script>
  </body>
</html>
