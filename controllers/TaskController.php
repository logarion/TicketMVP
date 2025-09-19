<?php
// controllers/TaskController.php
require_once __DIR__ . '/../models/ProjectTask.php';
class TaskController {
    private $model;
    public function __construct() {
        $this->model = new ProjectTask();
        if (session_status()===PHP_SESSION_NONE) session_start();
    }

    public function create($post) {
        $project_id = intval($post['project_id'] ?? 0);
        $title = trim($post['title'] ?? '');
        $desc = trim($post['description'] ?? '');
        $assigned = !empty($post['assigned_to']) ? intval($post['assigned_to']) : null;
        $due = !empty($post['due_date']) ? $post['due_date'] : null;

        if (!$project_id || !$title) {
            set_flash('Project and title required');
            header('Location: index.php?action=projects');
            exit;
        }
        $this->model->create($project_id, $title, $desc, $assigned, $due);
        set_flash('Task added');
        header('Location: index.php?action=project_view&id=' . $project_id);
        exit;
    }

    public function list() {
        // optional: implement by project / user
        return [];
    }
}
