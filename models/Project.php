<?php
// models/Project.php
require_once __DIR__ . '/DB.php';

class Project {
    private $db;
    public function __construct() {
        $this->db = DB::getInstance();
    }

    public function createFromTicket($ticketId, $name = null, $description = null) {
        // fetch ticket
        $t = $this->db->prepare("SELECT * FROM tickets WHERE id = :id LIMIT 1");
        $t->execute([':id'=>$ticketId]);
        $ticket = $t->fetch();
        if (!$ticket) return false;
        $name = $name ?? ('Project from ticket #' . $ticketId . ' - ' . substr($ticket['subject'],0,80));
        $description = $description ?? $ticket['description'];
        $stmt = $this->db->prepare("INSERT INTO projects (ticket_id, name, description) VALUES (:tid, :name, :desc)");
        $stmt->execute([':tid'=>$ticketId, ':name'=>$name, ':desc'=>$description]);
        $projectId = $this->db->lastInsertId();
        // mark ticket as converted
        $u = $this->db->prepare("UPDATE tickets SET status='converted' WHERE id=:id");
        $u->execute([':id'=>$ticketId]);
        return $projectId;
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT p.*, t.subject AS from_ticket_subject FROM projects p LEFT JOIN tickets t ON p.ticket_id = t.id WHERE p.id = :id LIMIT 1");
        $stmt->execute([':id'=>$id]);
        return $stmt->fetch();
    }

    public function listAll() {
        $stmt = $this->db->query("SELECT p.*, t.subject AS from_ticket_subject FROM projects p LEFT JOIN tickets t ON p.ticket_id = t.id ORDER BY p.created_at DESC");
        return $stmt->fetchAll();
    }
}
