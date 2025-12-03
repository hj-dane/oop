<?php
declare(strict_types=1);
header('Content-Type: application/json');
require '../../../database/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid method']); exit;
}

$user_id      = (int)($_POST['user_id'] ?? 0);
$username     = trim($_POST['username']     ?? '');
$email        = trim($_POST['email']        ?? '');
$first_name   = trim($_POST['first_name']   ?? '');
$middle_name  = trim($_POST['middle_name']  ?? '');
$last_name    = trim($_POST['last_name']    ?? '');
$mobile       = trim($_POST['mobile']       ?? '');
$address      = trim($_POST['address']      ?? '');
$role_id      = (int)($_POST['role_id']     ?? 0);
$department_id= (int)($_POST['department_id'] ?? 0);
$status       = trim($_POST['status']       ?? '');

if ($user_id <= 0 || $username === '' || $email === '' || $first_name === '' || $last_name === '' || $role_id <= 0 || $department_id <= 0 || ($status !== 'active' && $status !== 'inactive')) {
    echo json_encode(['error' => 'Missing or invalid required fields']); exit;
}

try {
    $conn->beginTransaction();

    // Check unique username/email
    $chk = $conn->prepare("SELECT 1 FROM users WHERE username = :u AND user_id <> :id");
    $chk->execute([':u'=>$username, ':id'=>$user_id]);
    if ($chk->fetch()) throw new RuntimeException('Username already taken.');

    $chk = $conn->prepare("SELECT 1 FROM users WHERE email = :e AND user_id <> :id");
    $chk->execute([':e'=>$email, ':id'=>$user_id]);
    if ($chk->fetch()) throw new RuntimeException('Email already used.');

    // Ensure role & department exist
    $r = $conn->prepare("SELECT 1 FROM roles WHERE role_id = :rid");
    $r->execute([':rid'=>$role_id]);
    if (!$r->fetch()) throw new RuntimeException('Invalid role.');

    $d = $conn->prepare("SELECT 1 FROM departments WHERE department_id = :did");
    $d->execute([':did'=>$department_id]);
    if (!$d->fetch()) throw new RuntimeException('Invalid department.');

    $sql = "UPDATE users
            SET username = :u,
                email = :e,
                first_name = :fn,
                middle_name = :mn,
                last_name = :ln,
                mobile = :m,
                address = :a,
                role_id = :rid,
                department_id = :did,
                status = :st,
                updated_at = NOW()
            WHERE user_id = :id";

    $st = $conn->prepare($sql);
    $st->execute([
        ':u'   => $username,
        ':e'   => $email,
        ':fn'  => $first_name,
        ':mn'  => $middle_name,
        ':ln'  => $last_name,
        ':m'   => $mobile,
        ':a'   => $address,
        ':rid' => $role_id,
        ':did' => $department_id,
        ':st'  => $status,
        ':id'  => $user_id
    ]);

    $conn->commit();
    echo json_encode(['success' => 'User updated successfully']);
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
