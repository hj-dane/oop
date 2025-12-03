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

$userId = (int)($_SESSION['accountID'] ?? 0);

require '../../../database/connection.php'; // PDO $conn

$reportType  = trim($_POST['report_type']  ?? '');
$periodStart = trim($_POST['period_start'] ?? '');
$periodEnd   = trim($_POST['period_end']   ?? '');

if ($userId <= 0 || $reportType === '' || $periodStart === '' || $periodEnd === '') {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
    exit();
}

if ($periodStart > $periodEnd) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid date range.']);
    exit();
}

/**
 * Map UI report type → actions.action_name
 */
$actionMap = [
    'Projects Summary'  => 'Generate Projects Summary Report',
    'Tasks Summary'     => 'Generate Tasks Summary Report',
    'Employee Workload' => 'Generate Employee Workload Report'
];

$actionName = $actionMap[$reportType] ?? null;

try {
    $conn->beginTransaction();

    // 1) Insert the report
    $stmt = $conn->prepare("
        INSERT INTO reports (generated_by, report_type, period_start, period_end)
        VALUES (:uid, :rtype, :pstart, :pend)
    ");
    $stmt->execute([
        ':uid'    => $userId,
        ':rtype'  => $reportType,
        ':pstart' => $periodStart,
        ':pend'   => $periodEnd
    ]);

    $reportId = (int)$conn->lastInsertId();

    // 2) Lookup action_id for this report type
    if ($actionName !== null) {
        $stmt = $conn->prepare("SELECT action_id FROM actions WHERE action_name = :name LIMIT 1");
        $stmt->execute([':name' => $actionName]);

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $actionId = (int)$row['action_id'];

            // 3) Insert log history entry
            $stmt = $conn->prepare("
                INSERT INTO log_history (user_id, action_type)
                VALUES (:uid, :action)
            ");
            $stmt->execute([
                ':uid'    => $userId,
                ':action' => $actionId
            ]);
        }
    }

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
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
