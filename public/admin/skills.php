<?php
// public/admin/skills.php
// Manajemen Keterampilan · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('admin');

$page_title = 'Manajemen Keterampilan · SkillBridge';
$role = 'admin';
$path_prefix = '../';
$admin_page = 'skills';
$body_class = 'admin-body';
$body_attrs = 'data-admin-page="skills" data-nav="skills"';
$page_script = 'admin-skills.js';

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
          <h1 class="admin-page-title">Manajemen Keterampilan</h1>
          <p class="admin-page-desc">Katalog keterampilan master yang dipakai seluruh platform SkillBridge.</p>
        </div>
      </div>

      <div class="admin-toolbar">
        <div class="admin-search"><input type="text" id="skill-search" placeholder="Cari keterampilan..." /></div>
        <select id="skill-category-filter" class="admin-select">
          <option value="all">Semua kategori</option>
        </select>
        <button type="button" class="btn btn-accent btn-44" id="btn-add-skill">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round">
            <path d="M3.333 8h9.334M8 3.333v9.334" />
          </svg>
          Tambah Keterampilan
        </button>
      </div>

      <div class="admin-card">
        <div class="admin-table-wrap table-responsive">
          <table class="admin-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nama Keterampilan</th>
                <th>Kategori</th>
                <th class="admin-text-end">Aksi</th>
              </tr>
            </thead>
            <tbody id="skills-table-body"></tbody>
          </table>
        </div>
      </div>
    </main>

<?php require_once __DIR__ . '/../../includes/layouts/admin_footer.php'; ?>