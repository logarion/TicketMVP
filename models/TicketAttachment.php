<?php
// models/TicketAttachment.php
require_once __DIR__ . '/DB.php';
class TicketAttachment {
    private $db;
    public function __construct() {
        $this->db = DB::getInstance();
    }
    public function add($ticket_id, $original_filename, $stored_filename, $size, $mime) {
        $sql = "INSERT INTO ticket_attachments (ticket_id, original_filename, stored_filename, file_size, mime_type) 
                VALUES (:ticket_id, :orig, :stored, :size, :mime)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':ticket_id'=>$ticket_id,
            ':orig'=>$original_filename,
            ':stored'=>$stored_filename,
            ':size'=>$size,
            ':mime'=>$mime
        ]);
    }
    public function listByTicket($ticket_id) {
        $sql = "SELECT * FROM ticket_attachments WHERE ticket_id = :ticket_id ORDER BY uploaded_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ticket_id'=>$ticket_id]);
        return $stmt->fetchAll();
    }
    public function getById($id) {
        $sql = "SELECT * FROM ticket_attachments WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id'=>$id]);
        return $stmt->fetch();
    }
}
