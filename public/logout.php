<?php
// public/logout.php
// Logout Sesi Pengguna
require_once __DIR__ . '/../includes/auth/auth.php';
logout();
header('Location: login.php');
exit;
