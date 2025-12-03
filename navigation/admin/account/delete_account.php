<?php
header('Content-Type: application/json');
require '../../../database/connection.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid user ID']); exit; }

try {
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = :id");
    $stmt->execute([':id'=>$id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success'=>false,'error'=>'User not found or already deleted.']);
    } else {
        echo json_encode(['success'=>'User deleted successfully.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success'=>false,'error'=>'Database error: '.$e->getMessage()]);
}
