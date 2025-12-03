<?php
// navigation/admin/announcements/fetch_announcements.php
session_start();
if (!isset($_SESSION['accountID'])) {
    echo json_encode(['data' => []]);
    exit();
}

require '../../../database/connection.php'; // PDO $conn

$sql = "SELECT 
          a.announcement_id,
          a.title,
          a.message,
          a.audience,
          a.status,
          DATE_FORMAT(a.created_at, '%Y-%m-%d %H:%i') AS created_at,
          CONCAT(u.first_name, ' ', u.last_name) AS posted_by_name
        FROM announcements a
        LEFT JOIN users u ON a.posted_by = u.user_id
        ORDER BY a.created_at DESC";

$stmt = $conn->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['data' => $rows]);
