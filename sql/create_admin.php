<?php
// create_admin.php - set credentials and run from CLI: php create_admin.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/DB.php';

$adminEmail = 'admin@demo.com';
$adminPass = 'Password123!'; // change immediately

$pdo = DB::getInstance();

$hash = password_hash($adminPass, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :ph, 'admin')");
try {
    $stmt->execute([':username' => 'admin', ':email' => $adminEmail, ':ph' => $hash]);
    echo "Admin created: {$adminEmail}\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
