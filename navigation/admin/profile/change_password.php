<?php
// navigation/admin/account/change_password.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['accountID'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit();
}

$userid = (int)$_SESSION['accountID'];

require '../../../database/connection.php'; // PDO $conn

$current = $_POST['current_password'] ?? '';
$new     = $_POST['new_password']     ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if ($current === '' || $new === '' || $confirm === '') {
    echo json_encode(['success' => false, 'error' => 'All password fields are required.']);
    exit();
}

if ($new !== $confirm) {
    echo json_encode(['success' => false, 'error' => 'New password and confirmation do not match.']);
    exit();
}

if (strlen($new) < 8) {
    echo json_encode(['success' => false, 'error' => 'New password must be at least 8 characters.']);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = :id");
    $stmt->execute([':id' => $userid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'User not found.']);
        exit();
    }

    if (!password_verify($current, $row['password_hash'])) {
        echo json_encode(['success' => false, 'error' => 'Current password is incorrect.']);
        exit();
    }

    $newHash = password_hash($new, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE users SET password_hash = :h WHERE user_id = :id");
    $stmt->execute([':h' => $newHash, ':id' => $userid]);

    echo json_encode(['success' => 'Password updated successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: '.$e->getMessage()]);
}
