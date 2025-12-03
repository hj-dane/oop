<?php
session_start();
$email = isset($_GET['email']) ? strtolower(trim($_GET['email'])) : '';
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
  <style>
    body{background:#f7f8fb;}
    .card{max-width:480px;margin:10vh auto;padding:24px;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.06);}
    .code-input{letter-spacing:6px;text-align:center;font-size:24px;}
  </style>
</head>
<body>
  <div class="card">
    <h4 class="mb-2">Email Verification</h4>
    <p class="text-muted">We sent a 6-digit code to <b id="userEmail"><?= htmlspecialchars($email) ?></b>. Enter it below to activate your account.</p>

    <div class="mb-3">
      <label class="form-label">Verification Code</label>
      <input type="text" id="code" class="form-control code-input" maxlength="6" pattern="\d{6}" placeholder="______">
    </div>

    <button id="verifyBtn" class="btn btn-primary w-100 mb-2">Verify</button>
    <button id="resendBtn" class="btn btn-outline-secondary w-100">Resend Code</button>

    <div class="mt-3">
      <a href="../index.php">&larr; Back to login</a>
    </div>
  </div>


  <script src="../bootstrap/jquery/jquery-3.5.1.min.js"></script>
<script>
const email = "<?= htmlspecialchars($email) ?>";

$("#verifyBtn").on("click", function(){
  const code = $("#code").val().trim();
  if(!/^\d{6}$/.test(code)){
    Swal.fire({icon:'warning', title:'Invalid code', text:'Please enter the 6-digit code.'});
    return;
  }
  $.post("verify.php", { email, code }, function(resp){
    if(resp.status === 'success'){
      Swal.fire({icon:'success', title:'Verified!', text:'Your account is now active.'})
        .then(()=> location.href = "../index.php");
    } else {
      Swal.fire({icon:'error', title:'Verification failed', text: resp.message || 'Invalid code.'});
    }
  }, "json").fail(()=> Swal.fire({icon:'error', title:'Network error', text:'Try again later.'}));
});

$("#resendBtn").on("click", function(){
  $.post("resend_verification.php", { email }, function(resp){
    if(resp.status === 'success'){
      Swal.fire({icon:'success', title:'Code sent', text:'Please check your inbox/spam.'});
    } else {
      Swal.fire({icon:'error', title:'Could not resend', text: resp.message || 'Please try again.'});
    }
  }, "json").fail(()=> Swal.fire({icon:'error', title:'Network error', text:'Try again later.'}));
});
</script>
</body>
</html>
