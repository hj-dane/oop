<?php
// navigation/admin/contact/fetch_contact_messages.php
header('Content-Type: application/json');
require_once '../../../database/connection.php'; // $conn is PDO

try {
  $sql = "SELECT id, user_id, name, email, subject, message, status, admin_reply, replied_by,
                 replied_at, created_at, updated_at
          FROM contact_messages
          ORDER BY id DESC";
  $rows = $conn->query($sql)->fetchAll();
  echo json_encode(['data' => $rows]);
} catch (Throwable $e) {
  error_log($e->getMessage());
  echo json_encode(['data' => []]);
}
