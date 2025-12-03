<?php
header('Content-Type: application/json');
require '../../../database/connection.php';

try {
    $sql = "SELECT department_id, department_name, status, created_at
            FROM departments
            ORDER BY department_name";
    $stmt = $conn->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['data' => $rows]);
} catch (PDOException $e) {
    echo json_encode(['data' => [], 'error' => $e->getMessage()]);
}
