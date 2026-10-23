<?php
// public/admin/mapping.php
// Career-Skill Mapping · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('admin');

$page_title = 'Career-Skill Mapping · SkillBridge';
$role = 'admin';
$path_prefix = '../';
$admin_page = 'mapping';
$body_class = 'admin-body';
$body_attrs = 'data-admin-page="mapping" data-nav="mapping"';
$page_script = 'admin-mapping.js';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/admin_sidebar.php';
?>

    <!-- ===== MAIN ===== -->
    <div class="admin-workspace">
      <?php require_once __DIR__ . '/../../includes/layouts/admin_header.php'; ?>

      <main class="admin-page-content">
      <div class="admin-page-heading">
        <div>
          <p class="admin-eyebrow">Relasi data master</p>
          <h1 class="admin-page-title">Career-Skill Mapping</h1>
          <p class="admin-page-desc">Hubungan antara target karier dan keterampilan yang dipersyaratkan.</p>
        </div>
        <button class="btn btn-accent" type="button" id="btn-add-mapping">+ Tambah Relasi</button>
      </div>

      <div class="admin-card">
        <div class="admin-toolbar">
          <div class="admin-search" style="flex:1;min-width:200px">
            <input type="search" id="admin-search" placeholder="Cari karier atau keterampilan..."
              style="width:100%;height:40px;padding:0 12px;border:1px solid var(--border);border-radius:10px;background:var(--admin-surface);font-size:14px" />
          </div>
          <select id="mapping-career-filter" class="admin-select"
            style="height:40px;padding:0 12px;border:1px solid var(--border);border-radius:10px;background:var(--admin-surface);font-size:14px">
            <option value="all">Semua karier</option>
          </select>
        </div>
        <div class="admin-table-wrap table-responsive">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Target Karier</th>
                <th>Keterampilan</th>
                <th>Kategori</th>
                <th class="admin-text-end">Aksi</th>
              </tr>
            </thead>
            <tbody id="mapping-table-body"></tbody>
          </table>
        </div>
      </div>
    </main>

<?php require_once __DIR__ . '/../../includes/layouts/admin_footer.php'; ?>