<?php
// navigation/employee/announcements/announcementpage.php  (read-only)
session_start();
if (!isset($_SESSION['accountID'])) {
    header("Location: ../../../logout.php");
    exit();
}

$userid    = (int)$_SESSION['accountID'];
$firstname = $_SESSION['firstname']  ?? '';
$middlename= $_SESSION['middlename'] ?? '';
$lastname  = $_SESSION['lastname']   ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Announcements</title>

  <link rel="stylesheet" href="../../../bootstrap/css/bootstrap.min.css?v=<?= filemtime('../../../bootstrap/css/bootstrap.min.css'); ?>">
  <link rel="stylesheet" href="../../../bootstrap/fontawesome/css/all.min.css?v=<?= filemtime('../../../bootstrap/fontawesome/css/all.min.css'); ?>">
  <link rel="stylesheet" href="../css/admin.css?<?= filemtime('../css/admin.css'); ?>">

  <!-- DataTables + SweetAlert (SweetAlert kept for possible future notices) -->
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
    .sidebar a.link-active{ background:#fff; color:#0b3a6f; }
    .sidebar a.link-active i{ color:#0b3a6f; }
    .logout-btn{ position:absolute; left:0; right:0; bottom:0; padding:.75rem 1rem; }
    .logout-btn a{ background: rgba(255,255,255,.08); border-radius:.5rem; }

    .content{ margin-left: var(--sidebar-w); min-height: 100vh; transition: margin-left .25s ease; }
    .navbar{ background:#fff; border-bottom:1px solid #e5e7eb; padding:.75rem 1rem; }
    .toggle-sidebar{ cursor:pointer; user-select:none; font-size:1.25rem; }

    @media (min-width: 992px){
      .sidebar.collapsed{ width: var(--sidebar-w-collapsed); }
      .content.collapsed{ margin-left: var(--sidebar-w-collapsed); }
      .sidebar{ transform:none !important; }
      .sidebar.collapsed .brand .full-text,
      .sidebar.collapsed a span{ display:none; }
      .sidebar.collapsed a{ text-align:center; padding:.75rem 0; }
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

    #annTable thead th{
      color:#000 !important;
      background-color:#f8f9fa !important;
      border-bottom:1px solid #e5e7eb !important;
      font-weight:600;
    }

    /* Modal body text formatting */
    #viewAnnBody {
      white-space: pre-wrap;
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <div class="brand">
      <i class="fas fa-sync"></i>
      <span class="full-text">InSync</span>
    </div>

    <a href="../dashboard.php" class="mb-2 mt-3"><i class="fas fa-chart-line me-2"></i> <span>Dashboard</span></a>
    <a href="../tasks/mytaskspage.php" class=" mb-2 mt-1 text-truncate">
      <i class="fas fa-tasks me-2"></i> <span>My Tasks</span>
    </a>
    <a href="../tasks/taskhistorypage.php" class="mb-2 mt-1 text-truncate">
      <i class="fas fa-history me-2"></i> <span>Task History</span>
    </a>
    <a href="../announcements/announcementpage.php" class="mb-2 mt-1 link-active"><i class="fas fa-bullhorn me-2"></i> <span>Announcements</span></a>
    <a href="../feedback/feedbackpage.php" class="mb-2 mt-1"><i class="fas fa-phone me-2"></i> <span>Feedback</span></a>
    <a href="../profile/profile.php" class="mb-2 mt-1"><i class="fas fa-user me-2"></i> <span>Profile</span></a>

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
        <div class="user-details d-flex align-items-center gap-2 bg-primary px-3 py-1 rounded-pill text-white">
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
          <h2 class="mb-1">Announcements</h2>
          <p class="mb-0">View important announcements from InSync administrators.</p>
        </div>
        <!-- no New Announcement button here (read-only) -->
      </div>

      <div class="card p-3">
        <div class="table-responsive">
          <table id="annTable" class="table">
            <thead>
              <tr>
                <th>Title</th>
                <th>Audience</th>
                <th>Status</th>
                <th>Posted By</th>
                <th>Created</th>
                <th style="width: 90px;">Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- View Announcement Modal -->
  <div class="modal fade" id="viewAnnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="viewAnnTitle">Announcement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <span class="badge bg-info me-1" id="viewAnnAudience"></span>
            <span class="badge" id="viewAnnStatus"></span>
          </div>
          <div class="mb-2 text-muted small">
            <span id="viewAnnPostedBy"></span> ·
            <span id="viewAnnCreatedAt"></span>
          </div>
          <hr>
          <div id="viewAnnBody"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
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

    function escHtml(str){
      return (str ?? '').toString()
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;')
        .replace(/'/g,'&#39;');
    }

    let annDT;
    $(function(){
      annDT = $('#annTable').DataTable({
        responsive: true,
        ajax: 'fetch_announcements.php', // backend should already filter by audience/status
        columns: [
          {
            data: 'title',
            render: function(d, type, row){
              // show title; keep message as tooltip (short)
              const msg = row.message ? escHtml(row.message) : '';
              return msg
                ? `<span title="${msg}">${escHtml(d)}</span>`
                : escHtml(d);
            }
          },
          { data: 'audience' },
          {
            data: 'status',
            render: function(d){
              const c = d === 'active' ? 'success' : 'secondary';
              return `<span class="badge bg-${c} text-capitalize">${escHtml(d)}</span>`;
            }
          },
          { data: 'posted_by_name' },
          { data: 'created_at' },
          {
            data: null,
            orderable: false,
            searchable: false,
            render: function(){
              return `
                <button type="button" class="btn btn-sm btn-primary view-ann">
                  <i class="fas fa-eye me-1"></i> View
                </button>
              `;
            }
          }
        ]
      });

      // View button handler
      $(document).on('click', '.view-ann', function(){
        const rowData = annDT.row($(this).closest('tr')).data();
        if (!rowData) return;

        const title   = rowData.title || 'Announcement';
        const msg     = rowData.message || '';
        const audience= rowData.audience || 'All';
        const status  = rowData.status || '';
        const postedBy= rowData.posted_by_name || '';
        const created = rowData.created_at || '';

        // fill modal
        $('#viewAnnTitle').text(title);
        $('#viewAnnAudience').text(audience);

        // status badge
        let sClass = 'bg-secondary';
        if (status === 'active') sClass = 'bg-success';
        if (status === 'inactive') sClass = 'bg-secondary';

        $('#viewAnnStatus')
          .removeClass()
          .addClass('badge ' + sClass)
          .text(status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Status');

        $('#viewAnnPostedBy').text(postedBy ? ('Posted by: ' + postedBy) : '');
        $('#viewAnnCreatedAt').text(created ? ('Created: ' + created) : '');

        $('#viewAnnBody').text(msg);

        const modal = new bootstrap.Modal(document.getElementById('viewAnnModal'));
        modal.show();
      });
    });
  </script>
</body>
</html>
