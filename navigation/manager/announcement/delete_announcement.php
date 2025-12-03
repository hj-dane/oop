<?php
// navigation/admin/announcements/delete_announcement.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['accountID'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit();
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID.']);
    exit();
}

require '../../../database/connection.php'; // PDO $conn

try {
    $stmt = $conn->prepare("DELETE FROM announcements WHERE announcement_id = :id");
    $stmt->execute([':id' => $id]);
    echo json_encode(['success' => 'Announcement deleted successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: '.$e->getMessage()]);
}
