<?php
header('Content-Type: application/json');
require '../../../database/connection.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid ID']); exit; }

try {
    $stmt = $conn->prepare("SELECT department_id, department_name, status, created_at
                            FROM departments WHERE department_id = :id LIMIT 1");
    $stmt->execute([':id'=>$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success'=>false,'error'=>'Department not found']); exit;
    }

    echo json_encode(['success'=>true,'data'=>$row]);
} catch (PDOException $e) {
    echo json_encode(['success'=>false,'error'=>'Database error: '.$e->getMessage()]);
}
