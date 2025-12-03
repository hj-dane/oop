<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['accountID'])) {
    echo json_encode(['data' => [], 'error' => 'Unauthorized']);
    exit();
}

$from = $_GET['from'] ?? '';
$to   = $_GET['to']   ?? '';

require '../../../database/connection.php'; // PDO $conn

try {
    $where  = [];
    $params = [];

    if ($from !== '') {
        $where[]         = "DATE(l.created_at) >= :from";
        $params[':from'] = $from;
    }
    if ($to !== '') {
        $where[]         = "DATE(l.created_at) <= :to";
        $params[':to']   = $to;
    }

    $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

    $sql = "
        SELECT
            l.log_id,
            l.created_at,
            u.username,
            CONCAT(u.first_name, ' ', u.last_name) AS full_name,
            a.action_name
        FROM log_history l
        LEFT JOIN users   u ON l.user_id    = u.user_id
        LEFT JOIN actions a ON l.action_type = a.action_id
        $whereSql
        ORDER BY l.created_at DESC, l.log_id DESC
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
