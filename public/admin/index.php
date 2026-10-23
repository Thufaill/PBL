<?php
// public/admin/index.php
// Admin Dashboard · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('admin');

$page_title = 'Admin Dashboard · SkillBridge';
$role = 'admin';
$path_prefix = '../';
$admin_page = 'dashboard';
$body_class = 'admin-body';
$body_attrs = 'data-admin-page="dashboard" data-nav="dashboard"';
$page_script = 'admin.js';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/admin_sidebar.php';
?>

    <!-- ===== MAIN ===== -->
    <div class="admin-workspace">
      <?php require_once __DIR__ . '/../../includes/layouts/admin_header.php'; ?>

      <main class="admin-page-content">

        <!-- Page heading -->
        <div class="admin-page-heading">
          <div>
            <p class="admin-eyebrow">Ringkasan platform</p>
            <h1 class="admin-page-title">Selamat datang kembali, Aditya.</h1>
            <p class="admin-page-desc">Pantau data master, aktivitas pengguna, dan konfigurasi SkillBridge dari satu tempat.</p>
          </div>
        </div>

        <!-- Welcome strip -->
        <div class="admin-welcome-strip">
          <div class="admin-welcome-icon">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2L3 5.5v5c0 4 3 7.5 7 8.5 4-1 7-4.5 7-8.5v-5L10 2z"/><path d="M7 10l2.5 2.5L14 8"/></svg>
          </div>
          <div class="admin-welcome-text">
            <strong>Sistem berjalan normal</strong>
            <span>Tidak ada peringatan keamanan atau error kritis hari ini.</span>
          </div>
          <span class="admin-badge-pill">Aktif</span>
        </div>

        <!-- Platform statistics — 5 cards -->
        <div class="admin-stats-grid" id="admin-stats-grid">
          <!-- filled by admin.js -->
        </div>

        <!-- Activity overview — 2 panels -->
        <div class="admin-activity-grid">

          <!-- System activity -->
          <div class="admin-card">
            <div class="admin-card-head">
              <div>
                <p class="admin-card-title">Aktivitas sistem</p>
                <p class="admin-card-sub">Pencatatan data terbaru · 18 Oktober 2026</p>
              </div>
              <span class="admin-badge-pill">Hari ini</span>
            </div>
            <div class="admin-table table-responsive">
              <div class="admin-table-head">
                <span class="col-time">Waktu</span>
                <span class="col-activity">Aktivitas</span>
                <span class="col-cat">Kategori</span>
              </div>
              <div class="admin-table-body" id="system-activity-body">
                <!-- filled by admin.js -->
              </div>
            </div>
          </div>

          <!-- Administrative activity -->
          <div class="admin-card">
            <div class="admin-card-head">
              <div>
                <p class="admin-card-title">Aktivitas administrasi</p>
                <p class="admin-card-sub">Perubahan akses dan akun terakhir</p>
              </div>
            </div>
            <div class="admin-table table-responsive">
              <div class="admin-table-head admin-table-head--admin">
                <span class="col-admin">Administrator / aktivitas</span>
                <span class="col-time-sm">Waktu</span>
              </div>
              <div class="admin-table-body" id="admin-activity-body">
                <!-- filled by admin.js -->
              </div>
            </div>
          </div>

        </div>
      </main>

<?php require_once __DIR__ . '/../../includes/layouts/admin_footer.php'; ?>