<?php
// navigation/manager/feedback/get_feedback.php
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
    $sql = "
      SELECT 
        f.feedback_id,
        f.subject,
        f.message,
        f.response,
        f.submitted_at,
        f.status_id,
        s.status_name,
        CONCAT(u.first_name, ' ', u.last_name) AS employee_name
      FROM feedback f
      JOIN fb_status s ON f.status_id = s.status_id
      JOIN users u     ON f.submitted_by = u.user_id
      WHERE f.feedback_id = :id
      LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Feedback not found.']);
        exit();
    }

    echo json_encode(['success' => true, 'data' => $row]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: '.$e->getMessage()]);
}
