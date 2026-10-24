<?php
// public/admin/search.php
// Pencarian profil publik · Konsol Admin
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('admin');

$page_title = 'Cari Profil · SkillBridge';
$role = 'admin';
$path_prefix = '../';
$admin_page = 'search';
$body_class = 'admin-body';
$body_attrs = 'data-admin-page="search" data-search-page="admin"';
$page_script = 'search.js';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/admin_sidebar.php';
?>

    <div class="admin-workspace">
      <?php require_once __DIR__ . '/../../includes/layouts/admin_header.php'; ?>

      <main class="admin-page-content">
        <div class="admin-page-heading">
          <div>
            <p class="admin-eyebrow">Direktori publik</p>
            <h1 class="admin-page-title">Cari Profil</h1>
            <p class="admin-page-desc">Temukan mahasiswa yang membuka profilnya untuk publik. Hanya profil aktif dan Public yang ditampilkan.</p>
          </div>
        </div>

        <section class="admin-card people-search-panel" aria-labelledby="people-search-title">
          <h2 class="visually-hidden" id="people-search-title">Pencarian profil publik</h2>
          <form class="people-search-form" role="search" id="people-search-form">
            <label class="visually-hidden" for="people-search-input">Cari berdasarkan nama, kampus, program, lokasi, target karier, atau skill</label>
            <input type="search" id="people-search-input" name="q" maxlength="100" autocomplete="off" placeholder="Cari nama, kampus, program, lokasi, karier, atau skill..." />
            <button type="submit" class="btn btn-accent btn-44">Cari</button>
          </form>
          <p class="people-search-status" id="people-search-status" aria-live="polite"></p>
          <div class="people-grid" id="people-search-results"></div>
        </section>
      </main>

<?php require_once __DIR__ . '/../../includes/layouts/admin_footer.php'; ?>
