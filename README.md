# نظام إدارة العيادة (دكاتره + مخازن)

مشروع PHP + MySQL بسيط لإدارة:
- **المخازن**: مخازن أدوية، كل مخزن فيه أدوية (صورة اختياري، اسم، ستوك)، بحث، تعديل، حذف.
- **الدكاتره**: بيانات دكتور (صورة اختياري، اسم، عنوان)، قائمة سوداء، سجل زيارات مع ملاحظات، ملاحظة عامة.

كل الصفحات محمية بتسجيل دخول (لا يوجد تسجيل حساب جديد من الموقع — الحسابات تُضاف يدويًا منك فقط، انظر تحت).

## المتطلبات
- PHP 8.0+ مع إضافات: `pdo_mysql`, `fileinfo`, `gd` (للتحقق من الصور)
- MySQL / MariaDB
- خادم ويب (Apache مثلاً مع `mod_php`، أو Nginx + PHP-FPM)

## خطوات التركيب

1. **إنشاء قاعدة البيانات:**
   ```bash
   mysql -u root -p < schema.sql
   ```
   هذا هينشئ قاعدة البيانات `clinic_db` وكل الجداول.

2. **إنشاء مستخدم MySQL مخصص للمشروع** (لا تستخدم root):
   ```sql
   CREATE USER 'clinic_user'@'localhost' IDENTIFIED BY 'اختار-باسورد-قوي';
   GRANT SELECT, INSERT, UPDATE, DELETE ON clinic_db.* TO 'clinic_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

3. **عدّل بيانات الاتصال** في `includes/config.php`:
   ```php
   const DB_HOST = 'localhost';
   const DB_NAME = 'clinic_db';
   const DB_USER = 'clinic_user';
   const DB_PASS = 'اختار-باسورد-قوي';
   ```

4. **أضف أول مستخدم (يدويًا، من التيرمنال فقط):**
   ```bash
   php create_admin.php admin "PasswordQuiJdaan123!"
   ```
   ده هيعمل هاش للباسورد (bcrypt) ويحطه في جدول `users`. كرر الأمر لأي مستخدم إضافي.
   **بعد ما تخلص، امسح أو انقل `create_admin.php` بره الفولدر اللي على الإنترنت** كإجراء احترازي إضافي.

5. **ارفع المشروع** على السيرفر، وتأكد إن فولدر `uploads/` قابل للكتابة:
   ```bash
   chmod -R 755 uploads
   ```

6. افتح الموقع → هيوجهك أوتوماتيك لصفحة **تسجيل الدخول**.

## ملاحظات أمان تم تطبيقها

- **SQL Injection**: كل استعلامات قاعدة البيانات باستخدام PDO Prepared Statements حصريًا (لا يوجد أي دمج نصوص مباشر في SQL).
- **XSS**: كل نص يُطبع في الصفحات يمر على `htmlspecialchars()`، وواجهات الـJS بتستخدم `textContent`/escaping قبل الحقن في HTML.
- **CSRF**: كل طلب يغيّر بيانات (إضافة/تعديل/حذف) لازم يبعت `csrf_token` صحيح مرتبط بالجلسة.
- **صفحة تسجيل الدخول**:
  - لا يوجد تسجيل حساب عام؛ الحسابات تُضاف يدويًا فقط.
  - الباسوردات مخزنة بـ `password_hash` (bcrypt)، وأبدًا نص صريح.
  - حماية من محاولات تخمين الباسورد (Brute-force): قفل مؤقت بعد 5 محاولات فاشلة خلال 15 دقيقة لكل IP.
  - `session_regenerate_id()` بعد كل تسجيل دخول ناجح لمنع Session Fixation.
  - كوكيز الجلسة: `HttpOnly`, `SameSite=Strict`, و`Secure` تلقائيًا لو الموقع شغال على HTTPS.
  - انتهاء صلاحية الجلسة تلقائيًا بعد عدم نشاط لمدة ساعتين.
- **رفع الصور**: يتم التحقق من نوع الملف الحقيقي (MIME) عبر `finfo` + `getimagesize`، حجم أقصى 4MB، وإعادة تسمية الملف باسم عشوائي لمنع أي تلاعب أو تنفيذ أكواد، بالإضافة لملف `.htaccess` داخل `uploads/` يمنع تنفيذ أي سكريبت هناك.
- **Headers أمان إضافية**: `X-Frame-Options`, `X-Content-Type-Options`, `Content-Security-Policy`, إلخ، مُرسلة مع كل صفحة.

## هيكل المشروع
```
├── index.php              # يوجّه لصفحة الدخول أو الرئيسية
├── login.php / logout.php
├── dashboard.php          # زرار الدكاتره + زرار المخازن
├── storages.php           # صفحة المخازن (سايدبار + أدوية)
├── doctors.php            # صفحة الدكاتره (سايدبار + تفاصيل الدكتور)
├── create_admin.php       # سكريبت CLI لإضافة مستخدم جديد يدويًا
├── schema.sql             # هيكل قاعدة البيانات
├── includes/
│   ├── config.php         # اتصال قاعدة البيانات + إعدادات الأمان
│   ├── auth_check.php     # حماية الصفحات
│   └── functions.php      # التحقق من المدخلات + رفع الصور
├── api/
│   ├── storages_api.php
│   ├── medicines_api.php
│   ├── doctors_api.php
│   └── visits_api.php
├── assets/css/style.css
├── assets/js/{app,storages,doctors}.js
└── uploads/{doctors,medicines}/
```
