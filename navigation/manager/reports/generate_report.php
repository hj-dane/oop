<?php
// navigation/admin/reports/generate_report.php
session_start();
ob_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['accountID'])) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$userId      = (int)($_SESSION['accountID'] ?? 0);
$reportType  = trim($_POST['report_type'] ?? '');
$periodStart = $_POST['period_start'] ?? '';
$periodEnd   = $_POST['period_end']   ?? '';

if ($reportType === '' || $periodStart === '' || $periodEnd === '') {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
    exit();
}
if ($periodStart > $periodEnd) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid date range.']);
    exit();
}

require '../../../database/connection.php'; // PDO $conn

try {
    $conn->beginTransaction();

    // Insert into reports table
    $stmt = $conn->prepare("
        INSERT INTO reports (generated_by, report_type, period_start, period_end)
        VALUES (:uid, :type, :ps, :pe)
    ");
    $stmt->execute([
        ':uid'  => $userId,
        ':type' => $reportType,
        ':ps'   => $periodStart,
        ':pe'   => $periodEnd,
    ]);

    $reportId = (int)$conn->lastInsertId();

    // Optional: insert into log_history
    // Make sure your actions table has something like:
    //   action_id = 4, action_name = 'Generate Report'
    $ACTION_GENERATE_REPORT = 4; // adjust if different

    $stmt = $conn->prepare("
        INSERT INTO log_history (user_id, action_type)
        VALUES (:uid, :atype)
    ");
    $stmt->execute([
        ':uid'   => $userId,
        ':atype' => $ACTION_GENERATE_REPORT
    ]);

    $conn->commit();
    ob_clean();
    echo json_encode([
        'success'   => true,
        'message'   => 'Report generated successfully.',
        'report_id' => $reportId
    ]);
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
