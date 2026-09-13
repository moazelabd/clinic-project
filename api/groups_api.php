<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {

        case 'list': {
            if (is_admin()) {
                $stmt = $pdo->query('SELECT id, name, join_code FROM groups ORDER BY name ASC');
                json_out(['ok' => true, 'groups' => $stmt->fetchAll(), 'active_group_id' => active_group_id()]);
            } else {
                if (empty($_SESSION['group_id'])) {
                    json_out(['ok' => true, 'groups' => []]);
                }
                $stmt = $pdo->prepare('SELECT id, name FROM groups WHERE id = :id');
                $stmt->execute([':id' => $_SESSION['group_id']]);
                $g = $stmt->fetch();
                json_out(['ok' => true, 'groups' => $g ? [$g] : []]);
            }
        }

        case 'create': {
            require_admin_or_die();
            csrf_require_or_die($_POST['csrf_token'] ?? null);
            $name = clean_text($_POST['name'] ?? null, 150);
            if (!$name) {
                json_out(['ok' => false, 'error' => 'اكتب اسم الجروب'], 400);
            }

            // generate a unique join code (retry on the rare collision)
            for ($i = 0; $i < 5; $i++) {
                $code = generate_join_code();
                $check = $pdo->prepare('SELECT COUNT(*) FROM groups WHERE join_code = :c');
                $check->execute([':c' => $code]);
                if ((int)$check->fetchColumn() === 0) {
                    break;
                }
                $code = null;
            }
            if (!$code) {
                json_out(['ok' => false, 'error' => 'حصل خطأ في توليد الكود، جرب تاني'], 500);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO groups (name, join_code, created_by) VALUES (:name, :code, :uid)'
            );
            $stmt->execute([':name' => $name, ':code' => $code, ':uid' => $_SESSION['user_id']]);
            json_out(['ok' => true, 'id' => (int)$pdo->lastInsertId(), 'join_code' => $code]);
        }

        case 'rename': {
            require_admin_or_die();
            csrf_require_or_die($_POST['csrf_token'] ?? null);
            $id = (int)($_POST['id'] ?? 0);
            $name = clean_text($_POST['name'] ?? null, 150);
            if ($id <= 0 || !$name) {
                json_out(['ok' => false, 'error' => 'بيانات غير صحيحة'], 400);
            }
            $stmt = $pdo->prepare('UPDATE groups SET name = :name WHERE id = :id');
            $stmt->execute([':name' => $name, ':id' => $id]);
            json_out(['ok' => true]);
        }

        case 'switch': {
            require_admin_or_die();
            csrf_require_or_die($_POST['csrf_token'] ?? null);
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT id FROM groups WHERE id = :id');
            $stmt->execute([':id' => $id]);
            if (!$stmt->fetch()) {
                json_out(['ok' => false, 'error' => 'الجروب مش موجود'], 404);
            }
            $_SESSION['active_group_id'] = $id;
            json_out(['ok' => true]);
        }

        case 'delete': {
            require_admin_or_die();
            csrf_require_or_die($_POST['csrf_token'] ?? null);
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                json_out(['ok' => false, 'error' => 'بيانات غير صحيحة'], 400);
            }
            $stmt = $pdo->prepare('SELECT id FROM groups WHERE id = :id');
            $stmt->execute([':id' => $id]);
            if (!$stmt->fetch()) {
                json_out(['ok' => false, 'error' => 'الجروب مش موجود'], 404);
            }

            $pdo->beginTransaction();
            // Detach any users pointing at this group before it (and its
            // storages/doctors/medicines/visits/requests) get cascade-deleted.
            $stmt = $pdo->prepare('UPDATE users SET group_id = NULL WHERE group_id = :id');
            $stmt->execute([':id' => $id]);
            $stmt = $pdo->prepare('DELETE FROM groups WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $pdo->commit();

            if (($_SESSION['active_group_id'] ?? null) == $id) {
                unset($_SESSION['active_group_id']);
            }
            json_out(['ok' => true]);
        }

        default:
            json_out(['ok' => false, 'error' => 'إجراء غير معروف'], 400);
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log($e->getMessage());
    json_out(['ok' => false, 'error' => 'حصل خطأ في السيرفر'], 500);
}
