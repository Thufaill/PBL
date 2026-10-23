<?php
// public/hr/roles.php
// Target Role & Kebutuhan Skill · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('hr');

$page_title = 'Target Role & Kebutuhan Skill · SkillBridge';
$role = 'hr';
$path_prefix = '../';
$rec_page = 'roles';
$body_class = 'rec-body';
$body_attrs = 'data-rec-page="roles"';
$page_script = 'recruiter-roles.js';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/hr_sidebar.php';
?>

    <!-- ===== MAIN ===== -->
    <div class="rec-workspace">
      <?php require_once __DIR__ . '/../../includes/layouts/hr_header.php'; ?>

      <main class="rec-page-content">

        <!-- Page heading -->
        <div>
          <div class="rec-page-context">
            <p class="rec-breadcrumb">A &ndash; PERUSAHAAN / TARGET ROLE &amp; SKILL</p>
            <div class="rec-date">
              <svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4.5" width="14" height="12.5" rx="1.5"/><path d="M3 8.5h14M7 3v3M13 3v3"/></svg>
              <span id="rec-today">18 Oktober 2026</span>
            </div>
          </div>
          <h1 class="rec-page-title">Target Role &amp; Kebutuhan Skill</h1>
          <p class="rec-page-desc">
            Kelola target role yang dibuka perusahaan serta kebutuhan skill terkait untuk menyaring kandidat.
          </p>
        </div>

        <!-- Kartu target role -->
        <section class="rec-card">
          <div class="rec-card-head">
            <h2 class="rec-card-title">Ringkasan Lowongan</h2>
            <span class="rec-card-count" id="roles-summary">—</span>
          </div>
          <div class="rec-role-cards" id="rec-role-cards">
            <!-- diisi js/pages/recruiter-roles.js -->
          </div>
        </section>

        <!-- Workspace mapping dua panel -->
        <section>
          <h2 class="rec-section-title">Mapping Skill ke Target Role</h2>
          <p class="rec-section-sub">Pilih target role di atas, lalu tentukan skill yang menjadi kebutuhan role tersebut.</p>
          <div class="rec-mapping-grid" id="rec-mapping-panel" style="margin-top: 16px">
            <!-- diisi js/pages/recruiter-roles.js -->
          </div>
        </section>

        <div class="rec-save-bar">
          <span class="rec-save-note">Simpan perubahan untuk menerapkan mapping baru ke pipeline kandidat.</span>
          <div class="rec-save-actions">
            <button type="button" class="btn" id="btn-cancel-mapping">Batal</button>
            <button type="button" class="btn btn-accent" id="btn-save-mapping">Simpan Mapping</button>
          </div>
        </div>

      </main>

<?php require_once __DIR__ . '/../../includes/layouts/hr_footer.php'; ?>