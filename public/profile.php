<?php
// public/profile.php
// Profil publik berbasis token. Tidak memerlukan login.
require_once __DIR__ . '/../includes/auth/auth.php';

$page_title = 'Profil Publik · SkillBridge';
$role = 'public';
$path_prefix = '';
$body_class = 'public-profile-body';
$extra_head = '<meta name="robots" content="noindex, nofollow">' . "\n"
    . '<meta name="referrer" content="no-referrer">';

$viewer_role = get_user_role();
if ($viewer_role === 'admin') {
    $back_url = base_url('admin/search.php');
    $back_label = 'Kembali ke pencarian';
} elseif ($viewer_role === 'mahasiswa') {
    $back_url = base_url('mahasiswa/search.php');
    $back_label = 'Kembali ke pencarian';
} else {
    $back_url = base_url('index.php');
    $back_label = 'Kembali ke beranda';
}

require_once __DIR__ . '/../includes/layouts/head.php';
require_once __DIR__ . '/../includes/layouts/public_header.php';
?>

<main class="container public-profile-main">
  <div class="public-profile">
    <a class="public-profile-back" href="<?= e($back_url) ?>">← <?= e($back_label) ?></a>
    <div id="public-profile-root" data-loading="text">
      <p class="public-profile-empty">Memuat profil...</p>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../includes/layouts/public_footer.php'; ?>

<script src="<?= e(asset('js/ui.js')) ?>"></script>
<script src="<?= e(asset('js/api.js')) ?>"></script>
<script src="<?= e(asset('js/pages/public-profile.js')) ?>"></script>
</body></html>
