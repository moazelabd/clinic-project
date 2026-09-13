<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {

        case 'list': {
            $gid = active_group_id();
            if (!$gid) {
                json_out(['ok' => true, 'storages' => []]);
            }
            $stmt = $pdo->prepare('SELECT id, name FROM storages WHERE group_id = :gid ORDER BY name ASC');
            $stmt->execute([':gid' => $gid]);
            json_out(['ok' => true, 'storages' => $stmt->fetchAll()]);
        }

        case 'add': {
            require_admin_or_die();
            csrf_require_or_die($_POST['csrf_token'] ?? null);
            $gid = require_active_group_or_die();
            $name = clean_text($_POST['name'] ?? null, 150);
            if (!$name) {
                json_out(['ok' => false, 'error' => 'اكتب اسم المخزن'], 400);
            }
            $stmt = $pdo->prepare('INSERT INTO storages (group_id, name) VALUES (:gid, :name)');
            $stmt->execute([':gid' => $gid, ':name' => $name]);
            json_out(['ok' => true, 'id' => (int)$pdo->lastInsertId(), 'name' => $name]);
        }

        case 'delete': {
            require_admin_or_die();
            csrf_require_or_die($_POST['csrf_token'] ?? null);
            $gid = require_active_group_or_die();
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                json_out(['ok' => false, 'error' => 'بيانات غير صحيحة'], 400);
            }
            // make sure the storage actually belongs to the group you're working in
            $check = $pdo->prepare('SELECT id FROM storages WHERE id = :id AND group_id = :gid');
            $check->execute([':id' => $id, ':gid' => $gid]);
            if (!$check->fetch()) {
                json_out(['ok' => false, 'error' => 'غير موجود'], 404);
            }
            // Clean up medicine images before cascading delete
            $stmt = $pdo->prepare('SELECT image_path FROM medicines WHERE storage_id = :id');
            $stmt->execute([':id' => $id]);
            foreach ($stmt->fetchAll() as $row) {
                delete_uploaded_file($row['image_path']);
            }
            $stmt = $pdo->prepare('DELETE FROM storages WHERE id = :id');
            $stmt->execute([':id' => $id]);
            json_out(['ok' => true]);
        }

        default:
            json_out(['ok' => false, 'error' => 'إجراء غير معروف'], 400);
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    json_out(['ok' => false, 'error' => 'حصل خطأ في السيرفر'], 500);
}
