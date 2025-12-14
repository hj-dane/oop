<?php
session_start();
if (!isset($_SESSION['accountID'])) {
    header("Location: ../../../logout.php");
    exit();
}

$firstname  = $_SESSION['firstname'] ?? '';
$middlename = $_SESSION['middlename'] ?? '';
$lastname   = $_SESSION['lastname'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Monitor Tasks</title>

  <link rel="stylesheet" href="../../../bootstrap/css/bootstrap.min.css?v=<?= filemtime('../../../bootstrap/css/bootstrap.min.css'); ?>">
  <link rel="stylesheet" href="../../../bootstrap/fontawesome/css/all.min.css?v=<?= filemtime('../../../bootstrap/fontawesome/css/all.min.css'); ?>">
  <link rel="stylesheet" href="../css/admin.css?<?= filemtime('../css/admin.css'); ?>">

  <!-- DataTables + SweetAlert -->
  <link rel="stylesheet" href="../../../bootstrap/datatable/css/dataTables.bootstrap5.min.css?<?= filemtime('../../../bootstrap/datatable/css/dataTables.bootstrap5.min.css'); ?>">
  <link rel="stylesheet" href="../../../bootstrap/datatable/css/responsive.bootstrap5.min.css?<?= filemtime('../../../bootstrap/datatable/css/responsive.bootstrap5.min.css'); ?>">
  <link rel="stylesheet" href="../../../bootstrap/libs/sweetalert2/sweetalert2.min.css?v=<?= filemtime('../../../bootstrap/libs/sweetalert2/sweetalert2.min.css'); ?>">

  <style>
    :root{
      --sidebar-w: 240px;
      --sidebar-w-collapsed: 80px;
      --sidebar-bg: #0b3a6f;
      --sidebar-fg: #ffffff;
    }
    html, body { height: 100%; background:#f5f7fb; }

    .sidebar{
      position: fixed; top:0; left:0; height:100vh;
      width: var(--sidebar-w);
      background: var(--sidebar-bg); color: var(--sidebar-fg);
      overflow-y: auto; z-index:1045;
      transition: width .25s ease, transform .25s ease;
      padding-bottom: 1rem;
    }
    .sidebar .brand{ display:flex; align-items:center; gap:.5rem; padding:1rem; border-bottom:1px solid rgba(255,255,255,.12); }
    .sidebar a{ display:block; color:#fff; text-decoration:none; padding:.75rem 1rem; }
    .sidebar a:hover{ background: rgba(255,255,255,.08); }
    .link-active{ background: rgba(255,255,255,.14); border-radius:.35rem; }
    .logout-btn{ position:absolute; left:0; right:0; bottom:0; padding:.75rem 1rem; }
    .logout-btn a{ background: rgba(255,255,255,.08); border-radius:.5rem; }

    .content{ margin-left: var(--sidebar-w); min-height: 100vh; transition: margin-left .25s ease; }
    .navbar{ background:#fff; border-bottom:1px solid #e5e7eb; padding:.75rem 1rem; }
    .toggle-sidebar{ cursor:pointer; user-select:none; font-size:1.25rem; }

    @media (min-width: 992px){
      .sidebar.collapsed{ width: var(--sidebar-w-collapsed); }
      .content.collapsed{ margin-left: var(--sidebar-w-collapsed); }
      .sidebar{ transform:none !important; }
      .sidebar a span{ transition: opacity .2s ease; }
      .sidebar.collapsed a span{ opacity:0; }
    }
    @media (max-width: 991.98px){
      .sidebar{ transform: translateX(-100%); }
      .sidebar.show{ transform: translateX(0); }
      .content{ margin-left: 0 !important; }
    }
    .sidebar-overlay{
      display:none; position:fixed; inset:0; background: rgba(0,0,0,.35); z-index:1040;
    }
    .sidebar-overlay.show{ display:block; }

    .welcome-banner{
      background: linear-gradient(135deg, #1e3a8a, #0ea5e9);
      color:#fff; padding:1rem 1.25rem; border-radius:.75rem; margin-bottom:1rem;
      box-shadow: 0 4px 10px rgba(2,6,23,.15);
      display:flex; align-items:center; justify-content:space-between; gap:12px;
    }

    #tasksTable thead th{
      color:#000 !important;
      background-color:#f8f9fa !important;
      border-bottom:1px solid #e5e7eb !important;
      font-weight:600;
    }
    .user-details i, #user-name { color: white; }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <div class="brand">
      <i class="fas fa-sync"></i>
      <span class="full-text">InSync</span>
    </div>

    <a href="../dashboard.php" class="mb-2 mt-3">
      <i class="fas fa-users-cog me-2"></i> <span>Dashboard</span>
    </a>

    <a href="../projects/projectpage.php" class="mb-2 mt-1">
      <i class="fas fa-folder me-2"></i> <span>Manage Projects</span>
    </a>
    <a href="taskspage.php" class="link-active mb-2 mt-1 text-truncate">
      <i class="fas fa-tasks me-2"></i> <span>Monitor Tasks</span>
    </a>
    <a href="taskhistorypage.php" class="mb-2 mt-1 text-truncate">
      <i class="fas fa-history me-2"></i> <span>Task History</span>
    </a>

    <a href="../logs/loghistorypage.php" class=" mb-2 mt-1 text-truncate">
      <i class="fas fa-book me-2"></i> <span>Log History</span>
    </a>
    <a href="../account/accountpage.php" class="mb-2 mt-1">
      <i class="fas fa-user me-2"></i> <span>Manage Users</span>
    </a>
    <a href="../departments/departmentpage.php" class="mb-2 mt-1 text-truncate">
      <i class="fas fa-home me-2"></i> <span>Departments</span>
    </a>
    <a href="../contact/contactpage.php" class="mb-2 mt-1">
      <i class="fas fa-phone me-2"></i> <span>Messages</span>
    </a>
    <a href="../profile/profile.php" class="mb-2 mt-1">
      <i class="fas fa-user-circle me-2"></i> <span>Profile</span>
    </a>
    
    <a href="../reports/reportspage.php" class=" mb-2 mt-1 text-truncate">
      <i class="fas fa-chart-bar me-2"></i> <span>Reports</span>
    </a>
    <div class="logout-btn">
      <a href="../../../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>
  </div>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>


  <!-- Main Content -->
  <div class="content" id="content">
    <nav class="navbar">
      <span class="toggle-sidebar" id="menuBtn">☰</span>
      <div class="ms-auto d-flex align-items-center">
        <div class="user-details d-flex align-items-center gap-2 bg-primary px-3 py-1 rounded-pill">
          <i class="fas fa-user me-2"></i>
          <p class="m-0"><span id="user-name"><?= htmlspecialchars("$firstname $middlename $lastname") ?></span></p>
        </div>
        <div class="clock-widget ms-3 d-flex align-items-center">
          <i class="fas fa-clock me-2"></i><span id="clock"></span>
        </div>
      </div>
    </nav>

    <div class="container mt-4">
      <div class="welcome-banner">
        <div>
          <h2 class="mb-1">Monitor Tasks</h2>
          <p class="mb-0">View tasks for your projects</p>
        </div>
        <button id="printListBtn" class="btn btn-light text-primary border">
          <i class="fa fa-print me-1"></i> Print Task List
        </button>
      </div>

      <div class="card p-3">
        <div class="table-responsive">
          <table id="tasksTable" class="table">
            <thead>
              <tr>
                <th>Task</th>
                <th>Project</th>
                <th>Assigned To</th>
                <th>Assigned By</th>
                <th>Due Date</th>
                <th>Status</th>
                <th style="width:90px;">Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- View Task Modal (READ ONLY) -->
  <div class="modal fade" id="taskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="taskModalTitle">Task Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <form id="taskForm">
            <input type="hidden" id="task_id">

            <div class="mb-3">
              <label class="form-label">Task Title</label>
              <input type="text" class="form-control" id="tTitle" readonly>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <label class="form-label">Project</label>
                <input type="text" class="form-control" id="tProject" readonly>
              </div>
              <div class="col-md-4">
                <label class="form-label">Assigned To</label>
                <input type="text" class="form-control" id="tAssignedTo" readonly>
              </div>
              <div class="col-md-4">
                <label class="form-label">Status</label>
                <input type="text" class="form-control" id="tStatus" readonly>
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label">Due Date</label>
                <input type="text" class="form-control" id="tDueDate" readonly>
              </div>
              <div class="col-md-6">
                <label class="form-label">Assigned By</label>
                <input type="text" class="form-control" id="tAssignedBy" readonly>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea class="form-control" id="tDescription" rows="3" readonly></textarea>
            </div>
          </form>
        </div>

        <div class="modal-footer">
          <button id="printOneBtn" type="button" class="btn btn-outline-primary">
            <i class="fa fa-print me-1"></i> Print this task
          </button>
          <!-- no Save button here = read only -->
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
  <script src="../../../bootstrap/libs/sweetalert2/sweetalert2.min.js?v=<?= filemtime('../../../bootstrap/libs/sweetalert2/sweetalert2.min.js'); ?>"></script>

  <script>
    // Sidebar + clock
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
    let tasksDT = null;
    let currentTaskPayload = null;

    function esc(s){
      return (s ?? '').toString().replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));
    }

    $(function(){
      // Manager uses the existing admin Ajax (read-only)
      tasksDT = $('#tasksTable').DataTable({
        responsive: true,
        ajax: '../tasks/fetch_tasks.php',
        columns: [
          {
            data: 'title',
            render: (data, type, row) => `
              <strong>${esc(data || '')}</strong><br>
              <small class="text-muted">${esc((row.description || '').substring(0,80))}${row.description && row.description.length>80 ? '…' : ''}</small>
            `
          },
          { data: 'project_name' },
          { data: 'assigned_to_name' },
          { data: 'assigned_by_name' },
          { data: 'due_date' },
          {
            data: 'status',
            render: s => {
              const map = { 'Pending':'secondary', 'In Progress':'primary', 'Completed':'success' };
              const cls = map[s] || 'secondary';
              return `<span class="badge bg-${cls}">${esc(s)}</span>`;
            }
          },
          {
            data: 'task_id',
            orderable: false,
            render: id => `
              <button class="btn btn-sm btn-info view-btn" data-id="${id}">
                <i class="fas fa-eye"></i> View
              </button>`
          }
        ]
      });
    });

    // Open modal (read-only)
    $(document).on('click', '.view-btn', function () {
      const id = $(this).data('id');

      $.post('../tasks/get_task.php', { id }, function (res) {
        if (!res || !res.success) {
          return Swal.fire('Error', res?.error || 'Failed to load task.', 'error');
        }

        const t = res.data;
        currentTaskPayload = t;

        $('#task_id').val(t.task_id);
        $('#tTitle').val(t.title);
        $('#tProject').val(t.project_name);
        $('#tAssignedTo').val(t.assigned_to_name);
        $('#tStatus').val(t.status);
        $('#tDueDate').val(t.due_date);
        $('#tAssignedBy').val(t.assigned_by_name);
        $('#tDescription').val(t.description);

        $('#taskModalTitle').text('Task Details');
        new bootstrap.Modal(document.getElementById('taskModal')).show();
      }, 'json').fail(()=> Swal.fire('Error', 'Request failed', 'error'));
    });

    // Print single task
    document.getElementById('printOneBtn').addEventListener('click', function(){
      if (!currentTaskPayload) return;
      const t   = currentTaskPayload;
      const me  = document.getElementById('user-name').textContent.trim();
      const now = new Date().toLocaleString();

      const html = `
<!doctype html><html><head>
<meta charset="utf-8">
<title>InSync – Task #${t.task_id}</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
  body{ background:#f5f7fb; font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial; }
  .wrap{ max-width:900px; margin:24px auto; padding:0 16px; }
  .print-header{
    background: linear-gradient(135deg, #1e3a8a, #0ea5e9);
    color:#fff; padding:16px 18px; border-radius:12px; margin-bottom:14px;
    box-shadow: 0 6px 14px rgba(2,6,23,.18);
  }
  .card-soft{ background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:14px; }
  .grid{ display:grid; grid-template-columns:1fr 1fr; gap:12px 16px; }
  @media (max-width:575.98px){ .grid{ grid-template-columns:1fr; } }
  .label{ font-size:.78rem; color:#64748b; text-transform:uppercase; }
  .value{ font-weight:700; color:#0f172a; }
  .desc{ white-space:pre-wrap; font-size:.9rem; color:#334155; }
  @media print { .no-print{ display:none !important; } }
</style>
</head>
<body>
  <div class="wrap">
    <div class="print-header d-flex justify-content-between align-items-center">
      <div>
        <h5 class="m-0">InSync • Task Sheet</h5>
        <div class="small opacity-75">Prepared by: <strong>${esc(me)}</strong></div>
      </div>
      <div class="text-end">
        <div class="small">Printed: ${esc(now)}</div>
        <div class="small">Task ID: <strong>#${t.task_id}</strong></div>
      </div>
    </div>

    <div class="card-soft mb-3">
      <div class="grid">
        <div>
          <div class="label">Title</div>
          <div class="value">${esc(t.title || '—')}</div>
        </div>
        <div>
          <div class="label">Status</div>
          <div class="value">${esc(t.status || '—')}</div>
        </div>
        <div>
          <div class="label">Project</div>
          <div class="value">${esc(t.project_name || '—')}</div>
        </div>
        <div>
          <div class="label">Assigned To</div>
          <div class="value">${esc(t.assigned_to_name || '—')}</div>
        </div>
        <div>
          <div class="label">Assigned By</div>
          <div class="value">${esc(t.assigned_by_name || '—')}</div>
        </div>
        <div>
          <div class="label">Due Date</div>
          <div class="value">${esc(t.due_date || '—')}</div>
        </div>
      </div>
      <hr>
      <div>
        <div class="label mb-1">Description</div>
        <div class="desc">${esc(t.description || '—')}</div>
      </div>
    </div>

    <div class="text-center mt-3 no-print">
      <button class="btn btn-primary" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
      <button class="btn btn-outline-secondary ms-2" onclick="window.close()">Close</button>
    </div>
  </div>
</body></html>`;

      const w = window.open('', '_blank', 'width=1000,height=800');
      w.document.open(); w.document.write(html); w.document.close();
      setTimeout(()=>{ try{ w.focus(); w.print(); }catch(e){} }, 300);
    });

    // Print task list
    document.getElementById('printListBtn').addEventListener('click', function(){
      if (!tasksDT) return;
      const rows = tasksDT.rows({search:'applied'}).data().toArray();
      const me   = document.getElementById('user-name').textContent.trim();
      const now  = new Date().toLocaleString();

      const tbody = rows.map(r=>`
        <tr>
          <td>${esc(r.title||'')}</td>
          <td>${esc(r.project_name||'')}</td>
          <td>${esc(r.assigned_to_name||'')}</td>
          <td>${esc(r.assigned_by_name||'')}</td>
          <td>${esc(r.due_date||'')}</td>
          <td>${esc(r.status||'')}</td>
        </tr>`).join('');

      const html = `
<!doctype html><html><head>
<meta charset="utf-8">
<title>InSync – Tasks List</title>
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
        <h5 class="m-0">InSync • Tasks List</h5>
        <div class="small opacity-75">Prepared by: <strong>${esc(me)}</strong></div>
      </div>
      <div class="text-end">
        <div class="small">Printed: ${esc(now)}</div>
        <div class="small">Total tasks: ${rows.length}</div>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle m-0">
          <thead class="table-light">
            <tr>
              <th>Task</th>
              <th>Project</th>
              <th>Assigned To</th>
              <th>Assigned By</th>
              <th>Due Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>${tbody || '<tr><td colspan="6" class="text-center text-muted py-3">No data</td></tr>'}</tbody>
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
