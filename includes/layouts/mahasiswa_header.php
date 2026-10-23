<?php
// includes/layouts/mahasiswa_header.php
// Top Navigation Bar Pill Mahasiswa
require_once __DIR__ . '/../config/app.php';

$nav_active = $nav_active ?? '';
$user = current_user() ?? [];

$nav_items = [
    'dashboard' => ['label' => 'Dashboard', 'href' => 'dashboard.php'],
    'skills'    => ['label' => 'My Skills', 'href' => 'skills.php'],
    'evidence'  => ['label' => 'Evidence', 'href' => 'evidence.php'],
    'career'    => ['label' => 'Career Target', 'href' => 'career.php'],
    'profile'   => ['label' => 'Profile', 'href' => 'profile.php'],
];
?>
<header class="navbar">
  <a class="navbar-brand" href="<?= e(base_url('index.php')) ?>" aria-label="SkillBridge home">
    <div class="navbar-logo">S</div>
    <div class="navbar-brand-text">
      <div class="navbar-brand-name">SkillBridge</div>
      <div class="navbar-brand-subtitle">STUDENT PORTFOLIO</div>
    </div>
  </a>

  <!-- Mobile Hamburger Toggle -->
  <button type="button" class="navbar-toggle" aria-label="Buka menu navigasi" aria-expanded="false">
    <span class="navbar-toggle-bar"></span>
    <span class="navbar-toggle-bar"></span>
    <span class="navbar-toggle-bar"></span>
  </button>

  <div class="navbar-nav">
    <?php foreach ($nav_items as $key => $item): ?>
      <a class="nav-item <?= $nav_active === $key ? 'active' : '' ?>"
         href="<?= e(base_url('mahasiswa/' . $item['href'])) ?>"
         <?= $nav_active === $key ? 'aria-current="page"' : '' ?>>
        <?= htmlspecialchars($item['label']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="navbar-profile">
    <a class="navbar-profile-link" href="<?= e(base_url('mahasiswa/profile.php')) ?>">
      <div class="navbar-profile-text">
        <div class="navbar-profile-name"><?= htmlspecialchars($user['name'] ?? 'Anda') ?></div>
        <div class="navbar-profile-email"><?= htmlspecialchars($user['email'] ?? '') ?></div>
      </div>
      <div class="navbar-avatar"><?= htmlspecialchars($user['initials'] ?? 'S') ?></div>
    </a>
    <a class="navbar-logout" href="<?= e(base_url('logout.php')) ?>" title="Keluar / Logout" aria-label="Keluar">
      <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M6.75 15.75H3.375A1.125 1.125 0 012.25 14.625V3.375A1.125 1.125 0 013.375 2.25h3.375M12.375 12.375L15.75 9l-3.375-3.375M15.75 9H6.75"/>
      </svg>
    </a>
  </div>
</header>
