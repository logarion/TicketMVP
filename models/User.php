<?php
class User {
  public static function findByUsername($username) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :u LIMIT 1");
    $stmt->execute([':u'=>$username]);
    return $stmt->fetch();
  }

  public static function create($username, $email, $password) {
    global $pdo;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6 || $username==='') return false;
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (:u,:e,:p)");
    return $stmt->execute([':u'=>$username, ':e'=>$email, ':p'=>$hash]);
  }

  // --- Admin-side helpers ---
  public static function listAll(): array {
    global $pdo;
    $sql = "SELECT u.*, d.name AS department_name
            FROM users u
            LEFT JOIN departments d ON d.id = u.department_id
            ORDER BY u.username";
    return $pdo->query($sql)->fetchAll();
  }

  public static function getById(int $id): ?array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=:id");
    $stmt->execute([':id'=>$id]);
    $row = $stmt->fetch();
    return $row ?: null;
  }

  public static function createAdmin(array $data): bool {
    global $pdo;
    $username = trim($data['username'] ?? '');
    $email    = trim($data['email'] ?? '');
    $role     = in_array(($data['role'] ?? 'user'), ['user','admin']) ? $data['role'] : 'user';
    $deptId   = !empty($data['department_id']) ? (int)$data['department_id'] : null;
    $pwd      = trim($data['password'] ?? '');

    if ($username==='' || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($pwd) < 6) return false;

    $stmt = $pdo->prepare("INSERT INTO users (username,email,password_hash,role,department_id)
                           VALUES (:u,:e,:p,:r,:d)");
    return $stmt->execute([
      ':u'=>$username, ':e'=>$email,
      ':p'=>password_hash($pwd, PASSWORD_DEFAULT),
      ':r'=>$role, ':d'=>$deptId
    ]);
  }

  public static function updateAdmin(int $id, array $data): bool {
    global $pdo;
    $username = trim($data['username'] ?? '');
    $email    = trim($data['email'] ?? '');
    $role     = in_array(($data['role'] ?? 'user'), ['user','admin']) ? $data['role'] : 'user';
    $deptId   = !empty($data['department_id']) ? (int)$data['department_id'] : null;

    if ($username==='' || !filter_var($email,FILTER_VALIDATE_EMAIL)) return false;

    $stmt = $pdo->prepare("UPDATE users
                           SET username=:u, email=:e, role=:r, department_id=:d
                           WHERE id=:id");
    return $stmt->execute([':u'=>$username, ':e'=>$email, ':r'=>$role, ':d'=>$deptId, ':id'=>$id]);
  }

  public static function delete(int $id): bool {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM users WHERE id=:id");
    return $stmt->execute([':id'=>$id]);
  }

  public static function setPassword(int $id, string $newPassword): bool {
    global $pdo;
    if (strlen($newPassword) < 6) return false;
    $stmt = $pdo->prepare("UPDATE users SET password_hash=:p WHERE id=:id");
    return $stmt->execute([':p'=>password_hash($newPassword, PASSWORD_DEFAULT), ':id'=>$id]);
  }

  public static function setGroups(int $userId, array $groupIds): void {
    global $pdo;
    $pdo->beginTransaction();
    $pdo->prepare("DELETE FROM user_groups WHERE user_id=:u")->execute([':u'=>$userId]);
    $ins = $pdo->prepare("INSERT INTO user_groups (user_id, group_id) VALUES (:u,:g)");
    foreach ($groupIds as $gid) {
      $gid = (int)$gid;
      if ($gid > 0) $ins->execute([':u'=>$userId, ':g'=>$gid]);
    }
    $pdo->commit();
  }

  public static function groupsOf(int $userId): array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT g.* FROM groups g JOIN user_groups ug ON ug.group_id=g.id WHERE ug.user_id=:u ORDER BY g.name");
    $stmt->execute([':u'=>$userId]);
    return $stmt->fetchAll();
  }
}
