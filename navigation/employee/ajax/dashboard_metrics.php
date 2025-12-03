<?php
// navigation/admin/ajax/dashboard_metrics.php
declare(strict_types=1);
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['accountID'])) {
  echo json_encode(['success'=>false,'message'=>'Not logged in']); exit;
}

require '../../../database/connection.php';
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/**
 * Input: optional ?date=YYYY-MM-DD
 * Output:
 * {
 *   success: true,
 *   day: { pending, approved, completed, cancelled, all },
 *   items: [ { ID, client_name, EventTitle, time_start, time_end, status } ]
 * }
 */
$date = trim($_GET['date'] ?? '');
$params = [];
$where  = '';

if ($date !== '') {
  $where = " WHERE s.date = :d ";
  $params[':d'] = $date;
}

/* ---- per-status counts ---- */
$sqlCounters = "
  SELECT
    SUM(CASE WHEN s.status='Pending'   THEN 1 ELSE 0 END) AS pending,
    SUM(CASE WHEN s.status='Approved'  THEN 1 ELSE 0 END) AS approved,
    SUM(CASE WHEN s.status='Completed' THEN 1 ELSE 0 END) AS completed,
    SUM(CASE WHEN s.status='Cancelled' THEN 1 ELSE 0 END) AS cancelled,
    COUNT(*) AS all_count
  FROM schedules s
  $where
";
$st = $conn->prepare($sqlCounters);
$st->execute($params);
$c = $st->fetch(PDO::FETCH_ASSOC) ?: [];

$day = [
  'pending'   => (int)($c['pending']   ?? 0),
  'approved'  => (int)($c['approved']  ?? 0),
  'completed' => (int)($c['completed'] ?? 0),
  'cancelled' => (int)($c['cancelled'] ?? 0),
  'all'       => (int)($c['all_count'] ?? 0),
];

/* ---- list (selected date; if blank, latest 12 overall) ----
 * JOIN FIX: clients.userID (not clients.client_id)
 */
$sqlItems = "
  SELECT
    s.ID, s.date, s.time_start, s.time_end, s.status,
    CONCAT(
      COALESCE(TRIM(c.FirstName), ''), ' ',
      COALESCE(TRIM(c.MiddleName), ''), ' ',
      COALESCE(TRIM(c.LastName), '')
    ) AS client_name,
    sv.EventTitle
  FROM schedules s
  LEFT JOIN clients  c  ON c.userID    = s.client_id
  LEFT JOIN services sv ON sv.service_id = s.service_id
  ".($where ?: '')."
  ORDER BY ".($date !== '' ? "s.time_start ASC" : "s.date DESC, s.time_start DESC")."
  LIMIT 12
";
$st2 = $conn->prepare($sqlItems);
$st2->execute($params);
$rows = $st2->fetchAll(PDO::FETCH_ASSOC);

$items = array_map(function($r){
  $name = trim(preg_replace('/\s+/', ' ', $r['client_name'] ?? ''));
  if ($name === '') $name = 'Client';
  return [
    'ID'          => (int)$r['ID'],
    'client_name' => $name,
    'EventTitle'  => (string)($r['EventTitle'] ?? ''),
    'time_start'  => (string)($r['time_start'] ?? ''),
    'time_end'    => (string)($r['time_end'] ?? ''),
    'status'      => (string)($r['status'] ?? ''),
  ];
}, $rows);

echo json_encode(['success'=>true, 'day'=>$day, 'items'=>$items], JSON_UNESCAPED_UNICODE);
