<?php
// index.php - very small router for auth views

require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? 'login';
$method = $_SERVER['REQUEST_METHOD'];

if ($action === 'login') {
    require_once __DIR__ . '/controllers/AuthController.php';
    $c = new AuthController();
    if ($method === 'POST') $c->login();
    else $c->showLogin();
    exit;
}

if ($action === 'register') {
    require_once __DIR__ . '/controllers/AuthController.php';
    $c = new AuthController();
    if ($method === 'POST') $c->register();
    else $c->showRegister();
    exit;
}

if ($action === 'logout') {
    require_once __DIR__ . '/controllers/AuthController.php';
    $c = new AuthController();
    $c->logout();
    exit;
}

// default
header('Location: /?action=login');
exit;
