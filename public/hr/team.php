<?php
// public/hr/team.php
// Manajemen Recruiter / Tim · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('hr');

$page_title = 'Manajemen Recruiter / Tim · SkillBridge';
$role = 'hr';
$path_prefix = '../';
$rec_page = 'team';
$body_class = 'rec-body';
$body_attrs = 'data-rec-page="team"';
$page_script = 'recruiter-team.js';

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
            <p class="rec-breadcrumb">A &ndash; PERUSAHAAN / MANAJEMEN RECRUITER / TIM</p>
            <div class="rec-date">
              <svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4.5" width="14" height="12.5" rx="1.5"/><path d="M3 8.5h14M7 3v3M13 3v3"/></svg>
              <span id="rec-today">18 Oktober 2026</span>
            </div>
          </div>
          <h1 class="rec-page-title">Manajemen Recruiter / Tim</h1>
          <p class="rec-page-desc">
            Kelola anggota tim recruiter, peran masing-masing, dan status akses untuk peninjauan kandidat.
          </p>
        </div>

        <!-- Metrics -->
        <section class="rec-stats-grid" id="rec-team-stats" aria-label="Metrik tim recruiter">
          <!-- diisi js/pages/recruiter-team.js -->
        </section>

        <!-- Team toolbar -->
        <div class="rec-toolbar">
          <div class="rec-search">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="9" r="5"/><path d="M15 15l-3-3"/></svg>
            <input type="search" id="rec-team-search" placeholder="Cari nama atau email recruiter…" />
          </div>
          <select id="rec-team-role-filter" class="rec-select">
            <option value="all">Semua Peran</option>
            <option value="lead">Lead Recruiter</option>
            <option value="hiring_manager">Hiring Manager</option>
            <option value="recruiter">Recruiter</option>
          </select>
          <select id="rec-team-status-filter" class="rec-select">
            <option value="all">Semua Status</option>
            <option value="active">Aktif</option>
            <option value="inactive">Nonaktif</option>
            <option value="pending">Pending</option>
          </select>
          <button type="button" class="btn btn-accent btn-44" id="btn-invite-recruiter">Undang Recruiter</button>
        </div>

        <!-- Team table -->
        <section class="rec-card">
          <div class="rec-table-wrap table-responsive">
            <table class="rec-native-table">
              <thead>
                <tr>
                  <th class="rec-col-name">Nama &amp; Email</th>
                  <th class="rec-col-role">Peran</th>
                  <th class="rec-col-status">Status Akses</th>
                  <th class="rec-col-date">Bergabung</th>
                  <th class="rec-col-actions">Aksi</th>
                </tr>
              </thead>
              <tbody id="rec-team-tbody">
                <tr><td colspan="5" class="rec-empty">Memuat tim recruiter…</td></tr>
              </tbody>
            </table>
          </div>

          <div class="rec-pager">
            <button type="button" class="rec-pager-link" id="rec-team-prev" disabled>Sebelumnya</button>
            <span class="rec-pager-page" id="rec-team-page">1</span>
            <button type="button" class="rec-pager-link" id="rec-team-next">Berikutnya</button>
          </div>
        </section>

      </main>

<?php require_once __DIR__ . '/../../includes/layouts/hr_footer.php'; ?>