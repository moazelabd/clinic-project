<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/permissions.php';

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

// Regular users must belong to an approved group before they can see any data.
// (Admins can use the app to create/manage groups even with none selected yet.)
// API endpoints are exempt: they already handle a missing group gracefully
// (empty lists / explicit JSON errors), and a redirect here would send back
// an HTML page to a fetch() call expecting JSON.
$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$isApiRequest = str_contains($scriptPath, '/api/');
$currentPage = basename($scriptPath);
if (!$isApiRequest && !is_admin() && empty($_SESSION['group_id']) && $currentPage !== 'join.php') {
    header('Location: join.php');
    exit;
}
