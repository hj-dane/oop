<?php
// navigation/employee/feedback/fetch_my_feedback.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['accountID'])) {
    echo json_encode(['data' => [], 'error' => 'Not authenticated.']);
    exit();
}

$userid = (int)$_SESSION['accountID'];

require '../../../database/connection.php'; // PDO $conn

try {
    $sql = "SELECT f.feedback_id,
                   f.subject,
                   f.message,
                   f.submitted_at,
                   f.response,
                   s.status_name
            FROM feedback f
            LEFT JOIN fb_status s ON f.status_id = s.status_id
            WHERE f.submitted_by = :uid
            ORDER BY f.submitted_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':uid' => $userid]);

    $rows = [];
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rows[] = [
            'feedback_id'  => (int)$r['feedback_id'],
            'subject'      => $r['subject'],
            'message'      => $r['message'],
            'status'       => $r['status_name'] ?? 'New',
            'submitted_at' => $r['submitted_at'],
            'response'     => $r['response'] ?? ''
        ];
    }

    echo json_encode(['data' => $rows]);
} catch (Exception $e) {
    echo json_encode(['data' => [], 'error' => $e->getMessage()]);
}
