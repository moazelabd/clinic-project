<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';

/** True if the given doctor belongs to the active group. */
function visit_doctor_in_active_group(PDO $pdo, int $doctorId): bool
{
    $gid = active_group_id();
    if (!$gid) return false;
    $stmt = $pdo->prepare('SELECT id FROM doctors WHERE id = :id AND group_id = :gid');
    $stmt->execute([':id' => $doctorId, ':gid' => $gid]);
    return (bool)$stmt->fetch();
}

/** True if the given visit's doctor belongs to the active group. */
function visit_in_active_group(PDO $pdo, int $visitId): bool
{
    $gid = active_group_id();
    if (!$gid) return false;
    $stmt = $pdo->prepare(
        'SELECT v.id FROM visits v JOIN doctors d ON d.id = v.doctor_id WHERE v.id = :vid AND d.group_id = :gid'
    );
    $stmt->execute([':vid' => $visitId, ':gid' => $gid]);
    return (bool)$stmt->fetch();
}

try {
    switch ($action) {

        case 'add': {
            require_admin_or_die();
            csrf_require_or_die($_POST['csrf_token'] ?? null);
            $doctorId = (int)($_POST['doctor_id'] ?? 0);
            $date = (string)($_POST['visit_date'] ?? '');
            $note = clean_text($_POST['note'] ?? null, 2000);

            $d = DateTime::createFromFormat('Y-m-d', $date);
            if ($doctorId <= 0 || !$d || $d->format('Y-m-d') !== $date || !visit_doctor_in_active_group($pdo, $doctorId)) {
                json_out(['ok' => false, 'error' => 'بيانات غير صحيحة'], 400);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO visits (doctor_id, visit_date, note) VALUES (:did, :date, :note)'
            );
            $stmt->execute([':did' => $doctorId, ':date' => $date, ':note' => $note]);
            json_out(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
        }

        case 'update_note': {
            require_admin_or_die();
            csrf_require_or_die($_POST['csrf_token'] ?? null);
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0 || !visit_in_active_group($pdo, $id)) {
                json_out(['ok' => false, 'error' => 'بيانات غير صحيحة'], 400);
            }
            $note = clean_text($_POST['note'] ?? null, 2000);
            $stmt = $pdo->prepare('UPDATE visits SET note = :n WHERE id = :id');
            $stmt->execute([':n' => $note, ':id' => $id]);
            json_out(['ok' => true]);
        }

        case 'delete': {
            require_admin_or_die();
            csrf_require_or_die($_POST['csrf_token'] ?? null);
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0 || !visit_in_active_group($pdo, $id)) {
                json_out(['ok' => false, 'error' => 'بيانات غير صحيحة'], 400);
            }
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
