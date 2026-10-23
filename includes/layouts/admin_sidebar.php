<?php
// includes/layouts/admin_sidebar.php
// Sidebar Navigasi Konsol Admin Platform (Desktop & Mobile Drawer)
require_once __DIR__ . '/../config/app.php';

$admin_page = $admin_page ?? 'dashboard';
?>
<!-- Backdrop Mobile Drawer -->
<div class="sidebar-backdrop" data-close-sidebar></div>

<!-- ===== SIDEBAR ADMIN ===== -->
<aside class="admin-sidebar">
  <div class="admin-brand">
    <div style="display: flex; align-items: center; gap: 10px;">
      <div class="admin-brand-mark">S</div>
      <div class="admin-brand-copy">
        <span class="admin-brand-name">SkillBridge</span>
        <span class="admin-brand-type">OWNER CONSOLE</span>
      </div>
    </div>
    <button type="button" class="sidebar-close-btn" data-close-sidebar aria-label="Tutup menu">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
  </div>

  <nav class="admin-nav">
    <details class="admin-nav-group cat-1" <?= ($admin_page === 'dashboard') ? 'open' : '' ?>>
      <summary class="admin-nav-group-head"><svg class="admin-nav-cat-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h4v4H3z"/><path d="M9 3h4v4H9z"/><path d="M3 9h4v4H3z"/><path d="M9 9h4v4H9z"/></svg><span class="admin-nav-group-title">Ringkasan (Overview)</span><svg class="admin-nav-chevron" width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 3l3 3-3 3"/></svg></summary>
      <div class="admin-nav-group-body">
        <a href="<?= e(base_url('admin/index.php')) ?>" class="admin-nav-item <?= ($admin_page === 'dashboard') ? 'active' : '' ?>" data-nav="dashboard">Dashboard</a>
      </div>
    </details>
    <details class="admin-nav-group cat-2" <?= in_array($admin_page, ['users', 'admins']) ? 'open' : '' ?>>
      <summary class="admin-nav-group-head"><svg class="admin-nav-cat-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="5.5" r="2.25"/><path d="M2.25 12.5c0-2.07 1.68-3.75 3.75-3.75s3.75 1.68 3.75 3.75"/><circle cx="11.75" cy="6.25" r="1.5"/><path d="M11.75 9.25c.9 0 1.65.45 2.15 1.13"/></svg><span class="admin-nav-group-title">Manajemen Pengguna &amp; Akses</span><svg class="admin-nav-chevron" width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 3l3 3-3 3"/></svg></summary>
      <div class="admin-nav-group-body">
        <a href="<?= e(base_url('admin/users.php')) ?>" class="admin-nav-item <?= ($admin_page === 'users') ? 'active' : '' ?>" data-nav="users">Manajemen Pengguna</a>
        <a href="<?= e(base_url('admin/admins.php')) ?>" class="admin-nav-item <?= ($admin_page === 'admins') ? 'active' : '' ?>" data-nav="admins">Manajemen Administrator</a>
      </div>
    </details>
    <details class="admin-nav-group cat-3" <?= in_array($admin_page, ['skills', 'careers', 'mapping', 'vacancies']) ? 'open' : '' ?>>
      <summary class="admin-nav-group-head"><svg class="admin-nav-cat-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 13V7.5l3.5-3.5 3.5 3.5V13"/><path d="M2.5 13h11"/><path d="M4.5 13v-2.5h4v2.5"/></svg><span class="admin-nav-group-title">Pengembangan Karier &amp; Keterampilan</span><svg class="admin-nav-chevron" width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 3l3 3-3 3"/></svg></summary>
      <div class="admin-nav-group-body">
        <a href="<?= e(base_url('admin/skills.php')) ?>" class="admin-nav-item <?= ($admin_page === 'skills') ? 'active' : '' ?>" data-nav="skills">Manajemen Keterampilan</a>
        <a href="<?= e(base_url('admin/careers.php')) ?>" class="admin-nav-item <?= ($admin_page === 'careers') ? 'active' : '' ?>" data-nav="careers">Manajemen Target Karier</a>
        <a href="<?= e(base_url('admin/mapping.php')) ?>" class="admin-nav-item <?= ($admin_page === 'mapping') ? 'active' : '' ?>" data-nav="mapping">Career-Skill Mapping</a>
        <a href="<?= e(base_url('admin/vacancies.php')) ?>" class="admin-nav-item <?= ($admin_page === 'vacancies') ? 'active' : '' ?>" data-nav="vacancies">Manajemen Lowongan</a>
      </div>
    </details>
    <details class="admin-nav-group cat-4" <?= ($admin_page === 'settings') ? 'open' : '' ?>>
      <summary class="admin-nav-group-head"><svg class="admin-nav-cat-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 10.5A2.5 2.5 0 108 5.5a2.5 2.5 0 000 5z"/><path d="M1.8 8h2M12.2 8h2M8 1.8v2M8 12.2v2M3.6 3.6l1.4 1.4M11 11l1.4 1.4M12.4 3.6L11 5M5 11l-1.4 1.4"/></svg><span class="admin-nav-group-title">Pengaturan</span><svg class="admin-nav-chevron" width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 3l3 3-3 3"/></svg></summary>
      <div class="admin-nav-group-body">
        <a href="<?= e(base_url('admin/settings.php')) ?>" class="admin-nav-item <?= ($admin_page === 'settings') ? 'active' : '' ?>" data-nav="settings">Pengaturan Sistem</a>
      </div>
    </details>
  </nav>

  <div class="admin-sidebar-spacer"></div>

  <div class="admin-owner-context">
    <div class="admin-owner-context-head">
      <svg width="17" height="17" viewBox="0 0 17 17" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 1.5L2.5 4v4.5c0 3.5 2.5 6.5 6 7.5 3.5-1 6-4 6-7.5V4L8.5 1.5z"/><path d="M6 8.5l2 2 3.5-3.5"/></svg>
      <span>Akses Admin</span>
    </div>
    <p>Kelola akun, data master, dan relasi SkillBridge.</p>
  </div>

  <div class="admin-logout-footer">
    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6.75 15.75H3.375A1.125 1.125 0 012.25 14.625V3.375A1.125 1.125 0 013.375 2.25h3.375M12.375 12.375L15.75 9l-3.375-3.375M15.75 9H6.75"/></svg>
    <a href="<?= e(base_url('logout.php')) ?>">Logout</a>
  </div>
</aside>
