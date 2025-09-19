<?php
class TicketMessage {
  public static function addOutbound(int $ticketId, string $to, string $subject, string $body): int {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, direction, from_email, to_email, subject, body) VALUES (:tid,'outbound',:from,:to,:sub,:body)");
    $stmt->execute([
      ':tid'=>$ticketId, ':from'=>SMTP_USER, ':to'=>$to, ':sub'=>$subject, ':body'=>$body
    ]);
    return (int)$pdo->lastInsertId();
  }

  public static function addInbound(int $ticketId, string $from, string $subject, string $body, ?string $externalId = null): int {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, direction, from_email, subject, body, external_id) VALUES (:tid,'inbound',:from,:sub,:body,:ext)");
    $stmt->execute([
      ':tid'=>$ticketId, ':from'=>$from, ':sub'=>$subject, ':body'=>$body, ':ext'=>$externalId
    ]);
    return (int)$pdo->lastInsertId();
  }

  public static function listByTicket(int $ticketId): array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM ticket_messages WHERE ticket_id=:tid ORDER BY created_at ASC, id ASC");
    $stmt->execute([':tid'=>$ticketId]);
    return $stmt->fetchAll();
  }

  public static function existsExternal(string $externalId): bool {
    global $pdo;
    $stmt = $pdo->prepare("SELECT 1 FROM ticket_messages WHERE external_id=:e LIMIT 1");
    $stmt->execute([':e'=>$externalId]);
    return (bool)$stmt->fetch();
  }
}
