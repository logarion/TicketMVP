<?php
// controllers/ProjectController.php
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../models/ProjectTask.php';

class ProjectController {
    private $model;
    private $taskModel;
    public function __construct() {
        $this->model = new Project();
        $this->taskModel = new ProjectTask();
        if (session_status()===PHP_SESSION_NONE) session_start();
    }

    public function convertFromTicket($ticketId) {
        if (!is_admin()) {
            die("Access denied");
        }
        return $this->model->createFromTicket($ticketId);
    }

    public function create($post) {
        $name = trim($post['name'] ?? '');
        $desc = trim($post['description'] ?? '');
        if ($name === '') {
            set_flash('Project name required');
            header('Location: index.php?action=projects');
            exit;
        }
        // simple insert
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("INSERT INTO projects (name, description) VALUES (:name, :desc)");
        $stmt->execute([':name'=>$name, ':desc'=>$desc]);
        set_flash('Project created');
        header('Location: index.php?action=projects');
        exit;
    }

    public function list() {
        return $this->model->listAll();
    }

    public function view($id) {
        $proj = $this->model->getById($id);
        if (!$proj) {
            set_flash('Project not found');
            header('Location: index.php?action=projects');
            exit;
        }
        $tasks = $this->taskModel->listByProject($id);
        require __DIR__ . '/../views/projects/view.php';
    }
}
