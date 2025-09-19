<?php
class TicketMessage
{
    /**
     * Return all messages for a given ticket, newest first.
     */
    public static function listByTicket(int $ticketId): array
    {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT id, ticket_id, direction, from_email, to_email, subject, body, locked, emailed, created_at
            FROM ticket_messages
            WHERE ticket_id = :t
            ORDER BY created_at DESC, id DESC
        ");
        $stmt->execute([':t' => $ticketId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lock all messages for a ticket so they become read-only.
     */
    public static function lockAll(int $ticketId): void
    {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE ticket_messages SET locked = 1 WHERE ticket_id = :t");
        $stmt->execute([':t' => $ticketId]);
    }

    /**
     * Create an outbound message (from system/agent to requester).
     * Set $emailed=1 if an email was sent; leave 0 to save without sending.
     * @return int New message ID
     */
    public static function createOutbound(int $ticketId, string $toEmail, string $subject, string $body, int $emailed = 0): int
    {
        global $pdo;
        $stmt = $pdo->prepare("
            INSERT INTO ticket_messages (ticket_id, direction, from_email, to_email, subject, body, locked, emailed, created_at)
            VALUES (:t, 'outbound', NULL, :to, :s, :b, 0, :emailed, NOW())
        ");
        $stmt->execute([
            ':t'       => $ticketId,
            ':to'      => $toEmail,
            ':s'       => $subject,
            ':b'       => $body,
            ':emailed' => $emailed
        ]);
        return (int)$pdo->lastInsertId();
    }

    /**
     * Create an inbound message (from requester to system/agent).
     * Useful if importing emails.
     * @return int New message ID
     */
    public static function createInbound(int $ticketId, string $fromEmail, string $subject, string $body): int
    {
        global $pdo;
        $stmt = $pdo->prepare("
            INSERT INTO ticket_messages (ticket_id, direction, from_email, to_email, subject, body, locked, emailed, created_at)
            VALUES (:t, 'inbound', :from, NULL, :s, :b, 1, 0, NOW())
        ");
        $stmt->execute([
            ':t'    => $ticketId,
            ':from' => $fromEmail,
            ':s'    => $subject,
            ':b'    => $body
        ]);
        return (int)$pdo->lastInsertId();
    }

    /**
     * Mark a message as emailed (optional helper if you send after insert).
     */
    public static function markEmailed(int $id): bool
    {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE ticket_messages SET emailed = 1 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Get a single message by ID.
     */
    public static function getById(int $id): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM ticket_messages WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Delete a message (only if needed for admin cleanup).
     */
    public static function delete(int $id): bool
    {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM ticket_messages WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
