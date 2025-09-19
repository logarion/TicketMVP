<?php
// models/User.php - User model

require_once __DIR__ . '/DB.php';

class User {
    private $db;

    public function __construct() {
        $this->db = DB::getInstance();
    }

    public function create($email, $password, $name = '', $role = 'user') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (email, password_hash, name, role, created_at) VALUES (:email, :ph, :name, :role, NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':email' => $email,
            ':ph' => $hash,
            ':name' => $name,
            ':role' => $role
        ]);
    }

    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    public function verifyCredentials($email, $password) {
        $user = $this->findByEmail($email);
        if (!$user) return false;
        if (password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return false;
    }

    public function getById($id) {
        $sql = "SELECT * FROM users WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
}
