<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth_check.php';
if (!is_admin()) {
    header('Location: dashboard.php');
    exit;
}
$token = csrf_token();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>الجروبات</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e($token) ?>">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="topbar">
    <div class="topbar-title"><a href="dashboard.php">⬅ الرئيسية</a> / الجروبات</div>
    <div class="topbar-user">
      <span><?= e($_SESSION['username'] ?? '') ?></span>
      <a class="logout-link" href="logout.php">خروج</a>
    </div>
  </header>

  <main class="content-area">
    <div class="section-box" style="margin-bottom:16px;">
      <div class="section-title">إضافة جروب جديد</div>
      <div class="content-toolbar">
        <input type="text" class="field-input" id="newGroupName" placeholder="اسم الجروب" style="flex:1;">
        <button class="btn btn-primary" id="btnCreateGroup">إضافة</button>
      </div>
    </div>

    <div class="section-box" style="margin-bottom:16px;">
      <div class="section-title">كل الجروبات (دوس على أي جروب عشان تشتغل عليه)</div>
      <div id="groupsList"></div>
    </div>

    <div class="section-box">
      <div class="section-title">طلبات انضمام معلّقة</div>
      <div id="pendingList"></div>
    </div>
  </main>

  <script src="assets/js/app.js"></script>
  <script src="assets/js/groups.js"></script>
</body>
</html>
