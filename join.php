<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth_check.php';
$token = csrf_token();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>الانضمام لجروب</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e($token) ?>">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="topbar">
    <div class="topbar-title">نظام إدارة العيادة</div>
    <div class="topbar-user">
      <span><?= e($_SESSION['username'] ?? '') ?></span>
      <a class="logout-link" href="logout.php">خروج</a>
    </div>
  </header>

  <main class="dashboard-main" style="flex-direction:column;">
    <div class="login-card" id="joinBox" style="width:360px;">
      <h1>الانضمام لجروب</h1>
      <div id="joinContent">جاري التحميل...</div>
    </div>
  </main>

  <script src="assets/js/app.js"></script>
  <script src="assets/js/join.js"></script>
</body>
</html>
