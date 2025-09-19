<?php
// config.php - DB and Email configuration

// Database (MySQL)
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'ticketing_php');
define('DB_USER', 'LDX');
define('DB_PASS', '6_*aMrCo13{B');
define('DB_CHARSET', 'utf8mb4');

// App settings
define('APP_NAME', 'TicketingSystemPHP');
define('BASE_URL', '/TicketingSystemPHP'); // adjust if running in subfolder

// Uploads
define('UPLOAD_PATH', __DIR__ . '/uploads'); // make writable by web server

// Email (Office365) - fill with your credentials
define('SMTP_HOST', 'smtp.office365.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'support@lincolnreference.com');
define('SMTP_PASS', 'password');

// IMAP (for receiving mail)
define('IMAP_HOST', 'outlook.office365.com');
define('IMAP_PORT', 993);
