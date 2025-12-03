<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['accountID'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require '../../../database/connection.php'; // PDO $conn

$userId = (int) $_SESSION['accountID'];

// ---------- helper: log action ----------
function logAction(PDO $conn, int $userId, string $actionName): void
{
    try {
        $stmt = $conn->prepare("SELECT action_id FROM actions WHERE action_name = :name LIMIT 1");
        $stmt->execute([':name' => $actionName]);
        $actionId = $stmt->fetchColumn();

        if (!$actionId) {
            $stmt = $conn->prepare("INSERT INTO actions (action_name) VALUES (:name)");
            $stmt->execute([':name' => $actionName]);
            $actionId = $conn->lastInsertId();
        }

        if ($actionId) {
            $stmt = $conn->prepare("INSERT INTO log_history (user_id, action_type) VALUES (:uid, :aid)");
            $stmt->execute([
                ':uid' => $userId,
                ':aid' => $actionId,
            ]);
        }
    } catch (Throwable $e) {
        // ignore logging errors
    }
}

// ---------- read + validate input ----------
$mode        = $_POST['mode'] ?? 'create';
$taskId      = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
$title       = trim($_POST['title'] ?? '');
$projectId   = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
$assignedTo  = isset($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : 0;
$status      = $_POST['status'] ?? 'Pending';
$dueDate     = $_POST['due_date'] ?? '';
$description = trim($_POST['description'] ?? '');
$remarks     = trim($_POST['remarks'] ?? '');
$oldStatus   = $_POST['old_status'] ?? null;

if ($title === '' || $projectId <= 0 || $assignedTo <= 0) {
    echo json_encode(['error' => 'Please fill in task title, project and assigned user.']);
    exit();
}
if (!in_array($status, ['Pending', 'In Progress', 'Completed'], true)) {
    $status = 'Pending';
}

try {
    if ($mode === 'update') {
        if ($taskId <= 0) {
            echo json_encode(['error' => 'Invalid task selected for update.']);
            exit();
        }

        // get current status from DB (for safety)
        $stmt = $conn->prepare("SELECT status FROM tasks WHERE task_id = :id");
        $stmt->execute([':id' => $taskId]);
        $currentStatus = $stmt->fetchColumn();
        if (!$currentStatus) {
            echo json_encode(['error' => 'Task not found.']);
            exit();
        }

        $sql = "
            UPDATE tasks
               SET project_id  = :project_id,
                   assigned_to = :assigned_to,
                   title       = :title,
                   description = :description,
                   due_date    = :due_date,
                   status      = :status
             WHERE task_id    = :id
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':project_id'  => $projectId,
            ':assigned_to' => $assignedTo,
            ':title'       => $title,
            ':description' => $description,
            ':due_date'    => $dueDate !== '' ? $dueDate : null,
            ':status'      => $status,
            ':id'          => $taskId,
        ]);

        // insert into task_history if status changed
        if ($currentStatus !== $status) {
            $stmt = $conn->prepare("
                INSERT INTO task_history (task_id, updated_by, old_status, new_status, remarks)
                VALUES (:task_id, :updated_by, :old_status, :new_status, :remarks)
            ");
            $stmt->execute([
                ':task_id'    => $taskId,
                ':updated_by' => $userId,
                ':old_status' => $currentStatus,
                ':new_status' => $status,
                ':remarks'    => $remarks !== '' ? $remarks : null,
            ]);
        }

        logAction($conn, $userId, 'Update Task');
        echo json_encode(['success' => 'Task updated successfully.']);
        exit();

    } else {
        // create
        $sql = "
            INSERT INTO tasks
                (project_id, assigned_to, assigned_by, title, description, due_date, status)
            VALUES
                (:project_id, :assigned_to, :assigned_by, :title, :description, :due_date, :status)
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':project_id'  => $projectId,
            ':assigned_to' => $assignedTo,
            ':assigned_by' => $userId,
            ':title'       => $title,
            ':description' => $description,
            ':due_date'    => $dueDate !== '' ? $dueDate : null,
            ':status'      => $status,
        ]);

        $newTaskId = (int)$conn->lastInsertId();

        // initial history row (optional but useful)
        $stmt = $conn->prepare("
            INSERT INTO task_history (task_id, updated_by, old_status, new_status, remarks)
            VALUES (:task_id, :updated_by, :old_status, :new_status, :remarks)
        ");
        $stmt->execute([
            ':task_id'    => $newTaskId,
            ':updated_by' => $userId,
            ':old_status' => null,
            ':new_status' => $status,
            ':remarks'    => $remarks !== '' ? $remarks : 'Task created',
        ]);

        logAction($conn, $userId, 'Create Task');
        echo json_encode(['success' => 'Task created successfully.']);
        exit();
    }
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit();
}
