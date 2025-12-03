<?php
// navigation/admin/announcements/save_announcement.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['accountID'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit();
}

$userid = (int)$_SESSION['accountID'];
require '../../../database/connection.php'; // PDO $conn

$id       = (int)($_POST['announcement_id'] ?? 0);
$title    = trim($_POST['title']   ?? '');
$message  = trim($_POST['message'] ?? '');
$audience = $_POST['audience'] ?? 'All';
$status   = $_POST['status']   ?? 'active';

if ($title === '' || $message === '') {
    echo json_encode(['success' => false, 'error' => 'Title and message are required.']);
    exit();
}

if (!in_array($audience, ['All','Manager','Employee'], true)) {
    $audience = 'All';
}
if (!in_array($status, ['active','inactive'], true)) {
    $status = 'active';
}

try {
    if ($id > 0) {
        $sql = "UPDATE announcements
                SET title = :title,
                    message = :message,
                    audience = :audience,
                    status = :status
                WHERE announcement_id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':title'    => $title,
            ':message'  => $message,
            ':audience' => $audience,
            ':status'   => $status,
            ':id'       => $id
        ]);
        $msg = 'Announcement updated successfully.';
    } else {
        $sql = "INSERT INTO announcements (posted_by, title, message, audience, status)
                VALUES (:posted_by, :title, :message, :audience, :status)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':posted_by'=> $userid,
            ':title'    => $title,
            ':message'  => $message,
            ':audience' => $audience,
            ':status'   => $status
        ]);
        $msg = 'Announcement created successfully.';
    }

    echo json_encode(['success' => $msg]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: '.$e->getMessage()]);
}
