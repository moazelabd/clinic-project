<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth_check.php';
$token = csrf_token();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>الدكاتره</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e($token) ?>">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="topbar">
    <div class="topbar-title"><a href="dashboard.php">⬅ الرئيسية</a> / الدكاتره</div>
    <div class="topbar-user">
      <span><?= e($_SESSION['username'] ?? '') ?></span>
      <a class="logout-link" href="logout.php">خروج</a>
    </div>
  </header>

  <div class="app-layout">
    <aside class="sidebar">
      <div class="sidebar-header">dr names</div>
      <div class="sidebar-list" id="doctorList"></div>
      <button class="sidebar-add-btn" id="btnAddDoctor">+ إضافة دكتور</button>
    </aside>

    <main class="content-area">
      <div id="noDoctorSelected" class="empty-hint">اختار دكتور من القايمة</div>

      <div id="doctorContent" class="doctor-detail" style="display:none;">
        <div class="doctor-head">
          <div id="doctorAvatarWrap"></div>
          <div class="doctor-head-info">
            <div class="doctor-name" id="doctorNameDisplay"></div>
            <input type="text" class="doctor-title-input" id="doctorTitle" placeholder="عنوان">
          </div>
          <button class="btn btn-danger" id="btnDeleteDoctor">حذف الدكتور</button>
        </div>

        <div class="section-box">
          <div class="section-title">القائمة السوداء</div>
          <div class="blacklist-row">
            <span class="status-badge" id="blacklistBadge"></span>
            <button class="btn" id="btnToggleBlacklist"></button>
          </div>
          <textarea class="field-textarea" id="blacklistReason" placeholder="سبب الإدراج في القائمة السوداء" style="margin-top:10px; display:none;"></textarea>
        </div>

        <div class="section-box">
          <div class="section-title">مواعيد الزيارات</div>
          <div id="visitsList"></div>
          <button class="btn btn-primary btn-sm" id="btnAddVisit" style="margin-top:8px;">+ إضافة زيارة</button>
        </div>

        <div class="section-box">
          <div class="section-title">ملاحظة عامة عن الدكتور</div>
          <textarea class="field-textarea" id="generalNote" placeholder="اكتب ملاحظة..."></textarea>
          <button class="btn btn-primary btn-sm" id="btnSaveNote" style="margin-top:8px;">حفظ الملاحظة</button>
        </div>
      </div>
    </main>
  </div>

  <!-- Add doctor modal -->
  <div class="modal-overlay" id="modalAddDoctor">
    <div class="modal-box">
      <h3>إضافة دكتور</h3>
      <label style="font-size:13px;color:var(--text-dim)">اسم الدكتور</label>
      <input type="text" class="field-input" id="newDoctorName" maxlength="150">
      <label style="font-size:13px;color:var(--text-dim)">صورة (اختياري)</label>
      <input type="file" id="newDoctorImage" accept="image/png,image/jpeg,image/webp,image/gif">
      <div class="modal-actions">
        <button class="btn js-modal-cancel" data-modal="modalAddDoctor">إلغاء</button>
        <button class="btn btn-primary" id="confirmAddDoctor">إضافة</button>
      </div>
    </div>
  </div>

  <!-- Blacklist confirm modal -->
  <div class="modal-overlay" id="modalBlacklist">
    <div class="modal-box">
      <h3>إضافة للقائمة السوداء؟</h3>
      <p style="color:var(--text-dim); font-size:14px;">متأكد إنك عايز تضيف الدكتور ده للقائمة السوداء؟</p>
      <textarea class="field-textarea" id="blacklistReasonInput" placeholder="السبب (اختياري)"></textarea>
      <div class="modal-actions">
        <button class="btn js-modal-cancel" data-modal="modalBlacklist">إلغاء</button>
        <button class="btn btn-danger" id="confirmBlacklist">تأكيد</button>
      </div>
    </div>
  </div>

  <!-- Add visit modal -->
  <div class="modal-overlay" id="modalAddVisit">
    <div class="modal-box">
      <h3>إضافة زيارة</h3>
      <label style="font-size:13px;color:var(--text-dim)">التاريخ</label>
      <input type="date" class="field-input" id="visitDate">
      <div class="modal-actions">
        <button class="btn js-modal-cancel" data-modal="modalAddVisit">إلغاء</button>
        <button class="btn btn-primary" id="confirmAddVisit">إضافة</button>
      </div>
    </div>
  </div>

  <!-- Visit note modal -->
  <div class="modal-overlay" id="modalVisitNote">
    <div class="modal-box">
      <h3>ملاحظة على الزيارة</h3>
      <input type="hidden" id="visitNoteId">
      <textarea class="field-textarea" id="visitNoteText" placeholder="اكتب ملاحظة..."></textarea>
      <div class="modal-actions">
        <button class="btn js-modal-cancel" data-modal="modalVisitNote">إلغاء</button>
        <button class="btn btn-primary" id="confirmVisitNote">حفظ</button>
      </div>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <script src="assets/js/doctors.js"></script>
</body>
</html>
