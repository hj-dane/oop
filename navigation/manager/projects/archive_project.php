<?php
// navigation/admin/projects/archive_project.php
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

// ---------- read & validate ----------
$projectId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$reason    = trim($_POST['reason'] ?? '');

if (!$projectId || $projectId <= 0) {
    echo json_encode(['error' => 'Invalid project id.']);
    exit();
}

try {
    // 1) Get project data
    $sql = "
        SELECT project_id, project_name, description, department_id, status
        FROM projects
        WHERE project_id = :id
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $projectId]);
    $proj = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$proj) {
        echo json_encode(['error' => 'Project not found.']);
        exit();
    }

    // 2) Prepare archive description
    $archiveDescription = $proj['description'] ?? '';
    if ($reason !== '') {
        $archiveDescription .= ($archiveDescription !== '' ? "\n\n" : '') .
            'Archive reason: ' . $reason;
    }

    // 3) Insert into archive table
    $sql = "
        INSERT INTO archive
            (entity_id, entity_type, name, description,
             related_department_id, related_project_id, archived_by, original_status)
        VALUES
            (:entity_id, :entity_type, :name, :description,
             :dept_id, :proj_id, :archived_by, :original_status)
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':entity_id'       => $proj['project_id'],
        ':entity_type'     => 'project',
        ':name'            => $proj['project_name'],
        ':description'     => $archiveDescription,
        ':dept_id'         => $proj['department_id'],
        ':proj_id'         => $proj['project_id'],
        ':archived_by'     => $userId,
        ':original_status' => $proj['status'],
    ]);

    // 4) Update project status
    $stmt = $conn->prepare("UPDATE projects SET status = 'Archived' WHERE project_id = :id");
    $stmt->execute([':id' => $projectId]);

    logAction($conn, $userId, 'Archive Project');

    echo json_encode(['success' => 'Project archived successfully.']);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
