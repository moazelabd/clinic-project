<?php
declare(strict_types=1);

/** True if the logged-in user is an admin. */
function is_admin(): bool
{
    return ($_SESSION['role'] ?? '') === 'admin';
}

/** Stop the request with 403 unless the user is an admin. Use in every write-action API. */
function require_admin_or_die(): void
{
    if (!is_admin()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'الصلاحية دي للأدمن بس']);
        exit;
    }
}

/**
 * The group the current user is currently working in.
 * - Regular users: always their approved group (from users.group_id).
 * - Admins: whichever group they've switched to (session), defaulting to their own group_id.
 * Returns null if no group is set yet.
 */
function active_group_id(): ?int
{
    if (is_admin() && isset($_SESSION['active_group_id'])) {
        return (int)$_SESSION['active_group_id'];
    }
    return isset($_SESSION['group_id']) && $_SESSION['group_id'] !== null
        ? (int)$_SESSION['group_id']
        : null;
}

/** Stop the request with 400 if no group is currently selected. */
function require_active_group_or_die(): int
{
    $gid = active_group_id();
    if (!$gid) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'اختار جروب الأول']);
        exit;
    }
    return $gid;
}

/** Generate a short, human-friendly join code like "A3F9K2". */
function generate_join_code(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no 0/O/1/I for readability
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $code;
}
