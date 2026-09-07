<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {

        case 'list': {
            // Non-blacklisted first, then blacklisted at the bottom, alphabetically within each group.
            $stmt = $pdo->query(
                'SELECT id, name, is_blacklisted FROM doctors ORDER BY is_blacklisted ASC, name ASC'
            );
            json_out(['ok' => true, 'doctors' => $stmt->fetchAll()]);
        }

        case 'get': {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare(
                'SELECT id, name, title, image_path, is_blacklisted, blacklist_reason, general_note FROM doctors WHERE id = :id'
            );
            $stmt->execute([':id' => $id]);
            $doc = $stmt->fetch();
            if (!$doc) {
                json_out(['ok' => false, 'error' => 'غير موجود'], 404);
            }

            $stmt = $pdo->prepare(
                'SELECT id, visit_date, note FROM visits WHERE doctor_id = :id ORDER BY visit_date DESC, id DESC'
            );
            $stmt->execute([':id' => $id]);
            $doc['visits'] = $stmt->fetchAll();

            json_out(['ok' => true, 'doctor' => $doc]);
        }

        case 'add': {
            csrf_require_or_die($_POST['csrf_token'] ?? null);
            $name = clean_text($_POST['name'] ?? null, 150);
            if (!$name) {
                json_out(['ok' => false, 'error' => 'اكتب اسم الدكتور'], 400);
            }
            $imagePath = handle_optional_image_upload('image', 'doctors');
            $stmt = $pdo->prepare('INSERT INTO doctors (name, image_path) VALUES (:name, :img)');
            $stmt->execute([':name' => $name, ':img' => $imagePath]);
            json_out(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
        }

        case 'update_title': {
            csrf_require_or_die($_POST['csrf_token'] ?? null);
            $id = (int)($_POST['id'] ?? 0);
            $title = clean_text($_POST['title'] ?? null, 255);
            $stmt = $pdo->prepare('UPDATE doctors SET title = :t WHERE id = :id');
            $stmt->execute([':t' => $title, ':id' => $id]);
            json_out(['ok' => true]);
        }

        case 'update_note': {
            csrf_require_or_die($_POST['csrf_token'] ?? null);
            $id = (int)($_POST['id'] ?? 0);
            $note = clean_text($_POST['note'] ?? null, 5000);
            $stmt = $pdo->prepare('UPDATE doctors SET general_note = :n WHERE id = :id');
            $stmt->execute([':n' => $note, ':id' => $id]);
            json_out(['ok' => true]);
        }

        case 'toggle_blacklist': {
            csrf_require_or_die($_POST['csrf_token'] ?? null);
            $id = (int)($_POST['id'] ?? 0);
            $toBlacklist = ((int)($_POST['blacklisted'] ?? 0)) === 1;
            $reason = clean_text($_POST['reason'] ?? null, 2000);

            if ($toBlacklist) {
                $stmt = $pdo->prepare('UPDATE doctors SET is_blacklisted = 1, blacklist_reason = :r WHERE id = :id');
                $stmt->execute([':r' => $reason, ':id' => $id]);
            } else {
                $stmt = $pdo->prepare('UPDATE doctors SET is_blacklisted = 0, blacklist_reason = NULL WHERE id = :id');
                $stmt->execute([':id' => $id]);
            }
            json_out(['ok' => true]);
        }

        case 'delete': {
            csrf_require_or_die($_POST['csrf_token'] ?? null);
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT image_path FROM doctors WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $img = $stmt->fetchColumn();
            $stmt = $pdo->prepare('DELETE FROM doctors WHERE id = :id');
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
