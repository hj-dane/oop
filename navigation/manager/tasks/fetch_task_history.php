<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['accountID'])) {
    echo json_encode(['data' => [], 'error' => 'Unauthorized']);
    exit();
}

$userId = (int)($_SESSION['accountID'] ?? 0);

$from = $_GET['from'] ?? '';
$to   = $_GET['to']   ?? '';

require '../../../database/connection.php'; // PDO $conn

try {
    // Base WHERE: manager sees history for tasks they ASSIGNED
    $where   = ["t.assigned_by = :uid"];
    $params  = [':uid' => $userId];

    if ($from !== '') {
        $where[]          = "DATE(h.change_date) >= :from";
        $params[':from']  = $from;
    }
    if ($to !== '') {
        $where[]          = "DATE(h.change_date) <= :to";
        $params[':to']    = $to;
    }

    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $sql = "
        SELECT
            h.history_id,
            h.task_id,
            t.title        AS task_title,
            p.project_name AS project_name,
            h.old_status,
            h.new_status,
            h.change_date,
            h.remarks,
            CONCAT(uup.first_name, ' ', uup.last_name) AS updated_by_name
        FROM task_history h
        INNER JOIN tasks   t  ON h.task_id = t.task_id
        LEFT  JOIN projects p ON t.project_id = p.project_id
        LEFT  JOIN users   uup ON h.updated_by = uup.user_id
        $whereSql
        ORDER BY h.change_date DESC, h.history_id DESC
    ";

    $stmt = $conn->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['data' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'data'  => [],
        'error' => $e->getMessage()
    ]);
}
