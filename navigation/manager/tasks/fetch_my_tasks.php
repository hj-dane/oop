<?php
// navigation/manager/tasks/fetch_my_tasks.php
session_start();

// catch stray output
ob_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['accountID'])) {
    ob_clean();
    echo json_encode(['data' => [], 'error' => 'Unauthorized']);
    exit();
}

$userId = (int)($_SESSION['accountID'] ?? 0);

require '../../../database/connection.php'; // PDO $conn

try {
    $sql = "
        SELECT 
            t.task_id,
            t.project_id,
            t.title,
            t.description,
            t.due_date,
            t.status,
            p.project_name,
            CONCAT(u2.first_name, ' ', u2.last_name) AS assigned_by_name
        FROM tasks t
        LEFT JOIN projects p ON t.project_id = p.project_id
        LEFT JOIN users u2   ON t.assigned_by = u2.user_id
        WHERE t.assigned_to = :uid
        ORDER BY t.due_date ASC, t.task_id DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_clean();
    echo json_encode(['data' => $rows]);
} catch (Throwable $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'data'  => [],
        'error' => $e->getMessage()
    ]);
}
