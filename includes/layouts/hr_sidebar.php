<?php
// includes/layouts/hr_sidebar.php
// Sidebar Navigasi Konsol HR / Recruiter (Desktop & Mobile Drawer)
require_once __DIR__ . '/../config/app.php';

$rec_page = $rec_page ?? 'profile';
?>
<!-- Backdrop Mobile Drawer -->
<div class="sidebar-backdrop" data-close-sidebar></div>

<!-- ===== SIDEBAR HR ===== -->
<aside class="rec-sidebar">
  <div class="rec-brand">
    <div style="display: flex; align-items: center; gap: 10px;">
      <div class="rec-brand-mark">S</div>
      <div class="rec-brand-copy">
        <span class="rec-brand-name">SkillBridge</span>
        <span class="rec-brand-type">RECRUITER CONSOLE</span>
      </div>
    </div>
    <button type="button" class="sidebar-close-btn" data-close-sidebar aria-label="Tutup menu">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
  </div>

  <nav class="rec-nav" aria-label="Navigasi recruiter">
    <details class="rec-nav-group" <?= in_array($rec_page, ['profile', 'settings']) ? 'open' : '' ?>>
      <summary class="rec-nav-group-head">
        <svg class="rec-nav-cat-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 13.5V4.5a1 1 0 011-1h4a1 1 0 011 1v9"/><path d="M9 7h3a1 1 0 011 1v5.5"/><path d="M2 13.5h12"/><path d="M5.5 6h1.5M5.5 9h1.5M11 10h.5"/></svg>
        <span class="rec-nav-group-title">Profil &amp; Pengaturan</span>
        <svg class="rec-nav-chevron" width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 3l3 3-3 3"/></svg>
      </summary>
      <div class="rec-nav-group-body">
        <a href="<?= e(base_url('hr/index.php')) ?>" class="rec-nav-item <?= $rec_page === 'profile' ? 'active' : '' ?>">Profil Perusahaan</a>
        <a href="<?= e(base_url('hr/settings.php')) ?>" class="rec-nav-item <?= $rec_page === 'settings' ? 'active' : '' ?>">Pengaturan Akun</a>
      </div>
    </details>
    <details class="rec-nav-group" <?= in_array($rec_page, ['team', 'activity']) ? 'open' : '' ?>>
      <summary class="rec-nav-group-head">
        <svg class="rec-nav-cat-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="5.5" r="2.25"/><path d="M2.25 12.5c0-2.07 1.68-3.75 3.75-3.75s3.75 1.68 3.75 3.75"/><circle cx="11.75" cy="6.25" r="1.5"/><path d="M11.75 9.25c.9 0 1.65.45 2.15 1.13"/></svg>
        <span class="rec-nav-group-title">Manajemen Tim &amp; Monitoring</span>
        <svg class="rec-nav-chevron" width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 3l3 3-3 3"/></svg>
      </summary>
      <div class="rec-nav-group-body">
        <a href="<?= e(base_url('hr/team.php')) ?>" class="rec-nav-item <?= $rec_page === 'team' ? 'active' : '' ?>">Manajemen Recruiter / Tim</a>
        <a href="<?= e(base_url('hr/activity.php')) ?>" class="rec-nav-item <?= $rec_page === 'activity' ? 'active' : '' ?>">Log Aktivitas Recruiter</a>
      </div>
    </details>
    <details class="rec-nav-group" <?= in_array($rec_page, ['roles', 'candidates']) ? 'open' : '' ?>>
      <summary class="rec-nav-group-head">
        <svg class="rec-nav-cat-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="8" r="5.5"/><circle cx="8" cy="8" r="2"/><path d="M8 1v2M8 13v2M1 8h2M13 8h2"/></svg>
        <span class="rec-nav-group-title">Rekrutmen &amp; Peran</span>
        <svg class="rec-nav-chevron" width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 3l3 3-3 3"/></svg>
      </summary>
      <div class="rec-nav-group-body">
        <a href="<?= e(base_url('hr/roles.php')) ?>" class="rec-nav-item <?= $rec_page === 'roles' ? 'active' : '' ?>">Target Role &amp; Kebutuhan Skill</a>
        <a href="<?= e(base_url('hr/candidates.php')) ?>" class="rec-nav-item <?= $rec_page === 'candidates' ? 'active' : '' ?>">Pipeline Kandidat</a>
      </div>
    </details>
  </nav>

  <div class="rec-sidebar-spacer"></div>

  <div class="rec-context">
    <div class="rec-context-head">
      <svg width="17" height="17" viewBox="0 0 17 17" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 1.5L2.5 4v4.5c0 3.5 2.5 6.5 6 7.5 3.5-1 6-4 6-7.5V4L8.5 1.5z"/><path d="M6 8.5l2 2 3.5-3.5"/></svg>
      <span>Akses Perusahaan</span>
    </div>
    <p>Kelola informasi profil perusahaan, anggota tim recruiter, dan kebutuhan role.</p>
  </div>

  <div class="rec-logout-footer">
    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6.75 15.75H3.375A1.125 1.125 0 012.25 14.625V3.375A1.125 1.125 0 013.375 2.25h3.375M12.375 12.375L15.75 9l-3.375-3.375M15.75 9H6.75"/></svg>
    <a href="<?= e(base_url('logout.php')) ?>">Logout</a>
  </div>
</aside>
