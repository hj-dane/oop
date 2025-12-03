<?php
header('Content-Type: application/json');
require_once '../database/connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../phpmailer/src/Exception.php';
require '../phpmailer/src/PHPMailer.php';
require '../phpmailer/src/SMTP.php';

function mail_code(string $to, string $name, string $code, int $expiryMinutes = 30): bool {
    require __DIR__ . '/mailer_config.php';
    $mail = new PHPMailer(true);

    $BRAND_NAME   = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Account Verification';
    $BRAND_HEADER = $BRAND_NAME;
    $BRAND_FOOTER = $BRAND_NAME;

    $COLOR_HEADER_BG = '#1e3a8a';
    $COLOR_BODY_BG   = '#f5f7fb';
    $COLOR_CARD_BG   = '#ffffff';
    $COLOR_TEXT      = '#0f172a';
    $COLOR_MUTED     = '#64748b';
    $COLOR_CODE_BG   = '#0b1220';
    $COLOR_CODE_TXT  = '#e2e8f0';

    $safeName = htmlspecialchars($name !== '' ? $name : $to, ENT_QUOTES, 'UTF-8');
    $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
    $verifyUrl = 'verificationpage.php?email=' . urlencode($to);
    $preview = "Your new verification code is {$code}. Expires in {$expiryMinutes} minutes.";

    $html = "<!doctype html>
<html>
<head>
  <meta charset='utf-8'><meta name='viewport' content='width=device-width'>
  <title>Your new verification code</title>
</head>
<body style='margin:0;background:{$COLOR_BODY_BG};font-family:Arial,Helvetica,sans-serif;color:{$COLOR_TEXT}'>
  <div style='display:none;max-height:0;overflow:hidden;opacity:0;color:transparent'>{$preview}</div>
  <table role='presentation' width='100%' cellspacing='0' cellpadding='0' style='background:{$COLOR_BODY_BG};padding:24px 0'>
    <tr><td align='center'>
      <table role='presentation' width='600' cellspacing='0' cellpadding='0' style='background:{$COLOR_CARD_BG};border-radius:12px;overflow:hidden;box-shadow:0 8px 24px rgba(15,23,42,.08)'>
        <tr>
          <td style='background:{$COLOR_HEADER_BG};padding:18px 24px;color:#fff'>
            <table width='100%' cellspacing='0' cellpadding='0'>
              <tr>
                <td style='font-size:18px;font-weight:700'>".htmlspecialchars($BRAND_HEADER, ENT_QUOTES, 'UTF-8')."</td>
                <td align='right' style='font-size:12px;opacity:.9'>Code Resend</td>
              </tr>
            </table>
          </td>
        </tr>
        <tr><td style='padding:24px'>
          <h2 style='margin:0 0 8px;font-size:20px;color:{$COLOR_TEXT}'>Hi {$safeName},</h2>
          <p style='margin:0 0 16px;line-height:1.6;color:#334155'>
            Here is your new verification code. Use it to complete your account verification:
          </p>
          <div style='margin:16px 0 8px;background:{$COLOR_CODE_BG};color:{$COLOR_CODE_TXT};border-radius:10px;padding:16px;text-align:center'>
            <div style='font-size:28px;letter-spacing:6px;font-weight:800;font-family:Courier, monospace'>{$safeCode}</div>
          </div>
          <p style='margin:8px 0 0;font-size:12px;color:{$COLOR_MUTED}'>
            This code may expire in {$expiryMinutes} minutes. If multiple codes were requested, only the most recent one will work.
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

    $alt = "Your new verification code\n"
         . "Name: " . ($name !== '' ? $name : $to) . "\n"
         . "Email: {$to}\n"
         . "Code: {$code}\n"
         . "This code may expire in {$expiryMinutes} minutes.\n"
         . "Only the latest code will work.\n\n"
         . "Open: {$verifyUrl}\n";

    try {
        $mail->CharSet    = 'UTF-8';
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to, $name !== '' ? $name : $to);
        $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);

        $mail->isHTML(true);
        $mail->Subject = 'Your new verification code';
        $mail->Body    = $html;
        $mail->AltBody = $alt;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

$email = strtolower(trim($_POST['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status"=>"error","message"=>"Invalid email."]); exit;
}

try {
    $sql = "
        SELECT user_id, First_name, Last_name, verification_status
        FROM users
        WHERE Email = :email
        LIMIT 1
    ";
    $st = $conn->prepare($sql);
    $st->execute(['email'=>$email]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(["status"=>"error","message"=>"Account not found."]); exit;
    }

    if ($row['verification_status'] === 'Verified') {
        echo json_encode(["status"=>"success","message"=>"Already verified."]); exit;
    }

    $code = str_pad((string)random_int(0,999999), 6, '0', STR_PAD_LEFT);

    $upd = $conn->prepare("
        UPDATE users
           SET verification_code = :c,
               verification_status = 'Pending'
         WHERE user_id = :id
    ");
    $upd->execute(['c'=>$code, 'id'=>$row['user_id']]);

    $name = trim($row['First_name'].' '.$row['Last_name']);
    $ok   = mail_code($email, $name, $code);

    echo json_encode($ok ? ["status"=>"success"] : ["status"=>"error","message"=>"Mail send failed."]);
} catch (PDOException $e) {
    echo json_encode(["status"=>"error","message"=>"Database error."]);
}
