<?php
class Mailer {
    /**
     * Send a ticket update via Office365 SMTP.
     *
     * @param array  $ticket   Ticket data from Ticket::getById()
     * @param string $to       Recipient email
     * @param string $subject
     * @param string $body
     * @return bool
     */
    public static function sendTicketUpdate(array $ticket, string $to, string $subject, string $body): bool {
        // Load PHPMailer
        require_once __DIR__ . '/../vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // SMTP config for Office365
            $mail->isSMTP();
            $mail->Host       = 'smtp.office365.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'youremail@yourdomain.com'; // change
            $mail->Password   = 'yourpassword';             // change or use env var
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('youremail@yourdomain.com', 'Ticket System'); // change
            $mail->addAddress($to);

            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);

            $mail->send();
            return true;
        } catch (\Throwable $e) {
            error_log("Mailer error: " . $e->getMessage());
            return false;
        }
    }
}
