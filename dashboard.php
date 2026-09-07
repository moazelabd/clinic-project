<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth_check.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>الرئيسية</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
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

  <main class="dashboard-main">
    <a class="big-nav-btn" href="doctors.php">
      <span class="big-nav-icon">🩺</span>
      <span>الدكاتره</span>
    </a>
    <a class="big-nav-btn" href="storages.php">
      <span class="big-nav-icon">📦</span>
      <span>المخازن</span>
    </a>
  </main>
</body>
</html>
