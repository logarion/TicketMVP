<?php
// functions.php - common helpers

// Simple flash messages
function set_flash($msg) {
    if (session_status()===PHP_SESSION_NONE) session_start();
    $_SESSION['_flash'] = $msg;
}
function get_flash() {
    if (session_status()===PHP_SESSION_NONE) session_start();
    $m = $_SESSION['_flash'] ?? null;
    unset($_SESSION['_flash']);
    return $m;
}

// Auth helpers
function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}
function current_user_role() {
    return $_SESSION['user_role'] ?? null;
}
function is_admin() {
    return current_user_role() === 'admin';
}

// CSRF hidden input for forms
function csrf_input() {
    if (session_status()===PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return '<input type="hidden" name="csrf_token" value="'.htmlspecialchars($_SESSION['csrf_token']).'">';
}

// verify CSRF used in index.php earlier - helper
function verify_csrf_token($token) {
    if (session_status()===PHP_SESSION_NONE) session_start();
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// simple input sanitization (for display)
function e($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
