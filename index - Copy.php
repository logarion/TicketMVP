ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/models/User.php';
require_once __DIR__.'/models/Ticket.php';

$page = $_GET['page'] ?? 'tickets_list';

function require_login() {
  if (empty($_SESSION['user_id']) && !in_array($_GET['page'] ?? 'tickets_list', ['login','register'])) {
    header('Location: index.php?page=login'); exit;
  }
}

switch ($page) {
  case 'login':
    if ($_SERVER['REQUEST_METHOD']==='POST') {
      $u = User::findByUsername($_POST['username'] ?? '');
      if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['username'] = $u['username'];
        header('Location: index.php?page=tickets_list'); exit;
      }
      $error = 'Invalid username or password';
    }
    require __DIR__.'/views/user/login.php';
    break;

  case 'register':
    if ($_SERVER['REQUEST_METHOD']==='POST') {
      $ok = User::create($_POST['username'] ?? '', $_POST['email'] ?? '', $_POST['password'] ?? '');
      if ($ok) { header('Location: index.php?page=login'); exit; }
      $error = 'Registration failed (username or email may already exist).';
    }
    require __DIR__.'/views/user/register.php';
    break;

  case 'logout':
    session_unset(); session_destroy(); header('Location: index.php?page=login'); break;

  case 'tickets_list':
    require_login();
    $tickets = Ticket::getAll();
    require __DIR__.'/views/ticket/list.php';
    break;

  case 'ticket_create':
    require_login();
    if ($_SERVER['REQUEST_METHOD']==='POST') {
      $id = Ticket::store($_SESSION['user_id'], $_POST);
      header('Location: index.php?page=ticket_view&id='.$id); exit;
    }
    require __DIR__.'/views/ticket/create.php';
    break;

  case 'ticket_view':
    require_login();
    $id = (int)($_GET['id'] ?? 0);
    $ticket = Ticket::getById($id);
    if (!$ticket) { http_response_code(404); echo 'Not found'; exit; }
    require __DIR__.'/views/ticket/view.php';
    break;

  default:
    http_response_code(404); echo 'Not found';
}
