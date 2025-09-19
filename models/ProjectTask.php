<?php
// models/ProjectTask.php
require_once __DIR__ . '/DB.php';
class ProjectTask {
    private $db;
    public function __construct() { $this->db = DB::getInstance(); }

    public function create($project_id, $title, $description = '', $assigned_to = null, $due_date = null) {
        $stmt = $this->db->prepare("INSERT INTO project_tasks (project_id, title, description, assigned_to, due_date) VALUES (:pid, :title, :desc, :ass, :due)");
        return $stmt->execute([
            ':pid'=>$project_id,
            ':title'=>$title,
            ':desc'=>$description,
            ':ass'=>$assigned_to,
            ':due'=>$due_date
        ]);
    }

    public function listByProject($project_id) {
        $stmt = $this->db->prepare("SELECT pt.*, u.email as assigned_email FROM project_tasks pt LEFT JOIN users u ON u.id = pt.assigned_to WHERE pt.project_id = :pid ORDER BY pt.created_at ASC");
        $stmt->execute([':pid'=>$project_id]);
        return $stmt->fetchAll();
    }

    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE project_tasks SET status = :status WHERE id = :id");
        return $stmt->execute([':status'=>$status, ':id'=>$id]);
    }
}
