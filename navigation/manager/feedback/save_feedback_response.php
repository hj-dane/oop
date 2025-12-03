<?php
// navigation/manager/feedback/save_feedback_response.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['accountID'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit();
}

$feedbackId = (int)($_POST['feedback_id'] ?? 0);
$statusId   = (int)($_POST['status_id']   ?? 0);
$response   = trim($_POST['response']     ?? '');

if ($feedbackId <= 0 || $statusId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Missing feedback or status.']);
    exit();
}

require '../../../database/connection.php'; // PDO $conn

try {
    // Optional: you could verify here that the feedback belongs to the manager's dept.

    $sql = "UPDATE feedback 
            SET status_id = :status_id,
                response  = :response
            WHERE feedback_id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':status_id' => $statusId,
        ':response'  => $response,
        ':id'        => $feedbackId
    ]);

    echo json_encode(['success' => 'Feedback response saved.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: '.$e->getMessage()]);
}
