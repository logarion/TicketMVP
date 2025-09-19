<?php
class Ticket {
  public static function getAll() {
    global $pdo;
    $sql = "SELECT t.*, u.username
            FROM tickets t
            JOIN users u ON u.id = t.user_id
            ORDER BY t.created_at DESC";
    return $pdo->query($sql)->fetchAll();
  }

  public static function getById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT t.*, u.username FROM tickets t JOIN users u ON u.id=t.user_id WHERE t.id=:id");
    $stmt->execute([':id'=>$id]);
    return $stmt->fetch();
  }

  public static function store($userId, $data) {
    global $pdo;
    $title = trim($data['title'] ?? '');
    $desc  = trim($data['description'] ?? '');
    $priority = in_array(($data['priority'] ?? 'Normal'), ['Normal','Urgent']) ? $data['priority'] : 'Normal';
    if ($title==='') return 0;

    $stmt = $pdo->prepare("INSERT INTO tickets (user_id, title, description, priority) VALUES (:uid,:t,:d,:p)");
    $stmt->execute([':uid'=>$userId, ':t'=>$title, ':d'=>$desc, ':p'=>$priority]);
    return (int)$pdo->lastInsertId();
  }
  
  public static function store($userId, $data) {
    global $pdo;
    $title = trim($data['title'] ?? '');
    $desc  = trim($data['description'] ?? '');
    $priority = in_array(($data['priority'] ?? 'Normal'), ['Normal','Urgent']) ? $data['priority'] : 'Normal';
    $req = trim($data['requester_email'] ?? '');
    if ($title==='') return 0;
  
    $stmt = $pdo->prepare("INSERT INTO tickets (user_id, title, description, requester_email, priority) VALUES (:uid,:t,:d,:r,:p)");
    $stmt->execute([':uid'=>$userId, ':t'=>$title, ':d'=>$desc, ':r'=>($req ?: null), ':p'=>$priority]);
    return (int)$pdo->lastInsertId();
  }  
}
