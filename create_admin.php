<?php
declare(strict_types=1);

/**
 * Run this from the command line ONLY, e.g.:
 *   php create_admin.php myusername "My$trongP4ssword"
 *
 * It is guarded against being run through a web server, since accounts
 * should be created by you directly on the server, not through a public form.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('This script can only be run from the command line.');
}

require_once __DIR__ . '/includes/config.php';

$username = $argv[1] ?? null;
$password = $argv[2] ?? null;

if (!$username || !$password) {
    fwrite(STDERR, "Usage: php create_admin.php <username> <password>\n");
    exit(1);
}

if (strlen($password) < 8) {
    fwrite(STDERR, "Password must be at least 8 characters.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    'INSERT INTO users (username, password_hash) VALUES (:u, :p)
     ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)'
);
$stmt->execute([':u' => $username, ':p' => $hash]);

echo "User '{$username}' created/updated successfully.\n";
