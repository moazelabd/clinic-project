<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth_check.php';
$token = csrf_token();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>المخازن</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e($token) ?>">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="topbar">
    <div class="topbar-title"><a href="dashboard.php">⬅ الرئيسية</a> / المخازن</div>
    <div class="topbar-user">
      <span><?= e($_SESSION['username'] ?? '') ?></span>
      <a class="logout-link" href="logout.php">خروج</a>
    </div>
  </header>

  <div class="app-layout">
    <aside class="sidebar">
      <div class="sidebar-header">storage names</div>
      <div class="sidebar-list" id="storageList"></div>
      <button class="sidebar-add-btn" id="btnAddStorage">+ إضافة مخزن جديد</button>
    </aside>

    <main class="content-area">
      <div id="noStorageSelected" class="empty-hint">اختار مخزن من القايمة</div>

      <div id="storageContent" style="display:none;">
        <div class="content-toolbar">
          <input type="text" class="search-input" id="searchInput" placeholder="ابحث باسم الدواء...">
          <button class="btn btn-primary" id="btnAddMedicine">+ إضافة دواء</button>
          <button class="btn btn-danger" id="btnDeleteStorage">حذف المخزن</button>
        </div>
        <div class="card-grid" id="medicineGrid"></div>
      </div>
    </main>
  </div>

  <!-- Add storage modal -->
  <div class="modal-overlay" id="modalAddStorage">
    <div class="modal-box">
      <h3>إضافة مخزن جديد</h3>
      <input type="text" class="field-input" id="newStorageName" maxlength="150" placeholder="اسم المخزن">
      <div class="modal-actions">
        <button class="btn js-modal-cancel" data-modal="modalAddStorage">إلغاء</button>
        <button class="btn btn-primary" id="confirmAddStorage">إضافة</button>
      </div>
    </div>
  </div>

  <!-- Add/Edit medicine modal -->
  <div class="modal-overlay" id="modalMedicine">
    <div class="modal-box">
      <h3 id="medicineModalTitle">إضافة دواء</h3>
      <input type="hidden" id="medId">
      <label style="font-size:13px;color:var(--text-dim)">اسم الدواء</label>
      <input type="text" class="field-input" id="medName" maxlength="200">
      <label style="font-size:13px;color:var(--text-dim)">صورة (اختياري)</label>
      <input type="file" id="medImage" accept="image/png,image/jpeg,image/webp,image/gif">

      <div id="stockRow">
        <label style="font-size:13px;color:var(--text-dim)">الستوك</label>
        <div class="stock-stepper">
          <button type="button" id="stockMinus">−</button>
          <input type="number" id="medStock" min="0" value="0">
          <button type="button" id="stockPlus">+</button>
        </div>
      </div>

      <div class="modal-actions">
        <button class="btn btn-danger" id="btnDeleteMedicine" style="display:none;">حذف الدواء</button>
        <button class="btn js-modal-cancel" data-modal="modalMedicine">إلغاء</button>
        <button class="btn btn-primary" id="confirmMedicine">حفظ</button>
      </div>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <script src="assets/js/storages.js"></script>
</body>
</html>
