<?php
header('Content-Type: application/json');
require '../../../database/connection.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid ID']); exit; }

try {
    // optional: block delete if assigned to users
    $chk = $conn->prepare("SELECT COUNT(*) AS c FROM users WHERE department_id = :id");
    $chk->execute([':id'=>$id]);
    $used = (int)$chk->fetchColumn();

    if ($used > 0) {
        echo json_encode(['success'=>false,'error'=>"Department is used by {$used} user(s). Archive it instead of deleting."]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM departments WHERE department_id = :id");
    $stmt->execute([':id'=>$id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success'=>false,'error'=>'Department not found or already deleted.']);
    } else {
        echo json_encode(['success'=>'Department deleted successfully.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success'=>false,'error'=>'Database error: '.$e->getMessage()]);
}
