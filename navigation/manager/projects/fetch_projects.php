<?php
// navigation/admin/projects/fetch_projects.php
session_start();

if (!isset($_SESSION['accountID'])) {
    http_response_code(401);
    echo json_encode(['data' => [], 'error' => 'Unauthorized']);
    exit();
}

require '../../../database/connection.php'; // PDO $conn

header('Content-Type: application/json; charset=utf-8');

try {
    $sql = "
        SELECT 
            p.project_id,
            p.project_name,
            p.description,
            p.start_date,
            p.end_date,
            p.status,
            p.department_id,
            p.manager_id,
            d.department_name,
            CONCAT(u.first_name, ' ', u.last_name) AS manager_name
        FROM projects p
        LEFT JOIN departments d ON p.department_id = d.department_id
        LEFT JOIN users u       ON p.manager_id   = u.user_id
        ORDER BY p.project_id DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'data' => $rows
    ]);
} catch (Throwable $e) {
    // For debugging – you can hide the message in production
    http_response_code(500);
    echo json_encode([
        'data'  => [],
        'error' => $e->getMessage()
    ]);
}
