<?php
// navigation/employee/feedback/feedbackpage.php
session_start();
if (!isset($_SESSION['accountID'])) {
    header("Location: ../../../logout.php");
    exit();
}

$userid    = (int)$_SESSION['accountID'];
$firstname = $_SESSION['firstname']  ?? '';
$middlename= $_SESSION['middlename'] ?? '';
$lastname  = $_SESSION['lastname']   ?? '';

require '../../../database/connection.php'; // PDO $conn

// get employee department (for managers view later, not needed on this page)
$stmt = $conn->prepare("SELECT department_id FROM users WHERE user_id = :id LIMIT 1");
$stmt->execute([':id' => $userid]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Feedback</title>

  <link rel="stylesheet" href="../../../bootstrap/css/bootstrap.min.css?v=<?= filemtime('../../../bootstrap/css/bootstrap.min.css'); ?>">
  <link rel="stylesheet" href="../../../bootstrap/fontawesome/css/all.min.css?v=<?= filemtime('../../../bootstrap/fontawesome/css/all.min.css'); ?>">
  <link rel="stylesheet" href="../css/admin.css?<?= filemtime('../css/admin.css'); ?>">

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

    #fbTable thead th{
      color:#000 !important;
      background-color:#f8f9fa !important;
      border-bottom:1px solid #e5e7eb !important;
      font-weight:600;
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
    <a href="../announcement/announcementpage.php" class="mb-2 mt-1"><i class="fas fa-bullhorn me-2"></i> <span>Announcements</span></a>
    <a href="../feedback/feedbackpage.php" class="mb-2 mt-1 link-active"><i class="fas fa-phone me-2"></i> <span>Feedback</span></a>
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
          <h2 class="mb-1">Feedback</h2>
          <p class="mb-0">Send feedback to your department’s management.</p>
        </div>
      </div>

      <div class="row g-3">
        <!-- Left: feedback form -->
        <div class="col-lg-5">
          <div class="card p-3">
            <h5 class="mb-3"><i class="fas fa-comment-dots me-2"></i> New Feedback</h5>

            <form id="fbForm">
              <input type="hidden" name="submitted_by" value="<?= (int)$userid ?>">

              <div class="mb-3">
                <label class="form-label">Subject</label>
                <input type="text" class="form-control" name="subject" id="fbSubject" required>
              </div>

              <div class="mb-3">
                <label class="form-label">Message</label>
                <textarea class="form-control" name="message" id="fbMessage" rows="4" required></textarea>
              </div>

              <button type="button" id="sendFeedbackBtn" class="btn btn-primary w-100">
                <i class="fas fa-paper-plane me-1"></i> Send Feedback
              </button>
            </form>
          </div>
        </div>

        <!-- Right: list of feedback sent -->
        <div class="col-lg-7">
          <div class="card p-3">
            <h5 class="mb-3"><i class="fas fa-list me-2"></i> My Submitted Feedback</h5>
            <div class="table-responsive">
              <table id="fbTable" class="table">
                <thead>
                  <tr>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Response</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
            <small class="text-muted">
              * Status / response is filled in by your manager or administrator.
            </small>
          </div>
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

    // DataTable of my feedback
    let fbDT;
    $(function(){
      fbDT = $('#fbTable').DataTable({
        responsive: true,
        ajax: 'fetch_my_feedback.php',   // <-- this file below
        columns: [
          { data: 'subject' },
          { data: 'message',
            render: function(d){
              const txt = d || '';
              return escHtml(txt.length > 80 ? txt.substring(0,77) + '...' : txt);
            }
          },
          { data: 'status',
            render: function(d){
              const v = (d || 'New').toLowerCase();
              let cls = 'secondary';
              if (v === 'new') cls = 'primary';
              else if (v === 'in review') cls = 'warning';
              else if (v === 'resolved') cls = 'success';
              return `<span class="badge bg-${cls} text-capitalize">${escHtml(d || 'New')}</span>`;
            }
          },
          { data: 'submitted_at' },
          { data: 'response',
            render: function(d){
              return escHtml(d || '');
            }
          }
        ]
      });
    });

    // Send feedback
    $('#sendFeedbackBtn').on('click', function(){
      const subject = $('#fbSubject').val().trim();
      const msg     = $('#fbMessage').val().trim();

      if (!subject){
        return Swal.fire('Required', 'Please enter a subject.', 'warning');
      }
      if (!msg){
        return Swal.fire('Required', 'Please enter your feedback message.', 'warning');
      }

      const fd = $('#fbForm').serialize();

      $.ajax({
        url: 'send_feedback.php',
        method: 'POST',
        data: fd,
        dataType: 'json'
      }).done(function(res){
        if (res.success){
          Swal.fire('Sent', res.success, 'success');
          $('#fbSubject').val('');
          $('#fbMessage').val('');
          if (fbDT) fbDT.ajax.reload(null, false);
        } else {
          Swal.fire('Error', res.error || 'Failed to send feedback.', 'error');
        }
      }).fail(function(){
        Swal.fire('Error', 'Server error occurred.', 'error');
      });
    });
  </script>
</body>
</html>
