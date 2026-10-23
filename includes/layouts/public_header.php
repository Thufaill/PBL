<?php
// includes/layouts/public_header.php
// Header Navigasi Publik untuk Landing Page & Halaman Publik
require_once __DIR__ . '/../config/app.php';
?>
<header class="navbar">
  <a class="navbar-brand" href="<?= e(base_url('index.php')) ?>" aria-label="SkillBridge home">
    <span class="navbar-logo" aria-hidden="true">S</span>
    <span class="navbar-brand-text">
      <span class="navbar-brand-name">SkillBridge</span>
      <span class="navbar-brand-subtitle">STUDENT PORTFOLIO</span>
    </span>
  </a>

  <!-- Mobile Hamburger Toggle -->
  <button type="button" class="navbar-toggle" aria-label="Buka menu navigasi" aria-expanded="false">
    <span class="navbar-toggle-bar"></span>
    <span class="navbar-toggle-bar"></span>
    <span class="navbar-toggle-bar"></span>
  </button>

  <nav class="navbar-nav" aria-label="Main">
    <a class="nav-item <?= ($nav_active ?? '') === 'home' ? 'active' : '' ?>" href="<?= e(base_url('index.php#main')) ?>" <?= ($nav_active ?? '') === 'home' ? 'aria-current="page"' : '' ?>>Home</a>
    <a class="nav-item <?= ($nav_active ?? '') === 'skills' ? 'active' : '' ?>" href="<?= e(base_url('mahasiswa/skills.php')) ?>">Explore Skills</a>
    <a class="nav-item" href="<?= e(base_url('index.php#learning-paths')) ?>">Learning Paths</a>
    <a class="nav-item <?= ($nav_active ?? '') === 'career' ? 'active' : '' ?>" href="<?= e(base_url('mahasiswa/career.php')) ?>">Career Opportunities</a>
    <a class="nav-item" href="<?= e(base_url('index.php#insights')) ?>">Resources</a>
  </nav>

  <div class="navbar-actions">
    <?php if (is_logged_in()): ?>
      <a class="navbar-login" href="<?= e(base_url('logout.php')) ?>">Logout</a>
      <a class="btn btn-accent btn-44" href="<?= e(base_url(get_user_role() === 'admin' ? 'admin/index.php' : (get_user_role() === 'hr' ? 'hr/index.php' : 'mahasiswa/dashboard.php'))) ?>">Dashboard</a>
    <?php else: ?>
      <a class="navbar-login" href="<?= e(base_url('login.php')) ?>">Login</a>
      <a class="btn btn-accent btn-44" href="<?= e(base_url('register.php')) ?>">Get Started</a>
    <?php endif; ?>
  </div>
</header>
