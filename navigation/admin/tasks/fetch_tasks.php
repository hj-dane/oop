<?php
// navigation/admin/tasks/fetch_tasks.php
session_start();

// Start output buffering to catch any stray output (echo, warnings, etc.)
ob_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['accountID'])) {
    // Clean any previous output, then send clean JSON
    ob_clean();
    echo json_encode(['data' => [], 'error' => 'Unauthorized']);
    exit();
}

require '../../../database/connection.php'; // PDO $conn

try {
    $sql = "
        SELECT 
            t.task_id,
            t.project_id,
            t.assigned_to,
            t.assigned_by,
            t.title,
            t.description,
            t.due_date,
            t.status,
            p.project_name,
            CONCAT(u1.first_name, ' ', u1.last_name) AS assigned_to_name,
            CONCAT(u2.first_name, ' ', u2.last_name) AS assigned_by_name
        FROM tasks t
        LEFT JOIN projects p ON t.project_id = p.project_id
        LEFT JOIN users u1   ON t.assigned_to = u1.user_id
        LEFT JOIN users u2   ON t.assigned_by = u2.user_id
        ORDER BY t.task_id DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Throw away anything that might have been output before
    ob_clean();

    echo json_encode(['data' => $rows]);
} catch (Throwable $e) {
    // Throw away anything that might have been output before
    ob_clean();

    // For debugging: include error message in JSON
    http_response_code(500);
    echo json_encode([
        'data'  => [],
        'error' => $e->getMessage()
    ]);
}
