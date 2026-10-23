<?php
// includes/layouts/hr_header.php
// Header Konsol HR / Recruiter
require_once __DIR__ . '/../config/app.php';

$user = current_user() ?? [];
$rec_title = $rec_title ?? 'Perusahaan Partner';
?>
<header class="rec-header">
  <div class="rec-header-left">
    <!-- Hamburger button for Off-canvas Sidebar Mobile/Tablet -->
    <button type="button" class="sidebar-toggle" aria-label="Buka navigasi sidebar" data-toggle-sidebar>
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 5h14M3 10h14M3 15h14"/></svg>
    </button>
    <div class="rec-workspace-title">
      <svg width="18" height="18" viewBox="0 0 17 17" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 1.5L2.5 4v4.5c0 3.5 2.5 6.5 6 7.5 3.5-1 6-4 6-7.5V4L8.5 1.5z"/><path d="M6 8.5l2 2 3.5-3.5"/></svg>
      <span><?= htmlspecialchars($rec_title) ?></span>
    </div>
    <span class="rec-role-pill">Company Admin</span>
  </div>
  <div class="rec-header-right">
    <div class="rec-header-profile-text">
      <span class="rec-header-name"><?= htmlspecialchars($user['name'] ?? 'Aditya Pratama') ?></span>
      <span class="rec-header-role"><?= htmlspecialchars($user['role_label'] ?? 'Admin Perusahaan · PT Tech Indonesia') ?></span>
    </div>
    <div class="rec-avatar"><?= htmlspecialchars($user['initials'] ?? 'AP') ?></div>
  </div>
</header>
