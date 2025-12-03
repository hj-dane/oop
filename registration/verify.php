<?php
header('Content-Type: application/json');
require_once '../database/connection.php';

$email = strtolower(trim($_POST['email'] ?? ''));
$code  = trim($_POST['code'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^\d{6}$/', $code)) {
    echo json_encode(["status"=>"error","message"=>"Invalid email or code."]); exit;
}

try {
    $sql = "
        SELECT user_id, verification_code, verification_status
        FROM users
        WHERE Email = :email
        LIMIT 1
    ";
    $st = $conn->prepare($sql);
    $st->execute(['email'=>$email]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(["status"=>"error","message"=>"Account not found."]); exit;
    }

    if ($row['verification_status'] === 'Verified') {
        echo json_encode(["status"=>"success","message"=>"Already verified."]); exit;
    }

    if ($row['verification_code'] !== $code) {
        echo json_encode(["status"=>"error","message"=>"Incorrect code."]); exit;
    }

    // ✅ Mark verified + activate account
    $upd = $conn->prepare("
        UPDATE users
           SET verification_status = 'Verified',
               verification_code   = NULL,
               status              = 'active'
         WHERE user_id = :id
    ");
    $upd->execute(['id'=>$row['user_id']]);

    echo json_encode(["status"=>"success","message"=>"Account verified."]);
} catch (PDOException $e) {
    echo json_encode(["status"=>"error","message"=>"Database error."]);
}
