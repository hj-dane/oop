<?php
// navigation/employee/ajax/user_performance_metrics.php
session_start();
ob_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['accountID'])) {
    ob_clean();
    echo json_encode([
        'kpi'      => ['score'=>0,'projects'=>0,'tasks_completed'=>0,'risk'=>0],
        'status'   => ['ontrack'=>0,'atrisk'=>0,'delayed'=>0,'completed'=>0],
        'activity' => [],
        'error'    => 'Unauthorized'
    ]);
    exit();
}

$currentUserId = (int)$_SESSION['accountID'];   // Assumes tasks.assigned_to stores this ID

require '../../../database/connection.php';     // PDO $conn

// Helper: detect column existence
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

// Optional date filter: YYYY-MM-DD
$filterDate = $_GET['date'] ?? '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDate)) {
    $filterDate = '';
}

// Reference dates for risk logic
$today    = date('Y-m-d');
$refDate  = $filterDate ?: $today;
$refPlus3 = date('Y-m-d', strtotime($refDate . ' +3 days'));

$kpiScore        = 0;
$kpiProjects     = 0;
$kpiTasksDone    = 0;
$kpiRisk         = 0;

$onTrack = $atRisk = $delayed = $completed = 0;
$activity = [];
$activityError = null;

try {
    /* ==========================================
       1) KPI: completion score for this user
       ========================================== */

    $whereTasks  = "WHERE t.assigned_to = :uid";
    $paramsTasks = [':uid' => $currentUserId];

    if ($filterDate !== '') {
        $whereTasks .= " AND DATE(COALESCE(t.updated_at, t.created_at)) = :d";
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
    $kpiScore       = $totalTasks > 0 ? round(100 * $completedTasks / max($totalTasks, 1)) : 0;

    // Also store completed count for KPI
    $kpiTasksDone = $completedTasks;

    /* ==========================================
       2) My active projects (where I have tasks)
       ========================================== */

    $paramsProj = [':uid' => $currentUserId];
    $sql = "
        SELECT COUNT(DISTINCT t.project_id)
        FROM tasks t
        INNER JOIN projects p ON t.project_id = p.project_id
        WHERE t.assigned_to = :uid
          AND p.status <> 'Archived'
    ";
    if ($filterDate !== '') {
        $sql .= " AND DATE(COALESCE(t.updated_at, t.created_at)) = :d";
        $paramsProj[':d'] = $filterDate;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($paramsProj);
    $kpiProjects = (int)$stmt->fetchColumn();

    /* ==========================================
       3) Items at risk for this user
       ========================================== */

    $hasDueDate = tableHasColumn($conn, 'tasks', 'due_date');

    if ($hasDueDate) {
        // simple rule: non-completed and due <= refPlus3
        $paramsRisk = [':uid'=>$currentUserId, ':refPlus3'=>$refPlus3];
        $sql = "
            SELECT COUNT(*)
            FROM tasks t
            WHERE t.assigned_to = :uid
              AND t.status <> 'Completed'
              AND t.due_date IS NOT NULL
              AND t.due_date <= :refPlus3
        ";
        if ($filterDate !== '') {
            $sql .= " AND DATE(COALESCE(t.updated_at,t.created_at)) = :d";
            $paramsRisk[':d'] = $filterDate;
        }
        $stmt = $conn->prepare($sql);
        $stmt->execute($paramsRisk);
        $kpiRisk = (int)$stmt->fetchColumn();
    } else {
        // fallback: all non-completed tasks are "at risk"
        $paramsRisk = [':uid'=>$currentUserId];
        $sql = "
            SELECT COUNT(*)
            FROM tasks t
            WHERE t.assigned_to = :uid
              AND t.status <> 'Completed'
        ";
        if ($filterDate !== '') {
            $sql .= " AND DATE(COALESCE(t.updated_at,t.created_at)) = :d";
            $paramsRisk[':d'] = $filterDate;
        }
        $stmt = $conn->prepare($sql);
        $stmt->execute($paramsRisk);
        $kpiRisk = (int)$stmt->fetchColumn();
    }

    /* ==========================================
       4) Status breakdown for my tasks
       ========================================== */

    $paramsChart = [':uid'=>$currentUserId];
    $sql = "
        SELECT t.status, " . ($hasDueDate ? "t.due_date," : "NULL AS due_date,") . "
               t.created_at, t.updated_at
        FROM tasks t
        WHERE t.assigned_to = :uid
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

        if (!$hasDueDate || !$due) {
            $onTrack++;
        } elseif ($due < $refDate) {
            $delayed++;
        } elseif ($due <= $refPlus3) {
            $atRisk++;
        } else {
            $onTrack++;
        }
    }

    /* ==========================================
       5) My recent activity (last 10 updates)
       ========================================== */

    // If you have task_history and want to use it, you can switch to that;
    // here we keep it simple: from tasks assigned_to current user.
    $paramsAct = [':uid'=>$currentUserId];
    $sql = "
        SELECT
            p.project_name AS project,
            t.title        AS task,
            t.status       AS status,
            DATE_FORMAT(COALESCE(t.updated_at, t.created_at), '%Y-%m-%d %H:%i:%s') AS updated_at
        FROM tasks t
        LEFT JOIN projects p ON t.project_id = p.project_id
        WHERE t.assigned_to = :uid
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
        $activity      = [];
        $activityError = $e->getMessage();
    }

    /* ==========================================
       6) Build JSON response
       ========================================== */

    $response = [
        'kpi' => [
            'score'          => $kpiScore,
            'projects'       => $kpiProjects,
            'tasks_completed'=> $kpiTasksDone,
            'risk'           => $kpiRisk,
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
        'kpi'      => ['score'=>0,'projects'=>0,'tasks_completed'=>0,'risk'=>0],
        'status'   => ['ontrack'=>0,'atrisk'=>0,'delayed'=>0,'completed'=>0],
        'activity' => [],
        'error'    => $e->getMessage()
    ]);
}
