<?php
// includes/layouts/hr_footer.php
// Footer & Script Loader Konsol HR
require_once __DIR__ . '/../config/app.php';

$page_script = $page_script ?? '';
?>
      <footer style="padding: 24px 32px 32px; color: var(--text-muted); font-size: 13px; text-align: center;">
        <p>© 2026 SkillBridge · Recruiter &amp; Industry Partnership Console</p>
      </footer>
    </div><!-- /.rec-workspace -->

    <script src="<?= asset('js/ui.js') ?>"></script>
    <script src="<?= asset('js/api.js') ?>"></script>
    <?php if ($page_script): ?>
      <script src="<?= asset('js/pages/' . $page_script) ?>"></script>
    <?php endif; ?>
  </body>
</html>
