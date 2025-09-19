<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Update credentials to your MySQL
define('DB_HOST','127.0.0.1');
define('DB_NAME','ticket_mvp');
define('DB_USER','LDX');
define('DB_PASS','6_*aMrCo13{B');

// --- Email (Office365) ---
define('SMTP_HOST', 'smtp.office365.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'you@yourdomain.com');
define('SMTP_PASS', 'your-app-password-or-oauth-token'); // use app password or OAuth

// Incoming IMAP (Office365)
define('IMAP_HOST', 'outlook.office365.com'); // IMAP hostname
define('IMAP_PORT', 993);
define('IMAP_USER', SMTP_USER);               // same mailbox
define('IMAP_PASS', SMTP_PASS);
define('IMAP_MAILBOX', '{'.IMAP_HOST.':'.IMAP_PORT.'/imap/ssl}INBOX');


// Where to store uploaded files 
define('UPLOAD_DIR', __DIR__ . '/attachments');

if (!is_dir(UPLOAD_DIR)) {
  @mkdir(UPLOAD_DIR, 0755, true);
}


try {
  $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (PDOException $e) {
  die("DB connection failed: ".$e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) session_start();
