<?php
// includes/layouts/head.php
// Head HTML bersama untuk seluruh role (Public, Mahasiswa, HR, Admin)
// Sesuai PRD Bagian 4 & CLAUDE.md Bagian 3

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../auth/csrf.php';

$page_title = $page_title ?? 'SkillBridge';
$role = $role ?? 'public';
$body_class = $body_class ?? '';
$body_attrs = $body_attrs ?? '';
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= e($page_title) ?></title>
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
    <!-- Stylesheet Utama & Per Role -->
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>" />
    <?php if ($role === 'hr'): ?>
      <link rel="stylesheet" href="<?= asset('css/recruiter.css') ?>" />
    <?php elseif ($role === 'admin'): ?>
      <link rel="stylesheet" href="<?= asset('css/admin.css') ?>" />
    <?php endif; ?>
    <script>
      window.APP_BASE_URL = <?= json_encode(base_url()) ?>;
    </script>
    <?= $extra_head ?? '' ?>
  </head>
  <body class="<?= e($body_class) ?>" <?= $body_attrs ?>>
