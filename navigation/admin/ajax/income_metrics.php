<?php
// navigation/admin/ajax/income_metrics.php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

require '../../../database/connection.php';
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$dateParam = trim($_GET['date'] ?? '');
$baseDate  = $dateParam !== '' ? $dateParam : date('Y-m-d');

try {
  $base = new DateTime($baseDate);
} catch (Throwable $e) {
  $base = new DateTime();
}

function rangeDay(DateTime $d): array { $s=$d->format('Y-m-d'); return [$s,$s]; }
function rangeWeek(DateTime $d): array {
  $start=(clone $d)->modify('monday this week')->format('Y-m-d');
  $end  =(clone $d)->modify('sunday this week')->format('Y-m-d');
  return [$start,$end];
}
function rangeMonth(DateTime $d): array {
  return [$d->format('Y-m-01'), $d->format('Y-m-t')];
}
function rangeYear(DateTime $d): array {
  return [$d->format('Y-01-01'), $d->format('Y-12-31')];
}

function sumBetween(PDO $conn, string $start, string $end): float {
  $sql = "
    SELECT COALESCE(SUM(p.price),0) AS total
    FROM schedules s
    JOIN packages  p ON p.package_ID = s.package_id
    WHERE s.status = 'Completed'
      AND s.date BETWEEN :start AND :end
  ";
  $st = $conn->prepare($sql);
  $st->bindValue(':start', $start);
  $st->bindValue(':end',   $end);
  $st->execute();
  return (float)$st->fetchColumn();
}

[$ds, $de] = rangeDay($base);
[$ws, $we] = rangeWeek($base);
[$ms, $me] = rangeMonth($base);
[$ys, $ye] = rangeYear($base);

echo json_encode([
  'ok'      => true,
  'date'    => $base->format('Y-m-d'),
  'daily'   => sumBetween($conn, $ds, $de),
  'weekly'  => sumBetween($conn, $ws, $we),
  'monthly' => sumBetween($conn, $ms, $me),
  'yearly'  => sumBetween($conn, $ys, $ye),
], JSON_UNESCAPED_UNICODE);
