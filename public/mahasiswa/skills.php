<?php
// public/mahasiswa/skills.php
// Halaman My Skills · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('mahasiswa');

$page_title = 'My Skills · SkillBridge';
$role = 'mahasiswa';
$path_prefix = '../';
$nav_active = 'skills';
$body_attrs = 'data-nav="skills"';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/mahasiswa_header.php';
?>


    <main class="container">
      <div class="page-head">
        <div class="page-head-left" style="max-width: 760px">
          <p class="eyebrow">Your profile</p>
          <h1>My Skills</h1>
          <p style="margin-top: 8px; font-size: 14px; line-height: 1.45; color: var(--text-muted)">
            A simple, reassuring view of your target career, strongest skills, and the evidence you already have.
          </p>
        </div>
        <button class="btn btn-accent btn-44" type="button" id="btn-add-skill">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false">
            <path d="M3.333 8h9.334M8 3.333v9.334"/>
          </svg>
          Add skill
        </button>
      </div>

      <div class="skills-layout-grid" style="display: grid; grid-template-columns: 1fr 360px; gap: 20px">
        <!-- Left Column -->
        <div style="display: grid; gap: 16px; align-content: start">
          <!-- Target Career -->
          <article class="card-lg" style="display: grid; gap: 16px">
            <div style="display: flex; justify-content: space-between; align-items: flex-start">
              <h3>Target career</h3>
              <div class="card-icon-box" style="background: var(--primary-subtle); color: var(--primary); border-color: #bfdbfe">
                <svg width="17" height="17" viewBox="0 0 17 17" fill="none" stroke="currentColor" stroke-width="1.5">
                  <path d="M8.5 8.5h.007M11.334 4.25V2.833a1.417 1.417 0 0 0-1.417-1.417H7.083a1.417 1.417 0 0 0-1.417 1.417V4.25M15.584 9.208a11.333 11.333 0 0 1-14.168 0M2.833 4.25h11.334c.782 0 1.416.634 1.416 1.416v7.084c0 .782-.634 1.416-1.416 1.416H2.833a1.417 1.417 0 0 1-1.417-1.416V5.666c0-.782.634-1.416 1.417-1.416z"/>
                </svg>
              </div>
            </div>
            <div id="card-target" data-loading="text"></div>
          </article>

          <!-- Skills Summary -->
          <article class="card-lg" style="display: grid; gap: 16px">
            <div style="display: flex; justify-content: space-between; align-items: flex-start">
              <div>
                <h3>Skills summary</h3>
                <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px">
                  Focus on the skills you can explain clearly and connect to real work examples.
                </p>
              </div>
              <span class="badge" id="badge-strong" style="font-size: 11px; padding: 4px 9px"><span class="sb-skeleton-line" data-sb-skeleton aria-hidden="true" style="width: 52px"></span></span>
            </div>
            <div>
              <h2 style="font-size: 20px; margin-bottom: 10px">Your strongest skills</h2>
              <div id="skills-list" data-loading="rows" data-loading-count="3"></div>
            </div>
          </article>
        </div>

        <!-- Right Column -->
        <div style="display: grid; gap: 16px; align-content: start">
          <!-- Evidence Summary -->
          <article class="card-lg" style="display: grid; gap: 16px">
            <div style="display: flex; justify-content: space-between; align-items: flex-start">
              <h3>Evidence summary</h3>
              <div class="card-icon-box" style="background: var(--tint-pink); color: var(--accent); border-color: var(--pink-border)">
                <svg width="17" height="17" viewBox="0 0 17 17" fill="none" stroke="currentColor" stroke-width="1.5">
                  <path d="M6.375 9.208l1.417 1.417 2.833-2.834M14.167 14.166a1.417 1.417 0 0 0 1.417-1.416V5.666a1.417 1.417 0 0 0-1.417-1.416H8.571a1.416 1.416 0 0 0-1.68-.375L6.8 2.762a1.417 1.417 0 0 0-1.183-.637H2.833a1.417 1.417 0 0 0-1.417 1.417v9.208c0 .782.635 1.416 1.417 1.416h11.334z"/>
                </svg>
              </div>
            </div>
            <div id="card-evidence" data-loading="text"></div>
          </article>
        </div>
      </div>
    </main>

    

<?php require_once __DIR__ . '/../../includes/layouts/mahasiswa_footer.php'; ?>

    <script src="<?= e(asset('js/ui.js')) ?>"></script>
    <script src="<?= e(asset('js/api.js')) ?>"></script>
    <script src="<?= e(asset('js/pages/skills.js')) ?>"></script>
  </body>
</html>