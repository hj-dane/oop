<?php
session_start();
if (!isset($_SESSION['accountID'])) {
    header("Location: ../../../logout.php");
    exit();
}

$firstname  = $_SESSION['firstname'] ?? '';
$middlename = $_SESSION['middlename'] ?? '';
$lastname   = $_SESSION['lastname'] ?? '';

require '../../../database/connection.php'; // PDO $conn

// ======================
// Fetch active departments
// ======================
$departments = [];
$stmt = $conn->query("SELECT department_id, department_name FROM departments WHERE status='active' ORDER BY department_name");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ======================
// Fetch active managers (role_name = 'Manager')
// ======================
$managers = [];
$sql = "
    SELECT u.user_id,
           CONCAT(u.first_name, ' ', u.last_name) AS full_name
    FROM users u
    INNER JOIN roles r ON u.role_id = r.role_id
    WHERE r.role_name = 'Manager' AND u.status = 'active'
    ORDER BY full_name
";
$stmt = $conn->query($sql);
$managers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Projects</title>

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

    #projectsTable thead th{
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
      <i class="fas fa-chart-line me-2"></i> <span>Dashboard</span>
    </a>
    <a href="../projects/projectpage.php" class="link-active mb-2 mt-1"><i class="fas fa-folder me-2"></i> <span>Manage Projects</span></a>
    
    <a href="../tasks/taskspage.php" class=" mb-2 mt-1 text-truncate">
      <i class="fas fa-tasks me-2"></i> <span>Monitor Tasks</span>
    </a>
    <a href="../tasks/taskhistorypage.php" class="mb-2 mt-1 text-truncate">
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
          <h2 class="mb-1">Project Management</h2>
          <p class="mb-0">Create, update and monitor projects</p>
        </div>
        <div class="d-flex gap-2">
          <button id="newProjectBtn" class="btn btn-light text-primary border">
            <i class="fa fa-plus me-1"></i> New Project
          </button>
          <button id="printListBtn" class="btn btn-light text-primary border">
            <i class="fa fa-print me-1"></i> Print Project List
          </button>
        </div>
      </div>

      <div class="card p-3">
        <div class="table-responsive">
          <table id="projectsTable" class="table">
            <thead>
              <tr>
                <th>Project</th>
                <th>Department</th>
                <th>Manager</th>
                <th>Start</th>
                <th>End</th>
                <th>Status</th>
                <th style="width:120px;">Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- View/Add/Edit Project Modal -->
  <div class="modal fade" id="projectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="projectModalTitle">Project Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <form id="projectForm">
            <input type="hidden" name="project_id" id="project_id">
            <input type="hidden" name="mode" id="project_mode" value="create">

            <div class="mb-3">
              <label class="form-label">Project Name</label>
              <input type="text" class="form-control" name="project_name" id="pName" required>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <label class="form-label">Department</label>
                <select class="form-select" name="department_id" id="pDepartment" required>
                  <option value="">-- Select Department --</option>
                  <?php foreach ($departments as $d): ?>
                    <option value="<?= (int)$d['department_id'] ?>"><?= htmlspecialchars($d['department_name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Project Manager</label>
                <select class="form-select" name="manager_id" id="pManager" required>
                  <option value="">-- Select Manager --</option>
                  <?php foreach ($managers as $m): ?>
                    <option value="<?= (int)$m['user_id'] ?>"><?= htmlspecialchars($m['full_name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Status</label>
                <!-- status is now read-only: hidden input + label -->
                <input type="hidden" name="status" id="pStatus">
                <div id="pStatusLabel" class="form-control-plaintext fw-semibold"></div>
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label">Start Date</label>
                <input type="date" class="form-control" name="start_date" id="pStartDate" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">End Date</label>
                <input type="date" class="form-control" name="end_date" id="pEndDate">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea class="form-control" name="description" id="pDescription" rows="3"></textarea>
            </div>

            <div class="mb-3" id="archiveReasonGroup" style="display:none;">
              <label class="form-label">Reason for archiving (optional)</label>
              <textarea class="form-control" name="archive_reason" id="pArchiveReason" rows="2"></textarea>
            </div>

          </form>
        </div>

        <div class="modal-footer">
          <button id="printOneBtn" type="button" class="btn btn-outline-primary">
            <i class="fa fa-print me-1"></i> Print this project
          </button>
          <button id="archiveProjectBtn" type="button" class="btn btn-warning">
            <i class="fas fa-box-archive"></i> Archive
          </button>
          <button id="saveProjectBtn" type="button" class="btn btn-primary">
            <i class="fas fa-save"></i> Save
          </button>
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
    let projectsDT = null;
    let currentProjectPayload = null;

    function esc(s){
      return (s ?? '').toString().replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));
    }

    $(function(){
      // DataTable
      projectsDT = $('#projectsTable').DataTable({
        responsive: true,
        ajax: 'fetch_projects.php',
        columns: [
          {
            data: 'project_name',
            render: (data, type, row) => `
              <strong>${esc(data || '')}</strong><br>
              <small class="text-muted">${esc((row.description || '').substring(0,80))}${row.description && row.description.length>80 ? '…' : ''}</small>
            `
          },
          { data: 'department_name' },
          { data: 'manager_name' },
          { data: 'start_date' },
          { data: 'end_date' },
          {
            data: 'status',
            render: s => {
              const map = { 'Ongoing':'primary', 'Completed':'success', 'Archived':'dark' };
              const cls = map[s] || 'secondary';
              return `<span class="badge bg-${cls}">${esc(s)}</span>`;
            }
          },
          {
            data: 'project_id',
            orderable: false,
            render: id => `
              <button class="btn btn-sm btn-info view-btn" data-id="${id}">
                <i class="fas fa-eye"></i> View
              </button>`
          }
        ]
      });

      // New Project button
      $('#newProjectBtn').on('click', function () {
        currentProjectPayload = null;
        $('#projectForm')[0].reset();
        $('#project_id').val('');
        $('#project_mode').val('create');

        // status is fixed to Ongoing on create
        $('#pStatus').val('Ongoing');
        $('#pStatusLabel').text('Ongoing');

        $('#archiveReasonGroup').hide();
        $('#archiveProjectBtn').hide();
        $('#projectModalTitle').text('New Project');
        new bootstrap.Modal(document.getElementById('projectModal')).show();
      });
    });

    // Open modal for existing project (status is readonly)
    $(document).on('click', '.view-btn', function () {
      const id = $(this).data('id');

      $.post('get_project.php', { id }, function (res) {
        if (!res || !res.success) {
          return Swal.fire('Error', res?.error || 'Failed to load project.', 'error');
        }

        const p = res.data;
        currentProjectPayload = p;

        $('#project_id').val(p.project_id);
        $('#project_mode').val('update');
        $('#pName').val(p.project_name);
        $('#pDepartment').val(p.department_id);
        $('#pManager').val(p.manager_id);

        $('#pStatus').val(p.status);
        $('#pStatusLabel').text(p.status);

        $('#pStartDate').val(p.start_date);
        $('#pEndDate').val(p.end_date);
        $('#pDescription').val(p.description);
        $('#pArchiveReason').val('');

        const isArchived = (p.status === 'Archived');
        $('#archiveReasonGroup').toggle(!isArchived);
        $('#archiveProjectBtn').toggle(!isArchived);

        $('#projectModalTitle').text('Project Details');
        new bootstrap.Modal(document.getElementById('projectModal')).show();
      }, 'json').fail(()=> Swal.fire('Error', 'Request failed', 'error'));
    });

    // Save create / update (status is not editable by user, just sent as hidden)
    $('#saveProjectBtn').on('click', function () {
      const fd = new FormData(document.getElementById('projectForm'));

      $.ajax({
        url: 'save_project.php',
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        dataType: 'json'
      }).done(function(res){
        if (res.success) {
          Swal.fire('Saved', res.success, 'success');
          $('#projectsTable').DataTable().ajax.reload(null, false);
        } else {
          Swal.fire('Error', res.error || 'Failed to save', 'error');
        }
      }).fail(function(){
        Swal.fire('Error', 'Request failed', 'error');
      });
    });

    // Archive project (will also archive tasks in backend)
    $('#archiveProjectBtn').on('click', function () {
      const projectId = $('#project_id').val();
      if (!projectId) return Swal.fire('Error', 'No project selected.', 'error');

      Swal.fire({
        title: 'Archive project?',
        text: "This will set the project status to Archived and archive its tasks.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d97706',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, archive it!'
      }).then((result) => {
        if (!result.isConfirmed) return;

        $.post('archive_project.php', {
          id: projectId,
          reason: $('#pArchiveReason').val()
        }, function(resp){
          if (resp.success) {
            Swal.fire('Archived!', resp.success, 'success');
            $('#projectModal').modal('hide');
            $('#projectsTable').DataTable().ajax.reload();
          } else {
            Swal.fire('Error', resp.error || 'Failed to archive project.', 'error');
          }
        }, 'json').fail(()=> Swal.fire('Error', 'Server error.', 'error'));
      });
    });

    // Print single project
    document.getElementById('printOneBtn').addEventListener('click', function(){
      if (!currentProjectPayload) return;
      const p   = currentProjectPayload;
      const me  = document.getElementById('user-name').textContent.trim();
      const now = new Date().toLocaleString();

      const html = `
<!doctype html><html><head>
<meta charset="utf-8">
<title>InSync – Project #${p.project_id}</title>
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
  .hr-soft{ border:0; height:1px; background:linear-gradient(90deg,transparent,#e5e7eb,transparent); margin:10px 0 14px; }
  .desc{ white-space:pre-wrap; font-size:.9rem; color:#334155; }
  @media print { .no-print{ display:none !important; } }
</style>
</head>
<body>
  <div class="wrap">
    <div class="print-header d-flex justify-content-between align-items-center">
      <div>
        <h5 class="m-0">InSync • Project Sheet</h5>
        <div class="small opacity-75">Prepared by: <strong>${esc(me)}</strong></div>
      </div>
      <div class="text-end">
        <div class="small">Printed: ${esc(now)}</div>
        <div class="small">Project ID: <strong>#${p.project_id}</strong></div>
      </div>
    </div>

    <div class="card-soft mb-3">
      <div class="grid">
        <div>
          <div class="label">Project Name</div>
          <div class="value">${esc(p.project_name || '—')}</div>
        </div>
        <div>
          <div class="label">Status</div>
          <div class="value">${esc(p.status || '—')}</div>
        </div>
        <div>
          <div class="label">Department</div>
          <div class="value">${esc(p.department_name || '—')}</div>
        </div>
        <div>
          <div class="label">Manager</div>
          <div class="value">${esc(p.manager_name || '—')}</div>
        </div>
        <div>
          <div class="label">Start Date</div>
          <div class="value">${esc(p.start_date || '—')}</div>
        </div>
        <div>
          <div class="label">End Date</div>
          <div class="value">${esc(p.end_date || '—')}</div>
        </div>
      </div>
      <hr class="hr-soft">
      <div>
        <div class="label mb-1">Description</div>
        <div class="desc">${esc(p.description || '—')}</div>
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

    // Print project list
    document.getElementById('printListBtn').addEventListener('click', function(){
      if (!projectsDT) return;
      const rows = projectsDT.rows({search:'applied'}).data().toArray();
      const me   = document.getElementById('user-name').textContent.trim();
      const now  = new Date().toLocaleString();

      const tbody = rows.map(r=>`
        <tr>
          <td>${esc(r.project_name||'')}</td>
          <td>${esc(r.department_name||'')}</td>
          <td>${esc(r.manager_name||'')}</td>
          <td>${esc(r.start_date||'')}</td>
          <td>${esc(r.end_date||'')}</td>
          <td>${esc(r.status||'')}</td>
        </tr>`).join('');

      const html = `
<!doctype html><html><head>
<meta charset="utf-8">
<title>InSync – Projects List</title>
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
        <h5 class="m-0">InSync • Projects List</h5>
        <div class="small opacity-75">Prepared by: <strong>${esc(me)}</strong></div>
      </div>
      <div class="text-end">
        <div class="small">Printed: ${esc(now)}</div>
        <div class="small">Total projects: ${rows.length}</div>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle m-0">
          <thead class="table-light">
            <tr>
              <th>Project</th>
              <th>Department</th>
              <th>Manager</th>
              <th>Start</th>
              <th>End</th>
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
