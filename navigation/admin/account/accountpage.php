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

// Fetch roles for dropdown (Admin / Manager / Employee)
$roles = [];
$stmt = $conn->query("SELECT role_id, role_name FROM roles ORDER BY role_name");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch active departments for dropdown
$departments = [];
$stmt = $conn->query("SELECT department_id, Department_name FROM departments WHERE status='active' ORDER BY Department_name");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Users</title>

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
    .logout-btn{ position:absolute; left:0; right:0; bottom:0; padding:.75rem 1rem; }
    .logout-btn a{ background: rgba(255,255,255,.08); border-radius:.5rem; }

    .content{ margin-left: var(--sidebar-w); min-height: 100vh; transition: margin-left .25s ease; }
    .navbar{ background:#fff; border-bottom:1px solid #e5e7eb; padding:.75rem 1rem; }
    .toggle-sidebar{ cursor:pointer; user-select:none; font-size:1.25rem; }

    @media (min-width: 992px){
      .sidebar.collapsed{ width: var(--sidebar-w-collapsed); }
      .content.collapsed{ margin-left: var(--sidebar-w-collapsed); }
      .sidebar{ transform:none !important; }
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

    #accountsTable thead th{
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

    <a href="../dashboard.php" class="mb-2 mt-3"><i class="fas fa-users-cog me-2"></i> <span>Dashboard</span></a>
    <a href="../projects/projectpage.php" class=" mb-2 mt-1"><i class="fas fa-folder me-2"></i> <span>Manage Projects</span></a>
    <a href="../tasks/taskspage.php" class=" mb-2 mt-1 text-truncate">
      <i class="fas fa-tasks me-2"></i> <span>Monitor Tasks</span>
    </a>
    
    <a href="../tasks/taskhistorypage.php" class="mb-2 mt-1 text-truncate">
      <i class="fas fa-history me-2"></i> <span>Task History</span>
    </a>
    <a href="../logs/loghistorypage.php" class=" mb-2 mt-1 text-truncate">
      <i class="fas fa-book me-2"></i> <span>Log History</span>
    </a>
    <a href="../account/accountpage.php" class="link-active mb-2 mt-1"><i class="fas fa-user me-2"></i> <span>Manage Users</span></a>
    <a href="../departments/departmentpage.php" class="mb-2 mt-1 text-truncate"><i class="fas fa-home me-2"></i> <span>Departments</span></a>
    <a href="../contact/contactpage.php" class="mb-2 mt-1"><i class="fas fa-phone me-2"></i> <span>Messages</span></a>
    
    <a href="../profile/profile.php" class="mb-2 mt-1"><i class="fas fa-user me-2"></i> <span>Profile</span></a>
    
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
          <h2 class="mb-1">User Management</h2>
          <p class="mb-0">View and manage user accounts</p>
        </div>
        <button id="printListBtn" class="btn btn-light text-primary border">
          <i class="fa fa-print me-1"></i> Print Users List
        </button>
      </div>

      <div class="card p-3">
        <div class="table-responsive">
          <table id="accountsTable" class="table">
            <thead>
              <tr>
                <th>Username</th>
                <th>Role</th>
                <th>Email</th>
                <th>Department</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- View/Edit User Modal -->
  <div class="modal fade" id="viewAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">User Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <form id="editAccountForm">
            <input type="hidden" name="user_id" id="user_id">

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" name="username" id="uUsername" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" id="uEmail" required>
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <label class="form-label">First Name</label>
                <input type="text" class="form-control" name="first_name" id="uFirstName" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Middle Name</label>
                <input type="text" class="form-control" name="middle_name" id="uMiddleName">
              </div>
              <div class="col-md-4">
                <label class="form-label">Last Name</label>
                <input type="text" class="form-control" name="last_name" id="uLastName" required>
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <label class="form-label">Mobile</label>
                <input type="text" class="form-control" name="mobile" id="uMobile">
              </div>
              <div class="col-md-8">
                <label class="form-label">Address</label>
                <input type="text" class="form-control" name="address" id="uAddress">
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Role</label>
                <select class="form-select" name="role_id" id="uRole" required>
                  <?php foreach ($roles as $r): ?>
                    <option value="<?= (int)$r['role_id'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Department</label>
                <select class="form-select" name="department_id" id="uDepartment" required>
                  <?php foreach ($departments as $d): ?>
                    <option value="<?= (int)$d['department_id'] ?>"><?= htmlspecialchars($d['Department_name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Status</label>
                <select class="form-select" name="status" id="uStatus" required>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
            </div>

          </form>
        </div>

        <div class="modal-footer">
          <button id="printOneBtn" type="button" class="btn btn-outline-primary">
            <i class="fa fa-print me-1"></i> Print this user
          </button>
          <button id="DeleteAccBtn" type="button" class="btn btn-danger">
            <i class="fas fa-trash-alt"></i> Delete
          </button>
          <button id="saveAccountBtn" type="button" class="btn btn-primary">
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
    let accountsDT = null;
    let currentUserPayload = null;

    function esc(s){ return (s ?? '').toString().replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }

    $(function(){
      accountsDT = $('#accountsTable').DataTable({
        responsive: true,
        ajax: 'fetch_accounts.php',
        columns: [
          { data: 'username' },
          { data: 'role_name' },
          { data: 'email' },
          { data: 'Department_name' },
          { data: 'status' },
          {
            data: 'user_id',
            orderable: false,
            render: id => `
              <button class="btn btn-sm btn-info view-btn" data-id="${id}">
                <i class="fas fa-eye"></i> View
              </button>`
          }
        ]
      });
    });

    // Open modal
    $(document).on('click', '.view-btn', function () {
      const id = $(this).data('id');

      $.post('get_accounts.php', { id }, function (res) {
        if (!res || !res.success) {
          return Swal.fire('Error', res?.error || 'Failed to load user.', 'error');
        }

        const u = res.data; // one users row
        currentUserPayload = u;

        $('#user_id').val(u.user_id);
        $('#uUsername').val(u.username);
        $('#uEmail').val(u.email);
        $('#uFirstName').val(u.first_name);
        $('#uMiddleName').val(u.middle_name);
        $('#uLastName').val(u.last_name);
        $('#uMobile').val(u.mobile);
        $('#uAddress').val(u.address);
        $('#uRole').val(u.role_id);
        $('#uDepartment').val(u.department_id);
        $('#uStatus').val(u.status);

        new bootstrap.Modal(document.getElementById('viewAccountModal')).show();
      }, 'json').fail(()=> Swal.fire('Error', 'Request failed', 'error'));
    });

    // Save
    $('#saveAccountBtn').on('click', function () {
      const fd = new FormData(document.getElementById('editAccountForm'));

      $.ajax({
        url: 'update_accounts.php',
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        dataType: 'json'
      }).done(function(res){
        if (res.success) {
          Swal.fire('Saved', res.success, 'success');
          $('#accountsTable').DataTable().ajax.reload(null, false);
        } else {
          Swal.fire('Error', res.error || 'Failed to save', 'error');
        }
      }).fail(function(){
        Swal.fire('Error', 'Request failed', 'error');
      });
    });

    // Delete
    $('#DeleteAccBtn').on('click', function () {
      const userId = $('#user_id').val();
      if (!userId) return Swal.fire('Error', 'No user selected.', 'error');

      Swal.fire({
        title: 'Are you sure?',
        text: "This will permanently delete the user.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (!result.isConfirmed) return;

        $.post('delete_account.php', { id: userId }, function(resp){
          if (resp.success) {
            Swal.fire('Deleted!', resp.success, 'success');
            $('#viewAccountModal').modal('hide');
            $('#accountsTable').DataTable().ajax.reload();
          } else {
            Swal.fire('Error', resp.error || 'Failed to delete user.', 'error');
          }
        }, 'json').fail(()=> Swal.fire('Error', 'Server error.', 'error'));
      });
    });

    // Print single user
    document.getElementById('printOneBtn').addEventListener('click', function(){
      if (!currentUserPayload) return;
      const u   = currentUserPayload;
      const me  = document.getElementById('user-name').textContent.trim();
      const now = new Date().toLocaleString();

      const html = `
<!doctype html><html><head>
<meta charset="utf-8">
<title>InSync – User #${u.user_id}</title>
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
  @media print { .no-print{ display:none !important; } }
</style>
</head>
<body>
  <div class="wrap">
    <div class="print-header d-flex justify-content-between align-items-center">
      <div>
        <h5 class="m-0">InSync • User Sheet</h5>
        <div class="small opacity-75">Prepared by: <strong>${esc(me)}</strong></div>
      </div>
      <div class="text-end">
        <div class="small">Printed: ${esc(now)}</div>
        <div class="small">User ID: <strong>#${u.user_id}</strong></div>
      </div>
    </div>

    <div class="card-soft mb-3">
      <div class="grid">
        <div>
          <div class="label">Username</div>
          <div class="value">${esc(u.username || '—')}</div>
        </div>
        <div>
          <div class="label">Role</div>
          <div class="value">${esc(u.role_name || '—')}</div>
        </div>
        <div>
          <div class="label">Email</div>
          <div class="value">${esc(u.email || '—')}</div>
        </div>
        <div>
          <div class="label">Department</div>
          <div class="value">${esc(u.Department_name || '—')}</div>
        </div>
        <div>
          <div class="label">Status</div>
          <div class="value text-capitalize">${esc(u.status || '—')}</div>
        </div>
        <div>
          <div class="label">Name</div>
          <div class="value">${esc([u.first_name,u.middle_name,u.last_name].filter(Boolean).join(' ') || '—')}</div>
        </div>
        <div>
          <div class="label">Mobile</div>
          <div class="value">${esc(u.mobile || '—')}</div>
        </div>
        <div>
          <div class="label">Address</div>
          <div class="value">${esc(u.address || '—')}</div>
        </div>
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

    // Print list
    document.getElementById('printListBtn').addEventListener('click', function(){
      if (!accountsDT) return;
      const rows = accountsDT.rows({search:'applied'}).data().toArray();
      const me   = document.getElementById('user-name').textContent.trim();
      const now  = new Date().toLocaleString();

      const tbody = rows.map(r=>`
        <tr>
          <td>${esc(r.username||'')}</td>
          <td>${esc(r.role_name||'')}</td>
          <td>${esc(r.email||'')}</td>
          <td>${esc(r.Department_name||'')}</td>
          <td>${esc(r.status||'')}</td>
        </tr>`).join('');

      const html = `
<!doctype html><html><head>
<meta charset="utf-8">
<title>InSync – Users List</title>
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
        <h5 class="m-0">InSync • Users List</h5>
        <div class="small opacity-75">Prepared by: <strong>${esc(me)}</strong></div>
      </div>
      <div class="text-end">
        <div class="small">Printed: ${esc(now)}</div>
        <div class="small">Total users: ${rows.length}</div>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle m-0">
          <thead class="table-light">
            <tr>
              <th>Username</th>
              <th>Role</th>
              <th>Email</th>
              <th>Department</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>${tbody || '<tr><td colspan="5" class="text-center text-muted py-3">No data</td></tr>'}</tbody>
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
