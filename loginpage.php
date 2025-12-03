<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css?v=<?= filemtime('bootstrap/css/bootstrap.min.css'); ?>">
  <link rel="stylesheet" href="bootstrap/fontawesome/css/all.min.css?v=<?= filemtime('bootstrap/fontawesome/css/all.min.css'); ?>">
  <link rel="stylesheet" href="registration/style.css?v=<?= filemtime('registration/style.css'); ?>">

  <!-- ✅ SweetAlert2 -->
  <link rel="stylesheet" href="bootstrap/libs/sweetalert2/sweetalert2.min.css?v=<?= filemtime('bootstrap/libs/sweetalert2/sweetalert2.min.css'); ?>">
</head>
<body>
  <div class="login-card">
    <h3>Login</h3>
    <form>
      <div class="form-group">
        <label for="email">Username</label>
        <input type="text" class="form-control mt-2" id="email" placeholder="Enter username" required>
      </div>

      <div class="form-group position-relative mt-3">
        <label for="password">Password</label>
        <input type="password" class="form-control mt-2 mb-2" id="password" placeholder="Enter password" required>
        <span toggle="#password" class="fa fa-fw fa-eye field-icon toggle-password pt-1"
              style="position:absolute; top:38px; right:15px; cursor:pointer;"></span>
      </div>

      <div class="js-error-container mt-3" style="display:none;">
        <p id="error-message"></p>
      </div>

      <button type="submit" id="submitBtn" class="btn btn-primary btn-block mt-2 w-100">Sign In</button>
    </form>
    
    <div class="register-link mt-4">
      Don't have an account? <a href="registration/registrationpage.php">Register here</a>
    </div>
  </div>

  <script src="bootstrap/jquery/jquery-3.5.1.min.js"></script>
  <script src="bootstrap/libs/sweetalert2/sweetalert2.min.js?v=<?= filemtime('bootstrap/libs/sweetalert2/sweetalert2.min.js'); ?>"></script>
  <script>
    // Show/Hide password
    $(document).on('click', '.toggle-password', function () {
      const input = $($(this).attr("toggle"));
      const icon  = $(this);
      if (input.attr("type") === "password") {
        input.attr("type", "text");
        icon.removeClass("fa-eye").addClass("fa-eye-slash");
      } else {
        input.attr("type", "password");
        icon.removeClass("fa-eye-slash").addClass("fa-eye");
      }
    });

    // Lockout helpers
    function lockLoginForm() {
      const lockTime = Date.now() + 30000; // 30s
      localStorage.setItem("login_lock_until", lockTime);
      Swal.fire({
        icon: 'info',
        title: 'Too many attempts',
        text: 'Login has been locked for 30 seconds.',
        timer: 1800,
        showConfirmButton: false
      });
      updateLockoutUI();
    }

    function updateLockoutUI() {
      const lockUntil = localStorage.getItem("login_lock_until");
      if (!lockUntil) return;

      const now = Date.now();
      const remaining = Math.max(0, Math.floor((lockUntil - now) / 1000));

      if (remaining > 0) {
        $("#email, #password, #submitBtn").prop("disabled", true);
        $(".js-error-container").show();
        $("#submitBtn").text("Locked");
        $("#error-message").html(`Too many failed attempts. Please wait <strong>${remaining}s</strong> before trying again.`);
        setTimeout(updateLockoutUI, 1000);
      } else {
        localStorage.removeItem("login_lock_until");
        localStorage.removeItem("login_attempts");
        $("#email, #password, #submitBtn").prop("disabled", false);
        $("#submitBtn").text("Sign In");
        $(".js-error-container").hide();
        $("#error-message").text("");
      }
    }

    document.addEventListener('DOMContentLoaded', function () {
      updateLockoutUI();

      $("#submitBtn").on("click", function (e) {
        e.preventDefault();

        const username = $("#email").val().trim();   // your field is "Username"
        const password = $("#password").val().trim();

        if (!username || !password) {
          Swal.fire({
            icon: 'warning',
            title: 'Missing information',
            text: 'Please fill in all fields.'
          });
          return;
        }

        $.ajax({
          url: "registration/signin.php",
          type: "POST",
          contentType: "application/json",
          dataType: "json",
          data: JSON.stringify({ email: username, password: password }), // backend expects "email" as username
          success: function (resp) {
            if (!resp || typeof resp !== "object") {
              Swal.fire({ icon: 'error', title: 'Unexpected response', text: 'Please try again.' });
              return;
            }

            if (resp.status === "success") {
              localStorage.removeItem("login_attempts");
              localStorage.removeItem("login_lock_until");
              Swal.fire({
                icon: 'success',
                title: 'Welcome!',
                timer: 900,
                showConfirmButton: false
              }).then(() => window.location.href = resp.redirect);
              return;
            }

            if (resp.status === "unverified") {
              const nextUrl = resp.redirect
                ? resp.redirect
                : ("registration/verificationpage.php?email=" + encodeURIComponent(username));
              Swal.fire({
                icon: 'info',
                title: 'Verify your email',
                text: resp.message || 'Please verify your email to continue.',
                confirmButtonText: 'Verify now'
              }).then(() => window.location.href = nextUrl);
              return;
            }

            // Any other error from server
            let attempts = parseInt(localStorage.getItem("login_attempts") || "0", 10) + 1;
            localStorage.setItem("login_attempts", attempts);

            Swal.fire({
              icon: 'error',
              title: 'Login failed',
              text: resp.message || 'Invalid credentials.'
            });

            if (attempts >= 3) lockLoginForm();
          },
          error: function (xhr) {
            console.error("XHR Error:", xhr.responseText);
            Swal.fire({
              icon: 'error',
              title: 'Server error',
              text: 'Please try again.'
            });
          }
        });
      });
    });
  </script>
</body>
</html>
