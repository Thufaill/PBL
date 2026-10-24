<?php
// public/mahasiswa/search.php
// Pencarian profil publik · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('mahasiswa');

$page_title = 'Find People · SkillBridge';
$role = 'mahasiswa';
$path_prefix = '../';
$nav_active = '';
$body_attrs = 'data-nav="" data-search-page="mahasiswa"';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/mahasiswa_header.php';
?>

<main class="container">
  <div class="page-head">
    <div class="page-head-left" style="max-width: 760px">
      <p class="eyebrow">Public directory</p>
      <h1>Find people</h1>
      <p style="margin-top: 8px; font-size: 14px; line-height: 1.45; color: var(--text-muted)">
        Search students who chose to share their public profile. Only public, active profiles appear here.
      </p>
    </div>
  </div>

  <section class="people-search-panel" aria-labelledby="people-search-title">
    <h2 class="visually-hidden" id="people-search-title">Search public profiles</h2>
    <form class="people-search-form" role="search" id="people-search-form">
      <label class="visually-hidden" for="people-search-input">Search by name, school, program, location, career target, or skill</label>
      <input type="search" id="people-search-input" name="q" maxlength="100" autocomplete="off" placeholder="Search by name, school, program, location, career, or skill..." />
      <button type="submit" class="btn btn-accent btn-44">Search</button>
    </form>
    <p class="people-search-status" id="people-search-status" aria-live="polite"></p>
    <div class="people-grid" id="people-search-results"></div>
  </section>
</main>

<?php require_once __DIR__ . '/../../includes/layouts/mahasiswa_footer.php'; ?>

<script src="<?= e(asset('js/ui.js')) ?>"></script>
<script src="<?= e(asset('js/api.js')) ?>"></script>
<script src="<?= e(asset('js/pages/search.js')) ?>"></script>
</body></html>
