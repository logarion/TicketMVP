<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Check DB connection
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error);
}

// Autoload controllers/models/helpers
spl_autoload_register(function($class) {
    $paths = ['controllers', 'models', 'services'];
    foreach ($paths as $path) {
        $file = __DIR__ . "/$path/$class.php";
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Security: CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Basic router
$action = $_GET['action'] ?? 'home';
$method = $_SERVER['REQUEST_METHOD'];

// Handle API token auth for REST endpoints
if (strpos($action, 'api_') === 0) {
    require_once __DIR__ . '/api.php';
    exit;
}

// Authentication check
$public_actions = ['login', 'register', 'forgot_password', 'reset_password'];
if (!in_array($action, $public_actions) && empty($_SESSION['user_id'])) {
    header("Location: index.php?action=login");
    exit;
}

// Email polling (create tickets from Office365 inbox)
if ($action === 'poll_email') {
    $emailService = new EmailService();
    $emailService->fetchInboundTickets();
    echo "Email polling completed.";
    exit;
}

// Main controller dispatch
switch ($action) {
    case 'home':
        include __DIR__ . '/views/home.php';
        break;

    case 'login':
        include __DIR__ . '/controllers/AuthController.php';
        $auth = new AuthController($db);
        if ($method === 'POST') $auth->login($_POST);
        include __DIR__ . '/views/login.php';
        break;

    case 'logout':
        session_destroy();
        header("Location: index.php");
        break;

    case 'register':
        include __DIR__ . '/controllers/AuthController.php';
        $auth = new AuthController($db);
        if ($method === 'POST') $auth->register($_POST);
        include __DIR__ . '/views/register.php';
        break;

    case 'forgot_password':
        include __DIR__ . '/controllers/AuthController.php';
        $auth = new AuthController($db);
        if ($method === 'POST') $auth->sendPasswordReset($_POST['email']);
        include __DIR__ . '/views/forgot_password.php';
        break;

    case 'reset_password':
        include __DIR__ . '/controllers/AuthController.php';
        $auth = new AuthController($db);
        if ($method === 'POST') $auth->resetPassword($_POST);
        include __DIR__ . '/views/reset_password.php';
        break;

    case 'tickets':
        include __DIR__ . '/controllers/TicketController.php';
        $ticketCtrl = new TicketController($db);
        if ($method === 'POST' && verify_csrf()) $ticketCtrl->create($_POST, $_FILES);
        $tickets = $ticketCtrl->list($_GET);
        include __DIR__ . '/views/tickets/index.php';
        break;

    case 'ticket_view':
        include __DIR__ . '/controllers/TicketController.php';
        $ticketCtrl = new TicketController($db);
        $ticket = $ticketCtrl->view($_GET['id']);
        include __DIR__ . '/views/tickets/view.php';
        break;

    case 'ticket_to_project':
        include __DIR__ . '/controllers/ProjectController.php';
        $projCtrl = new ProjectController($db);
        $projCtrl->convertFromTicket($_GET['ticket_id']);
        header("Location: index.php?action=projects");
        break;

    case 'projects':
        include __DIR__ . '/controllers/ProjectController.php';
        $projCtrl = new ProjectController($db);
        if ($method === 'POST' && verify_csrf()) $projCtrl->create($_POST);
        $projects = $projCtrl->list();
        include __DIR__ . '/views/projects/index.php';
        break;

    case 'tasks':
        include __DIR__ . '/controllers/TaskController.php';
        $taskCtrl = new TaskController($db);
        if ($method === 'POST' && verify_csrf()) $taskCtrl->create($_POST);
        $tasks = $taskCtrl->list();
        include __DIR__ . '/views/tasks/index.php';
        break;

    case 'admin':
        if ($_SESSION['role'] !== 'admin') die("Access denied.");
        include __DIR__ . '/controllers/AdminController.php';
        $adminCtrl = new AdminController($db);
        $stats = $adminCtrl->getDashboardStats();
        $users = $adminCtrl->listUsers();
        include __DIR__ . '/views/admin/dashboard.php';
        break;

    case 'export_csv':
        include __DIR__ . '/controllers/ExportController.php';
        $exportCtrl = new ExportController($db);
        $exportCtrl->exportTicketsCSV();
        break;

    default:
        http_response_code(404);
        echo "<h1>404 Not Found</h1>";
        break;
}

// Security function
function verify_csrf() {
    return isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}
?>
