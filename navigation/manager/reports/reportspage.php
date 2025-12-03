<?php
session_start();
if (!isset($_SESSION['accountID'])) {
  header("Location: ../../../logout.php");
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
  <title>InSync – Reports (Manager)</title>

  <!-- Bootstrap & FontAwesome -->
  <link rel="stylesheet" href="../../../bootstrap/css/bootstrap.min.css?v=<?= filemtime('../../../bootstrap/css/bootstrap.min.css'); ?>">
  <link rel="stylesheet" href="../../../bootstrap/fontawesome/css/all.min.css?v=<?= filemtime('../../../bootstrap/fontawesome/css/all.min.css'); ?>">

  <!-- Shared admin/manager style -->
  <link rel="stylesheet" href="../css/admin.css?<?= filemtime('../css/admin.css'); ?>">

  <!-- DataTables + SweetAlert -->
  <link rel="stylesheet" href="../../../bootstrap/datatable/css/dataTables.bootstrap5.min.css?<?= filemtime('../../../bootstrap/datatable/css/dataTables.bootstrap5.min.css'); ?>">
  <link rel="stylesheet" href="../../../bootstrap/datatable/css/responsive.bootstrap5.min.css?<?= filemtime('../../../bootstrap/datatable/css/responsive.bootstrap5.min.css'); ?>">
  <link rel="stylesheet" href="../../../bootstrap/libs/sweetalert2/sweetalert2.min.css?v=<?= filemtime('../../../bootstrap/libs/sweetalert2/sweetalert2.min.css'); ?>">

  <style>
    :root{
      --sidebar-w: 250px;
      --sidebar-w-collapsed: 80px;
      --sidebar-bg: #0b3a6f;
      --sidebar-fg: #ffffff;
      --nav-fg: #0f172a;
    }
    html, body { height: 100%; background:#f5f7fb; }

    /* Sidebar */
    .sidebar{
      position: fixed; top:0; left:0; height:100vh;
      width: var(--sidebar-w);
      background: var(--sidebar-bg); color: var(--sidebar-fg);
      display:flex; flex-direction:column;
      transition: width .25s ease, transform .25s ease;
      z-index: 1045;
    }
    .sidebar-menu{
      overflow-y: auto;
      overflow-x: hidden;
      scrollbar-width: thin;
      scrollbar-color: rgba(255,255,255,.45) transparent;
      scrollbar-gutter: stable both-edges;
      padding-right: 4px;
    }
    .sidebar-menu::-webkit-scrollbar{ width: 10px; }
    .sidebar-menu::-webkit-scrollbar-track{ background: transparent; }
    .sidebar-menu::-webkit-scrollbar-thumb{
      background: rgba(255,255,255,.35);
      border-radius: 999px;
      border: 2px solid transparent;
      background-clip: padding-box;
    }
    .sidebar-menu:hover::-webkit-scrollbar-thumb{ background: rgba(255,255,255,.6); }
    .sidebar-menu::-webkit-scrollbar-thumb:active{ background: rgba(255,255,255,.8); }

    .sidebar .brand{
      display:flex; align-items:center; gap:.5rem;
      padding:1rem; font-weight:600; font-size:1.4rem;
      border-bottom:1px solid rgba(255,255,255,.12);
    }
    .sidebar a{
      display:block; color:#fff; text-decoration:none;
      padding:.75rem 1rem;
    }
    .sidebar a:hover{ background: rgba(255,255,255,.08); }
    .sidebar a.link-active{ background:#fff; color:#0b3a6f; }

    .logout-btn{
      flex:0 0 auto;
      position: sticky;
      bottom: 0;
      background: rgba(255,255,255,.06);
      border-top: 1px solid rgba(255,255,255,.12);
      padding:.75rem 1rem;
    }
    .logout-btn a{
      display:block;
      color:#fff;
      text-decoration:none;
      background: rgba(255,255,255,.08);
      border-radius:.5rem;
      padding:.6rem .9rem;
    }

    /* Content */
    .content{
      min-height:100vh;
      margin-left: var(--sidebar-w);
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
      margin-right:.5rem;
    }

    /* Desktop collapse */
    @media (min-width: 992px){
      .sidebar.collapsed{ width: var(--sidebar-w-collapsed); }
      .content.collapsed{ margin-left: var(--sidebar-w-collapsed); }

      .sidebar.collapsed .brand .full-text,
      .sidebar.collapsed a span { display:none; }
      .sidebar.collapsed a { text-align:center; padding:.75rem 0; }
    }

    /* Mobile: slide-in */
    @media (max-width: 991.98px){
      .sidebar{ transform: translateX(-100%); width: var(--sidebar-w); }
      .sidebar.open{ transform: translateX(0); }
      .content{ margin-left: 0 !important; }
    }
    .sidebar-overlay{
      display:none; position:fixed; inset:0;
      background: rgba(0,0,0,.35); z-index:1040;
    }
    .sidebar-overlay.show{ display:block; }

    .welcome-banner{
      background: linear-gradient(135deg, #1e3a8a, #0ea5e9);
      color:#fff; padding:1rem 1.25rem; border-radius:.75rem;
      box-shadow: 0 4px 10px rgba(2,6,23,.15);
      margin-bottom:1rem;
      display:flex; align-items:center; justify-content:space-between; gap:12px;
    }
    .welcome-banner small{ opacity:.9; }

    #reportsTable thead th{
      color:#000 !important;
      background-color:#f8f9fa !important;
      border-bottom:1px solid #e5e7eb !important;
      font-weight:600;
    }

    .user-details i, #user-name { color:white; }
  </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
  <div class="brand">
    <i class="fas fa-sync"></i>
    <span class="full-text">InSync</span>
  </div>

  <div class="sidebar" id="sidebar">
    <div class="brand">
      <i class="fas fa-sync"></i>
      <span class="full-text">InSync</span>
    </div>

    <a href="../dashboard.php" class="mb-2 mt-3"><i class="fas fa-users-cog me-2"></i> <span>Dashboard</span></a>
    <a href="../projects/projectpage.php" class=" mb-2 mt-1"><i class="fas fa-folder me-2"></i> <span>Manage Project</span></a>
    <a href="../tasks/monitortaskspage.php" class="mb-2 mt-1 text-truncate">
      <i class="fas fa-eye me-2"></i> <span>Monitor Tasks</span>
    </a>
    
    <a href="../tasks/mytaskspage.php" class=" mb-2 mt-1 text-truncate">
      <i class="fas fa-tasks me-2"></i> <span>My Tasks</span>
    </a>
    <a href="../tasks/taskhistorypage.php" class="mb-2 mt-1 text-truncate">
      <i class="fas fa-history me-2"></i> <span>Task History</span>
    </a>
    <a href="../announcement/announcementpage.php" class="mb-2 mt-1">
      <i class="fas fa-bullhorn me-2"></i> <span>Announcements</span>
    </a> 

    <a href="../feedback/feedbackpage.php" class="mb-2 mt-1"><i class="fas fa-phone me-2"></i> <span>Feedback</span></a>
    
    <a href="../profile/profile.php" class=" mb-2 mt-1"><i class="fas fa-user me-2"></i> <span>Profile</span></a>
    <a href="../reports/reportspage.php" class="link-active mb-2 mt-1 text-truncate">
      <i class="fas fa-chart-bar me-2"></i> <span>Reports</span>
    </a>
    <div class="logout-btn">
      <a href="../../../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>
  </div>

</div>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- MAIN CONTENT -->
<div class="content" id="content">
  <nav class="navbar">
    <span class="toggle-sidebar" id="menuBtn">☰</span>
    <div class="ms-auto d-flex align-items-center">
      <div class="user-details d-flex align-items-center gap-2 bg-primary px-3 py-1 rounded-pill text-white">
        <i class="fas fa-user me-2"></i>
        <p class="m-0">
          <span id="user-name"><?= htmlspecialchars(trim("$firstname $middlename $lastname")) ?></span>
        </p>
      </div>
      <div class="clock-widget ms-3 d-flex align-items-center">
        <i class="fas fa-clock me-2"></i>
        <span id="clock"></span>
      </div>
    </div>
  </nav>

  <div class="container mt-4">
    <div class="welcome-banner">
      <div>
        <h2 class="mb-1">Reports</h2>
        <p class="mb-0">Generate and review reports for your projects and tasks.</p>
      </div>
      <button id="btnPrintSelected" class="btn btn-light btn-sm text-primary border">
        <i class="fa fa-print me-1"></i> Print Selected
      </button>
    </div>

    <!-- Generate form -->
    <div class="card p-3 mb-3">
      <div class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label mb-1">Report Type</label>
          <select id="reportType" class="form-select">
            <option value="">Select report type</option>
            <option value="Projects Summary">Projects Summary</option>
            <option value="Tasks Summary">Tasks Summary</option>
            <option value="Employee Workload">Employee Workload</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label mb-1">Period Start</label>
          <input type="date" id="periodStart" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label mb-1">Period End</label>
          <input type="date" id="periodEnd" class="form-control">
        </div>
        <div class="col-md-2">
          <button id="btnGenerate" class="btn btn-primary w-100">
            <i class="fa fa-file-alt me-1"></i> Generate
          </button>
        </div>
      </div>
      <small class="text-muted d-block mt-2">
        Generated reports are saved in the list below so you can open or print them anytime.
      </small>
    </div>

    <!-- Report list -->
    <div class="card p-3">
      <div class="table-responsive">
        <table id="reportsTable" class="table">
          <thead>
            <tr>
              <th></th>
              <th>Created At</th>
              <th>Report Type</th>
              <th>Generated By</th>
              <th>Period</th>
              <th>Actions</th>
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
<script src="../../../bootstrap/libs/sweetalert2/sweetalert2.min.js?v=<?= filemtime('../../../bootstrap/libs/sweetalert2/sweetalert2.min.js'); ?>"></script>

<script>
  // Sidebar & clock
  const sidebar = document.getElementById("sidebar");
  const content = document.getElementById("content");
  const overlay = document.getElementById("sidebarOverlay");
  const menuBtn = document.getElementById("menuBtn");

  function openMobileSidebar(){
    sidebar.classList.add('open');
    overlay.classList.add('show');
    document.body.style.overflow='hidden';
  }
  function closeMobileSidebar(){
    sidebar.classList.remove('open');
    overlay.classList.remove('show');
    document.body.style.overflow='';
  }
  function toggleSidebar(){
    if (window.innerWidth < 992){
      sidebar.classList.contains('open') ? closeMobileSidebar() : openMobileSidebar();
    } else {
      sidebar.classList.toggle("collapsed");
      content.classList.toggle("collapsed");
    }
  }
  menuBtn.addEventListener('click', toggleSidebar);
  overlay.addEventListener('click', closeMobileSidebar);
  window.addEventListener('resize', ()=>{ if(window.innerWidth>=992){ closeMobileSidebar(); }});

  function updateClock(){
    document.getElementById('clock').innerText = new Date().toLocaleTimeString();
  }
  setInterval(updateClock,1000); updateClock();
</script>

<script>
  let reportsDT = null;

  function esc(s){
    return (s ?? '').toString().replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));
  }

  $(function(){
    // DataTable
    reportsDT = $('#reportsTable').DataTable({
      responsive:true,
      ajax:{
        url:'fetch_reports.php',
        error:function(xhr,status,err){
          console.error('Reports Ajax error:', status, err);
          console.log('Server response:', xhr.responseText);
        }
      },
      order:[[1,'desc']],
      columns:[
        {
          data:'report_id',
          orderable:false,
          searchable:false,
          render:id => `<input type="radio" name="selected_report" value="${id}">`
        },
        { data:'created_at' },
        { data:'report_type' },
        { data:'generated_by_name' },
        {
          data:null,
          render: row => {
            const s = row.period_start || '';
            const e = row.period_end   || '';
            if(!s && !e) return '';
            return `${esc(s)} → ${esc(e)}`;
          }
        },
        {
          data:'report_id',
          orderable:false,
          render:id => `
            <button class="btn btn-sm btn-outline-primary btn-open" data-id="${id}">
              <i class="fa fa-eye"></i> Open
            </button>`
        }
      ]
    });

    // Generate
    $('#btnGenerate').on('click', function(){
      const type  = $('#reportType').val().trim();
      const start = $('#periodStart').val();
      const end   = $('#periodEnd').val();

      if(!type){
        return Swal.fire('Missing type','Please select a report type.','warning');
      }
      if(!start || !end){
        return Swal.fire('Missing dates','Please select a start and end date.','warning');
      }
      if(start > end){
        return Swal.fire('Invalid period','Start date cannot be after end date.','warning');
      }

      const fd = new FormData();
      fd.append('report_type', type);
      fd.append('period_start', start);
      fd.append('period_end', end);

      $.ajax({
        url:'generate_report.php',
        method:'POST',
        data:fd,
        processData:false,
        contentType:false,
        dataType:'json'
      }).done(function(res){
        if(res && res.success){
          Swal.fire('Generated', res.message || 'Report generated.', 'success');
          $('#reportsTable').DataTable().ajax.reload(null,false);
          if(res.report_id){
            window.open('print_report.php?id=' + encodeURIComponent(res.report_id), '_blank');
          }
        }else{
          Swal.fire('Error', (res && res.error) || 'Failed to generate report.', 'error');
        }
      }).fail(function(xhr){
        console.error('Generate report error:', xhr.responseText);
        Swal.fire('Error','Server error while generating report.','error');
      });
    });

    // Open from button
    $(document).on('click', '.btn-open', function(){
      const id = $(this).data('id');
      if(!id) return;
      window.open('print_report.php?id=' + encodeURIComponent(id), '_blank');
    });

    // Print selected
    $('#btnPrintSelected').on('click', function(){
      const id = $('input[name="selected_report"]:checked').val();
      if(!id){
        return Swal.fire('No report selected','Please select a report from the list first.','info');
      }
      window.open('print_report.php?id=' + encodeURIComponent(id), '_blank');
    });
  });
</script>

</body>
</html>
