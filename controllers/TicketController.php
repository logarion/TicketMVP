<?php
// controllers/TicketController.php
require_once __DIR__ . '/../models/Ticket.php';
require_once __DIR__ . '/../models/TicketAttachment.php';
require_once __DIR__ . '/../services/FileService.php';

class TicketController {
    private $ticketModel;
    private $attachModel;
    private $fileService;

    public function __construct() {
        $this->ticketModel = new Ticket();
        $this->attachModel = new TicketAttachment();
        $this->fileService = new FileService();
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    public function index() {
        $q = $_GET['q'] ?? null;
        $status = $_GET['status'] ?? null;
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = 10;

        $res = $this->ticketModel->searchList($q, $status, $page, $perPage);
        $tickets = $res['rows'];
        $total = $res['total'];
        $pages = ceil($total / $perPage);

        require __DIR__ . '/../views/ticket/index.php';
    }

    public function create() {
        // GET -> show create form
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            require __DIR__ . '/../views/ticket/create.php';
            return;
        }

        // POST -> handle create
        $subject = trim($_POST['subject'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $userId = $_SESSION['user_id'] ?? null;

        $errors = [];
        if ($subject === '') $errors[] = 'Subject required';
        if ($description === '') $errors[] = 'Description required';

        if (count($errors)) {
            require __DIR__ . '/../views/ticket/create.php';
            return;
        }

        // create ticket
        $ticketId = $this->ticketModel->create($subject, $description, $userId);

        // handle attachments (multiple)
        if (!empty($_FILES['attachments'])) {
            // normalize multi-file arrays
            $files = $this->restructureFilesArray($_FILES['attachments']);
            foreach ($files as $f) {
                try {
                    $info = $this->fileService->saveUploadedFile($f);
                    $this->attachModel->add($ticketId, $info['original_filename'], $info['stored_filename'], $info['file_size'], $info['mime_type']);
                } catch (Exception $ex) {
                    // log or collect error; continue with other files
                    error_log("Attachment error: " . $ex->getMessage());
                }
            }
        }

        // redirect to details
        header("Location: /?action=details&id=" . $ticketId);
        exit;
    }

    public function details() {
        $id = intval($_GET['id'] ?? 0);
        if (!$id) {
            header('Location: /?action=index');
            exit;
        }
        $ticket = $this->ticketModel->getById($id);
        if (!$ticket) {
            echo "Ticket not found";
            exit;
        }
        $attachments = $this->attachModel->listByTicket($id);
        require __DIR__ . '/../views/ticket/details.php';
    }

    public function downloadAttachment() {
        $id = intval($_GET['atid'] ?? 0);
        if (!$id) {
            http_response_code(400);
            exit;
        }
        $att = $this->attachModel->getById($id);
        if (!$att) {
            http_response_code(404);
            exit;
        }
        $fs = new FileService();
        $fs->serveFile($att['stored_filename'], $att['original_filename']);
    }

    private function restructureFilesArray($files) {
        $out = [];
        if (!isset($files['name'])) return $out;
        $count = is_array($files['name']) ? count($files['name']) : 0;
        for ($i=0;$i<$count;$i++) {
            $out[] = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            ];
        }
        return $out;
    }
}
