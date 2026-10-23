<?php
// public/admin/users.php
// Manajemen Pengguna · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('admin');

$page_title = 'Manajemen Pengguna · SkillBridge';
$role = 'admin';
$path_prefix = '../';
$admin_page = 'users';
$body_class = 'admin-body';
$body_attrs = 'data-admin-page="users" data-nav="users"';
$page_script = 'admin-users.js';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/admin_sidebar.php';
?>

    <!-- ===== MAIN ===== -->
    <div class="admin-workspace">
      <?php require_once __DIR__ . '/../../includes/layouts/admin_header.php'; ?>

      <main class="admin-page-content">
      <div class="admin-page-heading">
        <div>
          <p class="admin-eyebrow">Pengguna</p>
          <h1 class="admin-page-title">Manajemen Pengguna</h1>
          <p class="admin-page-desc">Kelola akun mahasiswa dan fresh graduate di platform SkillBridge. Gunakan pencarian
            dan filter untuk menemukan pengguna tertentu.</p>
        </div>
      </div>

      <div class="admin-toolbar">
        <div class="admin-search">
          <input type="text" id="user-search" placeholder="Cari nama atau email..." />
        </div>
        <select id="user-status-filter" class="admin-select">
          <option value="all">Semua status</option>
          <option value="active">Aktif</option>
          <option value="inactive">Non-aktif</option>
          <option value="suspended">Suspended</option>
        </select>
        <button type="button" class="btn btn-accent btn-44" id="btn-add-user">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round">
            <path d="M3.333 8h9.334M8 3.333v9.334" />
          </svg>
          Tambah Pengguna
        </button>
      </div>

      <div class="admin-card">
        <div class="admin-table-wrap table-responsive">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Pengguna</th>
                <th>Target Karier</th>
                <th>Skill</th>
                <th>Evidence</th>
                <th>Status</th>
                <th>Dibuat</th>
                <th class="admin-text-end">Aksi</th>
              </tr>
            </thead>
            <tbody id="users-table-body">
              <!-- filled by admin-users.js -->
            </tbody>
          </table>
        </div>
      </div>
    </main>

<?php require_once __DIR__ . '/../../includes/layouts/admin_footer.php'; ?>