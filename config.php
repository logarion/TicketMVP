<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Use Replit PostgreSQL database credentials from environment
define('DB_HOST', $_ENV['PGHOST'] ?? 'helium');
define('DB_NAME', $_ENV['PGDATABASE'] ?? 'heliumdb'); 
define('DB_USER', $_ENV['PGUSER'] ?? 'postgres');
define('DB_PASS', $_ENV['PGPASSWORD'] ?? 'password');

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
  $pdo = new PDO("pgsql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (PDOException $e) {
  die("DB connection failed: ".$e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) session_start();
