<?php
// navigation/mail.php — Handles contact form (guest-friendly)

require_once '../database/connection.php'; // adjust path if needed ($conn is PDO)
require_once('../registration/mailer_config.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once('../phpmailer/src/Exception.php');
require_once('../phpmailer/src/PHPMailer.php');
require_once('../phpmailer/src/SMTP.php');

function clean($s){ return trim((string)$s); }
function wantsJson(): bool {
  $xrw = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
  $acc = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
  return $xrw === 'xmlhttprequest' || str_contains($acc, 'application/json') || str_contains($acc, 'text/json');
}
function jsonOut($ok, $msg=''){
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>$ok, 'message'=>$msg], JSON_UNESCAPED_UNICODE);
  exit;
}

/* ---- gather ---- */
$name    = clean($_POST['name']    ?? '');
$email   = clean($_POST['email']   ?? '');
$subject = clean($_POST['subject'] ?? '');
$message = clean($_POST['message'] ?? '');

if ($name === '' || $email === '' || $subject === '' || $message === '') {
  if (wantsJson()) jsonOut(false, 'Missing fields');
  header('Location: contact.php?sent=0&msg=Missing+fields'); exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  if (wantsJson()) jsonOut(false, 'Invalid email');
  header('Location: contact.php?sent=0&msg=Invalid+email'); exit;
}

/* ---- save (no session: user_id is NULL for guests) ---- */
try {
  $sql = "INSERT INTO contact_messages (user_id, name, email, subject, message, status)
          VALUES (NULL, :name, :email, :subject, :message, 'new')";
  $st = $conn->prepare($sql);
  $st->bindValue(':name',    $name);
  $st->bindValue(':email',   $email);
  $st->bindValue(':subject', $subject);
  $st->bindValue(':message', $message);
  $st->execute();
} catch (Throwable $e) {
  error_log('Contact insert failed: '.$e->getMessage());
  if (wantsJson()) jsonOut(false, 'Database error');
  header('Location: contact.php?sent=0&msg=DB+error'); exit;
}

/* ---- optional ack email to sender ---- */
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
  $mail->addAddress($email, $name);
  $mail->Subject = "We received your message: {$subject}";
  $mail->isHTML(true);
  $mail->Body = "
    <div style='font-family:system-ui,Segoe UI,Arial,sans-serif'>
      <p>Hi ".htmlspecialchars($name).",</p>
      <p>Thanks for contacting <strong>Insync</strong>. We’ve received your message and will get back to you soon.</p>
      <p style='margin:1em 0;padding:10px;border-left:4px solid #ddd;background:#fafafa'>
        <strong>Your message:</strong><br>".nl2br(htmlspecialchars($message))."
      </p>
      <p>– Insync Support</p>
    </div>";
  $mail->AltBody = "We received your message.\nSubject: {$subject}\n\n{$message}";
  $mail->send();
} catch (Exception $e) {
  error_log('Ack email failed: '.$e->getMessage());
}

/* ---- done ---- */
if (wantsJson()) jsonOut(true, 'Message sent successfully!');
header('Location: contact.php?sent=1');
exit;
