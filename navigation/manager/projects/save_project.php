<?php
// navigation/admin/projects/save_project.php
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
        // find existing action
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
        // silent fail for logging
    }
}

// ---------- read + validate input ----------
$mode          = $_POST['mode'] ?? 'create';
$projectId     = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
$projectName   = trim($_POST['project_name'] ?? '');
$departmentId  = isset($_POST['department_id']) ? (int)$_POST['department_id'] : 0;
$managerId     = isset($_POST['manager_id']) ? (int)$_POST['manager_id'] : 0;
$status        = $_POST['status'] ?? 'Ongoing';
$startDate     = $_POST['start_date'] ?? '';
$endDate       = $_POST['end_date'] ?? '';
$description   = trim($_POST['description'] ?? '');

if ($projectName === '' || $departmentId <= 0 || $managerId <= 0 || $startDate === '') {
    echo json_encode(['error' => 'Please fill in project name, department, manager and start date.']);
    exit();
}

if (!in_array($status, ['Ongoing', 'Completed', 'Archived'], true)) {
    $status = 'Ongoing';
}

try {
    if ($mode === 'update') {
        if ($projectId <= 0) {
            echo json_encode(['error' => 'Invalid project selected for update.']);
            exit();
        }

        $sql = "
            UPDATE projects
               SET project_name  = :name,
                   department_id = :dept,
                   manager_id    = :mgr,
                   description   = :descr,
                   start_date    = :start_date,
                   end_date      = :end_date,
                   status        = :status
             WHERE project_id    = :id
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':name'       => $projectName,
            ':dept'       => $departmentId,
            ':mgr'        => $managerId,
            ':descr'      => $description,
            ':start_date' => $startDate,
            ':end_date'   => $endDate !== '' ? $endDate : null,
            ':status'     => $status,
            ':id'         => $projectId,
        ]);

        logAction($conn, $userId, 'Update Project');

        echo json_encode(['success' => 'Project updated successfully.']);
        exit();
    } else {
        // create
        $sql = "
            INSERT INTO projects
                (project_name, department_id, manager_id, description, start_date, end_date, status)
            VALUES
                (:name, :dept, :mgr, :descr, :start_date, :end_date, :status)
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':name'       => $projectName,
            ':dept'       => $departmentId,
            ':mgr'        => $managerId,
            ':descr'      => $description,
            ':start_date' => $startDate,
            ':end_date'   => $endDate !== '' ? $endDate : null,
            ':status'     => $status,
        ]);

        logAction($conn, $userId, 'Create Project');

        echo json_encode(['success' => 'Project created successfully.']);
        exit();
    }
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit();
}
