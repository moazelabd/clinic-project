<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Basic session fixation / idle timeout protection
$maxIdleSeconds = 60 * 60 * 2; // 2 hours
if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $maxIdleSeconds) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit;
}
$_SESSION['last_activity'] = time();
