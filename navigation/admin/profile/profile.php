<?php
// navigation/admin/account/profile.php
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

// Fetch logged-in user info
$sql = "SELECT u.*, d.Department_name, r.role_name
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.department_id
        LEFT JOIN roles r       ON u.role_id = r.role_id
        WHERE u.user_id = :id
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute([':id' => $userid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: ../../../logout.php");
    exit();
}

// profile image path (relative path stored in users.profile_image)
$profileImage = $user['profile_image'] ?? '';
if (!$profileImage) {
    $profileImageUrl = "https://via.placeholder.com/150x150.png?text=Profile";
} else {
    $profileImageUrl = "../../../" . ltrim($profileImage, '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Profile</title>

  <link rel="stylesheet" href="../../../bootstrap/css/bootstrap.min.css?v=<?= filemtime('../../../bootstrap/css/bootstrap.min.css'); ?>">
  <link rel="stylesheet" href="../../../bootstrap/fontawesome/css/all.min.css?v=<?= filemtime('../../../bootstrap/fontawesome/css/all.min.css'); ?>">
  <link rel="stylesheet" href="../css/admin.css?<?= filemtime('../css/admin.css'); ?>">

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
    .sidebar a.link-active{ background: #fff; }
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

    .profile-card{
      background:#fff; border:1px solid #e5e7eb; border-radius:.75rem;
      padding:1.25rem; box-shadow:0 1px 2px rgba(0,0,0,.04);
    }

    .avatar-wrapper{
      width:140px; height:140px; border-radius:50%;
      overflow:hidden; border:3px solid #e5e7eb;
    }
    .avatar-wrapper img{
      width:100%; height:100%; object-fit:cover;
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
    <a href="../departments/departmentpage.php" class="mb-2 mt-1 text-truncate"><i class="fas fa-home me-2"></i> <span>Departments</span></a>
    <a href="../contact/contactpage.php" class="mb-2 mt-1"><i class="fas fa-phone me-2"></i> <span>Messages</span></a>
    
    <a href="../profile/profile.php" class="mb-2 mt-1 link-active"><i class="fas fa-user me-2"></i> <span>Profile</span></a>

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
          <p class="m-0">
            <span id="user-name"><?= htmlspecialchars("$firstname $middlename $lastname") ?></span>
          </p>
        </div>
        <div class="clock-widget ms-3 d-flex align-items-center">
          <i class="fas fa-clock me-2"></i><span id="clock"></span>
        </div>
      </div>
    </nav>

    <div class="container mt-4">
      <div class="welcome-banner">
        <div>
          <h2 class="mb-1">My Profile</h2>
          <p class="mb-0">Update your personal information and password.</p>
        </div>
      </div>

      <div class="row g-3">
        <!-- Left: Profile picture + basic info -->
        <div class="col-lg-4">
          <div class="profile-card text-center">
            <div class="avatar-wrapper mx-auto mb-3">
              <img id="profilePreview" src="<?= htmlspecialchars($profileImageUrl) ?>" alt="Profile Picture">
            </div>
            <h5 class="mb-1"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h5>
            <p class="text-muted mb-2">
              <?= htmlspecialchars($user['role_name'] ?: 'User') ?><br>
              <small><?= htmlspecialchars($user['Department_name'] ?: '') ?></small>
            </p>

            <form id="profileForm" enctype="multipart/form-data">
              <input type="hidden" name="user_id" value="<?= (int)$user['user_id'] ?>">

              <div class="mb-3 text-start">
                <label class="form-label">Change Profile Picture</label>
                <input type="file" class="form-control" id="profileImageInput" name="profile_image" accept="image/*">
                <small class="text-muted">Max 2MB. JPG / PNG only.</small>
              </div>

              <div class="mb-3 text-start">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                <small class="text-muted">Username is managed by the system.</small>
              </div>

              <div class="mb-3 text-start">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" id="pEmail"
                       value="<?= htmlspecialchars($user['email']) ?>" required>
              </div>

              <div class="mb-3 text-start">
                <label class="form-label">Mobile</label>
                <input type="text" class="form-control" name="mobile" id="pMobile"
                       value="<?= htmlspecialchars($user['mobile']) ?>">
              </div>

              <div class="mb-3 text-start">
                <label class="form-label">Address</label>
                <input type="text" class="form-control" name="address" id="pAddress"
                       value="<?= htmlspecialchars($user['address']) ?>">
              </div>

              <div class="row g-2">
                <div class="col-6 text-start">
                  <label class="form-label">First Name</label>
                  <input type="text" class="form-control" name="first_name" id="pFirstName"
                         value="<?= htmlspecialchars($user['first_name']) ?>" required>
                </div>
                <div class="col-6 text-start">
                  <label class="form-label">Last Name</label>
                  <input type="text" class="form-control" name="last_name" id="pLastName"
                         value="<?= htmlspecialchars($user['last_name']) ?>" required>
                </div>
              </div>

              <div class="mt-3 d-grid">
                <button type="button" id="saveProfileBtn" class="btn btn-primary">
                  <i class="fas fa-save me-1"></i> Save Profile
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Right: Change password -->
        <div class="col-lg-8">
          <div class="profile-card mb-3">
            <h5 class="mb-3"><i class="fas fa-lock me-2"></i> Change Password</h5>
            <form id="passwordForm">
              <div class="mb-3">
                <label class="form-label">Current Password</label>
                <input type="password" class="form-control" name="current_password" required>
              </div>
              <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" class="form-control" name="new_password" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" name="confirm_password" required>
              </div>
              <button type="button" id="changePasswordBtn" class="btn btn-outline-primary">
                <i class="fas fa-key me-1"></i> Update Password
              </button>
            </form>
          </div>

          <div class="profile-card">
            <h6 class="mb-2">Account Details</h6>
            <p class="mb-1"><strong>Role:</strong> <?= htmlspecialchars($user['role_name'] ?: 'User') ?></p>
            <p class="mb-1"><strong>Department:</strong> <?= htmlspecialchars($user['Department_name'] ?: '—') ?></p>
            <p class="mb-0"><strong>Status:</strong> <?= htmlspecialchars(ucfirst($user['status'])) ?></p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="../../../bootstrap/jquery/jquery-3.6.0.min.js"></script>
  <script src="../../../bootstrap/js/bootstrap.bundle.min.js"></script>
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

    // Preview profile image
    $('#profileImageInput').on('change', function(){
      const file = this.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = e => $('#profilePreview').attr('src', e.target.result);
      reader.readAsDataURL(file);
    });

    // Save profile
    $('#saveProfileBtn').on('click', function(){
      const form = document.getElementById('profileForm');
      const fd   = new FormData(form);

      $.ajax({
        url: 'update_profile.php',
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        dataType: 'json'
      }).done(function(res){
        if (res.success) {
          Swal.fire('Saved', res.success, 'success');
          if (res.reloadName) {
            $('#user-name').text(res.reloadName);
          }
        } else {
          Swal.fire('Error', res.error || 'Failed to update profile.', 'error');
        }
      }).fail(function(){
        Swal.fire('Error', 'Server error occurred.', 'error');
      });
    });

    // Change password
    $('#changePasswordBtn').on('click', function(){
      const fd = $('#passwordForm').serialize();

      $.ajax({
        url: 'change_password.php',
        method: 'POST',
        data: fd,
        dataType: 'json'
      }).done(function(res){
        if (res.success) {
          Swal.fire('Password Updated', res.success, 'success');
          $('#passwordForm')[0].reset();
        } else {
          Swal.fire('Error', res.error || 'Failed to update password.', 'error');
        }
      }).fail(function(){
        Swal.fire('Error', 'Server error occurred.', 'error');
      });
    });
  </script>
</body>
</html>
