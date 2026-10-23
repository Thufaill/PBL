<?php
// public/hr/activity.php
// Log Aktivitas Recruiter · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('hr');

$page_title = 'Log Aktivitas Recruiter · SkillBridge';
$role = 'hr';
$path_prefix = '../';
$rec_page = 'activity';
$body_class = 'rec-body';
$body_attrs = 'data-rec-page="activity"';
$page_script = 'recruiter-activity.js';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/hr_sidebar.php';
?>

    <!-- ===== MAIN ===== -->
    <div class="rec-workspace">
      <?php require_once __DIR__ . '/../../includes/layouts/hr_header.php'; ?>

      <main class="rec-page-content">
        <div>
          <div class="rec-page-context">
            <p class="rec-breadcrumb">A &ndash; RECRUITER / LOG AKTIVITAS</p>
            <div class="rec-date">
              <svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4.5" width="14" height="12.5" rx="1.5"/><path d="M3 8.5h14M7 3v3M13 3v3"/></svg>
              <span id="rec-today">18 Oktober 2026</span>
            </div>
          </div>
          <h1 class="rec-page-title">Log Aktivitas Recruiter</h1>
          <p class="rec-page-desc">Riwayat tindakan tim recruiter: peninjauan kandidat, perubahan target role, dan undangan wawancara.</p>
        </div>

        <section class="rec-card">
          <div class="rec-card-head">
            <h2 class="rec-card-title">Aktivitas Terbaru</h2>
            <span class="rec-card-sub" id="rec-act-count">—</span>
          </div>
          <div class="rec-toolbar">
            <label class="rec-search">
              <svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="9" r="6"/><path d="M14 14l3.5 3.5"/></svg>
              <input type="search" id="rec-act-search" placeholder="Cari aktivitas atau nama recruiter…" aria-label="Cari aktivitas" />
            </label>
            <select class="rec-select" id="rec-act-type" aria-label="Filter jenis aktivitas">
              <option value="">Semua Jenis</option>
              <option value="interview">Interview</option>
              <option value="role">Target Role</option>
              <option value="review">Review</option>
            </select>
          </div>
          <div class="rec-activity-body" id="rec-act-body">
            <!-- diisi js/pages/recruiter-activity.js -->
          </div>
        </section>
      </main>

<?php require_once __DIR__ . '/../../includes/layouts/hr_footer.php'; ?>