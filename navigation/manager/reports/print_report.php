<?php
// navigation/admin/reports/print_report.php
session_start();

if (!isset($_SESSION['accountID'])) {
    echo '<p>Unauthorized.</p>';
    exit();
}

$reportId = (int)($_GET['id'] ?? 0);
if ($reportId <= 0) {
    echo '<p>Invalid report ID.</p>';
    exit();
}

require '../../../database/connection.php'; // PDO $conn

try {
    // Fetch report + user
    $stmt = $conn->prepare("
        SELECT
            r.report_id,
            r.report_type,
            r.period_start,
            r.period_end,
            r.created_at,
            u.user_id,
            u.username,
            CONCAT(u.first_name, ' ', u.last_name) AS generated_by_name
        FROM reports r
        LEFT JOIN users u ON r.generated_by = u.user_id
        WHERE r.report_id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $reportId]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$report) {
        echo '<p>Report not found.</p>';
        exit();
    }

    $type       = $report['report_type'];
    $startDate  = $report['period_start'];
    $endDate    = $report['period_end'];
    $createdAt  = $report['created_at'];
    $generatedByName = $report['generated_by_name'] ?? 'Unknown';
} catch (Throwable $e) {
    echo '<p>Error: '.htmlspecialchars($e->getMessage()).'</p>';
    exit();
}

/**
 * Helpers
 */
function esc($s){
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/**
 * Build data depending on report type
 */
$summaryTitle = '';
$summaryText  = '';
$tableHeader  = '';
$tableRows    = '';
$extraSection = '';

try {
    if ($type === 'Projects Summary') {
        $summaryTitle = 'Projects Overview';

        // Count projects by status in date range (using start_date / end_date overlap)
        $stmt = $conn->prepare("
            SELECT
                status,
                COUNT(*) AS total
            FROM projects
            WHERE
                (
                    (start_date IS NOT NULL AND start_date BETWEEN :s AND :e)
                    OR (end_date IS NOT NULL AND end_date BETWEEN :s AND :e)
                    OR (start_date IS NOT NULL AND start_date <= :s AND (end_date IS NULL OR end_date >= :s))
                )
            GROUP BY status
        ");
        $stmt->execute([':s' => $startDate, ':e' => $endDate]);
        $counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // status => total

        $totalProjects = array_sum($counts);
        $summaryText = 'Total projects in period: '.$totalProjects;

        // Detailed list
        $stmt = $conn->prepare("
            SELECT
                p.project_name,
                p.start_date,
                p.end_date,
                p.status,
                d.department_name,
                CONCAT(m.first_name, ' ', m.last_name) AS manager_name
            FROM projects p
            LEFT JOIN departments d ON p.department_id = d.department_id
            LEFT JOIN users m        ON p.manager_id   = m.user_id
            WHERE
                (
                    (p.start_date IS NOT NULL AND p.start_date BETWEEN :s AND :e)
                    OR (p.end_date IS NOT NULL AND p.end_date BETWEEN :s AND :e)
                    OR (p.start_date IS NOT NULL AND p.start_date <= :s AND (p.end_date IS NULL OR p.end_date >= :s))
                )
            ORDER BY p.start_date ASC, p.project_name ASC
        ");
        $stmt->execute([':s' => $startDate, ':e' => $endDate]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tableHeader = '
            <tr>
              <th>Project</th>
              <th>Department</th>
              <th>Manager</th>
              <th>Start</th>
              <th>End</th>
              <th>Status</th>
            </tr>';

        foreach ($rows as $r) {
            $tableRows .= '<tr>'
                .'<td>'.esc($r['project_name']).'</td>'
                .'<td>'.esc($r['department_name']).'</td>'
                .'<td>'.esc($r['manager_name']).'</td>'
                .'<td>'.esc($r['start_date']).'</td>'
                .'<td>'.esc($r['end_date']).'</td>'
                .'<td>'.esc($r['status']).'</td>'
                .'</tr>';
        }

        $statusLines = [];
        foreach ($counts as $status => $cnt) {
            $statusLines[] = esc($status).': '.$cnt;
        }
        $extraSection = $statusLines
            ? '<p class="mb-0"><strong>By status:</strong> '.implode(' • ', $statusLines).'</p>'
            : '';

    } elseif ($type === 'Tasks Summary') {
        $summaryTitle = 'Tasks Overview';

        // Count tasks by status (filter by created_at range)
        $stmt = $conn->prepare("
            SELECT status, COUNT(*) AS total
            FROM tasks
            WHERE DATE(created_at) BETWEEN :s AND :e
            GROUP BY status
        ");
        $stmt->execute([':s' => $startDate, ':e' => $endDate]);
        $counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $totalTasks = array_sum($counts);
        $summaryText = 'Total tasks created in period: '.$totalTasks;

        $stmt = $conn->prepare("
            SELECT
                t.title,
                t.status,
                t.due_date,
                t.created_at,
                p.project_name,
                CONCAT(u.first_name, ' ', u.last_name) AS assignee
            FROM tasks t
            LEFT JOIN projects p ON t.project_id = p.project_id
            LEFT JOIN users u    ON t.assigned_to = u.user_id
            WHERE DATE(t.created_at) BETWEEN :s AND :e
            ORDER BY t.created_at ASC
            LIMIT 300
        ");
        $stmt->execute([':s' => $startDate, ':e' => $endDate]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tableHeader = '
            <tr>
              <th>Created</th>
              <th>Task</th>
              <th>Project</th>
              <th>Assignee</th>
              <th>Due Date</th>
              <th>Status</th>
            </tr>';

        foreach ($rows as $r) {
            $tableRows .= '<tr>'
                .'<td>'.esc($r['created_at']).'</td>'
                .'<td>'.esc($r['title']).'</td>'
                .'<td>'.esc($r['project_name']).'</td>'
                .'<td>'.esc($r['assignee']).'</td>'
                .'<td>'.esc($r['due_date']).'</td>'
                .'<td>'.esc($r['status']).'</td>'
                .'</tr>';
        }

        $statusLines = [];
        foreach ($counts as $status => $cnt) {
            $statusLines[] = esc($status).': '.$cnt;
        }
        $extraSection = $statusLines
            ? '<p class="mb-0"><strong>By status:</strong> '.implode(' • ', $statusLines).'</p>'
            : '';

    } elseif ($type === 'Employee Workload') {
        $summaryTitle = 'Employee Workload';

        // Group tasks by assignee within range (created_at)
        $stmt = $conn->prepare("
            SELECT
                u.user_id,
                CONCAT(u.first_name, ' ', u.last_name) AS emp_name,
                SUM(CASE WHEN t.status = 'Pending'       THEN 1 ELSE 0 END) AS pending_cnt,
                SUM(CASE WHEN t.status = 'In Progress'   THEN 1 ELSE 0 END) AS inprogress_cnt,
                SUM(CASE WHEN t.status = 'Completed'     THEN 1 ELSE 0 END) AS completed_cnt,
                COUNT(*) AS total_cnt
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.user_id
            WHERE DATE(t.created_at) BETWEEN :s AND :e
            GROUP BY u.user_id, emp_name
            ORDER BY emp_name ASC
        ");
        $stmt->execute([':s' => $startDate, ':e' => $endDate]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalEmployees = count($rows);
        $summaryText = 'Employees with tasks in period: '.$totalEmployees;

        $tableHeader = '
            <tr>
              <th>Employee</th>
              <th>Pending</th>
              <th>In Progress</th>
              <th>Completed</th>
              <th>Total</th>
            </tr>';

        foreach ($rows as $r) {
            $tableRows .= '<tr>'
                .'<td>'.esc($r['emp_name']).'</td>'
                .'<td>'.esc($r['pending_cnt']).'</td>'
                .'<td>'.esc($r['inprogress_cnt']).'</td>'
                .'<td>'.esc($r['completed_cnt']).'</td>'
                .'<td>'.esc($r['total_cnt']).'</td>'
                .'</tr>';
        }

    } else {
        $summaryTitle = 'Report';
        $summaryText  = 'Custom report type: '.esc($type);
        $tableHeader  = '';
        $tableRows    = '';
    }
} catch (Throwable $e) {
    $summaryTitle = 'Error while building report';
    $summaryText  = esc($e->getMessage());
    $tableHeader  = '';
    $tableRows    = '';
}

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>InSync – Report #<?= esc($reportId) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
  <style>
    body{
      background:#f5f7fb;
      font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;
    }
    .wrap{ max-width:1100px; margin:24px auto; padding:0 16px; }
    .header{
      background:linear-gradient(135deg,#1e3a8a,#0ea5e9);
      color:#fff; padding:18px 20px; border-radius:14px;
      box-shadow:0 6px 14px rgba(2,6,23,.18); margin-bottom:16px;
    }
    .brand{ font-weight:800; font-size:20px; }
    .sub{ opacity:.9; font-size:.9rem; }
    .card-soft{
      background:#fff; border:1px solid #e5e7eb; border-radius:14px;
      padding:16px 18px; margin-bottom:16px;
      box-shadow:0 1px 2px rgba(0,0,0,.04);
    }
    .table-sm td, .table-sm th{ padding:.4rem .55rem; }
    @media print{
      .no-print{ display:none !important; }
      body{ background:#fff; }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="header d-flex justify-content-between align-items-center">
      <div>
        <div class="brand">InSync • <?= esc($type) ?></div>
        <div class="sub">Report ID: #<?= esc($reportId) ?> • Generated: <?= esc($createdAt) ?></div>
      </div>
      <div class="text-end">
        <div class="sub">Prepared by: <strong><?= esc($generatedByName) ?></strong></div>
        <div class="sub">Period: <?= esc($startDate) ?> → <?= esc($endDate) ?></div>
      </div>
    </div>

    <div class="card-soft">
      <h5 class="mb-1"><?= esc($summaryTitle) ?></h5>
      <p class="mb-1"><?= esc($summaryText) ?></p>
      <?= $extraSection ?>
    </div>

    <?php if ($tableHeader && $tableRows): ?>
      <div class="card-soft">
        <h6 class="mb-2">Details</h6>
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead class="table-light">
              <?= $tableHeader ?>
            </thead>
            <tbody>
              <?= $tableRows ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

    <div class="text-center mt-3 no-print">
      <button class="btn btn-primary" onclick="window.print()">
        <i class="fa fa-print me-1"></i> Print
      </button>
      <button class="btn btn-outline-secondary ms-2" onclick="window.close()">
        Close
      </button>
    </div>
  </div>
</body>
</html>
