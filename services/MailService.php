<?php
// services/MailService.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class MailService {
    private $mailer;
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        // SMTP config from config.php
        $this->mailer->isSMTP();
        $this->mailer->Host = SMTP_HOST;
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = SMTP_USER;
        $this->mailer->Password = SMTP_PASS;
        $this->mailer->SMTPSecure = 'tls';
        $this->mailer->Port = SMTP_PORT;
        $this->mailer->setFrom(SMTP_USER, APP_NAME);
    }

    public function send($to, $subject, $body, $isHtml=false) {
        try {
            $this->mailer->clearAllRecipients();
            $this->mailer->addAddress($to);
            $this->mailer->Subject = $subject;
            if ($isHtml) $this->mailer->isHTML(true);
            $this->mailer->Body = $body;
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("MailService error: " . $e->getMessage());
            return false;
        }
    }
}
