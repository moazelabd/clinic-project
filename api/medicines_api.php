<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {

        case 'list': {
            $storageId = (int)($_GET['storage_id'] ?? 0);
            $q = clean_text($_GET['q'] ?? null, 150);
            if ($storageId <= 0) {
                json_out(['ok' => false, 'error' => 'بيانات غير صحيحة'], 400);
            }

            if ($q !== null) {
                // "smart" search: match anywhere in the name, case-insensitive,
                // ranks exact-prefix matches first.
                $stmt = $pdo->prepare(
                    'SELECT id, name, image_path, stock FROM medicines
                     WHERE storage_id = :sid AND name LIKE :q
                     ORDER BY (name LIKE :qprefix) DESC, name ASC
                     LIMIT 200'
                );
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
                $prefix = str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
                $stmt->execute([':sid' => $storageId, ':q' => $like, ':qprefix' => $prefix]);
            } else {
                $stmt = $pdo->prepare(
                    'SELECT id, name, image_path, stock FROM medicines WHERE storage_id = :sid ORDER BY name ASC'
                );
                $stmt->execute([':sid' => $storageId]);
            }
            json_out(['ok' => true, 'medicines' => $stmt->fetchAll()]);
        }

        case 'get': {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT id, storage_id, name, image_path, stock FROM medicines WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $med = $stmt->fetch();
            if (!$med) {
                json_out(['ok' => false, 'error' => 'غير موجود'], 404);
            }
            json_out(['ok' => true, 'medicine' => $med]);
        }

        case 'add': {
            csrf_require_or_die($_POST['csrf_token'] ?? null);
            $storageId = (int)($_POST['storage_id'] ?? 0);
            $name = clean_text($_POST['name'] ?? null, 200);
            $stock = max(0, (int)($_POST['stock'] ?? 0));
            if ($storageId <= 0 || !$name) {
                json_out(['ok' => false, 'error' => 'اكتب اسم الدواء'], 400);
            }
            $imagePath = handle_optional_image_upload('image', 'medicines');

            $stmt = $pdo->prepare(
                'INSERT INTO medicines (storage_id, name, image_path, stock) VALUES (:sid, :name, :img, :stock)'
            );
            $stmt->execute([':sid' => $storageId, ':name' => $name, ':img' => $imagePath, ':stock' => $stock]);
            json_out(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
        }

        case 'update': {
            csrf_require_or_die($_POST['csrf_token'] ?? null);
            $id = (int)($_POST['id'] ?? 0);
            $name = clean_text($_POST['name'] ?? null, 200);
            $stock = max(0, (int)($_POST['stock'] ?? 0));
            if ($id <= 0 || !$name) {
                json_out(['ok' => false, 'error' => 'بيانات غير صحيحة'], 400);
            }

            $newImage = null;
            try {
                $newImage = handle_optional_image_upload('image', 'medicines');
            } catch (RuntimeException $e) {
                json_out(['ok' => false, 'error' => $e->getMessage()], 400);
            }

            if ($newImage !== null) {
                $stmt = $pdo->prepare('SELECT image_path FROM medicines WHERE id = :id');
                $stmt->execute([':id' => $id]);
                $old = $stmt->fetchColumn();
                $stmt = $pdo->prepare('UPDATE medicines SET name = :name, image_path = :img, stock = :stock WHERE id = :id');
                $stmt->execute([':name' => $name, ':img' => $newImage, ':stock' => $stock, ':id' => $id]);
                if ($old) delete_uploaded_file($old);
            } else {
                $stmt = $pdo->prepare('UPDATE medicines SET name = :name, stock = :stock WHERE id = :id');
                $stmt->execute([':name' => $name, ':stock' => $stock, ':id' => $id]);
            }
            json_out(['ok' => true]);
        }

        case 'set_stock': {
            csrf_require_or_die($_POST['csrf_token'] ?? null);
            $id = (int)($_POST['id'] ?? 0);
            $stock = (int)($_POST['stock'] ?? -1);
            if ($id <= 0 || $stock < 0) {
                json_out(['ok' => false, 'error' => 'قيمة غير صحيحة'], 400);
            }
            $stmt = $pdo->prepare('UPDATE medicines SET stock = :s WHERE id = :id');
            $stmt->execute([':s' => $stock, ':id' => $id]);
            json_out(['ok' => true, 'stock' => $stock]);
        }

        case 'adjust_stock': {
            csrf_require_or_die($_POST['csrf_token'] ?? null);
            $id = (int)($_POST['id'] ?? 0);
            $delta = (int)($_POST['delta'] ?? 0);
            if ($id <= 0 || $delta === 0) {
                json_out(['ok' => false, 'error' => 'قيمة غير صحيحة'], 400);
            }
            $stmt = $pdo->prepare('UPDATE medicines SET stock = GREATEST(0, stock + :d) WHERE id = :id');
            $stmt->execute([':d' => $delta, ':id' => $id]);
            $stmt = $pdo->prepare('SELECT stock FROM medicines WHERE id = :id');
            $stmt->execute([':id' => $id]);
            json_out(['ok' => true, 'stock' => (int)$stmt->fetchColumn()]);
        }

        case 'delete': {
            csrf_require_or_die($_POST['csrf_token'] ?? null);
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT image_path FROM medicines WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $img = $stmt->fetchColumn();
            $stmt = $pdo->prepare('DELETE FROM medicines WHERE id = :id');
            $stmt->execute([':id' => $id]);
            if ($img) delete_uploaded_file($img);
            json_out(['ok' => true]);
        }

        default:
            json_out(['ok' => false, 'error' => 'إجراء غير معروف'], 400);
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    json_out(['ok' => false, 'error' => 'حصل خطأ في السيرفر'], 500);
}
