<?php
class Group {
  public static function all(): array {
    global $pdo;
    return $pdo->query("SELECT * FROM groups ORDER BY name")->fetchAll();
  }
  public static function create(string $name): bool {
    global $pdo;
    $name = trim($name);
    if ($name==='') return false;
    $stmt = $pdo->prepare("INSERT INTO groups (name) VALUES (:n)");
    return $stmt->execute([':n'=>$name]);
  }
  public static function delete(int $id): bool {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM groups WHERE id=:id");
    return $stmt->execute([':id'=>$id]);
  }
}
