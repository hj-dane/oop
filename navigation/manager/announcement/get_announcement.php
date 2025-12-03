<?php
// navigation/admin/announcements/get_announcement.php
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

$stmt = $conn->prepare("SELECT * FROM announcements WHERE announcement_id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['success' => false, 'error' => 'Announcement not found.']);
    exit();
}

echo json_encode(['success' => true, 'data' => $row]);
