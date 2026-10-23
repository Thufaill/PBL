<?php
// public/mahasiswa/dashboard.php
// Halaman Dashboard · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('mahasiswa');

$page_title = 'Dashboard · SkillBridge';
$role = 'mahasiswa';
$path_prefix = '../';
$nav_active = 'dashboard';
$body_attrs = 'data-nav="dashboard"';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/mahasiswa_header.php';
?>


    <main style="max-width: 1040px; margin: 0 auto; padding: 40px 48px 48px">
      <!-- Page Heading -->
      <div style="display: flex; justify-content: space-between; align-items: flex-end; gap: 24px; margin-bottom: 24px">
        <div style="max-width: 760px">
          <p class="eyebrow">Semester 3 focus</p>
          <h1 style="margin-top: 8px">Finding your direction doesn't have to feel overwhelming.</h1>
          <p style="margin-top: 10px; font-size: 15px; line-height: 1.45; color: var(--text-muted)">
            Keep your target career, current skills, and next steps in one calm and simple place.
          </p>
        </div>
        <a class="btn btn-accent" href="evidence.php" style="height: 44px; padding: 0 17px; border-radius: 10px; gap: 8px; box-shadow: var(--shadow-btn)">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M3.333 8h9.334M8 3.333v9.334"/>
          </svg>
          Add Evidence
        </a>
      </div>

      <!-- Main Content: 2 columns -->
      <div class="dashboard-layout-grid" style="display: grid; grid-template-columns: 1fr 360px; gap: 24px">
        <!-- Left Column: 3 cards -->
        <div style="display: grid; gap: 20px">
          <!-- Card 1: Target Career -->
          <a href="career.php" class="card-interactive">
            <article class="card-lg" style="gap: 18px">
              <div style="display: flex; justify-content: space-between; align-items: flex-start">
                <h3>Target career</h3>
                <div class="card-icon-box">
                  <svg width="17" height="17" viewBox="0 0 17 17" fill="none" stroke="currentColor" stroke-width="1">
                    <path d="M8.5 8.5h.007M11.334 4.25V2.833a1.417 1.417 0 0 0-1.417-1.417H7.083a1.417 1.417 0 0 0-1.417 1.417V4.25M15.584 9.208a11.333 11.333 0 0 1-14.168 0M2.833 4.25h11.334c.782 0 1.416.634 1.416 1.416v7.084c0 .782-.634 1.416-1.416 1.416H2.833a1.417 1.417 0 0 1-1.417-1.416V5.666c0-.782.634-1.416 1.417-1.416z"/>
                  </svg>
                </div>
              </div>
              <div id="card-target"></div>
              <svg class="card-arrow" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M6 12l4-4-4-4"/>
              </svg>
            </article>
          </a>

          <!-- Card 2: Skills Summary -->
          <a href="skills.php" class="card-interactive">
            <article class="card-lg" style="gap: 9px">
              <div style="display: flex; justify-content: space-between; align-items: flex-start">
                <h3>Skills summary</h3>
                <div class="card-icon-box">
                  <svg width="17" height="17" viewBox="0 0 17 17" fill="none" stroke="currentColor" stroke-width="1">
                    <path d="M9.088 1.544a1.417 1.417 0 0 0-1.176 0L1.841 4.307a.709.709 0 0 0 0 1.296l6.078 2.77a1.417 1.417 0 0 0 1.176 0l6.078-2.763a.708.708 0 0 0 0-1.296L9.088 1.544zM1.416 8.5c0 .135.038.268.111.382.072.115.177.206.3.263l6.092 2.77a1.417 1.417 0 0 0 1.169 0l6.078-2.763a.708.708 0 0 0 0-1.296M1.416 12.042c0 .135.038.268.111.382.072.114.177.205.3.262l6.092 2.77a1.417 1.417 0 0 0 1.169 0l6.078-2.763a.708.708 0 0 0 0-1.296"/>
                  </svg>
                </div>
              </div>
              <div id="card-skills"></div>
              <svg class="card-arrow" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M6 12l4-4-4-4"/>
              </svg>
            </article>
          </a>

          <!-- Card 3: Simple Skill Gap -->
          <a href="gap.php" class="card-interactive">
            <article class="card-lg" style="gap: 18px">
              <div style="display: flex; justify-content: space-between; align-items: flex-start">
                <h3>Simple skill gap</h3>
                <div class="card-icon-box">
                  <svg width="17" height="17" viewBox="0 0 17 17" fill="none" stroke="currentColor" stroke-width="1">
                    <circle cx="8.5" cy="8.5" r="7.084"/>
                  </svg>
                </div>
              </div>
              <div id="card-explore"></div>
              <svg class="card-arrow" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M6 12l4-4-4-4"/>
              </svg>
            </article>
          </a>
        </div>

        <!-- Right Column: Evidence Summary -->
        <article class="card-lg" style="gap: 18px; align-self: start">
          <div style="display: flex; justify-content: space-between; align-items: flex-start">
            <h3>Evidence summary</h3>
            <div style="width: 36px; height: 36px; border-radius: 10px; background: var(--tint-pink); display: grid; place-items: center">
              <svg width="17" height="17" viewBox="0 0 17 17" fill="none" stroke="currentColor" stroke-width="1">
                <path d="M6.375 9.208l1.417 1.417 2.833-2.834M14.167 14.166a1.417 1.417 0 0 0 1.417-1.416V5.666a1.417 1.417 0 0 0-1.417-1.416H8.571a1.416 1.416 0 0 0-1.68-.375L6.8 2.762a1.417 1.417 0 0 0-1.183-.637H2.833a1.417 1.417 0 0 0-1.417 1.417v9.208c0 .782.635 1.416 1.417 1.416h11.334z"/>
              </svg>
            </div>
          </div>
          <div id="card-evidence"></div>
        </article>
      </div>
    </main>

    

<?php require_once __DIR__ . '/../../includes/layouts/mahasiswa_footer.php'; ?>

    <script src="<?= e(asset('js/ui.js')) ?>"></script>
    <script src="<?= e(asset('js/api.js')) ?>"></script>
    <script src="<?= e(asset('js/pages/dashboard.js')) ?>"></script>
  </body>
</html>