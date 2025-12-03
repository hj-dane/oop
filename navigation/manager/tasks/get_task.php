<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['accountID'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require '../../../database/connection.php'; // PDO $conn

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid task id.']);
    exit();
}

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
        WHERE t.task_id = :id
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Task not found.']);
        exit();
    }

    echo json_encode(['success' => true, 'data' => $row]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
