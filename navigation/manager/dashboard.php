<?php
session_start();
if (!isset($_SESSION['accountID'])) {
  header("Location: ../logout.php");
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
  <title>InSync – Performance Dashboard</title>

  <!-- Bootstrap & FontAwesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../bootstrap/fontawesome/css/all.min.css?v=<?= filemtime('../../bootstrap/fontawesome/css/all.min.css'); ?>">

  <!-- Optional custom CSS -->
  <link rel="stylesheet" href="css/admin.css?<?= time(); ?>"> 

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
    .sidebar-menu:hover::-webkit-scrollbar-thumb{
      background: rgba(255,255,255,.6);
    }
    .sidebar-menu::-webkit-scrollbar-thumb:active{
      background: rgba(255,255,255,.8);
    }

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
    .sidebar a.link-active{
      background: #fff;
    }

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

    /* UI bits */
    .welcome-banner{
      background: linear-gradient(135deg, #1e3a8a, #0ea5e9);
      color:#fff; padding:1rem 1.25rem; border-radius:.75rem;
      box-shadow: 0 4px 10px rgba(2,6,23,.15);
      margin-bottom:1rem;
    }
    .welcome-banner small{
      opacity:.9;
    }

    .card-kpi{
      background:#fff; border:1px solid #e5e7eb;
      border-radius:.75rem;
      padding:1rem 1.25rem;
      margin-bottom:1rem;
      box-shadow:0 1px 2px rgba(0,0,0,.04);
    }
    .card-kpi .card-title{
      font-size:.9rem; color:#6b7280; text-transform:uppercase;
      letter-spacing:.03em; margin-bottom:.35rem;
    }
    .card-kpi .card-value{
      font-size:1.75rem; font-weight:700; color:#0f172a;
    }
    .card-kpi .card-sub{
      font-size:.8rem; color:#9ca3af;
    }

    .info-card{
      background:#fff; border:1px solid #e5e7eb;
      border-radius:.75rem; padding:1rem 1.25rem;
      margin-bottom:1rem;
    }

    #statusChart{ height:280px !important; }

    @media print{
      .sidebar, .navbar, .btn-print-report { display:none !important; }
      .content{ margin:0 !important; }
    }

    .badge-soft{
      display:inline-block; padding:.3rem .6rem; border-radius:999px;
      background:#eef2ff; color:#1e40af; font-weight:600; font-size:.8rem;
    }
  </style>
</head>
<body>

<!-- SIDEBAR -->
  <div class="sidebar" id="sidebar">
    <div class="brand">
      <i class="fas fa-sync"></i>
      <span class="full-text">InSync</span>
    </div>

    <a href="dashboard.php" class="link-active mb-2 mt-3"><i class="fas fa-users-cog me-2"></i> <span>Dashboard</span></a>
    <a href="projects/projectpage.php" class="mb-2 mt-1"><i class="fas fa-folder me-2"></i> <span>Manage Project</span></a>
    
    <a href="tasks/monitortaskspage.php" class="mb-2 mt-1 text-truncate">
      <i class="fas fa-eye me-2"></i> <span>Monitor Tasks</span>
    </a>
    <a href="tasks/mytaskspage.php" class=" mb-2 mt-1 text-truncate">
      <i class="fas fa-tasks me-2"></i> <span>My Tasks</span>
    </a>
    
    <a href="tasks/taskhistorypage.php" class="mb-2 mt-1 text-truncate">
      <i class="fas fa-history me-2"></i> <span>Task History</span>
    </a>
    <a href="announcement/announcementpage.php" class="mb-2 mt-1">
      <i class="fas fa-bullhorn me-2"></i> <span>Announcements</span>
    </a> 
    <a href="feedback/feedbackpage.php" class="mb-2 mt-1"><i class="fas fa-phone me-2"></i> <span>Feedback</span></a>
    
    <a href="profile/profile.php" class="mb-2 mt-1"><i class="fas fa-user me-2"></i> <span>Profile</span></a>
    <a href="reports/reportspage.php" class=" mb-2 mt-1 text-truncate">
      <i class="fas fa-chart-bar me-2"></i> <span>Reports</span>
    </a>
    <div class="logout-btn">
      <a href="../../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
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
          <span id="user-name">
            <?= htmlspecialchars(trim("$firstname $middlename $lastname")) ?>
          </span>
        </p>
      </div>
      <div class="clock-widget ms-3 d-flex align-items-center">
        <i class="fas fa-clock me-2"></i>
        <span id="clock"></span>
      </div>
    </div>
  </nav>

  <div class="container mt-4">
    <!-- Banner (simplified, no explicit SOP text) -->
    <div class="welcome-banner">
      <div>
        <h2 class="mb-1">Performance Dashboard</h2>
        <p class="mb-1">
          Monitor employee and project performance in one place.
        </p>
      </div>
      <div class="text-end">
        <span class="badge-soft mb-1">
          Role: <?= htmlspecialchars($userrole ?: 'User') ?>
        </span>
        <button id="btnPrintReport" class="btn btn-light btn-sm btn-print-report">
          <i class="fa fa-print me-1"></i> Print Performance Report
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="info-card mb-3">
      <div class="row g-2 align-items-end">
        <div class="col-sm-4">
          <label class="form-label mb-1">Filter by date</label>
          <input type="date" id="datePicker" class="form-control">
        </div>
        <div class="col-sm-4 d-flex gap-2">
          <button id="btnToday" class="btn btn-outline-primary w-50">
            <i class="fa fa-calendar-day me-1"></i> Today
          </button>
          <button id="btnClear" class="btn btn-outline-secondary w-50">
            <i class="fa fa-times me-1"></i> Clear
          </button>
        </div>
        <div class="col-sm-4 text-sm-end">
          <small class="text-muted d-block mb-1">
            The metrics below update based on the selected date.
          </small>
          <span class="badge-soft">
            Selected: <span id="selectedDateLabel">All dates</span>
          </span>
        </div>
      </div>
    </div>

    <!-- KPI cards -->
    <div class="row mb-2">
      <div class="col-md-3">
        <div class="card-kpi">
          <div class="card-title"><i class="fa fa-gauge-high me-1"></i> Avg. Performance Score</div>
          <div class="card-value" id="kpi-score">0%</div>
          <div class="card-sub">Weighted score across active employees</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card-kpi">
          <div class="card-title"><i class="fa fa-diagram-project me-1"></i> Active Projects</div>
          <div class="card-value" id="kpi-projects">0</div>
          <div class="card-sub">Projects currently being tracked</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card-kpi">
          <div class="card-title"><i class="fa fa-users me-1"></i> Employees Monitored</div>
          <div class="card-value" id="kpi-employees">0</div>
          <div class="card-sub">Employees with assigned work items</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card-kpi">
          <div class="card-title"><i class="fa fa-triangle-exclamation me-1"></i> Items at Risk</div>
          <div class="card-value" id="kpi-risk">0</div>
          <div class="card-sub">Tasks behind schedule or below target</div>
        </div>
      </div>
    </div>

    <!-- Chart + Recent Activity -->
    <div class="row">
      <div class="col-lg-6 mb-3">
        <div class="card-kpi">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="card-title m-0">
              Work Items by Status
            </div>
          </div>
          <canvas id="statusChart"></canvas>
        </div>
      </div>
      <div class="col-lg-6 mb-3">
        <div class="card-kpi">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="card-title m-0">Recent Activity</div>
            <small class="text-muted">Latest 10 updates</small>
          </div>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle m-0">
              <thead>
                <tr>
                  <th>Employee</th>
                  <th>Project</th>
                  <th>Task</th>
                  <th>Status</th>
                  <th>Updated</th>
                </tr>
              </thead>
              <tbody id="activityBody">
                <tr>
                  <td colspan="5" class="text-center text-muted py-3">No activity yet.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>


  </div><!-- /.container -->
</div><!-- /.content -->

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // -------- Sidebar & clock ----------
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

  // -------- Performance dashboard logic ----------
  const datePicker = document.getElementById('datePicker');
  const btnToday   = document.getElementById('btnToday');
  const btnClear   = document.getElementById('btnClear');
  const dateLabel  = document.getElementById('selectedDateLabel');

  const elScore     = document.getElementById('kpi-score');
  const elProjects  = document.getElementById('kpi-projects');
  const elEmployees = document.getElementById('kpi-employees');
  const elRisk      = document.getElementById('kpi-risk');

  const activityBody = document.getElementById('activityBody');

  let statusChart = null;

  function ymd(d){ return d.toISOString().slice(0,10); }

  function badgeClass(status){
    status = (status || '').toLowerCase();
    if (status === 'on track') return 'text-bg-success';
    if (status === 'at risk')  return 'text-bg-warning';
    if (status === 'delayed')  return 'text-bg-danger';
    return 'text-bg-secondary';
  }

  async function loadPerformance(){
    const date = datePicker.value || '';
    dateLabel.textContent = date ? new Date(date).toLocaleDateString() : 'All dates';

    let payload = {
      kpi: { score:0, projects:0, employees:0, risk:0 },
      status: { ontrack:0, atrisk:0, delayed:0, completed:0 },
      activity:[]
    };

    try{
      // create your own backend script to return these fields
      const res = await fetch('ajax/performance_metrics.php?date=' + encodeURIComponent(date), {
        headers:{ 'Accept':'application/json' }
      });
      if(res.ok){
        payload = await res.json();
      }
    }catch(e){
      console.error('performance_metrics fetch failed:', e);
    }

    const k = payload.kpi || {};
    elScore.textContent     = (k.score ?? 0) + '%';
    elProjects.textContent  = k.projects   ?? 0;
    elEmployees.textContent = k.employees  ?? 0;
    elRisk.textContent      = k.risk       ?? 0;

    const s = payload.status || {};
    const labels = ['On Track','At Risk','Delayed','Completed'];
    const values = [
      s.ontrack   || 0,
      s.atrisk    || 0,
      s.delayed   || 0,
      s.completed || 0
    ];

    const canvas = document.getElementById('statusChart');
    if(canvas){
      const ctx = canvas.getContext('2d');
      if(statusChart) statusChart.destroy();
      statusChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels,
          datasets:[{
            label:'Items',
            data: values,
            borderWidth:1,
            backgroundColor:'rgba(37, 99, 235, 0.35)',
            borderColor:'rgba(37, 99, 235, 1)'
          }]
        },
        options:{
          responsive:true,
          maintainAspectRatio:false,
          plugins:{ legend:{ display:false } },
          scales:{ y:{ beginAtZero:true, ticks:{ stepSize:1 } } }
        }
      });
      window.statusChartInstance = statusChart;
    }

    const items = Array.isArray(payload.activity) ? payload.activity.slice(0,10) : [];
    if(!items.length){
      activityBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">No activity yet.</td></tr>';
    }else{
      activityBody.innerHTML = items.map(row => `
        <tr>
          <td>${row.employee || ''}</td>
          <td>${row.project || ''}</td>
          <td>${row.task || ''}</td>
          <td><span class="badge ${badgeClass(row.status)}">${row.status || ''}</span></td>
          <td>${row.updated_at || ''}</td>
        </tr>
      `).join('');
    }
  }

  function initDate(){
    datePicker.value = ymd(new Date());
  }
  initDate();
  loadPerformance();

  btnToday.addEventListener('click', ()=>{ initDate(); loadPerformance(); });
  btnClear.addEventListener('click', ()=>{ datePicker.value=''; loadPerformance(); });
  datePicker.addEventListener('change', loadPerformance);

  // -------- Print Performance Report ----------
  document.getElementById('btnPrintReport').addEventListener('click', () => {
    const userName = document.getElementById('user-name')?.textContent?.trim() || 'User';
    const selDate  = document.getElementById('selectedDateLabel')?.textContent || 'All dates';

    const score     = elScore.textContent;
    const projects  = elProjects.textContent;
    const employees = elEmployees.textContent;
    const risk      = elRisk.textContent;

    // export chart image if available
    let chartImg = '';
    try{
      if(window.statusChartInstance && typeof window.statusChartInstance.toBase64Image === 'function'){
        const src = window.statusChartInstance.toBase64Image();
        chartImg = `<img src="${src}" style="max-width:100%;height:auto;border-radius:10px;border:1px solid #e5e7eb;padding:6px;background:#fff;" alt="Work Items by Status">`;
      }
    }catch(e){}

    const now = new Date().toLocaleString();
    const html = `
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>InSync – Performance Report</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
  body{ background:#f5f7fb; font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial; }
  .wrap{ max-width:950px; margin:24px auto; padding:0 16px; }
  .header{
    background:linear-gradient(135deg,#1e3a8a,#0ea5e9); color:#fff;
    padding:18px 20px; border-radius:14px;
    box-shadow:0 6px 14px rgba(2,6,23,.18); margin-bottom:16px;
  }
  .brand{ font-weight:800; font-size:20px; }
  .sub{ opacity:.9; font-size:.9rem; }
  .card{ border-radius:14px; border:1px solid #e5e7eb; box-shadow:0 1px 2px rgba(0,0,0,.04); }
  .badge-soft{
    display:inline-block; padding:.3rem .6rem; border-radius:999px;
    background:#eef2ff; color:#1e40af; font-weight:600; font-size:.8rem;
  }
  @media print{ .no-print{ display:none !important; } }
</style>
</head>
<body>
  <div class="wrap">
    <div class="header d-flex justify-content-between align-items-center">
      <div>
        <div class="brand">InSync • Performance Report</div>
        <div class="sub">Printed: ${now}</div>
      </div>
      <div class="text-end">
        <div><span class="badge-soft">Date filter: ${selDate}</span></div>
        <div class="mt-1"><small>Prepared by: <strong>${userName}</strong></small></div>
      </div>
    </div>

    <div class="card mb-3 p-3">
      <h6 class="mb-3">Key Performance Indicators</h6>
      <div class="row g-3">
        <div class="col-sm-6 col-lg-3">
          <div class="border rounded-3 p-3 h-100">
            <div class="text-muted small mb-1"><i class="fa fa-gauge-high me-1"></i> Avg. Score</div>
            <div class="fs-4 fw-bold">${score}</div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="border rounded-3 p-3 h-100">
            <div class="text-muted small mb-1"><i class="fa fa-diagram-project me-1"></i> Active Projects</div>
            <div class="fs-4 fw-bold">${projects}</div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="border rounded-3 p-3 h-100">
            <div class="text-muted small mb-1"><i class="fa fa-users me-1"></i> Employees</div>
            <div class="fs-4 fw-bold">${employees}</div>
          </div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <div class="border rounded-3 p-3 h-100">
            <div class="text-muted small mb-1"><i class="fa fa-triangle-exclamation me-1"></i> Items at Risk</div>
            <div class="fs-4 fw-bold">${risk}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-3 p-3">
      <h6 class="mb-2">Work Items by Status</h6>
      ${chartImg || '<div class="text-muted">Chart not available.</div>'}
    </div>

    <div class="card mb-3 p-3">
      <h6 class="mb-2">Performance Modules Overview</h6>
      <ul class="mb-0">
        <li><strong>Performance Dashboard</strong> – shows KPIs and status breakdown in real time.</li>
        <li><strong>Reports</strong> – deeper analysis for trends and project comparisons.</li>
        <li><strong>Export/Print</strong> – export data or use this report for review meetings.</li>
        <li><strong>Log History</strong> – ensures accountability by tracking changes over time.</li>
      </ul>
    </div>

    <div class="text-center mt-3 no-print">
      <button class="btn btn-primary" onclick="window.print()">
        <i class="fa fa-print me-1"></i> Print
      </button>
      <button class="btn btn-outline-secondary ms-2" onclick="window.close()">Close</button>
    </div>
  </div>
</body>
</html>
    `;

    const w = window.open('', '_blank', 'width=1000,height=800');
    w.document.open();
    w.document.write(html);
    w.document.close();
    setTimeout(()=>{ try{ w.focus(); w.print(); } catch(e){} }, 400);
  });
</script>

</body>
</html>
