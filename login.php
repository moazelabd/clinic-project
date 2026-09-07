<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';

// already logged in? go straight to dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$LOCKOUT_MAX_ATTEMPTS = 5;
$LOCKOUT_WINDOW_MIN   = 15;

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function recent_failed_attempts(PDO $pdo, string $ip, int $windowMinutes): int
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM login_attempts WHERE ip_address = :ip AND attempted_at > (NOW() - INTERVAL :mins MINUTE)'
    );
    $stmt->bindValue(':ip', $ip);
    $stmt->bindValue(':mins', $windowMinutes, PDO::PARAM_INT);
    $stmt->execute();
    return (int)$stmt->fetchColumn();
}

function record_failed_attempt(PDO $pdo, string $ip): void
{
    $stmt = $pdo->prepare('INSERT INTO login_attempts (ip_address, attempted_at) VALUES (:ip, NOW())');
    $stmt->execute([':ip' => $ip]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = client_ip();
    $attempts = recent_failed_attempts($pdo, $ip, $LOCKOUT_WINDOW_MIN);

    if ($attempts >= $LOCKOUT_MAX_ATTEMPTS) {
        $error = 'محاولات كثيرة خطأ، حاول تاني بعد شوية.';
    } elseif (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'الجلسة غير صالحة، حدّث الصفحة وحاول تاني.';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $error = 'من فضلك ادخل اسم المستخدم وكلمة السر.';
        } else {
            $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = :u LIMIT 1');
            $stmt->execute([':u' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['last_activity'] = time();
                header('Location: dashboard.php');
                exit;
            } else {
                record_failed_attempt($pdo, $ip);
                $error = 'اسم المستخدم أو كلمة السر غلط.';
            }
        }
    }
}

$token = csrf_token();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تسجيل الدخول</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
  <form class="login-card" method="post" action="login.php" autocomplete="off">
    <h1>تسجيل الدخول</h1>
    <?php if ($error): ?>
      <div class="alert"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['timeout'])): ?>
      <div class="alert">انتهت الجلسة، سجل دخول تاني.</div>
    <?php endif; ?>
    <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
    <label>اسم المستخدم</label>
    <input type="text" name="username" required maxlength="64" autofocus>
    <label>كلمة السر</label>
    <input type="password" name="password" required maxlength="255">
    <button type="submit">دخول</button>
  </form>
</body>
</html>
