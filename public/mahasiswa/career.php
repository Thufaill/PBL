<?php
// public/mahasiswa/career.php
// Halaman Career Target · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('mahasiswa');

$page_title = 'Career Target · SkillBridge';
$role = 'mahasiswa';
$path_prefix = '../';
$nav_active = 'career';
$body_attrs = 'data-nav="career"';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/mahasiswa_header.php';
?>


    <main class="container">
      <div class="page-head">
        <div class="page-head-left">
          <p class="eyebrow">Choose a direction</p>
          <h1>Career Target</h1>
          <p>Select one simple target to compare with your current skills. You can change it at any time.</p>
        </div>
      </div>

      <div class="banner" id="banner">
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="9" cy="9" r="7.5" />
          <path d="M9 5.25v5.25M9 12.75h.008" stroke-linecap="round" />
        </svg>
        <span id="banner-text"></span>
      </div>

      <div class="career-grid" id="career-grid"></div>
    </main>

    

<?php require_once __DIR__ . '/../../includes/layouts/mahasiswa_footer.php'; ?>

    <script src="<?= e(asset('js/ui.js')) ?>"></script>
    <script src="<?= e(asset('js/api.js')) ?>"></script>
    <script src="<?= e(asset('js/pages/career.js')) ?>"></script>
  </body>
</html>