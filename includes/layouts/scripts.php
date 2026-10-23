<?php
// includes/layouts/scripts.php
// Script Loader Global (ui.js, api.js, admin-nav.js) dan Page Specific Script

require_once __DIR__ . '/../config/app.php';

$role = $role ?? 'public';
$page_js = $page_js ?? ($page_script ?? '');
?>
    <script src="<?= asset('js/ui.js') ?>"></script>
    <?php if ($role === 'admin'): ?>
      <script src="<?= asset('js/admin-nav.js') ?>"></script>
    <?php endif; ?>
    <script src="<?= asset('js/api.js') ?>"></script>
    <?php if (!empty($page_js)): ?>
      <script src="<?= asset('js/pages/' . $page_js) ?>"></script>
    <?php endif; ?>
  </body>
</html>
