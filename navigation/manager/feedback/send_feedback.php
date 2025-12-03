<?php
// navigation/employee/feedback/send_feedback.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['accountID'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit();
}

$userid  = (int)$_SESSION['accountID'];
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($subject === '' || $message === '') {
    echo json_encode(['success' => false, 'error' => 'Subject and message are required.']);
    exit();
}

require '../../../database/connection.php'; // PDO $conn

try {
    // 1) Get status_id for "New"
    $stmt = $conn->prepare("SELECT status_id FROM fb_status WHERE status_name = :name LIMIT 1");
    $stmt->execute([':name' => 'New']);
    $defaultStatusId = (int)$stmt->fetchColumn();

    // 2) If not found, create it
    if ($defaultStatusId === 0) {
        $stmt = $conn->prepare("INSERT INTO fb_status (status_name) VALUES (:name)");
        $stmt->execute([':name' => 'New']);
        $defaultStatusId = (int)$conn->lastInsertId();
    }

    // 3) Insert feedback
    $sql = "INSERT INTO feedback (submitted_by, subject, message, status_id)
            VALUES (:uid, :subject, :message, :status_id)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':uid'       => $userid,
        ':subject'   => $subject,
        ':message'   => $message,
        ':status_id' => $defaultStatusId
    ]);

    echo json_encode(['success' => 'Feedback submitted successfully.']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: '.$e->getMessage()]);
}
