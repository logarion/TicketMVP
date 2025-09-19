<?php
// --- Debug (turn off in production) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --------------------------------------
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Ticket.php';
require_once __DIR__ . '/models/Attachment.php'; // used in view + download

$page = $_GET['page'] ?? 'login';

function require_login($page) {
  if (empty($_SESSION['user_id']) && !in_array($page, ['login','register'])) {
    header('Location: index.php?page=login');
    exit;
  }
}

switch ($page) {
  // ---------------- AUTH ----------------
  case 'login':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $u = User::findByUsername($_POST['username'] ?? '');
      if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['username'] = $u['username'];
        header('Location: index.php?page=tickets_list');
        exit;
      }
      $error = 'Invalid username or password';
    }
    require __DIR__ . '/views/user/login.php';
    break;

  case 'register':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $ok = User::create($_POST['username'] ?? '', $_POST['email'] ?? '', $_POST['password'] ?? '');
      if ($ok) { header('Location: index.php?page=login'); exit; }
      $error = 'Registration failed (username or email may already exist).';
    }
    require __DIR__ . '/views/user/register.php';
    break;

  case 'logout':
    session_unset();
    session_destroy();
    header('Location: index.php?page=login');
    break;

  // --------------- TIC
