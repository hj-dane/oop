<?php
// navigation/admin/contact/get_contact_message.php
header('Content-Type: application/json');
require_once '../../../database/connection.php'; // $conn is PDO

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) { echo json_encode(['success'=>false, 'error'=>'Invalid ID']); exit; }

try {
  $st = $conn->prepare("SELECT id, user_id, name, email, subject, message, status,
                               admin_reply, replied_by, replied_at, created_at, updated_at
                        FROM contact_messages WHERE id = :id LIMIT 1");
  $st->execute([':id'=>$id]);
  $row = $st->fetch();
  if (!$row) { echo json_encode(['success'=>false, 'error'=>'Message not found']); exit; }
  echo json_encode(['success'=>true, 'data'=>$row]);
} catch (Throwable $e) {
  error_log($e->getMessage());
  echo json_encode(['success'=>false, 'error'=>'Server error']);
}
