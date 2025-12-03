<?php
// navigation/admin/contact/contactpage.php
session_start();
if (!isset($_SESSION['accountID'])) {
    header("Location: ../logout.php");
    exit();
}

/* ---- Robust DB include path (3 levels up to project root) ---- */
$dbPath = dirname(__DIR__, 3) . '/database/connection.php'; // .../MJevent-EventScheduling/database/connection.php
if (!file_exists($dbPath)) {
    // optional graceful fallback (adjust if your structure differs)
    $dbPathAlt = dirname(__DIR__, 2) . '/database/connection.php';
    if (file_exists($dbPathAlt)) $dbPath = $dbPathAlt;
}
require_once $dbPath; // must define PDO $conn or mysqli $conn, per your app

/* Page header data */
$firstname  = $_SESSION['firstname'] ?? '';
$middlename = $_SESSION['middlename'] ?? '';
$lastname   = $_SESSION['lastname']  ?? '';

/* Controls for active/collapsed groups in the sidebar */
$activeSchedule = '';               // 'pending'|'approved'|'completed'|'cancelled'|''
$activeContent  = 'contact';        // mark this page as active under Content
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Contact (Admin)</title>

  <link rel="stylesheet" href="../../../bootstrap/css/bootstrap.min.css?v=<?= filemtime('../../../bootstrap/css/bootstrap.min.css'); ?>">
  <link rel="stylesheet" href="../../../bootstrap/fontawesome/css/all.min.css?v=<?= filemtime('../../../bootstrap/fontawesome/css/all.min.css'); ?>">
  <link rel="stylesheet" href="../css/admin.css?<?= filemtime('../css/admin.css'); ?>">

  <!-- Optional libs (keep if you use tables/alerts here) -->
  <link rel="stylesheet" href="../../../bootstrap/datatable/css/dataTables.bootstrap5.min.css?<?= filemtime('../../../bootstrap/datatable/css/dataTables.bootstrap5.min.css'); ?>">
  <link rel="stylesheet" href="../../../bootstrap/datatable/css/responsive.bootstrap5.min.css?<?= filemtime('../../../bootstrap/datatable/css/responsive.bootstrap5.min.css'); ?>">
  <link rel="stylesheet" href="../../../bootstrap/libs/sweetalert2/sweetalert2.min.css?v=<?= filemtime('../../../bootstrap/libs/sweetalert2/sweetalert2.min.css'); ?>">

  <style>
    :root{
      --sidebar-w: 240px;
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
      overflow-y: auto; z-index:1045;
      transition: width .25s ease, transform .25s ease;
      padding-bottom: 1rem;
    }
    .sidebar .brand{ display:flex; align-items:center; gap:.5rem; padding:1rem; border-bottom:1px solid rgba(255,255,255,.12); }
    .sidebar a{ display:block; color:#fff; text-decoration:none; padding:.75rem 1rem; }
    .sidebar a:hover{ background: rgba(255,255,255,.08); }
    .logout-btn{ position:absolute; left:0; right:0; bottom:0; padding:.75rem 1rem; }
    .logout-btn a{ background: rgba(255,255,255,.08); border-radius:.5rem; }

    /* Collapsible groups */
    .sidebar .menu-toggle{
      display:flex; align-items:center; gap:.5rem;
      padding:.75rem 1rem; color:#fff; text-decoration:none; cursor:pointer;
    }
    .sidebar .submenu{
      display:block; color:#e7e7e7; text-decoration:none;
      padding:.5rem 2.25rem; font-size:.95rem;
    }
    .sidebar .submenu:hover,
    .sidebar .submenu.active{ background:rgba(255,255,255,.12); color:#fff; border-radius: 10px; }
    .menu-toggle .chev{ margin-left:auto; transition:transform .2s; }
    .menu-toggle[aria-expanded="true"] .chev{ transform:rotate(180deg); }

    /* Content */
    .content{ margin-left: var(--sidebar-w); min-height: 100vh; transition: margin-left .25s ease; }
    .navbar{ background:#fff; border-bottom:1px solid #e5e7eb; padding:.75rem 1rem; }
    .toggle-sidebar{ cursor:pointer; user-select:none; font-size:1.25rem; }

    /* Desktop collapse */
    @media (min-width: 992px){
      .sidebar.collapsed{ width: var(--sidebar-w-collapsed); }
      .content.collapsed{ margin-left: var(--sidebar-w-collapsed); }
      .sidebar{ transform:none !important; }
    }

    /* Mobile: off-canvas */
    @media (max-width: 991.98px){
      .sidebar{ transform: translateX(-100%); }
      .sidebar.show{ transform: translateX(0); }
      .content{ margin-left: 0 !important; }
    }

    /* Overlay for mobile */
    .sidebar-overlay{
      display:none; position:fixed; inset:0; background: rgba(0,0,0,.35); z-index:1040;
    }
    .sidebar-overlay.show{ display:block; }

    /* Banner */
    .welcome-banner{
      background: linear-gradient(135deg, #1e3a8a, #0ea5e9);
      color:#fff; padding:1rem 1.25rem; border-radius:.75rem; margin-bottom:1rem;
      box-shadow: 0 4px 10px rgba(2,6,23,.15);
      display:flex; align-items:center; justify-content:space-between; gap:12px;
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

    <a href="../dashboard.php" class=" mb-2 mt-3"><i class="fas fa-users-cog me-2"></i> <span>Dashboard</span></a>
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
    <a href="../account/accountpage.php" class="mb-2 mt-1"><i class="fas fa-user me-2"></i> <span>Manage Users</span></a>
    <a href="../departments/departmentpage.php" class=" mb-2 mt-1 text-truncate"><i class="fas fa-home me-2"></i> <span>Departments</span></a>
    <a href="../contact/contactpage.php" class="link-active mb-2 mt-1"><i class="fas fa-phone me-2"></i> <span>Messages</span></a>
    
    <a href="../profile/profile.php" class="mb-2 mt-1"><i class="fas fa-user me-2"></i> <span>Profile</span></a>
    
    <a href="../reports/reportspage.php" class=" mb-2 mt-1 text-truncate">
      <i class="fas fa-chart-bar me-2"></i> <span>Reports</span>
    </a>

    <div class="logout-btn">
      <a href="../../../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>
  </div>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- Main -->
  <div class="content" id="content">
    <nav class="navbar">
      <span class="toggle-sidebar" id="menuBtn">☰</span>
      <div class="ms-auto d-flex align-items-center">
        <div class="user-details d-flex align-items-center gap-2 bg-primary px-3 py-1 rounded-pill text-white">
          <i class="fas fa-user me-2"></i>
          <p class="m-0"><?= htmlspecialchars("$firstname $middlename $lastname") ?></p>
        </div>
        <div class="clock-widget ms-3 d-flex align-items-center">
          <i class="fas fa-clock me-2"></i><span id="clock"></span>
        </div>
      </div>
    </nav>

    <div class="container mt-4">
      <div class="welcome-banner">
        <div>
          <h2 class="mb-1">Contact Messages</h2>
          <p class="mb-0">View and manage messages sent via your public Contact page.</p>
        </div>
      </div>

      <!-- Your admin content here -->
      <div class="card p-3">
        <p class="text-muted mb-0">
          TODO: Hook up your messages table or list here (e.g., DataTables pulling from an AJAX endpoint).
        </p>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="../../../bootstrap/jquery/jquery-3.6.0.min.js"></script>
  <script src="../../../bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../../../bootstrap/datatable/js/jquery.dataTables.min.js"></script>
  <script src="../../../bootstrap/datatable/js/dataTables.bootstrap5.min.js"></script>
  <script src="../../../bootstrap/datatable/js/dataTables.responsive.min.js"></script>
  <script src="../../../bootstrap/datatable/js/responsive.bootstrap5.min.js"></script>
  <script src="../../../bootstrap/libs/sweetalert2/sweetalert2.min.js?v=<?= filemtime('../../../bootstrap/libs/sweetalert2/sweetalert2.min.js'); ?>"></script>

  <script>
    // Sidebar behavior
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const content = document.getElementById('content');

    document.getElementById('menuBtn').addEventListener('click', ()=>{
      if (window.innerWidth < 992){
        sidebar.classList.toggle('show'); overlay.classList.toggle('show');
        document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
      } else {
        sidebar.classList.toggle('collapsed'); content.classList.toggle('collapsed');
      }
    });
    overlay.addEventListener('click', ()=>{
      sidebar.classList.remove('show'); overlay.classList.remove('show'); document.body.style.overflow = '';
    });

    // Clock
    function updateClock(){ document.getElementById('clock').innerText = new Date().toLocaleTimeString(); }
    setInterval(updateClock, 1000); updateClock();
  </script>
</body>
</html>
