<?php
// navigation/admin/ajax/performance_metrics.php
session_start();
ob_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['accountID'])) {
    ob_clean();
    echo json_encode([
        'kpi'      => ['score'=>0,'projects'=>0,'employees'=>0,'risk'=>0],
        'status'   => ['ontrack'=>0,'atrisk'=>0,'delayed'=>0,'completed'=>0],
        'activity' => [],
        'error'    => 'Unauthorized'
    ]);
    exit();
}

require '../../../database/connection.php'; // PDO $conn

// ---- date filter (optional) ----
$filterDate = $_GET['date'] ?? '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDate)) {
    $filterDate = '';
}
$today    = date('Y-m-d');
$refDate  = $filterDate ?: $today;                 // for risk calculations
$refPlus3 = date('Y-m-d', strtotime($refDate.' +3 days'));

try {
    /* ======================================================
       1) KPI: tasks → score, risk, employees
    ====================================================== */

    // ---- tasks for KPI / score ----
    $whereTasks = '';
    $paramsTasks = [];
    if ($filterDate !== '') {
        $whereTasks =
            "WHERE (DATE(t.updated_at) = :d
                 OR (t.updated_at IS NULL AND DATE(t.created_at) = :d))";
        $paramsTasks[':d'] = $filterDate;
    }

    $sql = "
        SELECT
            COUNT(*) AS total_tasks,
            SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) AS completed_tasks
        FROM tasks t
        $whereTasks
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute($paramsTasks);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $totalTasks     = (int)($row['total_tasks'] ?? 0);
    $completedTasks = (int)($row['completed_tasks'] ?? 0);
    $score          = $totalTasks > 0 ? round(100 * $completedTasks / $totalTasks) : 0;

    // ---- active (non-archived) projects ----
    $paramsProj = [];
    $sql = "SELECT COUNT(*) FROM projects p WHERE p.status <> 'Archived'";
    if ($filterDate !== '') {
        $sql .= " AND :d BETWEEN p.start_date AND COALESCE(p.end_date, :d)";
        $paramsProj[':d'] = $filterDate;
    }
    $stmt = $conn->prepare($sql);
    $stmt->execute($paramsProj);
    $activeProjects = (int)$stmt->fetchColumn();

    // ---- employees with tasks ----
    $paramsEmp = [];
    $sql = "
        SELECT COUNT(DISTINCT t.assigned_to)
        FROM tasks t
        INNER JOIN users u ON t.assigned_to = u.user_id
        WHERE u.status = 'active'
    ";
    if ($filterDate !== '') {
        $sql .= " AND (DATE(t.updated_at) = :d
                   OR (t.updated_at IS NULL AND DATE(t.created_at) = :d))";
        $paramsEmp[':d'] = $filterDate;
    }
    $stmt = $conn->prepare($sql);
    $stmt->execute($paramsEmp);
    $employeesMonitored = (int)$stmt->fetchColumn();

    // ---- risk items: overdue & not completed ----
    $stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM tasks t
        WHERE t.status <> 'Completed'
          AND t.due_date IS NOT NULL
          AND t.due_date < :ref
    ");
    $stmt->execute([':ref' => $refDate]);
    $itemsAtRisk = (int)$stmt->fetchColumn();

    /* ======================================================
       2) Status breakdown for chart
          - On Track: not completed, due later or no due date
          - At Risk : not completed, due within 3 days
          - Delayed : not completed, overdue
          - Completed: completed
    ====================================================== */

    $paramsChart = [];
    $sql = "SELECT t.status, t.due_date, t.created_at, t.updated_at FROM tasks t";
    if ($filterDate !== '') {
        $sql .= " WHERE (DATE(t.updated_at) = :d
                     OR (t.updated_at IS NULL AND DATE(t.created_at) = :d))";
        $paramsChart[':d'] = $filterDate;
    }
    $stmt = $conn->prepare($sql);
    $stmt->execute($paramsChart);

    $onTrack = $atRisk = $delayed = $completed = 0;

    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status = $r['status'] ?? '';
        $due    = $r['due_date'] ?? null;

        if ($status === 'Completed') {
            $completed++;
            continue;
        }

        if (!$due) {
            $onTrack++;
        } elseif ($due < $refDate) {
            $delayed++;
        } elseif ($due <= $refPlus3) {
            $atRisk++;
        } else {
            $onTrack++;
        }
    }

    /* ======================================================
       3) Recent activity (task_history)
    ====================================================== */

    $paramsAct = [];
    $sql = "
        SELECT
            CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS employee,
            p.project_name AS project,
            t.task_name    AS task,
            th.new_status  AS status,
            DATE_FORMAT(th.updated_at, '%Y-%m-%d %H:%i:%s') AS updated_at
        FROM task_history th
        INNER JOIN tasks t   ON th.task_id   = t.task_id
        LEFT  JOIN projects p ON t.project_id = p.project_id
        LEFT  JOIN users u    ON th.updated_by = u.user_id
    ";
    if ($filterDate !== '') {
        $sql .= " WHERE DATE(th.updated_at) = :d";
        $paramsAct[':d'] = $filterDate;
    }
    $sql .= " ORDER BY th.updated_at DESC LIMIT 10";

    $stmt = $conn->prepare($sql);
    $stmt->execute($paramsAct);
    $activity = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    /* ======================================================
       4) Build response
    ====================================================== */

    $response = [
        'kpi' => [
            'score'     => $score,
            'projects'  => $activeProjects,
            'employees' => $employeesMonitored,
            'risk'      => $itemsAtRisk,
        ],
        'status' => [
            'ontrack'   => $onTrack,
            'atrisk'    => $atRisk,
            'delayed'   => $delayed,
            'completed' => $completed,
        ],
        'activity' => $activity
    ];

    ob_clean();
    echo json_encode($response);
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'kpi'      => ['score'=>0,'projects'=>0,'employees'=>0,'risk'=>0],
        'status'   => ['ontrack'=>0,'atrisk'=>0,'delayed'=>0,'completed'=>0],
        'activity' => [],
        'error'    => $e->getMessage()
    ]);
}
