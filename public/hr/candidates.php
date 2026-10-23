<?php
// public/hr/candidates.php
// Pipeline Kandidat · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('hr');

$page_title = 'Pipeline Kandidat · SkillBridge';
$role = 'hr';
$path_prefix = '../';
$rec_page = 'candidates';
$body_class = 'rec-body';
$body_attrs = 'data-rec-page="candidates"';
$page_script = 'recruiter-candidates.js';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/hr_sidebar.php';
?>

    <!-- ===== MAIN ===== -->
    <div class="rec-workspace">
      <?php require_once __DIR__ . '/../../includes/layouts/hr_header.php'; ?>

      <main class="rec-page-content">
        <div>
          <div class="rec-page-context">
            <p class="rec-breadcrumb">A &ndash; RECRUITER / MANAJEMEN PENGGUNA</p>
            <div class="rec-date">
              <svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4.5" width="14" height="12.5" rx="1.5"/><path d="M3 8.5h14M7 3v3M13 3v3"/></svg>
              <span id="rec-today">18 Oktober 2026</span>
            </div>
          </div>
          <h1 class="rec-page-title">Daftar &amp; Kualifikasi Kandidat</h1>
          <p class="rec-page-desc">Temukan kandidat yang sesuai, tinjau kecocokan skill, dan undang talenta terbaik untuk perusahaan Anda.</p>
        </div>

        <section class="rec-card">
          <div class="rec-card-head">
            <h2 class="rec-card-title">Candidate Pool</h2>
            <span class="rec-card-sub" id="rec-cand-count">—</span>
          </div>

          <div class="rec-toolbar">
            <label class="rec-search">
              <svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="9" r="6"/><path d="M14 14l3.5 3.5"/></svg>
              <input type="search" id="rec-cand-search" placeholder="Cari kandidat berdasarkan nama atau skill…" aria-label="Cari kandidat" />
            </label>
            <select class="rec-select" id="rec-cand-role" aria-label="Filter target role">
              <option value="">Target Role</option>
            </select>
            <select class="rec-select" id="rec-cand-evidence" aria-label="Filter jumlah evidence">
              <option value="">Status Evidence</option>
              <option value="high">≥ 5 items</option>
              <option value="low">&lt; 5 items</option>
            </select>
          </div>

          <div class="rec-table-wrap table-responsive">
            <table class="rec-native-table">
              <thead>
                <tr>
                  <th class="rec-cand-id">Kandidat</th>
                  <th class="rec-cand-role">Target Role</th>
                  <th class="rec-cand-skills">Skill Utama Matched</th>
                  <th class="rec-cand-gaps">Skill Gap</th>
                  <th class="rec-cand-evidence">Evidence</th>
                  <th class="rec-cand-actions">Aksi</th>
                </tr>
              </thead>
              <tbody id="rec-cand-body"></tbody>
            </table>
          </div>

          <div class="rec-pager">
            <span id="rec-cand-shown">—</span>
            <button type="button" class="rec-pager-link" id="rec-cand-prev" disabled>Sebelumnya</button>
            <span class="rec-pager-page" id="rec-cand-page">1</span>
            <button type="button" class="rec-pager-link" id="rec-cand-next">Berikutnya</button>
          </div>
        </section>

        <section class="rec-info-strip rec-info-strip--indigo">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="10" r="7.5"/><path d="M10 6.5v4.5M10 13.5h.01"/></svg>
          <div class="rec-info-strip-text">
            <span class="rec-info-strip-title">Kualifikasi berbasis skill &amp; evidence</span>
            <span class="rec-info-strip-sub">Skill Utama Matched menunjukkan keterampilan yang sesuai dengan target role. Tinjau evidence kandidat sebelum mengirim undangan.</span>
          </div>
        </section>
      </main>

<?php require_once __DIR__ . '/../../includes/layouts/hr_footer.php'; ?>