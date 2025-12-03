<?php
// navigation/admin/projects/get_project.php
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['accountID'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require '../../../database/connection.php'; // PDO $conn

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid project id.']);
    exit();
}

try {
    $sql = "
        SELECT
            p.project_id,
            p.project_name,
            p.department_id,
            p.manager_id,
            p.description,
            p.start_date,
            p.end_date,
            p.status,
            d.department_name,
            CONCAT(u.first_name, ' ', u.last_name) AS manager_name
        FROM projects p
        LEFT JOIN departments d ON p.department_id = d.department_id
        LEFT JOIN users u       ON p.manager_id   = u.user_id
        WHERE p.project_id = :id
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Project not found.']);
        exit();
    }

    echo json_encode([
        'success' => true,
        'data'    => $row,
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
    ]);
}
