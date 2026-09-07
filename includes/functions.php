<?php
declare(strict_types=1);

/**
 * Handle an optional image upload safely.
 * - Validates real MIME type (not just extension)
 * - Restricts size
 * - Renames file to a random name (prevents path traversal / overwrite / exec tricks)
 * - Saves into $subdir under /uploads
 *
 * Returns the relative path to store in DB, or null if no file was uploaded.
 * Throws RuntimeException on invalid file.
 */
function handle_optional_image_upload(string $fieldName, string $subdir): ?string
{
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // optional - fine if missing
    }

    $file = $_FILES[$fieldName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload error code: ' . $file['error']);
    }

    $maxBytes = 4 * 1024 * 1024; // 4MB
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException('File too large.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Unsupported image type.');
    }

    // re-encode-safe check: make sure it is actually a readable image
    if (@getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('Invalid image file.');
    }

    $ext = $allowed[$mime];
    $randomName = bin2hex(random_bytes(16)) . '.' . $ext;

    $baseDir = __DIR__ . '/../uploads/' . trim($subdir, '/');
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0755, true);
    }

    $destPath = $baseDir . '/' . $randomName;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new RuntimeException('Failed to save uploaded file.');
    }

    return 'uploads/' . trim($subdir, '/') . '/' . $randomName;
}

/** Delete an uploaded file (relative path as stored in DB) if it exists. */
function delete_uploaded_file(?string $relativePath): void
{
    if (!$relativePath) {
        return;
    }
    $full = __DIR__ . '/../' . ltrim($relativePath, '/');
    // guard against path traversal
    $real = realpath($full);
    $uploadsRoot = realpath(__DIR__ . '/../uploads');
    if ($real !== false && $uploadsRoot !== false && str_starts_with($real, $uploadsRoot)) {
        @unlink($real);
    }
}

/** Trim + enforce a max length on a string, return null if empty. */
function clean_text(?string $value, int $maxLen = 5000): ?string
{
    if ($value === null) {
        return null;
    }
    $value = trim($value);
    if ($value === '') {
        return null;
    }
    if (mb_strlen($value) > $maxLen) {
        $value = mb_substr($value, 0, $maxLen);
    }
    return $value;
}

/** JSON response helper. */
function json_out(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
