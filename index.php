<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__.'/config.php';
require_once __DIR__.'/models/User.php';
require_once __DIR__.'/models/Ticket.php';
require_once __DIR__ . '/models/Project.php';
require_once __DIR__ . '/models/Task.php';
require_once __DIR__ . '/models/MailService.php';
require_once __DIR__ . '/models/TicketMessage.php';
require_once __DIR__ . '/models/ImapService.php';
require_once __DIR__ . '/models/Department.php';
require_once __DIR__ . '/models/Group.php';





$page = $_GET['page'] ?? 'login'; // start at login to test routing

function require_login($page) {
  if (empty($_SESSION['user_id']) && !in_array($page, ['login','register'])) {
    header('Location: index.php?page=login');
    exit;
  }
}

function require_admin($page) {
  require_login($page);
  if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'admin') {
    http_response_code(403); echo 'Admins only'; exit;
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
        $_SESSION['role'] = $u['role'];
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
      require_login($page);
    
      // current user context
      $currentUser = [
        'id' => (int)($_SESSION['user_id'] ?? 0),
        'role' => $_SESSION['role'] ?? 'user',
        'department_id' => null,
      ];
    
      // fetch department for current user
      if ($currentUser['id']) {
        $st = $pdo->prepare("SELECT department_id FROM users WHERE id=:id");
        $st->execute([':id'=>$currentUser['id']]);
        $row = $st->fetch();
        $currentUser['department_id'] = $row['department_id'] ?? null;
      }
    
      // filters from GET
      $q       = trim($_GET['q'] ?? '');
      $p       = (int)($_GET['p'] ?? 1);
      $perPage = (int)($_GET['pp'] ?? 10);
      $filters = [
        'department_id' => $_GET['department_id'] ?? '',
        'group_id'      => $_GET['group_id'] ?? '',
        'assigned_to'   => $_GET['assigned_to'] ?? '',
        'priority'      => $_GET['priority'] ?? '',
        'status'        => $_GET['status'] ?? '',
      ];
    
      // Lookups for filter dropdowns
      $departments = $pdo->query("SELECT id, name FROM departments ORDER BY name")->fetchAll();
      $groups      = $pdo->query("SELECT id, name FROM groups ORDER BY name")->fetchAll();
      $users       = $pdo->query("SELECT id, username FROM users ORDER BY username")->fetchAll();
    
      $result  = Ticket::searchPaginated($q, $p, $perPage, $filters, $currentUser);
    
      $tickets = $result['rows'];
      $total   = $result['total'];
      $pageNum = $result['page'];
      $pp      = $result['perPage'];
      $query   = $result['q'];
    
      require __DIR__ . '/views/ticket/list.php';
      break;
    

case 'ticket_create':
    require_login($page);
    if ($_SERVER['REQUEST_METHOD']==='POST') {
     $id = Ticket::store($_SESSION['user_id'], $_POST);
     if ($id) {
      require_once __DIR__.'/models/Attachment.php';
      Attachment::saveFiles($id, $_FILES['attachments'] ?? null);
    }
    header('Location: index.php?page=ticket_view&id='.$id); exit;
  }
  require __DIR__.'/views/ticket/create.php';
  break;


  case 'ticket_view':
    require_login($page);
    $id = (int)($_GET['id'] ?? 0);

    require_once __DIR__ . '/models/Ticket.php';
    require_once __DIR__ . '/models/Attachment.php';
    require_once __DIR__ . '/models/TicketMessage.php';
    require_once __DIR__ . '/models/Project.php';
    require_once __DIR__ . '/models/MailService.php';
    require_once __DIR__ . '/models/Mailer.php';

    // Assignee dropdown data
    $users = $pdo->query("SELECT id, username FROM users ORDER BY username")->fetchAll();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
          //  Quick assign (works even in view mode)
    if (isset($_POST['assign_ticket'])) {
      $assignTo = ($_POST['assigned_to'] ?? '') === '' ? null : (int)$_POST['assigned_to'];
      Ticket::assignTo($id, $assignTo);
      header('Location: index.php?page=ticket_view&id='.$id.'&saved=1');
      exit;
    }
      // Save ticket fields (inline)
      if (isset($_POST['save_ticket'])) {
        $ok = Ticket::update($id, [
          'title'       => $_POST['title'] ?? '',
          'description' => $_POST['description'] ?? '',
          'priority'    => $_POST['priority'] ?? 'Normal',
          'assigned_to' => $_POST['assigned_to'] ?? '',
          'requester'   => $_POST['requester'] ?? null,
        ]);
        header('Location: index.php?page=ticket_view&id='.$id.'&saved=1');
        exit;
      }
      if (isset($_POST['close_ticket'])) {
        Ticket::setStatus($id, 'Closed');
        header('Location: index.php?page=ticket_view&id='.$id.'&saved=1');
        exit;
      }
      
      if (isset($_POST['reopen_ticket'])) {
        Ticket::setStatus($id, 'In Progress'); // or 'New', up to you
        header('Location: index.php?page=ticket_view&id='.$id.'&saved=1');
        exit;
      }
      
      // you can also add:
      if (isset($_POST['resolve_ticket'])) {
        Ticket::setStatus($id, 'Resolved');
        header('Location: index.php?page=ticket_view&id='.$id.'&saved=1');
        exit;
      }
      // Compose values
      if (isset($_POST['save_conversation']) || isset($_POST['email_conversation'])) {
        $ticket = Ticket::getById($id);
        if (!$ticket) { http_response_code(404); echo 'Not found'; exit; }
  
        $to = $ticket['requester_email'] ?? ($ticket['requester_user_email'] ?? '');
        $subject = trim($_POST['conversation_subject'] ?? ('Update: '.$ticket['title']));
        $body    = trim($_POST['conversation_body'] ?? '');
  
        if ($body === '' || $to === '') {
          $conv_error = 'Missing message body or requester email.';
        } else {
          // lock history, create new latest
          TicketMessage::lockAll($id);
  
          $sendNow = isset($_POST['email_conversation']) ? 1 : 0;
          TicketMessage::createOutbound($id, $to, $subject, $body, $sendNow);
  
          if ($sendNow) {
            Mailer::sendTicketUpdate($ticket, $to, $subject, $body);
            header('Location: index.php?page=ticket_view&id='.$id.'&conv_saved=1&emailed=1');
          } else {
            header('Location: index.php?page=ticket_view&id='.$id.'&conv_saved=1');
          }
          exit;
        }
      }
    }
  
    $ticket          = Ticket::getById($id);
    if (!$ticket) { http_response_code(404); echo 'Not found'; exit; }
    $attachments     = Attachment::listByTicket($id);
    $messages        = TicketMessage::listByTicket($id);  // newest first
    $existingProject = Project::getByTicketId($id);
  
    require __DIR__ . '/views/ticket/view.php';
    break;


  

 case 'attachment_download':
    require_login($page);
    require_once __DIR__.'/models/Attachment.php';
    $attId = (int)($_GET['id'] ?? 0);
    Attachment::download($attId); // exits
    break;

 case 'project_from_ticket':
    require_login($page);
    $ticketId = (int)($_GET['ticket_id'] ?? 0);
    if ($ticketId) {
          $pid = Project::createFromTicket($ticketId);
          if ($pid) { header('Location: index.php?page=project_view&id=' . $pid); exit; }
        }
    http_response_code(400); echo 'Cannot create project from ticket.'; 
        break;
      
 case 'projects_list':
    require_login($page);
     $projects = Project::getAll();
    $q       = trim($_GET['q'] ?? '');
    $p       = (int)($_GET['p'] ?? 1);
    $perPage = (int)($_GET['pp'] ?? 10);
    $result  = Project::searchPaginated($q, $p, $perPage);
    $projects= $result['rows'];
    $total   = $result['total'];
    $pageNum = $result['page'];
    $pp      = $result['perPage'];
    $query   = $result['q'];
    require __DIR__ . '/views/project/list.php';
    break;
      
 case 'project_view':
    require_login($page);
    $pid = (int)($_GET['id'] ?? 0);
    $project = Project::getById($pid);
    if (!$project) { http_response_code(404); echo 'Project not found'; exit; }
    require_once __DIR__ . '/models/Task.php';
    require_once __DIR__ . '/models/User.php';
    $tasks = Task::listByProject($pid);
    $users = User::listAll();
    require __DIR__ . '/views/project/view.php';
    break;

 case 'task_create':
    require_login($page);
        // expects POST with project_id, title, [description, status, assigned_to, due_date]
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
          $pid = (int)($_POST['project_id'] ?? 0);
          if ($pid && !empty($_POST['title'])) {
            Task::create($_POST);
            header('Location: index.php?page=project_view&id=' . $pid);
            exit;
          }
          http_response_code(400); echo 'Project and title required.'; exit;
        }
        http_response_code(405); echo 'Method not allowed'; 
        break;
      
case 'task_update_status':
        require_login($page);
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'Pending';
        $task = $id ? Task::get($id) : null;
        if ($task && Task::updateStatus($id, $status)) {
          header('Location: index.php?page=project_view&id=' . $task['project_id']);
          exit;
        }
        http_response_code(400); echo 'Cannot update status';
        break;
      
case 'task_edit':
        require_login($page);
        $id = (int)($_GET['id'] ?? 0);
        $task = $id ? Task::get($id) : null;
        if (!$task) { http_response_code(404); echo 'Task not found'; exit; }
        $users = User::listAll();
        // load simple edit view
        require __DIR__ . '/views/task/edit.php';
        break;
      
case 'task_update':
        require_login($page);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
          $id = (int)($_POST['id'] ?? 0);
          $task = $id ? Task::get($id) : null;
          if ($task && Task::update($id, $_POST)) {
            header('Location: index.php?page=project_view&id=' . $task['project_id']);
            exit;
          }
          http_response_code(400); echo 'Update failed';
          break;
        }
        http_response_code(405); echo 'Method not allowed';
        break;
      
case 'ticket_send_email':
  require_login($page);
  $ticketId = (int)($_POST['ticket_id'] ?? 0);
  $to       = trim($_POST['to'] ?? '');
  $subject  = trim($_POST['subject'] ?? '');
  $body     = trim($_POST['body'] ?? '');

  if (!$ticketId || !$to || !$subject || !$body) {
    http_response_code(400); echo 'Missing fields'; exit;
  }

  if (MailService::send($to, $subject, $body)) {
    TicketMessage::addOutbound($ticketId, $to, $subject, $body);
    header('Location: index.php?page=ticket_view&id=' . $ticketId);
    exit;
  } else {
    http_response_code(500); echo 'Email send failed';
  }
  break;
  case 'ticket_send_email':
    require_login($page);
    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    $to       = trim($_POST['to'] ?? '');
    $subject  = trim($_POST['subject'] ?? '');
    $body     = trim($_POST['body'] ?? '');
  
    if (!$ticketId || !$to || !$subject || !$body) {
      http_response_code(400); echo 'Missing fields'; exit;
    }
  
    if (MailService::send($to, $subject, $body)) {
      TicketMessage::addOutbound($ticketId, $to, $subject, $body);
      header('Location: index.php?page=ticket_view&id=' . $ticketId);
      exit;
    } else {
      http_response_code(500); echo 'Email send failed';
    }
    break;
 case 'email_poll':
      require_login($page); // optional; you might allow CLI only
      $count = ImapService::pollAndCreateTickets();
      header('Content-Type: text/plain');
      echo "Processed $count message(s).";
      break;

// ------- ADMIN: Users -------
case 'admin_users':
  require_admin($page);
  $users = User::listAll();
  $departments = Department::all();
  require __DIR__ . '/views/admin/users.php';
  break;

case 'admin_user_create':
  require_admin($page);
  $departments = Department::all();
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (User::createAdmin($_POST)) {
      header('Location: index.php?page=admin_users'); exit;
    }
    $error = 'Create failed';
  }
  require __DIR__ . '/views/admin/user_form.php';
  break;

case 'admin_user_edit':
  require_admin($page);
  $id   = (int)($_GET['id'] ?? 0);
  $user = User::getById($id);
  $departments = Department::all();
  if (!$user) { http_response_code(404); echo 'Not found'; exit; }
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (User::updateAdmin($id, $_POST)) {
      header('Location: index.php?page=admin_users'); exit;
    }
    $error = 'Update failed';
  }
  require __DIR__ . '/views/admin/user_form.php';
  break;

case 'admin_user_delete':
  require_admin($page);
  $id = (int)($_GET['id'] ?? 0);
  User::delete($id);
  header('Location: index.php?page=admin_users'); exit;
  break;

case 'admin_user_reset_pw':
  require_admin($page);
  $id = (int)($_GET['id'] ?? 0);
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (User::setPassword($id, $_POST['password'] ?? '')) {
      header('Location: index.php?page=admin_users'); exit;
    }
    $error = 'Password reset failed';
  }
  require __DIR__ . '/views/admin/reset_password.php';
  break;

case 'admin_user_memberships':
  require_admin($page);
  $id = (int)($_GET['id'] ?? 0);
  $user = User::getById($id);
  if (!$user) { http_response_code(404); echo 'Not found'; exit; }
  $groups = Group::all();
  $departments = Department::all();
  // current groups
  $current = array_column(User::groupsOf($id), 'id');
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // dept
    if (isset($_POST['department_id'])) {
      User::updateAdmin($id, ['username'=>$user['username'], 'email'=>$user['email'], 'role'=>$user['role'], 'department_id'=>$_POST['department_id'] !== '' ? (int)$_POST['department_id'] : null]);
      $user = User::getById($id);
    }
    // groups
    $sel = isset($_POST['groups']) ? array_map('intval', $_POST['groups']) : [];
    User::setGroups($id, $sel);
    $current = array_column(User::groupsOf($id), 'id');
    $saved = true;
  }
  require __DIR__ . '/views/admin/user_memberships.php';
  break;

// ------- ADMIN: Departments -------
case 'admin_departments':
  require_admin($page);
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['name'])) Department::create($_POST['name']);
    if (isset($_POST['delete_id'])) Department::delete((int)$_POST['delete_id']);
  }
  $departments = Department::all();
  require __DIR__ . '/views/admin/departments.php';
  break;

// ------- ADMIN: Groups -------
case 'admin_groups':
  require_admin($page);
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['name'])) Group::create($_POST['name']);
    if (isset($_POST['delete_id'])) Group::delete((int)$_POST['delete_id']);
  }
  $groups = Group::all();
  require __DIR__ . '/views/admin/groups.php';
  break;

  case 'ticket_edit':
    require_login($page);
    $id = (int)($_GET['id'] ?? 0);
    $ticket = Ticket::getById($id);
    if (!$ticket) { http_response_code(404); echo 'Not found'; exit; }
    // load users for the assignee dropdown
    $users = $pdo->query("SELECT id, username FROM users ORDER BY username")->fetchAll();
  
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      if (Ticket::update($id, $_POST)) {
        header('Location: index.php?page=ticket_view&id='.$id); exit;
      }
      $error = 'Update failed';
    }
    require __DIR__ . '/views/ticket/edit.php';
    break;
  
  case 'ticket_assign_quick':
    require_login($page);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $id = (int)($_POST['ticket_id'] ?? 0);
      $assignee = ($_POST['assigned_to'] ?? '') !== '' ? (int)$_POST['assigned_to'] : null;
      if ($id && Ticket::assignTo($id, $assignee)) {
        header('Location: index.php?page=ticket_view&id='.$id); exit;
      }
    }
    http_response_code(400); echo 'Assignment failed';
    break;
    case 'attachment_upload':
      require_login($page); // if your app requires login for uploads
  
      if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
          http_response_code(405);
          echo 'Method not allowed';
          exit;
      }
  
      $ticketId = (int)($_POST['ticket_id'] ?? 0);
      if (!$ticketId) {
          http_response_code(400);
          echo 'Missing ticket ID';
          exit;
      }
  
      if (!isset($_FILES['file'])) {
          http_response_code(400);
          echo 'No file field';
          exit;
      }
  
      require_once __DIR__ . '/models/Attachment.php';
  
      try {
          Attachment::uploadFromForm($ticketId, $_FILES['file']);
          header('Location: index.php?page=ticket_view&id=' . $ticketId);
          exit;
      } catch (Throwable $e) {
          http_response_code(400);
          echo 'Upload failed: ' . htmlspecialchars($e->getMessage());
      }
      break;
    

  default:
    http_response_code(404); echo 'Not found';
}
