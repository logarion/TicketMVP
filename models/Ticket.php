<?php
class Ticket {

  /* ---------- CRUD / getters ---------- */

  public static function getById(int $id): ?array {
    global $pdo;
    $stmt = $pdo->prepare("
      SELECT t.*,
             u.username  AS username,              -- creator alias kept for view compat
             au.username AS assigned_username,
             d.name      AS department_name,
             ru.username AS requester_username,
             ru.email    AS requester_user_email
      FROM tickets t
      LEFT JOIN users u  ON u.id  = t.user_id
      LEFT JOIN users au ON au.id = t.assigned_to
      LEFT JOIN departments d ON d.id = t.department_id
      LEFT JOIN users ru ON ru.id = t.requester_user_id
      WHERE t.id = :id
      LIMIT 1
    ");
    $stmt->execute([':id'=>$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public static function store(int $userId, array $data): int {
    global $pdo;
    $title    = trim($data['title'] ?? '');
    $desc     = trim($data['description'] ?? '');
    $priority = in_array(($data['priority'] ?? 'Normal'), ['Normal','Urgent']) ? $data['priority'] : 'Normal';
    $assigned = isset($data['assigned_to']) && $data['assigned_to'] !== '' ? (int)$data['assigned_to'] : null;

    if ($title === '') return 0;

    // Resolve requester from unified input ('requester' can be email or username)
    $reqInput = (string)($data['requester'] ?? '');
    [$reqUserId, $reqEmail] = self::resolveRequesterPair($reqInput);

    // derive creator's department
    $deptId = null;
    if ($userId) {
      $st = $pdo->prepare("SELECT department_id FROM users WHERE id=:id");
      $st->execute([':id'=>$userId]);
      $r = $st->fetch();
      $deptId = $r['department_id'] ?? null;
    }

    $stmt = $pdo->prepare("
      INSERT INTO tickets
        (user_id, assigned_to, department_id, title, description, requester_user_id, requester_email, priority, status)
      VALUES
        (:uid,    :assigned,   :dept,         :t,    :d,          :ruid,            :remail,          :p,       'New')
    ");
    $stmt->execute([
      ':uid'=>$userId ?: null,
      ':assigned'=>$assigned,
      ':dept'=>$deptId,
      ':t'=>$title, ':d'=>$desc,
      ':ruid'=>$reqUserId, ':remail'=>$reqEmail,
      ':p'=>$priority
    ]);
    return (int)$pdo->lastInsertId();
  }

// inside Ticket class

public static function update(int $id, array $data): bool {
  global $pdo;
  $assigned = (isset($data['assigned_to']) && $data['assigned_to'] !== '') ? (int)$data['assigned_to'] : null;

  // requester (optional)
  $setReq = '';
  $params = [];
  if (array_key_exists('requester', $data)) {
    [$reqUserId, $reqEmail] = self::resolveRequesterPair((string)$data['requester'] ?? '');
    $setReq = ", requester_user_id=:ruid, requester_email=:remail";
    $params[':ruid'] = $reqUserId;
    $params[':remail'] = $reqEmail;
  }

  // status (optional)
  $setStatus = '';
  if (array_key_exists('status', $data)) {
    $allowed = ['New','In Progress','Resolved','Closed'];
    $status = in_array($data['status'], $allowed, true) ? $data['status'] : 'New';
    $setStatus = ", status=:status";
    $params[':status'] = $status;
  }

  $sql = "
    UPDATE tickets
    SET title=:t, description=:d, priority=:p, assigned_to=:a
        $setReq
        $setStatus
    WHERE id=:id
  ";
  $stmt = $pdo->prepare($sql);
  return $stmt->execute($params + [
    ':t'=>trim($data['title'] ?? ''),
    ':d'=>trim($data['description'] ?? ''),
    ':p'=>in_array(($data['priority'] ?? 'Normal'), ['Normal','Urgent']) ? $data['priority'] : 'Normal',
    ':a'=>$assigned,
    ':id'=>$id
  ]);
}

public static function setStatus(int $id, string $status): bool {
  global $pdo;
  $allowed = ['New','In Progress','Resolved','Closed'];
  if (!in_array($status, $allowed, true)) $status = 'New';
  $st = $pdo->prepare("UPDATE tickets SET status=:s WHERE id=:id");
  return $st->execute([':s'=>$status, ':id'=>$id]);
}


  public static function assignTo(int $id, ?int $userId): bool {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE tickets SET assigned_to = :a WHERE id = :id");
    return $stmt->execute([':a'=>$userId ?: null, ':id'=>$id]);
  }

  /* ---------- Search + pagination + permissions ---------- */

  /**
   * @param string $q
   * @param int $page
   * @param int $perPage
   * @param array $filters keys: department_id, group_id, assigned_to, priority, status
   * @param array $currentUser keys: id, role, department_id
   * @return array{rows:array,total:int,page:int,perPage:int,q:string}
   */
  public static function searchPaginated(string $q, int $page, int $perPage, array $filters = [], array $currentUser = []): array {
    global $pdo;

    $page    = max(1, (int)$page);
    $perPage = min(100, max(1, (int)$perPage));
    $offset  = ($page - 1) * $perPage;

    $q = trim((string)$q);
    $where  = [];
    $params = [];

    // SEARCH (LIKE; switch to FT if you added it)
    if ($q !== '') {
      $where[] = "(t.title LIKE :ql OR t.description LIKE :ql OR t.requester_email LIKE :ql OR ru.username LIKE :ql)";
      $params[':ql'] = '%'.$q.'%';
    }

    // FILTERS
    if (!empty($filters['department_id'])) {
      $where[] = "t.department_id = :f_dept";
      $params[':f_dept'] = (int)$filters['department_id'];
    }
    if (!empty($filters['group_id'])) {
      $where[] = "EXISTS (SELECT 1 FROM user_groups ug WHERE ug.user_id = t.user_id AND ug.group_id = :f_gid)";
      $params[':f_gid'] = (int)$filters['group_id'];
    }
    if (!empty($filters['assigned_to'])) {
      $where[] = "t.assigned_to = :f_assigned";
      $params[':f_assigned'] = (int)$filters['assigned_to'];
    }
    if (!empty($filters['priority'])) {
      $where[] = "t.priority = :f_priority";
      $params[':f_priority'] = $filters['priority'];
    }
    if (!empty($filters['status'])) {
      $where[] = "t.status = :f_status";
      $params[':f_status'] = $filters['status'];
    }

    // PERMISSIONS for non-admins:
    if (($currentUser['role'] ?? 'user') !== 'admin') {
      $uid = (int)($currentUser['id'] ?? 0);
      $deptId = $currentUser['department_id'] ?? null;

      $clauses = [
        "t.user_id = :perm_uid",
        "t.assigned_to = :perm_uid",
      ];
      $params[':perm_uid'] = $uid;

      if ($deptId) {
        $clauses[] = "t.department_id = :perm_dept";
        $params[':perm_dept'] = (int)$deptId;
      }

      // share any group with creator
      $clauses[] = "EXISTS (
        SELECT 1
        FROM user_groups ug_me
        JOIN user_groups ug_owner ON ug_owner.user_id = t.user_id AND ug_owner.group_id = ug_me.group_id
        WHERE ug_me.user_id = :perm_uid
      )";

      $where[] = '(' . implode(' OR ', $clauses) . ')';
    }

    $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

    // COUNT
    $countSql = "
      SELECT COUNT(*)
      FROM tickets t
      LEFT JOIN users ru ON ru.id = t.requester_user_id
      $whereSql
    ";
    $stmtC = $pdo->prepare($countSql);
    $stmtC->execute($params);
    $total = (int)$stmtC->fetchColumn();

    // ROWS
    $sql = "
      SELECT t.*,
             u.username  AS username,
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
      LIMIT :limit OFFSET :offset
    ";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
    $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return ['rows'=>$rows,'total'=>$total,'page'=>$page,'perPage'=>$perPage,'q'=>$q];
  }

  /* ---------- helpers ---------- */

  // Resolve 'requester' input (email or username) to [user_id, email]
  private static function resolveRequesterPair(string $input): array {
    global $pdo;
    $input = trim($input);
    if ($input === '') return [null, null];

    if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
      $s = $pdo->prepare("SELECT id FROM users WHERE email=:e LIMIT 1");
      $s->execute([':e'=>$input]);
      $uid = ($r = $s->fetch()) ? (int)$r['id'] : null;
      return [$uid, $input];
    }

    $s = $pdo->prepare("SELECT id, email FROM users WHERE username=:u LIMIT 1");
    $s->execute([':u'=>$input]);
    if ($r = $s->fetch()) {
      return [(int)$r['id'], $r['email'] ?? null];
    }

    return [null, null];
  }
}
