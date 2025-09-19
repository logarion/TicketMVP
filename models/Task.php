<?php
class Task {
  public static function create(array $data): int {
    global $pdo;
    $stmt = $pdo->prepare("
      INSERT INTO tasks (project_id, title, description, status, assigned_to, due_date)
      VALUES (:pid, :title, :desc, :status, :assigned, :due)
    ");
    $stmt->execute([
      ':pid'      => (int)$data['project_id'],
      ':title'    => trim($data['title']),
      ':desc'     => trim($data['description'] ?? ''),
      ':status'   => in_array(($data['status'] ?? 'Pending'), ['Pending','In Progress','Done']) ? $data['status'] : 'Pending',
      ':assigned' => !empty($data['assigned_to']) ? (int)$data['assigned_to'] : null,
      ':due'      => !empty($data['due_date']) ? $data['due_date'] : null,
    ]);
    return (int)$pdo->lastInsertId();
  }

  public static function listByProject(int $project_id): array {
    global $pdo;
    $stmt = $pdo->prepare("
      SELECT t.*, u.username AS assigned_username
      FROM tasks t
      LEFT JOIN users u ON u.id = t.assigned_to
      WHERE t.project_id = :pid
      ORDER BY COALESCE(due_date, '9999-12-31') ASC, id ASC
    ");
    $stmt->execute([':pid'=>$project_id]);
    return $stmt->fetchAll();
  }

  public static function get(int $id): ?array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = :id LIMIT 1");
    $stmt->execute([':id'=>$id]);
    $row = $stmt->fetch();
    return $row ?: null;
  }

  public static function updateStatus(int $id, string $status): bool {
    global $pdo;
    if (!in_array($status, ['Pending','In Progress','Done'])) return false;
    $stmt = $pdo->prepare("UPDATE tasks SET status = :s WHERE id = :id");
    return $stmt->execute([':s'=>$status, ':id'=>$id]);
  }

  public static function update(int $id, array $data): bool {
    global $pdo;
    $assigned = (isset($data['assigned_to']) && $data['assigned_to'] !== '')
                  ? (int)$data['assigned_to']
                  : null;
  
    $stmt = $pdo->prepare("
      UPDATE tasks
      SET title=:title, description=:desc, status=:status, assigned_to=:assigned, due_date=:due
      WHERE id=:id
    ");
    return $stmt->execute([
      ':title'    => trim($data['title']),
      ':desc'     => trim($data['description'] ?? ''),
      ':status'   => in_array(($data['status'] ?? 'Pending'), ['Pending','In Progress','Done']) ? $data['status'] : 'Pending',
      ':assigned' => $assigned,
      ':due'      => !empty($data['due_date']) ? $data['due_date'] : null,
      ':id'       => $id
    ]);
  }
  
}
