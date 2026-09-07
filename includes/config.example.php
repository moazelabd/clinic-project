<?php
declare(strict_types=1);

/**
 * Core configuration:
 *  - security headers
 *  - hardened session cookie
 *  - PDO connection (prepared statements everywhere = SQLi protection)
 *  - CSRF + output-escaping helpers (XSS protection)
 */

// ---------- Security headers (sent on every page) ----------
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer-when-downgrade');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; frame-ancestors 'none';");
header('X-XSS-Protection: 1; mode=block');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

// ---------- Hardened session ----------
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// ---------- Database credentials ----------
// EDIT these to match your MySQL setup.
const DB_HOST = 'localhost';
const DB_NAME = 'clinic_db';
const DB_USER = 'root';
const DB_PASS = 'CHANGE_ME';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST .
        ';port=' . DB_PORT .
        ';dbname=' . DB_NAME .
        ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Database connection error.');
}

// ---------- CSRF helpers ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && is_string($token) && $token !== ''
        && hash_equals($_SESSION['csrf_token'], $token);
}

/** Require a valid CSRF token for JSON API calls, otherwise stop with 403. */
function csrf_require_or_die(?string $token): void
{
    if (!csrf_verify($token)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'invalid_csrf']);
        exit;
    }
}

// ---------- Output escaping (XSS protection) ----------
function e(?string $str): string
{
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}
<?php
declare(strict_types=1);

/**
 * Core configuration:
 *  - security headers
 *  - hardened session cookie
 *  - PDO connection (prepared statements everywhere = SQLi protection)
 *  - CSRF + output-escaping helpers (XSS protection)
 */

// ---------- Security headers (sent on every page) ----------
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer-when-downgrade');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; frame-ancestors 'none';");
header('X-XSS-Protection: 1; mode=block');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

// ---------- Hardened session ----------
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// ---------- Database credentials ----------
// EDIT these to match your MySQL setup.
const DB_HOST = 'localhost';
const DB_PORT = '3306';
const DB_NAME = 'clinic_db';
const DB_USER = 'root';
const DB_PASS = 'root';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST .
        ';port=' . DB_PORT .
        ';dbname=' . DB_NAME .
        ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Database connection error.');
}

// ---------- CSRF helpers ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && is_string($token) && $token !== ''
        && hash_equals($_SESSION['csrf_token'], $token);
}

/** Require a valid CSRF token for JSON API calls, otherwise stop with 403. */
function csrf_require_or_die(?string $token): void
{
    if (!csrf_verify($token)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'invalid_csrf']);
        exit;
    }
}

// ---------- Output escaping (XSS protection) ----------
function e(?string $str): string
{
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}
