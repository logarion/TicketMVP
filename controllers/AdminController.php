<?php
// controllers/AdminController.php
require_once __DIR__ . '/../models/DB.php';

class AdminController {
    private $pdo;
    public function __construct() {
        $this->pdo = DB::getInstance();
    }

    public function getDashboardStats() {
        $r = [];
        $r['open_tickets'] = (int)$this->pdo->query("SELECT COUNT(*) FROM tickets WHERE status='open'")->fetchColumn();
        $r['in_progress'] = (int)$this->pdo->query("SELECT COUNT(*) FROM tickets WHERE status='in_progress'")->fetchColumn();
        $r['closed'] = (int)$this->pdo->query("SELECT COUNT(*) FROM tickets WHERE status='closed'")->fetchColumn();
        $r['projects'] = (int)$this->pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
        return $r;
    }

    public function listUsers() {
        $stmt = $this->pdo->query("SELECT id, email, name, role, created_at FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public function changeUserRole($userId, $role) {
        $stmt = $this->pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
        return $stmt->execute([':role'=>$role, ':id'=>$userId]);
    }
}
