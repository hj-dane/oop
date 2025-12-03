<?php
// registrationpage.php
session_start();
require_once '../database/connection.php'; // PDO $conn

// fetch active departments
$deptStmt = $conn->prepare("
    SELECT department_id, department_name 
    FROM departments 
    WHERE status = 'active'
    ORDER BY department_name
");
$deptStmt->execute();
$departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap + Font Awesome + SweetAlert2 -->
  <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css?v=<?= filemtime('../bootstrap/css/bootstrap.min.css'); ?>">
  <link rel="stylesheet" href="../bootstrap/fontawesome/css/all.min.css?v=<?= filemtime('../bootstrap/fontawesome/css/all.min.css'); ?>">
  <link rel="stylesheet" href="../bootstrap/libs/sweetalert2/sweetalert2.min.css?v=<?= filemtime('../bootstrap/libs/sweetalert2/sweetalert2.min.css'); ?>">
  <script src="../bootstrap/libs/sweetalert2/sweetalert2.min.js?v=<?= filemtime('../bootstrap/libs/sweetalert2/sweetalert2.min.js'); ?>"></script>
  <link rel="stylesheet" href="style.css?<?= filemtime('style.css'); ?>">
</head>
<body>
  <div class="register-card">
    <h3>Register</h3>
    <form onsubmit="return false;">

      <div class="form-row d-flex">
        <div class="col-md-4 pe-3">
          <label for="lastname">Last Name</label>
          <input type="text" class="form-control my-2" id="lastname" required>
        </div>
        <div class="col-md-4 pe-3">
          <label for="firstname">First Name</label>
          <input type="text" class="form-control my-2" id="firstname" required>
        </div>
        <div class="col-md-4">
          <label for="middlename">Middle Initial</label>
          <input type="text" class="form-control my-2" id="middlename">
        </div>
      </div>

      <!-- Department -->
      <div class="form-group mt-3">
        <label for="department">Department</label>
        <select class="form-control my-2" id="department" required>
          <option value="">-- Select Department --</option>
          <?php foreach ($departments as $dept): ?>
            <option value="<?= htmlspecialchars($dept['department_id']) ?>">
              <?= htmlspecialchars($dept['department_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group mt-3">
        <label for="address">Address</label>
        <input type="text" class="form-control my-2" id="address">
      </div>

      <div class="form-group">
        <label for="mobile">Mobile Number</label>
        <input type="tel" class="form-control my-2" id="mobile" required pattern="[0-9]{11}" maxlength="11">
      </div>

      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" class="form-control my-2" id="email" required>
      </div>

      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" class="form-control my-2" id="username" required>
      </div>

      <!-- Password Field -->
      <div class="form-group position-relative">
        <label for="password">Password</label>
        <input type="password" class="form-control my-2" id="password" required>
        <span toggle="#password" class="fa fa-fw fa-eye field-icon toggle-password pt-1"
              style="position:absolute; top:38px; right:15px; cursor:pointer;"></span>
      </div>

      <!-- Confirm Password Field -->
      <div class="form-group position-relative">
        <label for="confirmpassword">Confirm Password</label>
        <input type="password" class="form-control my-2" id="confirmpassword" required>
        <span toggle="#confirmpassword" class="fa fa-fw fa-eye field-icon toggle-password pt-1"
              style="position:absolute; top:38px; right:15px; cursor:pointer;"></span>
      </div>

      <button type="button" class="btn btn-danger btn-block w-100 mt-3" id="submitBtn">
        Register
        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
      </button>

      <div class="login-link">
        Already have an account? <a href="../index.php">Login here</a>
      </div>
    </form>
  </div>

  <script src="../bootstrap/jquery/jquery-3.5.1.min.js"></script>
  <script>
    // Toggle password visibility
    $(document).on('click', '.toggle-password', function () {
      let input = $($(this).attr("toggle"));
      let icon = $(this);
      if (input.attr("type") === "password") {
        input.attr("type", "text");
        icon.removeClass("fa-eye").addClass("fa-eye-slash");
      } else {
        input.attr("type", "password");
        icon.removeClass("fa-eye-slash").addClass("fa-eye");
      }
    });

    // Submit with SweetAlert2 and AJAX
    document.getElementById("submitBtn").addEventListener("click", function () {
      const password = $("#password").val();
      const confirmpassword = $("#confirmpassword").val();
      const submitBtn = $("#submitBtn");
      const spinner = submitBtn.find(".spinner-border");

      if (password !== confirmpassword) {
        Swal.fire({
          icon: 'warning',
          title: 'Password Mismatch',
          text: 'Passwords do not match.',
          confirmButtonColor: '#CE1126'
        });
        return;
      }

      if (!$("#department").val()) {
        Swal.fire({
          icon: 'warning',
          title: 'Missing Department',
          text: 'Please select a department.',
          confirmButtonColor: '#CE1126'
        });
        return;
      }

      const data = {
        lastname: $("#lastname").val(),
        firstname: $("#firstname").val(),
        middlename: $("#middlename").val(),
        department_id: $("#department").val(),
        mobile: $("#mobile").val(),
        address: $("#address").val(),
        username: $("#username").val(),
        email: $("#email").val(),
        password: password
      };

      // show spinner and disable button
      spinner.removeClass("d-none");
      submitBtn.prop("disabled", true);

      $.ajax({
        url: "register.php",
        type: "POST",
        data: JSON.stringify(data),
        contentType: "application/json",
        success: function (response) {
          if (response.status === "success" || response.status === "warn") {
            Swal.fire({
              icon: 'success',
              title: 'Almost there!',
              text: response.message,
              confirmButtonColor: '#182242'
            }).then(() => {
              window.location.href = response.redirect;
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Registration Failed',
              text: response.message,
              confirmButtonColor: '#CE1126'
            });
          }
        },
        error: function (xhr) {
          let message = "Could not connect to server. Please try again later.";
          if (xhr.responseJSON && xhr.responseJSON.message) {
            message = xhr.responseJSON.message;
          }

          Swal.fire({
            icon: 'error',
            title: 'Registration Failed',
            text: message,
            confirmButtonColor: '#CE1126'
          });
        },
        complete: function () {
          spinner.addClass("d-none");
          submitBtn.prop("disabled", false);
        }
      });
    });
  </script>
</body>
</html>
