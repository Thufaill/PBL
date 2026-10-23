<?php
// includes/layouts/public_footer.php
// Footer Publik untuk Landing Page & Halaman Publik
require_once __DIR__ . '/../config/app.php';
?>
<footer class="lp-footer">
  <div class="lp-footer-nav">
    <div class="lp-footer-brand">
      <div class="navbar-brand">
        <span class="navbar-logo" aria-hidden="true">S</span>
        <span class="navbar-brand-text">
          <span class="navbar-brand-name">SkillBridge</span>
          <span class="navbar-brand-subtitle">STUDENT PORTFOLIO</span>
        </span>
      </div>
      <p>
        Discover your strengths, build your skills, and connect your learning to a future that feels like
        you.
      </p>
    </div>

    <nav class="lp-footer-groups" aria-label="Footer">
      <div class="lp-footer-group">
        <h3>Platform</h3>
        <a href="<?= e(base_url('index.php#main')) ?>">Home</a>
        <a href="<?= e(base_url('mahasiswa/skills.php')) ?>">Explore Skills</a>
        <a href="<?= e(base_url('index.php#learning-paths')) ?>">Learning Paths</a>
        <a href="<?= e(base_url('mahasiswa/career.php')) ?>">Career Opportunities</a>
      </div>

      <div class="lp-footer-group">
        <h3>Learning resources</h3>
        <a href="<?= e(base_url('index.php#insights')) ?>">Resources</a>
        <a href="<?= e(base_url('mahasiswa/skills.php')) ?>">Skill development</a>
        <a href="<?= e(base_url('mahasiswa/evidence.php')) ?>">Portfolio guidance</a>
        <a href="<?= e(base_url('mahasiswa/career.php')) ?>">Career direction</a>
      </div>

      <div class="lp-footer-group">
        <h3>SkillBridge</h3>
        <a href="<?= e(base_url('index.php#how-title')) ?>">About</a>
        <a href="<?= e(base_url('index.php#main')) ?>">Contact</a>
      </div>
    </nav>
  </div>

  <div class="lp-footer-legal">
    <span>© 2026 SkillBridge. All rights reserved.</span>
    <span>Keep growing. Keep moving forward.</span>
  </div>
</footer>
