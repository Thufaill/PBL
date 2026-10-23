<?php
// includes/layouts/admin_header.php
// Header Konsol Admin Platform
require_once __DIR__ . '/../config/app.php';

$user = current_user() ?? [];
$admin_header_title = $admin_header_title ?? 'Admin SkillBridge';
?>
<!-- Header Admin -->
<header class="admin-header">
  <div class="admin-header-left">
    <!-- Hamburger button for Off-canvas Sidebar Mobile/Tablet -->
    <button type="button" class="sidebar-toggle" aria-label="Buka navigasi sidebar" data-toggle-sidebar>
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 5h14M3 10h14M3 15h14"/></svg>
    </button>
    <svg width="18" height="18" viewBox="0 0 17 17" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 1.5L2.5 4v4.5c0 3.5 2.5 6.5 6 7.5 3.5-1 6-4 6-7.5V4L8.5 1.5z"/><path d="M6 8.5l2 2 3.5-3.5"/></svg>
    <span class="admin-header-title"><?= htmlspecialchars($admin_header_title) ?></span>
    <span class="admin-badge-pill">Admin</span>
  </div>
  <div class="admin-header-right">
    <div class="admin-header-profile-text">
      <span class="admin-header-name"><?= htmlspecialchars($user['name'] ?? 'Aditya Pratama') ?></span>
      <span class="admin-header-role"><?= htmlspecialchars($user['role_label'] ?? 'Admin utama · Dilindungi') ?></span>
    </div>
    <div class="admin-avatar"><?= htmlspecialchars($user['initials'] ?? 'AP') ?></div>
  </div>
</header>
