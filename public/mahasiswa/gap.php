<?php
// public/mahasiswa/gap.php
// Halaman Skill Gap · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('mahasiswa');

$page_title = 'Skill Gap · SkillBridge';
$role = 'mahasiswa';
$path_prefix = '../';
$nav_active = 'gap';
$body_attrs = 'data-nav="gap"';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/mahasiswa_header.php';
?>


    <main class="container">
      <div class="page-head">
        <div class="page-head-left">
          <p class="eyebrow">Target comparison</p>
          <h1>Skill Gap</h1>
          <p style="opacity: 0.85">You already have a strong foundation. Here is the simple next step to help your portfolio feel complete.</p>
        </div>
      </div>

      <!-- Two columns: left (target + have) + right (explore) -->
      <div class="gap-layout-grid" style="display: grid; grid-template-columns: 1fr 400px; gap: 24px; align-items: start">
        <!-- Left column -->
        <div style="display: grid; gap: 24px">
          <!-- Target Career Tile -->
          <div class="card-lg" style="display: grid; gap: 20px">
            <div style="display: grid; gap: 8px">
              <p class="eyebrow">Target career</p>
              <div id="target-info"></div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px" id="stat-tiles"></div>
          </div>

          <!-- Skills You Already Have -->
          <article class="card-lg" style="display: grid; gap: 18px">
            <div class="section-heading">
              <svg width="22" height="22" viewBox="0 0 22 22" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="10" stroke-linecap="round"/>
                <path d="M6 11l3 3 7-7" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <h3>Skills You Already Have</h3>
            </div>
            <p style="font-size: 14px; color: var(--text-muted); opacity: 0.85">These are the skills already recorded in My Skills and matched to this target.</p>
            <div id="have-list"></div>
          </article>
        </div>

        <!-- Right column: What to Explore Next -->
        <article class="card-lg" style="display: grid; gap: 18px; align-content: start">
          <div class="section-heading">
            <svg width="22" height="22" viewBox="0 0 22 22" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="10" stroke-linecap="round" stroke-dasharray="3 3"/>
            </svg>
            <h3>What to Explore Next</h3>
          </div>
          <p style="font-size: 14px; color: var(--text-muted); opacity: 0.85">Focus on one skill at a time. Once you add Express.js and attach evidence, your portfolio will feel much stronger.</p>
          <div id="explore-list"></div>
          <div class="inset-box" id="next-step-box"></div>
        </article>
      </div>
    </main>

    

<?php require_once __DIR__ . '/../../includes/layouts/mahasiswa_footer.php'; ?>

    <script src="<?= e(asset('js/ui.js')) ?>"></script>
    <script src="<?= e(asset('js/api.js')) ?>"></script>
    <script src="<?= e(asset('js/pages/gap.js')) ?>"></script>
  </body>
</html>