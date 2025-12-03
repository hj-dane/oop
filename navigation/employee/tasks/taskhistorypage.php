<?php
session_start();
if (!isset($_SESSION['accountID'])) {
  header("Location: ../../logout.php");
  exit();
}

$firstname  = $_SESSION['firstname']  ?? '';
$middlename = $_SESSION['middlename'] ?? '';
$lastname   = $_SESSION['lastname']   ?? '';
$userrole   = $_SESSION['userrole']   ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>InSync – Task History</title>

  <link rel="stylesheet" href="../../../bootstrap/css/bootstrap.min.css?v=<?= filemtime('../../../bootstrap/css/bootstrap.min.css'); ?>">
  <link rel="stylesheet" href="../../../bootstrap/fontawesome/css/all.min.css?v=<?= filemtime('../../../bootstrap/fontawesome/css/all.min.css'); ?>">
  <link rel="stylesheet" href="../css/admin.css?<?= filemtime('../css/admin.css'); ?>">

  <link rel="stylesheet" href="../../../bootstrap/datatable/css/dataTables.bootstrap5.min.css?<?= filemtime('../../../bootstrap/datatable/css/dataTables.bootstrap5.min.css'); ?>">
  <link rel="stylesheet" href="../../../bootstrap/datatable/css/responsive.bootstrap5.min.css?<?= filemtime('../../../bootstrap/datatable/css/responsive.bootstrap5.min.css'); ?>">

  <style>
    :root{
      --sidebar-w: 250px;
      --sidebar-w-collapsed: 80px;
      --sidebar-bg: #0b3a6f;
      --sidebar-fg: #ffffff;
    }
    html, body { height: 100%; background:#f5f7fb; }

    .sidebar{
      position: fixed; top:0; left:0; height:100vh;
      width: var(--sidebar-w);
      background: var(--sidebar-bg); color: var(--sidebar-fg);
      overflow-y:auto; z-index:1045;
      transition: width .25s ease, transform .25s ease;
      padding-bottom:1rem;
    }
    .sidebar .brand{
      display:flex; align-items:center; gap:.5rem; padding:1rem;
      border-bottom:1px solid rgba(255,255,255,.12);
    }
    .sidebar a{
      display:block; color:#fff; text-decoration:none;
      padding:.75rem 1rem;
    }
    .sidebar a:hover{ background: rgba(255,255,255,.08); }
    .sidebar a.link-active{
      background: #fff;
      border-radius:.35rem;
    }
    .logout-btn{
      position:absolute; left:0; right:0; bottom:0;
      padding:.75rem 1rem;
    }
    .logout-btn a{
      display:block;
      color:#fff; text-decoration:none;
      background: rgba(255,255,255,.08);
      border-radius:.5rem;
      padding:.6rem .9rem;
    }

    .content{
      margin-left: var(--sidebar-w);
      min-height:100vh;
      transition: margin-left .25s ease;
    }
    .navbar{
      background:#fff;
      border-bottom:1px solid #e5e7eb;
      padding:.75rem 1rem;
    }
    .toggle-sidebar{
      cursor:pointer;
      user-select:none;
      font-size:1.25rem;
    }

    @media (min-width: 992px){
      .sidebar.collapsed{ width: var(--sidebar-w-collapsed); }
      .content.collapsed{ margin-left: var(--sidebar-w-collapsed); }
      .sidebar.collapsed .brand .full-text,
      .sidebar.collapsed a span{ display:none; }
      .sidebar.collapsed a{ text-align:center; padding:.75rem 0; }
    }
    @media (max-width: 991.98px){
      .sidebar{ transform: translateX(-100%); }
      .sidebar.show{ transform: translateX(0); }
      .content{ margin-left:0 !important; }
    }
    .sidebar-overlay{
      display:none; position:fixed; inset:0; background:rgba(0,0,0,.35); z-index:1040;
    }
    .sidebar-overlay.show{ display:block; }

    .welcome-banner{
      background: linear-gradient(135deg, #1e3a8a, #0ea5e9);
      color:#fff; padding:1rem 1.25rem; border-radius:.75rem; margin-bottom:1rem;
      box-shadow: 0 4px 10px rgba(2,6,23,.15);
      display:flex; align-items:center; justify-content:space-between; gap:12px;
    }

    #historyTable thead th{
      color:#000 !important;
      background-color:#f8f9fa !important;
      border-bottom:1px solid #e5e7eb !important;
      font-weight:600;
    }
    .user-details i, #user-name { color:#fff; }
  </style>
</head>
<body>
  <!-- SIDEBAR -->
  <div class="sidebar" id="sidebar">
    <div class="brand">
      <i class="fas fa-sync"></i>
      <span class="full-text">InSync</span>
    </div>

    <a href="../dashboard.php" class="mb-2 mt-3">
      <i class="fas fa-chart-line me-2"></i> <span>Dashboard</span>
    </a>
    <a href="../tasks/mytaskspage.php" class="mb-2 mt-1 text-truncate">
      <i class="fas fa-tasks me-2"></i> <span>My Tasks</span>
    </a>
    <a href="../tasks/taskhistorypage.php" class="link-active mb-2 mt-1 text-truncate">
      <i class="fas fa-history me-2"></i> <span>Task History</span>
    </a>
    <a href="../announcement/announcementpage.php" class="mb-2 mt-1">
      <i class="fas fa-bullhorn me-2"></i> <span>Announcements</span>
    </a>
    <a href="../feedback/feedbackpage.php" class="mb-2 mt-1">
      <i class="fas fa-phone me-2"></i> <span>Feedback</span>
    </a>
    <a href="../profile/profile.php" class="mb-2 mt-1">
      <i class="fas fa-user me-2"></i> <span>Profile</span>
    </a>

    <div class="logout-btn">
      <a href="../../../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>
  </div>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- MAIN CONTENT (same JS as manager, but ajax URL different) -->
  <!-- You can copy the entire HTML + JS body from manager page,
       and only change ajax url 'fetch_task_history.php' and sidebar links.
       For brevity, reuse the same table + filters + print JS. -->
  <!-- Replace just this part: -->

  <div class="content" id="content">
    <nav class="navbar">
      <span class="toggle-sidebar" id="menuBtn">☰</span>
      <div class="ms-auto d-flex align-items-center">
        <div class="user-details d-flex align-items-center gap-2 bg-primary px-3 py-1 rounded-pill">
          <i class="fas fa-user me-2"></i>
          <p class="m-0"><span id="user-name"><?= htmlspecialchars(trim("$firstname $middlename $lastname")) ?></span></p>
        </div>
        <div class="clock-widget ms-3 d-flex align-items-center">
          <i class="fas fa-clock me-2"></i><span id="clock"></span>
        </div>
      </div>
    </nav>

    <div class="container mt-4">
      <div class="welcome-banner">
        <div>
          <h2 class="mb-1">Task History</h2>
          <p class="mb-0">Read-only history of changes to your tasks.</p>
        </div>
        <button id="printListBtn" class="btn btn-light text-primary border">
          <i class="fa fa-print me-1"></i> Print History
        </button>
      </div>

      <div class="card p-3 mb-3">
        <div class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="form-label mb-1">From</label>
            <input type="date" id="fromDate" class="form-control">
          </div>
          <div class="col-md-3">
            <label class="form-label mb-1">To</label>
            <input type="date" id="toDate" class="form-control">
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button id="btnToday" class="btn btn-outline-primary w-50">
              <i class="fa fa-calendar-day me-1"></i> Today
            </button>
            <button id="btnClear" class="btn btn-outline-secondary w-50">
              <i class="fa fa-times me-1"></i> Clear
            </button>
          </div>
          <div class="col-md-3 text-md-end">
            <small class="text-muted d-block mb-1">
              Showing changes within selected dates.
            </small>
            <span class="badge bg-light text-dark border" id="dateLabel">All dates</span>
          </div>
        </div>
      </div>

      <div class="card p-3">
        <div class="table-responsive">
          <table id="historyTable" class="table">
            <thead>
              <tr>
                <th>Changed At</th>
                <th>Task</th>
                <th>Project</th>
                <th>Old Status</th>
                <th>New Status</th>
                <th>Updated By</th>
                <th>Remarks</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script src="../../../bootstrap/jquery/jquery-3.6.0.min.js"></script>
  <script src="../../../bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../../../bootstrap/datatable/js/jquery.dataTables.min.js"></script>
  <script src="../../../bootstrap/datatable/js/dataTables.bootstrap5.min.js"></script>
  <script src="../../../bootstrap/datatable/js/dataTables.responsive.min.js"></script>
  <script src="../../../bootstrap/datatable/js/responsive.bootstrap5.min.js"></script>

  <script>
    // sidebar + clock same as manager
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const content = document.getElementById('content');

    document.getElementById('menuBtn').addEventListener('click', () => {
      if (window.innerWidth < 992){
        sidebar.classList.toggle('show'); overlay.classList.toggle('show');
        document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
      } else {
        sidebar.classList.toggle('collapsed'); content.classList.toggle('collapsed');
      }
    });
    overlay.addEventListener('click', ()=>{ sidebar.classList.remove('show'); overlay.classList.remove('show'); document.body.style.overflow=''; });

    function updateClock(){ document.getElementById('clock').innerText = new Date().toLocaleTimeString(); }
    setInterval(updateClock,1000); updateClock();
  </script>

  <script>
    let historyDT = null;

    function esc(s){
      return (s ?? '').toString().replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));
    }
    function refreshLabel(){
      const f = $('#fromDate').val();
      const t = $('#toDate').val();
      $('#dateLabel').text(!f && !t ? 'All dates' : (f || '…') + ' → ' + (t || '…'));
    }

    $(function(){
      historyDT = $('#historyTable').DataTable({
        responsive:true,
        ajax:{
          url:'fetch_task_history.php',
          data:function(d){
            d.from = $('#fromDate').val();
            d.to   = $('#toDate').val();
          }
        },
        order:[[0,'desc']],
        columns:[
          { data:'change_date' },
          { data:'task_title' },
          { data:'project_name' },
          { data:'old_status' },
          { data:'new_status' },
          { data:'updated_by_name' },
          { data:'remarks', render:(v)=>esc(v||'') }
        ]
      });

      $('#fromDate,#toDate').on('change', function(){
        refreshLabel();
        historyDT.ajax.reload();
      });

      $('#btnToday').on('click', function(){
        const today = new Date().toISOString().slice(0,10);
        $('#fromDate').val(today);
        $('#toDate').val(today);
        refreshLabel();
        historyDT.ajax.reload();
      });

      $('#btnClear').on('click', function(){
        $('#fromDate').val('');
        $('#toDate').val('');
        refreshLabel();
        historyDT.ajax.reload();
      });

      refreshLabel();
    });

    document.getElementById('printListBtn').addEventListener('click', function(){
      if (!historyDT) return;
      const rows = historyDT.rows({search:'applied'}).data().toArray();
      const me   = document.getElementById('user-name').textContent.trim();
      const now  = new Date().toLocaleString();
      const dateRange = document.getElementById('dateLabel').textContent;

      const tbody = rows.map(r=>`
        <tr>
          <td>${esc(r.change_date||'')}</td>
          <td>${esc(r.task_title||'')}</td>
          <td>${esc(r.project_name||'')}</td>
          <td>${esc(r.old_status||'')}</td>
          <td>${esc(r.new_status||'')}</td>
          <td>${esc(r.updated_by_name||'')}</td>
          <td>${esc(r.remarks||'')}</td>
        </tr>`).join('');

      const html = `
<!doctype html><html><head>
<meta charset="utf-8">
<title>InSync – Task History</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
  body{ background:#f5f7fb; font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial; }
  .wrap{ max-width:1100px; margin:24px auto; padding:0 16px; }
  .print-header{
    background: linear-gradient(135deg, #1e3a8a, #0ea5e9);
    color:#fff; padding:16px 18px; border-radius:12px; margin-bottom:14px;
    box-shadow: 0 6px 14px rgba(2,6,23,.18);
  }
  .table-sm td, .table-sm th{ padding:.45rem .6rem; }
  @media print { .no-print{ display:none !important; } }
</style>
</head>
<body>
  <div class="wrap">
    <div class="print-header d-flex justify-content-between align-items-center">
      <div>
        <h5 class="m-0">InSync • Task History</h5>
        <div class="small opacity-75">Prepared by: <strong>${esc(me)}</strong></div>
      </div>
      <div class="text-end">
        <div class="small">Printed: ${esc(now)}</div>
        <div class="small">Date range: ${esc(dateRange)}</div>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle m-0">
          <thead class="table-light">
            <tr>
              <th>Changed At</th>
              <th>Task</th>
              <th>Project</th>
              <th>Old</th>
              <th>New</th>
              <th>Updated By</th>
              <th>Remarks</th>
            </tr>
          </thead>
          <tbody>${tbody || '<tr><td colspan="7" class="text-center text-muted py-3">No history.</td></tr>'}</tbody>
        </table>
      </div>
    </div>

    <div class="text-center mt-3 no-print">
      <button class="btn btn-primary" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
      <button class="btn btn-outline-secondary ms-2" onclick="window.close()">Close</button>
    </div>
  </div>
</body></html>`;

      const w = window.open('', '_blank', 'width=1200,height=800');
      w.document.open(); w.document.write(html); w.document.close();
      setTimeout(()=>{ try{ w.focus(); w.print(); }catch(e){} }, 300);
    });
  </script>
</body>
</html>
