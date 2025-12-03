<?php
// navigation/admin/reports/fetch_reports.php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['accountID'])) {
    echo json_encode(['data' => [], 'error' => 'Unauthorized']);
    exit();
}

require '../../../database/connection.php'; // PDO $conn

try {
    $sql = "
        SELECT
            r.report_id,
            r.report_type,
            r.period_start,
            r.period_end,
            r.created_at,
            CONCAT(u.first_name, ' ', u.last_name) AS generated_by_name
        FROM reports r
        LEFT JOIN users u ON r.generated_by = u.user_id
        ORDER BY r.created_at DESC, r.report_id DESC
    ";

    $stmt = $conn->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['data' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'data'  => [],
        'error' => $e->getMessage()
    ]);
}
