<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class MailService {
  public static function send(string $to, string $subject, string $body, ?string $fromName = 'Ticketing System'): bool {
    $m = new PHPMailer(true);
    try {
      $m->isSMTP();
      $m->Host = SMTP_HOST;
      $m->Port = SMTP_PORT;
      $m->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $m->SMTPAuth = true;
      $m->Username = SMTP_USER;
      $m->Password = SMTP_PASS;
      $m->CharSet = 'UTF-8';

      $m->setFrom(SMTP_USER, $fromName);
      $m->addAddress($to);
      $m->Subject = $subject;
      $m->isHTML(false);
      $m->Body = $body;

      return $m->send();
    } catch (Exception $e) {
      error_log('Mail send failed: '.$e->getMessage());
      return false;
    }
  }
}
