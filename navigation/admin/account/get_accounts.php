<?php
header('Content-Type: application/json');
require '../../../database/connection.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) { echo json_encode(['success'=>false,'error'=>'Missing user ID']); exit; }

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
                u.role_id,
                r.role_name,
                d.Department_name
            FROM users u
            LEFT JOIN roles r       ON r.role_id = u.role_id
            LEFT JOIN departments d ON d.department_id = u.department_id
            WHERE u.user_id = :id
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success'=>false,'error'=>'User not found']);
        exit;
    }

    echo json_encode(['success'=>true,'data'=>$user]);
} catch (PDOException $e) {
    echo json_encode(['success'=>false,'error'=>'Database error: '.$e->getMessage()]);
}
