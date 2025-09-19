<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "PHP OK<br>";
echo "Version: " . PHP_VERSION . "<br>";

require_once __DIR__.'/config.php';
echo "DB OK<br>";

echo "Session OK: " . (session_status() === PHP_SESSION_ACTIVE ? "ACTIVE" : "NOT ACTIVE");
