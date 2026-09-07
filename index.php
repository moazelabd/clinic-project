<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
header('Location: ' . (!empty($_SESSION['user_id']) ? 'dashboard.php' : 'login.php'));
exit;
