<?php
session_start();
ob_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['accountID'])) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$userId  = (int)($_SESSION['accountID'] ?? 0);
$taskId  = (int)($_POST['task_id'] ?? 0);
$status  = $_POST['status']  ?? '';
$remarks = trim($_POST['remarks'] ?? '');

$allowedStatus = ['Pending','In Progress','Completed'];
if ($taskId <= 0 || !in_array($status, $allowedStatus, true)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid input.']);
    exit();
}

require '../../../database/connection.php'; // PDO $conn

try {
    $conn->beginTransaction();

    // lock + verify task belongs to this employee
    $stmt = $conn->prepare("
        SELECT status, assigned_to
        FROM tasks
        WHERE task_id = :id
        FOR UPDATE
    ");
    $stmt->execute([':id' => $taskId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task || (int)$task['assigned_to'] !== $userId) {
        throw new Exception('Task not found or not assigned to you.');
    }

    $oldStatus = $task['status'];

    // update task status if changed
    if ($oldStatus !== $status) {
        $stmt = $conn->prepare("
            UPDATE tasks
            SET status = :st, updated_at = NOW()
            WHERE task_id = :id
        ");
        $stmt->execute([
            ':st' => $status,
            ':id' => $taskId
        ]);
    }

    // insert into task_history (status change and/or remarks)
    if ($oldStatus !== $status || $remarks !== '') {
        $stmt = $conn->prepare("
            INSERT INTO task_history (task_id, updated_by, old_status, new_status, remarks)
            VALUES (:tid, :uid, :old, :new, :rem)
        ");
        $stmt->execute([
            ':tid' => $taskId,
            ':uid' => $userId,
            ':old' => $oldStatus,
            ':new' => $status,
            ':rem' => ($remarks !== '' ? $remarks : null)
        ]);
    }

    // ===== log_history entry =====
    // Make sure actions table has: 3 = 'Update Task Status'
    $ACTION_UPDATE_TASK_STATUS = 3; // change if you use another ID

    $stmt = $conn->prepare("
        INSERT INTO log_history (user_id, action_type)
        VALUES (:uid, :atype)
    ");
    $stmt->execute([
        ':uid'   => $userId,
        ':atype' => $ACTION_UPDATE_TASK_STATUS
    ]);
    // created_at uses default CURRENT_TIMESTAMP()

    $conn->commit();
    ob_clean();
    echo json_encode(['success' => true, 'message' => 'Task updated successfully.']);
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
