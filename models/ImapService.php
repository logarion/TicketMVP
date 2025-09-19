<?php
class ImapService {
  public static function pollAndCreateTickets(): int {
    $mbox = @imap_open(IMAP_MAILBOX, IMAP_USER, IMAP_PASS);
    if (!$mbox) { error_log("IMAP open failed: ".imap_last_error()); return 0; }

    $nums = imap_search($mbox, 'UNSEEN');
    if (!$nums) { imap_close($mbox); return 0; }

    rsort($nums); // newest first
    $count = 0;

    foreach ($nums as $num) {
      $overview = imap_fetch_overview($mbox, $num, 0)[0] ?? null;
      if (!$overview) continue;

      $subject = isset($overview->subject) ? imap_utf8($overview->subject) : '(no subject)';
      $fromRaw = $overview->from ?? '';
      $fromEmail = self::extractEmail($fromRaw) ?: 'unknown@example.com';
      $msgId = $overview->message_id ?? null;

      // Skip if we've seen this message_id
      if ($msgId && TicketMessage::existsExternal($msgId)) {
        imap_setflag_full($mbox, $num, "\\Seen"); continue;
      }

      $body = self::getBody($mbox, $num);

      // Create a new ticket
      $ticketId = self::createTicketFromEmail($subject, $body, $fromEmail);

      // Save any attachments
      self::saveAttachments($mbox, $num, $ticketId);

      // Add inbound message to thread
      TicketMessage::addInbound($ticketId, $fromEmail, $subject, $body, $msgId ?: null);

      // Mark seen
      imap_setflag_full($mbox, $num, "\\Seen");
      $count++;
    }

    imap_close($mbox);
    return $count;
  }

  private static function createTicketFromEmail(string $subject, string $body, string $fromEmail): int {
    // Minimal create: user_id null, store requester email
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO tickets (user_id, title, description, requester_email, priority) VALUES (NULL, :t, :d, :e, 'Normal')");
    $stmt->execute([':t'=>$subject, ':d'=>mb_substr($body,0,65535), ':e'=>$fromEmail]);
    return (int)$pdo->lastInsertId();
  }

  private static function getBody($mbox, int $msgnum): string {
    $structure = imap_fetchstructure($mbox, $msgnum);
    if (!isset($structure->parts)) {
      $raw = imap_body($mbox, $msgnum);
      return self::decode($raw, $structure->encoding ?? 0);
    }
    // Find text/plain first, fallback to text/html
    for ($i=0; $i<count($structure->parts); $i++) {
      $part = $structure->parts[$i];
      if ($part->type == 0) { // text
        $data = imap_fetchbody($mbox, $msgnum, $i+1);
        $txt = self::decode($data, $part->encoding ?? 0);
        if (strtolower($part->subtype) === 'plain') return $txt;
        $html = strip_tags($txt);
        return $html;
      }
    }
    $raw = imap_body($mbox, $msgnum);
    return self::decode($raw, $structure->encoding ?? 0);
  }

  private static function decode($data, int $encoding): string {
    switch ($encoding) {
      case 3: return base64_decode($data);               // BASE64
      case 4: return quoted_printable_decode($data);     // QUOTED-PRINTABLE
      default: return $data;
    }
  }

  private static function saveAttachments($mbox, int $msgnum, int $ticketId): void {
    $structure = imap_fetchstructure($mbox, $msgnum);
    if (empty($structure->parts)) return;

    for ($i=0; $i<count($structure->parts); $i++) {
      $part = $structure->parts[$i];
      $filename = null;

      // filename can be in dparameters or parameters
      if (!empty($part->dparameters)) {
        foreach ($part->dparameters as $obj) {
          if (strtolower($obj->attribute) === 'filename') $filename = $obj->value;
        }
      }
      if (!$filename && !empty($part->parameters)) {
        foreach ($part->parameters as $obj) {
          if (strtolower($obj->attribute) === 'name') $filename = $obj->value;
        }
      }

      if ($filename) {
        $data = imap_fetchbody($mbox, $msgnum, $i+1);
        $data = self::decode($data, $part->encoding ?? 0);
        $tmp = tempnam(sys_get_temp_dir(), 'att_');
        file_put_contents($tmp, $data);
        try {
          Attachment::saveFromPath($ticketId, $filename, $tmp);
        } catch (Throwable $e) {
          error_log("IMAP attachment save failed: ".$e->getMessage());
        }
        @unlink($tmp);
      }
    }
  }

  private static function extractEmail(string $from): ?string {
    if (preg_match('/<([^>]+)>/', $from, $m)) return strtolower(trim($m[1]));
    if (filter_var($from, FILTER_VALIDATE_EMAIL)) return strtolower($from);
    return null;
  }
}
