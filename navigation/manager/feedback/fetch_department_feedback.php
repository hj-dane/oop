<?php
// navigation/manager/feedback/fetch_department_feedback.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['accountID'])) {
    echo json_encode(['data' => []]);
    exit();
}

$userid = (int)$_SESSION['accountID'];

require '../../../database/connection.php'; // PDO $conn

try {
    // Get manager's department
    $stmt = $conn->prepare("SELECT department_id FROM users WHERE user_id = :id LIMIT 1");
    $stmt->execute([':id' => $userid]);
    $me = $stmt->fetch(PDO::FETCH_ASSOC);
    $deptId = (int)($me['department_id'] ?? 0);

    if ($deptId === 0) {
        echo json_encode(['data' => []]);
        exit();
    }

    $sql = "
      SELECT 
        f.feedback_id,
        f.subject,
        f.message,
        f.submitted_at,
        f.response,
        s.status_name AS status,
        CONCAT(u.first_name, ' ', u.last_name) AS employee_name
      FROM feedback f
      JOIN users u      ON f.submitted_by = u.user_id
      JOIN fb_status s  ON f.status_id    = s.status_id
      WHERE u.department_id = :dept
      ORDER BY f.submitted_at DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':dept' => $deptId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['data' => $rows]);
} catch (Exception $e) {
    echo json_encode(['data' => [], 'error' => $e->getMessage()]);
}
