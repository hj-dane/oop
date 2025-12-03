<?php
header('Content-Type: application/json');
require '../../../database/connection.php';

try {
    $sql = "SELECT 
                u.user_id,
                u.username,
                u.first_name,
                u.middle_name,
                u.last_name,
                u.email,
                u.mobile,
                u.address,
                u.status,
                u.department_id,
                r.role_name,
                r.role_id,
                d.Department_name
            FROM users u
            LEFT JOIN roles r       ON r.role_id = u.role_id
            LEFT JOIN departments d ON d.department_id = u.department_id
            ORDER BY u.user_id DESC";

    $stmt = $conn->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['data' => $rows]);
} catch (PDOException $e) {
    echo json_encode(['data' => [], 'error' => $e->getMessage()]);
}
