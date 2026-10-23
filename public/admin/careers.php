<?php
// public/admin/careers.php
// Manajemen Target Karier · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('admin');

$page_title = 'Manajemen Target Karier · SkillBridge';
$role = 'admin';
$path_prefix = '../';
$admin_page = 'careers';
$body_class = 'admin-body';
$body_attrs = 'data-admin-page="careers" data-nav="careers"';
$page_script = 'admin-careers.js';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/admin_sidebar.php';
?>

    <!-- ===== MAIN ===== -->
    <div class="admin-workspace">
      <?php require_once __DIR__ . '/../../includes/layouts/admin_header.php'; ?>

      <main class="admin-page-content">
      <div class="admin-page-heading">
        <div>
          <p class="admin-eyebrow">Data master</p>
          <h1 class="admin-page-title">Manajemen Target Karier</h1>
          <p class="admin-page-desc">Kelola daftar target karier dan keterampilan yang dipersyaratkan.</p>
        </div>
        <button class="btn btn-accent" type="button" id="btn-add-career">+ Tambah Karier</button>
      </div>

      <div class="admin-card">
        <div class="admin-toolbar">
          <div class="admin-search" style="flex:1;min-width:200px">
            <input type="search" id="admin-search" placeholder="Cari karier..."
              style="width:100%;height:40px;padding:0 12px;border:1px solid var(--border);border-radius:10px;background:var(--admin-surface);font-size:14px" />
          </div>
        </div>
        <div class="admin-table-wrap table-responsive">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Karier</th>
                <th>Keterampilan Diperlukan</th>
                <th class="admin-text-end">Aksi</th>
              </tr>
            </thead>
            <tbody id="careers-table-body"></tbody>
          </table>
        </div>
      </div>
    </main>

<?php require_once __DIR__ . '/../../includes/layouts/admin_footer.php'; ?>