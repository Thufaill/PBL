<?php
// public/hr/index.php
// Panel Perusahaan & Recruiter · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('hr');

$page_title = 'Panel Perusahaan & Recruiter · SkillBridge';
$role = 'hr';
$path_prefix = '../';
$rec_page = 'profile';
$body_class = 'rec-body';
$body_attrs = 'data-rec-page="profile"';
$page_script = 'recruiter.js';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/hr_sidebar.php';
?>

    <!-- ===== MAIN ===== -->
    <div class="rec-workspace">
      <?php require_once __DIR__ . '/../../includes/layouts/hr_header.php'; ?>

      <main class="rec-page-content">

        <div>
          <div class="rec-page-context">
            <p class="rec-breadcrumb">A &ndash; PERUSAHAAN / PROFIL &amp; TIM</p>
            <div class="rec-date">
              <svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4.5" width="14" height="12.5" rx="1.5"/><path d="M3 8.5h14M7 3v3M13 3v3"/></svg>
              <span id="rec-today">18 Oktober 2026</span>
            </div>
          </div>
          <h1 class="rec-page-title">Panel Perusahaan &amp; Recruiter</h1>
          <p class="rec-page-desc">
            Kelola profil perusahaan, anggota tim recruiter, target role, dan peninjauan kandidat SkillBridge
            dari satu ruang kerja.
          </p>
        </div>

        <!-- Profil perusahaan aktif -->
        <section class="rec-company-strip">
          <div class="rec-company-emblem">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V5.5A1.5 1.5 0 015.5 4h7A1.5 1.5 0 0114 5.5V21"/><path d="M14 10h4.5A1.5 1.5 0 0120 11.5V21"/><path d="M2.5 21h19"/><path d="M7 8h4M7 12h4M7 16h4M16.5 14h1M16.5 17.5h1"/></svg>
          </div>
          <div class="rec-company-summary">
            <div class="rec-company-title-row">
              <span class="rec-company-title" id="company-title">Profil Perusahaan Aktif.</span>
              <span class="rec-badge-verified" id="company-badge">Terverifikasi</span>
            </div>
            <p class="rec-company-sub" id="company-sub">PT Tech Indonesia · Mitra Resmi SkillBridge</p>
          </div>
          <button type="button" class="btn btn-accent btn-44" id="btn-add-recruiter">Tambah Recruiter</button>
        </section>

        <!-- Metrics -->
        <section class="rec-stats-grid" id="rec-stats-grid" aria-label="Metrik perusahaan">
          <!-- diisi js/pages/recruiter.js -->
        </section>

        <!-- Tim recruiter + target role -->
        <div class="rec-dual-grid">

          <section class="rec-card">
            <div class="rec-card-head">
              <div>
                <h2 class="rec-card-title">Anggota Tim Recruiter</h2>
                <p class="rec-card-sub" id="team-summary">—</p>
              </div>
              <a href="team.php" class="rec-pager-link">Kelola Tim &rarr;</a>
            </div>
            <div class="rec-table">
              <div class="rec-table-head">
                <span class="rec-col-name">Nama &amp; Email</span>
                <span class="rec-col-role">Peran</span>
                <span class="rec-col-status">Status Akses</span>
                <span class="rec-col-actions">Aksi</span>
              </div>
              <div class="rec-table-body" id="rec-team-body">
                <!-- diisi js/pages/recruiter.js -->
              </div>
            </div>
          </section>

          <section class="rec-card">
            <div class="rec-card-head">
              <div>
                <h2 class="rec-card-title">Target Role &amp; Skill Mapping Perusahaan</h2>
                <p class="rec-card-sub" id="roles-summary">—</p>
              </div>
              <a href="roles.php" class="rec-pager-link">Kelola Role &rarr;</a>
            </div>
            <div class="rec-role-head">
              <span class="rec-col-name">Target Role</span>
              <span class="rec-col-skill">Kebutuhan Skill</span>
              <span class="rec-col-match">Kandidat</span>
            </div>
            <div class="rec-role-body" id="rec-roles-body">
              <!-- diisi js/pages/recruiter.js -->
            </div>
            <p class="rec-card-footer" id="roles-footer">—</p>
          </section>

        </div>

        <!-- Aktivitas recruiter -->
        <section class="rec-card">
          <div class="rec-card-head">
            <h2 class="rec-card-title">Aktivitas Recruiter Terbaru</h2>
            <a href="activity.php" class="rec-pager-link">Lihat semua &rarr;</a>
          </div>
          <div class="rec-activity-body" id="rec-activity-body">
            <!-- diisi js/pages/recruiter.js -->
          </div>
        </section>

        <!-- Notice -->
        <section class="rec-info-strip rec-info-strip--pink">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="10" r="7.5"/><path d="M10 6.5v4.5M10 13.5h.01"/></svg>
          <div class="rec-info-strip-text">
            <span class="rec-info-strip-title">Perubahan akses memengaruhi seluruh tim</span>
            <span class="rec-info-strip-sub">Perubahan data recruiter dan penambahan target role akan berdampak pada akses peninjauan kandidat di seluruh tim perusahaan Anda.</span>
          </div>
        </section>

      </main>

<?php require_once __DIR__ . '/../../includes/layouts/hr_footer.php'; ?>