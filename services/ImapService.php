<?php
// services/ImapService.php
require_once __DIR__ . '/../models/DB.php';
require_once __DIR__ . '/../services/FileService.php';

class ImapService {
    private $mailbox;
    private $username;
    private $password;
    private $mailboxPath;

    public function __construct() {
        $this->username = SMTP_USER; // use same account for inbox
        $this->password = SMTP_PASS;
        $this->mailboxPath = "{" . IMAP_HOST . ":" . IMAP_PORT . "/imap/ssl}INBOX";
    }

    public function fetchAndCreateTickets() {
        $mbox = @imap_open($this->mailboxPath, $this->username, $this->password);
        if (!$mbox) {
            error_log("IMAP open failed: " . imap_last_error());
            return;
        }
        // search unseen messages
        $emails = imap_search($mbox, 'UNSEEN');
        if ($emails) {
            rsort($emails);
            foreach ($emails as $num) {
                $overview = imap_fetch_overview($mbox, $num, 0)[0];
                $body = $this->getBody($mbox, $num);
                $from = $overview->from;
                $subject = $overview->subject ?? 'No subject';
                // create ticket
                $pdo = DB::getInstance();
                $stmt = $pdo->prepare("INSERT INTO tickets (subject, description, created_by) VALUES (:s, :d, NULL)");
                $stmt->execute([':s'=>$subject, ':d'=>substr($body,0,65535)]);
                $ticketId = $pdo->lastInsertId();

                // attachments
                $structure = imap_fetchstructure($mbox, $num);
                if (!empty($structure->parts)) {
                    $fileSvc = new FileService();
                    for ($i=0;$i<count($structure->parts);$i++) {
                        $part = $structure->parts[$i];
                        if ($part->ifdparameters) {
                            foreach ($part->dparameters as $object) {
                                if (strtolower($object->attribute) == 'filename') {
                                    $filename = $object->value;
                                    $attachment = imap_fetchbody($mbox, $num, $i+1);
                                    if ($part->encoding == 3) $attachment = base64_decode($attachment);
                                    elseif ($part->encoding == 4) $attachment = quoted_printable_decode($attachment);
                                    $tmp = tempnam(sys_get_temp_dir(), 'att');
                                    file_put_contents($tmp, $attachment);
                                    $fakeFile = ['name'=>$filename, 'tmp_name'=>$tmp, 'error'=>UPLOAD_ERR_OK, 'size'=>filesize($tmp)];
                                    try {
                                        $info = $fileSvc->saveUploadedFile($fakeFile);
                                        // insert into db
                                        $stmt2 = $pdo->prepare("INSERT INTO ticket_attachments (ticket_id, original_filename, stored_filename, file_size, mime_type) VALUES (:tid, :orig, :stored, :size, :mime)");
                                        $stmt2->execute([':tid'=>$ticketId, ':orig'=>$info['original_filename'], ':stored'=>$info['stored_filename'], ':size'=>$info['file_size'], ':mime'=>$info['mime_type']]);
                                    } catch (Exception $e) {
                                        error_log("IMAP attachment save error: " . $e->getMessage());
                                    }
                                    @unlink($tmp);
                                }
                            }
                        }
                    }
                }
                // mark seen
                imap_setflag_full($mbox, $num, "\\Seen");
            }
        }
        imap_close($mbox);
    }

    private function getBody($mbox, $msgnum) {
        $struct = imap_fetchstructure($mbox, $msgnum);
        if (!isset($struct->parts)) return imap_body($mbox, $msgnum);
        // find text/plain part
        foreach ($struct->parts as $partno => $part) {
            if ($part->type == 0) {
                $text = imap_fetchbody($mbox, $msgnum, $partno+1);
                if ($part->encoding == 3) $text = base64_decode($text);
                if ($part->encoding == 4) $text = quoted_printable_decode($text);
                return $text;
            }
        }
        return imap_body($mbox, $msgnum);
    }
}
