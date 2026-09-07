<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {

        case 'add': {
            csrf_require_or_die($_POST['csrf_token'] ?? null);
            $doctorId = (int)($_POST['doctor_id'] ?? 0);
            $date = (string)($_POST['visit_date'] ?? '');
            $note = clean_text($_POST['note'] ?? null, 2000);

            $d = DateTime::createFromFormat('Y-m-d', $date);
            if ($doctorId <= 0 || !$d || $d->format('Y-m-d') !== $date) {
                json_out(['ok' => false, 'error' => 'تاريخ غير صحيح'], 400);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO visits (doctor_id, visit_date, note) VALUES (:did, :date, :note)'
            );
            $stmt->execute([':did' => $doctorId, ':date' => $date, ':note' => $note]);
            json_out(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
        }

        case 'update_note': {
            csrf_require_or_die($_POST['csrf_token'] ?? null);
            $id = (int)($_POST['id'] ?? 0);
            $note = clean_text($_POST['note'] ?? null, 2000);
            if ($id <= 0) {
                json_out(['ok' => false, 'error' => 'بيانات غير صحيحة'], 400);
            }
            $stmt = $pdo->prepare('UPDATE visits SET note = :n WHERE id = :id');
            $stmt->execute([':n' => $note, ':id' => $id]);
            json_out(['ok' => true]);
        }

        case 'delete': {
            csrf_require_or_die($_POST['csrf_token'] ?? null);
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM visits WHERE id = :id');
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
