<?php
class Ticket {
  public static function getAll() {
    // kept for backward-compat; prefer searchPaginated going forward
    return self::searchPaginated('', 1, 20)['rows'];
  }

  public static function getById(int $id): ?array {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT t.*,
               u.username AS created_by_username,
               au.username AS assigned_username,
               d.name AS department_name,
               ru.username AS requester_username,
               ru.email   AS requester_user_email
        FROM tickets t
        LEFT JOIN users u  ON u.id  = t.user_id
        LEFT JOIN users au ON au.id = t.assigned_to
        LEFT JOIN departments d ON d.id = t.department_id
        LEFT JOIN users ru ON ru.id = t.requester_user_id
        WHERE t.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    return $ticket ?: null;
  }

  public static function store(int $userId, array $data): int {
    global $pdo;
    $title    = trim($data['title'] ?? '');
    $desc     = trim($data['description'] ?? '');
    $priority = in_array(($data['priority'] ?? 'Normal'), ['Normal','Urgent']) ? $data['priority'] : 'Normal';
    $requester= trim($data['requester_email'] ?? '');
    $assigned = !empty($data['assigned_to']) ? (int)$data['assigned_to'] : null;

    if ($title === '') return 0;
  // Resolve requester from 'requester' input (email or username)
  $reqInput = (string)($data['requester'] ?? '');
  $resolved = self::resolveRequester($reqInput);
  $reqUserId = $resolved['user_id'];
  $reqEmail  = $resolved['email'];

  // derive creator's department
    $deptId = null;
    if ($userId) {
      $st = $pdo->prepare("SELECT department_id FROM users WHERE id=:id");
      $st->execute([':id'=>$userId]);
      $r = $st->fetch();
      $deptId = $r['department_id'] ?? null;
    }
  
    $stmt = $pdo->prepare(
      "INSERT INTO tickets (user_id, assigned_to, department_id, title, description, requester_email, priority)
       VALUES (:uid,:a,:d,:t,:desc,:r,:p)"
    );
    $stmt->execute([
      ':uid'=>$userId ?: null, ':a'=>$assigned, ':d'=>$deptId,
      ':t'=>$title, ':desc'=>$desc, ':r'=>($requester !== '' ? $requester : null), ':p'=>$priority
    ]);
    return (int)$pdo->lastInsertId();
  }

  public static function update(int $id, array $data): bool {
    global $pdo;
    $assigned = (isset($data['assigned_to']) && $data['assigned_to'] !== '') ? (int)$data['assigned_to'] : null;
  
    // Resolve requester again if the field is present
    $reqUserId = null; $reqEmail = null;
    if (array_key_exists('requester', $data)) {
      $resolved = self::resolveRequester((string)$data['requester']);
      $reqUserId = $resolved['user_id'];
      $reqEmail  = $resolved['email'];
    }
  
    $sql = "
      UPDATE tickets
      SET title=:t, description=:d, priority=:p, assigned_to=:a
          ".(array_key_exists('requester',$data) ? ", requester_user_id=:ruid, requester_email=:remail" : "")."
      WHERE id=:id
    ";
    $stmt = $pdo->prepare($sql);
    $params = [
      ':t'=>trim($data['title'] ?? ''),
      ':d'=>trim($data['description'] ?? ''),
      ':p'=>in_array(($data['priority'] ?? 'Normal'), ['Normal','Urgent']) ? $data['priority'] : 'Normal',
      ':a'=>$assigned,
      ':id'=>$id
    ];
    if (array_key_exists('requester',$data)) {
      $params[':ruid']  = $reqUserId;
      $params[':remail'] = $reqEmail;
    }
    return $stmt->execute($params);
  }
    public static function assignTo(int $id, ?int $userId): bool {
      global $pdo;
      $stmt = $pdo->prepare("UPDATE tickets SET assigned_to = :a WHERE id = :id");
      return $stmt->execute([':a'=>$userId ?: null, ':id'=>$id]);
    }
  

  /**
   * Search + pagination for tickets.
   * @return array{rows: array<int,array>, total: int, page: int, perPage: int, q: string}
   */
  // models/Ticket.php
  public static function searchPaginated(string $q, int $page, int $perPage, array $filters = [], array $currentUser = []): array {
    global $pdo;

    $where = [];
    $params = [];

    // Basic search
    if ($q !== '') {
        $where[] = "(t.title LIKE :ql OR t.description LIKE :ql OR t.requester_email LIKE :ql OR ru.username LIKE :ql)";
        $params[':ql'] = "%$q%";
    }

    // Department filter
    if (!empty($filters['department_id'])) {
        $where[] = "t.department_id = :dept";
        $params[':dept'] = (int)$filters['department_id'];
    }

    // Assigned_to filter
    if (!empty($filters['assigned_to'])) {
        $where[] = "t.assigned_to = :assignee";
        $params[':assignee'] = (int)$filters['assigned_to'];
    }

    // Other filters if present
    if (!empty($filters['priority'])) {
        $where[] = "t.priority = :priority";
        $params[':priority'] = $filters['priority'];
    }
    if (!empty($filters['status'])) {
        $where[] = "t.status = :status";
        $params[':status'] = $filters['status'];
    }

    // Group permissions: if not admin, restrict by department
    if (($currentUser['role'] ?? '') !== 'admin') {
        if (!empty($currentUser['department_id'])) {
            $where[] = "(t.department_id = :userDept OR t.user_id = :uid)";
            $params[':userDept'] = $currentUser['department_id'];
            $params[':uid'] = $currentUser['id'] ?? 0;
        } else {
            $where[] = "t.user_id = :uid";
            $params[':uid'] = $currentUser['id'] ?? 0;
        }
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Count total
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM tickets t
        LEFT JOIN users u  ON u.id  = t.user_id
        LEFT JOIN users au ON au.id = t.assigned_to
        LEFT JOIN departments d ON d.id = t.department_id
        LEFT JOIN users ru ON ru.id = t.requester_user_id
        $whereSql");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Pagination calc
    $page = max(1, $page);
    $offset = ($page - 1) * $perPage;

    // Fetch paginated rows
    $stmt = $pdo->prepare("SELECT t.*,
               u.username  AS created_by_username,
               au.username AS assigned_username,
               d.name      AS department_name,
               ru.username AS requester_username,
               ru.email    AS requester_user_email
        FROM tickets t
        LEFT JOIN users u  ON u.id  = t.user_id
        LEFT JOIN users au ON au.id = t.assigned_to
        LEFT JOIN departments d ON d.id = t.department_id
        LEFT JOIN users ru ON ru.id = t.requester_user_id
        $whereSql
        ORDER BY t.created_at DESC
        LIMIT :limit OFFSET :offset");

    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'rows'    => $rows,
        'total'   => $total,
        'page'    => $page,
        'perPage' => $perPage,
        'q'       => $q
    ];
}



private static function resolveRequester(string $input): array {
  // Returns ['user_id' => ?int, 'email' => ?string]
  global $pdo;
  $input = trim($input);

  if ($input === '') return ['user_id'=>null, 'email'=>null];

  // If it looks like an email, accept as-is (and map to user if found)
  if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :e LIMIT 1");
    $stmt->execute([':e'=>$input]);
    $uid = ($row = $stmt->fetch()) ? (int)$row['id'] : null;
    return ['user_id'=>$uid, 'email'=>$input];
  }

  // Otherwise treat as username
  $stmt = $pdo->prepare("SELECT id, email FROM users WHERE username = :u LIMIT 1");
  $stmt->execute([':u'=>$input]);
  if ($row = $stmt->fetch()) {
    return ['user_id'=>(int)$row['id'], 'email'=>$row['email'] ?? null];
  }

  // Unknown username -> keep as-is? We’ll store nothing unless it’s a valid email.
  return ['user_id'=>null, 'email'=>null];
}
}