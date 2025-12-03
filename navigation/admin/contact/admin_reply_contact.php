<?php
// navigation/admin/contact/admin_reply_contact.php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['accountID'])) {
  echo json_encode(['success'=>false, 'error'=>'Not authorized']);
  exit();
}

require_once '../../../database/connection.php'; // $conn is PDO

// Mailer (optional emailing)
require_once('../../../registration/mailer_config.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once('../../../phpmailer/src/Exception.php');
require_once('../../../phpmailer/src/PHPMailer.php');
require_once('../../../phpmailer/src/SMTP.php');

function clean($s){ return trim((string)$s); }

$id         = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$reply      = clean($_POST['reply'] ?? '');
$send_email = isset($_POST['send_email']); // checkbox

if ($id <= 0 || $reply === '') {
  echo json_encode(['success'=>false, 'error'=>'Reply cannot be empty.']);
  exit();
}

try {
  // Load original message details
  $st = $conn->prepare("SELECT email, name, subject FROM contact_messages WHERE id = :id LIMIT 1");
  $st->execute([':id'=>$id]);
  $orig = $st->fetch();
  if (!$orig) { echo json_encode(['success'=>false, 'error'=>'Message not found']); exit; }

  // Update DB
  $st = $conn->prepare("UPDATE contact_messages
                        SET admin_reply = :r, replied_by = :aid, replied_at = NOW(),
                            status = 'replied', updated_at = NOW()
                        WHERE id = :id");
  $st->execute([
    ':r'   => $reply,
    ':aid' => (int)$_SESSION['accountID'],
    ':id'  => $id
  ]);

  // Optional: email the reply to the user
  if ($send_email) {
    try {
      $mail = new PHPMailer(true);
      $mail->isSMTP();
      $mail->Host       = SMTP_HOST;
      $mail->SMTPAuth   = true;
      $mail->Username   = SMTP_USERNAME;
      $mail->Password   = SMTP_PASSWORD;
      $mail->SMTPSecure = SMTP_SECURE;
      $mail->Port       = SMTP_PORT;

      $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
      $mail->addAddress($orig['email'], $orig['name'] ?: $orig['email']);
      $sub = 'Re: '.$orig['subject'];
      $mail->Subject = $sub;
      $mail->isHTML(true);
      $safeReply = nl2br(htmlspecialchars($reply, ENT_QUOTES, 'UTF-8'));
      $mail->Body = "
        <div style='font-family:system-ui,Segoe UI,Arial,sans-serif'>
          <p>Hi ".htmlspecialchars($orig['name'] ?: 'there').",</p>
          <p><strong>Reply from MJ Shop:</strong></p>
          <div style='margin:1em 0;padding:12px;border-left:4px solid #e5e7eb;background:#f9fafb'>
            {$safeReply}
          </div>
          <p>— MJ Shop Support</p>
        </div>
      ";
      $mail->AltBody = "Reply from MJ Shop:\n\n".$reply;
      $mail->send();
    } catch (Exception $e) {
      // don't fail the whole request if email fails; just log it
      error_log('Admin reply email failed: '.$e->getMessage());
    }
  }

  echo json_encode(['success'=>'Reply saved' . ($send_email ? ' and emailed.' : '.')]);
} catch (Throwable $e) {
  error_log($e->getMessage());
  echo json_encode(['success'=>false, 'error'=>'Server error']);
}
