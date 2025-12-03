<?php
ob_start();
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../database/connection.php'; // PDO: $conn

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../phpmailer/src/Exception.php';
require '../phpmailer/src/PHPMailer.php';
require '../phpmailer/src/SMTP.php';

function send_verification_mail(string $toEmail, string $toName, string $code, int $expiryMinutes = 30): bool {
    require __DIR__ . '/mailer_config.php'; // uses your SMTP_* constants
    $mail = new PHPMailer(true);

    // Brand pulls from your config
    $BRAND_NAME   = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Verification';
    $BRAND_HEADER = $BRAND_NAME;
    $BRAND_FOOTER = $BRAND_NAME;

    // Colors
    $COLOR_HEADER_BG = '#1e3a8a';
    $COLOR_BODY_BG   = '#f5f7fb';
    $COLOR_CARD_BG   = '#ffffff';
    $COLOR_TEXT      = '#0f172a';
    $COLOR_MUTED     = '#64748b';
    $COLOR_CODE_BG   = '#0b1220';
    $COLOR_CODE_TXT  = '#e2e8f0';

    $safeName = htmlspecialchars($toName !== '' ? $toName : $toEmail, ENT_QUOTES, 'UTF-8');
    $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
    $verifyUrl = 'verificationpage.php?email=' . urlencode($toEmail);

    // inbox preview
    $preview = "Your verification code is {$code}. Expires in {$expiryMinutes} minutes.";

    $html = "<!doctype html>
<html>
<head>
<meta charset='utf-8'><meta name='viewport' content='width=device-width'>
<title>Verify your account</title>
</head>
<body style='margin:0;background:{$COLOR_BODY_BG};font-family:Arial,Helvetica,sans-serif;color:{$COLOR_TEXT}'>
  <div style='display:none;max-height:0;overflow:hidden;opacity:0;color:transparent'>
    {$preview}
  </div>
  <table role='presentation' width='100%' cellspacing='0' cellpadding='0' style='background:{$COLOR_BODY_BG};padding:24px 0'>
    <tr><td align='center'>
      <table role='presentation' width='600' cellspacing='0' cellpadding='0' style='background:{$COLOR_CARD_BG};border-radius:12px;overflow:hidden;box-shadow:0 8px 24px rgba(15,23,42,.08)'>
        <tr>
          <td style='background:{$COLOR_HEADER_BG};padding:18px 24px;color:#fff'>
            <table width='100%' cellspacing='0' cellpadding='0'>
              <tr>
                <td style='font-size:18px;font-weight:700'>".htmlspecialchars($BRAND_HEADER, ENT_QUOTES, 'UTF-8')."</td>
                <td align='right' style='font-size:12px;opacity:.9'>Account Verification</td>
              </tr>
            </table>
          </td>
        </tr>

        <tr><td style='padding:24px'>
          <h2 style='margin:0 0 8px;font-size:20px;color:{$COLOR_TEXT}'>Hi {$safeName},</h2>
          <p style='margin:0 0 16px;line-height:1.6;color:#334155'>
            Use this verification code to activate your account:
          </p>

          <div style='margin:16px 0 8px;background:{$COLOR_CODE_BG};color:{$COLOR_CODE_TXT};border-radius:10px;padding:16px;text-align:center'>
            <div style='font-size:28px;letter-spacing:6px;font-weight:800;font-family:Courier, monospace'>{$safeCode}</div>
          </div>

          <p style='margin:8px 0 0;font-size:12px;color:{$COLOR_MUTED}'>
            This code may expire in {$expiryMinutes} minutes. If you didn’t request this, you can ignore this email.
          </p>

          <table role='presentation' width='100%' cellspacing='0' cellpadding='0' style='margin:22px 0 6px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px'>
            <tr><td style='padding:14px 16px'>
              <table width='100%' cellspacing='0' cellpadding='0' style='font-size:14px;color:{$COLOR_TEXT}'>
                <tr>
                  <td style='padding:6px 0;width:140px;color:{$COLOR_MUTED}'>Email</td>
                  <td style='padding:6px 0'>".htmlspecialchars($toEmail, ENT_QUOTES, 'UTF-8')."</td>
                </tr>
                <tr>
                  <td style='padding:6px 0;width:140px;color:{$COLOR_MUTED}'>Status</td>
                  <td style='padding:6px 0'>
                    <span style='display:inline-block;background:#ecfeff;color:#0e7490;border:1px solid #67e8f9;border-radius:999px;padding:3px 8px;font-weight:700'>
                      Code Required
                    </span>
                  </td>
                </tr>
              </table>
            </td></tr>
          </table>

          <p style='margin:14px 0 0;font-size:12px;color:{$COLOR_MUTED}'>
            Need help? Just reply to this email.
          </p>
        </td></tr>

        <tr>
          <td style='background:#0b1220;color:#a7b0c3;padding:14px 24px;font-size:12px;text-align:center'>
            ".htmlspecialchars($BRAND_FOOTER, ENT_QUOTES, 'UTF-8')."
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>";

    $alt = "Verify your account\n"
         . "Name: " . ($toName !== '' ? $toName : $toEmail) . "\n"
         . "Email: {$toEmail}\n"
         . "Verification Code: {$code}\n"
         . "This code may expire in {$expiryMinutes} minutes.\n\n"
         . "Open: {$verifyUrl}\n";

    try {
        $mail->CharSet    = 'UTF-8';
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE; // 'tls' or 'ssl'
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName ?: $toEmail);
        $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);

        $mail->isHTML(true);
        $mail->Subject = 'Verify your account';
        $mail->Body    = $html;
        $mail->AltBody = $alt;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}


try {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid input."]);
        ob_end_flush(); exit;
    }

    $lastname     = trim($input['lastname'] ?? '');
    $firstname    = trim($input['firstname'] ?? '');
    $middlename   = trim($input['middlename'] ?? '');
    $address      = trim($input['address'] ?? '');
    $mobile       = trim($input['mobile'] ?? '');
    $username     = trim($input['username'] ?? '');
    $email        = strtolower(trim($input['email'] ?? ''));
    $password     = $input['password'] ?? '';
    $departmentId = isset($input['department_id']) ? (int)$input['department_id'] : 0;

    // username max 16 chars
    if (strlen($username) > 16) {
        http_response_code(400);
        echo json_encode([
            "status"  => "error",
            "message" => "Username must not exceed 16 characters."
        ]);
        ob_end_flush(); exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid email format."]);
        ob_end_flush(); exit;
    }

    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Password must be at least 8 characters and include uppercase, lowercase, number, and special character."
        ]);
        ob_end_flush(); exit;
    }

    if (empty($lastname) || empty($firstname) || empty($address) ||
        empty($username) || empty($mobile) || empty($email) || empty($password) ||
        $departmentId <= 0) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Please fill in all required fields, including department."]);
        ob_end_flush(); exit;
    }

    if (!preg_match('/^09\d{9}$/', $mobile)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid mobile number format."]);
        ob_end_flush(); exit;
    }

    // ensure department exists and is active
    $d = $conn->prepare("SELECT 1 FROM departments WHERE department_id = :id AND status = 'active'");
    $d->execute(['id' => $departmentId]);
    if (!$d->fetch(PDO::FETCH_NUM)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid department selected."]);
        ob_end_flush(); exit;
    }

    // uniqueness checks
    $stmt = $conn->prepare("SELECT 1 FROM users WHERE Email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch(PDO::FETCH_NUM)) {
        http_response_code(409);
        echo json_encode(["status" => "error", "message" => "Email already registered."]);
        ob_end_flush(); exit;
    }

    $stmt = $conn->prepare("SELECT 1 FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    if ($stmt->fetch(PDO::FETCH_NUM)) {
        http_response_code(409);
        echo json_encode(["status" => "error", "message" => "Username already taken."]);
        ob_end_flush(); exit;
    }

    // default role = Employee
    $r = $conn->prepare("SELECT role_id FROM roles WHERE role_name = 'Employee' LIMIT 1");
    $r->execute();
    $roleRow = $r->fetch(PDO::FETCH_ASSOC);
    if (!$roleRow) {
        throw new RuntimeException("Role 'Employee' not found in roles table.");
    }
    $employeeRoleId = (int)$roleRow['role_id'];

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $vcode = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    $conn->beginTransaction();

    $insertUser = $conn->prepare("
        INSERT INTO users (
            role_id,
            department_id,
            username,
            First_name,
            Last_name,
            middle_name,
            Email,
            mobile,
            address,
            password_hash,
            verification_code,
            verification_status,
            status,
            created_at,
            updated_at
        ) VALUES (
            :role_id,
            :department_id,
            :username,
            :firstname,
            :lastname,
            :middlename,
            :email,
            :mobile,
            :address,
            :password_hash,
            :vcode,
            'Pending',
            'inactive',
            NOW(),
            NOW()
        )
    ");

    $insertUser->execute([
        'role_id'       => $employeeRoleId,
        'department_id' => $departmentId,
        'username'      => $username,
        'firstname'     => $firstname,
        'lastname'      => $lastname,
        'middlename'    => $middlename,
        'email'         => $email,
        'mobile'        => $mobile,
        'address'       => $address,
        'password_hash' => $hashed_password,
        'vcode'         => $vcode
    ]);

    $conn->commit();

    $fullName = trim($firstname . ' ' . $lastname);
    $mailed   = send_verification_mail($email, $fullName, $vcode);

    echo json_encode([
        "status"   => $mailed ? "success" : "warn",
        "message"  => $mailed ? "Verification code sent to your email." : "Registered, but failed to send email. Use Resend Code.",
        "redirect" => "verificationpage.php?email=" . urlencode($email)
    ]);

} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
ob_end_flush();
exit;
