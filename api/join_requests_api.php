<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {

        // ---- regular user: send a join request ----
        case 'request': {
            csrf_require_or_die($_POST['csrf_token'] ?? null);
            if (!empty($_SESSION['group_id'])) {
                json_out(['ok' => false, 'error' => 'انت عضو في جروب بالفعل'], 400);
            }
            $code = strtoupper(trim((string)($_POST['join_code'] ?? '')));
            if ($code === '') {
                json_out(['ok' => false, 'error' => 'اكتب كود الجروب'], 400);
            }

            $stmt = $pdo->prepare('SELECT id, name FROM groups WHERE join_code = :c');
            $stmt->execute([':c' => $code]);
            $group = $stmt->fetch();
            if (!$group) {
                json_out(['ok' => false, 'error' => 'الكود غير صحيح'], 404);
            }

            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM group_join_requests WHERE user_id = :uid AND status = 'pending'"
            );
            $stmt->execute([':uid' => $_SESSION['user_id']]);
            if ((int)$stmt->fetchColumn() > 0) {
                json_out(['ok' => false, 'error' => 'عندك طلب معلّق بالفعل'], 400);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO group_join_requests (group_id, user_id, status) VALUES (:gid, :uid, "pending")'
            );
            $stmt->execute([':gid' => $group['id'], ':uid' => $_SESSION['user_id']]);
            json_out(['ok' => true, 'group_name' => $group['name']]);
        }

        // ---- regular user: check my own status ----
        case 'my_status': {
            if (!empty($_SESSION['group_id'])) {
                $stmt = $pdo->prepare('SELECT name FROM groups WHERE id = :id');
                $stmt->execute([':id' => $_SESSION['group_id']]);
                json_out(['ok' => true, 'state' => 'approved', 'group_name' => $stmt->fetchColumn()]);
            }
            $stmt = $pdo->prepare(
                "SELECT g.name FROM group_join_requests r JOIN groups g ON g.id = r.group_id
                 WHERE r.user_id = :uid AND r.status = 'pending' ORDER BY r.id DESC LIMIT 1"
            );
            $stmt->execute([':uid' => $_SESSION['user_id']]);
            $pendingGroup = $stmt->fetchColumn();
            if ($pendingGroup !== false) {
                json_out(['ok' => true, 'state' => 'pending', 'group_name' => $pendingGroup]);
            }
            json_out(['ok' => true, 'state' => 'none']);
        }

        // ---- admin: list pending requests ----
        case 'list_pending': {
            require_admin_or_die();
            $stmt = $pdo->query(
                "SELECT r.id, u.username, g.name AS group_name, r.requested_at
                 FROM group_join_requests r
                 JOIN users u ON u.id = r.user_id
                 JOIN groups g ON g.id = r.group_id
                 WHERE r.status = 'pending'
                 ORDER BY r.requested_at ASC"
            );
            json_out(['ok' => true, 'requests' => $stmt->fetchAll()]);
        }

        // ---- admin: approve or reject ----
        case 'decide': {
            require_admin_or_die();
            csrf_require_or_die($_POST['csrf_token'] ?? null);
            $id = (int)($_POST['id'] ?? 0);
            $approve = ((int)($_POST['approve'] ?? 0)) === 1;

            $stmt = $pdo->prepare("SELECT * FROM group_join_requests WHERE id = :id AND status = 'pending'");
            $stmt->execute([':id' => $id]);
            $req = $stmt->fetch();
            if (!$req) {
                json_out(['ok' => false, 'error' => 'الطلب مش موجود أو اتحسم قبل كده'], 404);
            }

            $pdo->beginTransaction();
            if ($approve) {
                $stmt = $pdo->prepare("UPDATE group_join_requests SET status = 'approved', decided_at = NOW() WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $stmt = $pdo->prepare('UPDATE users SET group_id = :gid WHERE id = :uid');
                $stmt->execute([':gid' => $req['group_id'], ':uid' => $req['user_id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE group_join_requests SET status = 'rejected', decided_at = NOW() WHERE id = :id");
                $stmt->execute([':id' => $id]);
            }
            $pdo->commit();
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
