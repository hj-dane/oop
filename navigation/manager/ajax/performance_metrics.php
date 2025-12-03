<?php
// navigation/manager/ajax/performance_metrics.php
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

$managerId = (int)$_SESSION['accountID'];
if ($managerId <= 0) {
    ob_clean();
    echo json_encode([
        'kpi'      => ['score'=>0,'projects'=>0,'employees'=>0,'risk'=>0],
        'status'   => ['ontrack'=>0,'atrisk'=>0,'delayed'=>0,'completed'=>0],
        'activity' => [],
        'error'    => 'Invalid manager ID'
    ]);
    exit();
}

require '../../../database/connection.php'; // PDO $conn

// helper to detect task title column
function tableHasColumn(PDO $conn, string $table, string $column): bool {
    $sql = "
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = :t
          AND COLUMN_NAME  = :c
        LIMIT 1
    ";
    $st = $conn->prepare($sql);
    if (!$st) return false;
    $st->execute([':t'=>$table, ':c'=>$column]);
    return (bool)$st->fetchColumn();
}

// Optional date filter (YYYY-MM-DD from <input type="date">)
$filterDate = $_GET['date'] ?? '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDate)) {
    $filterDate = '';
}

$today    = date('Y-m-d');
$refDate  = $filterDate ?: $today;
$refPlus3 = date('Y-m-d', strtotime($refDate . ' +3 days'));

$kpiScore     = 0;
$kpiProjects  = 0;
$kpiEmployees = 0;
$kpiRisk      = 0;

$onTrack = $atRisk = $delayed = $completed = 0;
$activity = [];
$activityError = null;

try {
    /* ===========================
       1) KPI: tasks -> score
       (only tasks in manager's projects)
       =========================== */
    $whereTasks  = "WHERE p.manager_id = :mid";
    $paramsTasks = [':mid' => $managerId];

    if ($filterDate !== '') {
        $whereTasks .= " AND DATE(COALESCE(t.updated_at, t.created_at)) = :d";
        $paramsTasks[':d'] = $filterDate;
    }

    $sql = "
        SELECT
            COUNT(*) AS total_tasks,
            SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) AS completed_tasks
        FROM tasks t
        INNER JOIN projects p ON t.project_id = p.project_id
        $whereTasks
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute($paramsTasks);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $totalTasks     = (int)($row['total_tasks'] ?? 0);
    $completedTasks = (int)($row['completed_tasks'] ?? 0);
    $kpiScore       = $totalTasks > 0 ? round(100 * $completedTasks / max($totalTasks, 1)) : 0;

    /* ===========================
       2) Active projects (this manager only)
       =========================== */
    $paramsProj = [':mid' => $managerId];
    $sql = "SELECT COUNT(*) FROM projects p WHERE p.status <> 'Archived' AND p.manager_id = :mid";

    if ($filterDate !== '') {
        // filterDate between start_date and end_date
        $sql .= " AND p.start_date <= :d1 AND (p.end_date IS NULL OR p.end_date >= :d2)";
        $paramsProj[':d1'] = $filterDate;
        $paramsProj[':d2'] = $filterDate;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($paramsProj);
    $kpiProjects = (int)$stmt->fetchColumn();

    /* ===========================
       3) Employees monitored
          (employees with tasks in manager's projects)
       =========================== */
    $paramsEmp = [':mid' => $managerId];
    $sql = "
        SELECT COUNT(DISTINCT t.assigned_to)
        FROM tasks t
        INNER JOIN projects p ON t.project_id = p.project_id
        INNER JOIN users u    ON t.assigned_to = u.user_id
        WHERE p.manager_id = :mid
          AND u.status = 'active'
    ";
    if ($filterDate !== '') {
        $sql .= " AND DATE(COALESCE(t.updated_at, t.created_at)) = :d";
        $paramsEmp[':d'] = $filterDate;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($paramsEmp);
    $kpiEmployees = (int)$stmt->fetchColumn();

    /* ===========================
       4) Items at risk
          (non-completed tasks in manager's projects)
       =========================== */
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*)
            FROM tasks t
            INNER JOIN projects p ON t.project_id = p.project_id
            WHERE p.manager_id = :mid
              AND t.status <> 'Completed'
        ");
        $stmt->execute([':mid' => $managerId]);
        $kpiRisk = (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        $kpiRisk = 0;
    }

    /* ===========================
       5) Status breakdown for chart
       =========================== */
    $paramsChart = [':mid' => $managerId];
    $sql = "
        SELECT t.status, t.due_date, t.created_at, t.updated_at
        FROM tasks t
        INNER JOIN projects p ON t.project_id = p.project_id
        WHERE p.manager_id = :mid
    ";
    if ($filterDate !== '') {
        $sql .= " AND DATE(COALESCE(t.updated_at, t.created_at)) = :d";
        $paramsChart[':d'] = $filterDate;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($paramsChart);

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

    /* ===========================
       6) Recent activity
          (last 10 task changes in manager's projects)
       =========================== */
    $taskTitleCol = 'task_id';
    foreach (['task_name','task_title','title','name'] as $candidate) {
        if (tableHasColumn($conn, 'tasks', $candidate)) {
            $taskTitleCol = $candidate;
            break;
        }
    }

    $paramsAct = [':mid' => $managerId];
    $sql = "
        SELECT
            CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS employee,
            p.project_name AS project,
            t.$taskTitleCol AS task,
            t.status AS status,
            DATE_FORMAT(COALESCE(t.updated_at, t.created_at), '%Y-%m-%d %H:%i:%s') AS updated_at
        FROM tasks t
        INNER JOIN projects p ON t.project_id  = p.project_id
        LEFT  JOIN users    u ON t.assigned_to = u.user_id
        WHERE p.manager_id = :mid
    ";
    if ($filterDate !== '') {
        $sql .= " AND DATE(COALESCE(t.updated_at, t.created_at)) = :d";
        $paramsAct[':d'] = $filterDate;
    }
    $sql .= "
        ORDER BY COALESCE(t.updated_at, t.created_at) DESC
        LIMIT 10
    ";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($paramsAct);
        $activity = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        $activity = [];
        $activityError = $e->getMessage();
    }

    /* ===========================
       7) Build response
       =========================== */
    $response = [
        'kpi' => [
            'score'     => $kpiScore,
            'projects'  => $kpiProjects,
            'employees' => $kpiEmployees,
            'risk'      => $kpiRisk,
        ],
        'status' => [
            'ontrack'   => $onTrack,
            'atrisk'    => $atRisk,
            'delayed'   => $delayed,
            'completed' => $completed,
        ],
        'activity' => $activity,
    ];

    if ($activityError !== null) {
        $response['activity_error'] = $activityError;
    }

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
