<?php
header('Content-Type: application/json');
require '../../../database/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error'=>'Invalid method']); exit;
}

$id   = isset($_POST['department_id']) ? (int)$_POST['department_id'] : 0;
$name = trim($_POST['department_name'] ?? '');
$stat = trim($_POST['status'] ?? '');

if ($name === '' || !in_array($stat, ['active','archived'], true)) {
    echo json_encode(['error'=>'Please provide a department name and valid status.']); exit;
}

try {
    // unique name (case-insensitive)
    $sqlChk = "SELECT 1 FROM departments
               WHERE LOWER(department_name) = LOWER(:n) AND department_id <> :id";
    $chk = $conn->prepare($sqlChk);
    $chk->execute([':n'=>$name, ':id'=>$id]);
    if ($chk->fetch()) {
        echo json_encode(['error'=>'Department name already exists.']); exit;
    }

    if ($id > 0) {
        // update
        $sql = "UPDATE departments
                SET department_name = :n,
                    status = :s
                WHERE department_id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':n'=>$name, ':s'=>$stat, ':id'=>$id]);
        echo json_encode(['success'=>'Department updated successfully.']);
    } else {
        // insert
        $sql = "INSERT INTO departments (department_name, status, created_at)
                VALUES (:n, :s, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':n'=>$name, ':s'=>$stat]);
        echo json_encode(['success'=>'Department created successfully.']);
    }
} catch (PDOException $e) {
    echo json_encode(['error'=>'Database error: '.$e->getMessage()]);
}
