<?php
// create_admin.php - set credentials and run from CLI: php create_admin.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/DB.php';

$adminEmail = 'admin@demo.com';
$adminPass = 'Password123!'; // change immediately

$pdo = DB::getInstance();

$hash = password_hash($adminPass, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (email, password_hash, name, role) VALUES (:email, :ph, :name, 'admin')");
try {
    $stmt->execute([':email' => $adminEmail, ':ph' => $hash, ':name' => 'Admin']);
    echo "Admin created: {$adminEmail}\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
