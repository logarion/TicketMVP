<?php
class Ticket {
  public static function getAll() {
    // kept for backward-compat; prefer searchPaginated going forward
    return self::searchPaginated('', 1, 20)['rows'];
  }

  public static function getById(int $id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT t.*, u.username
                           FROM tickets t
                           LEFT JOIN users u ON u.id = t.user_id
                           WHERE t.id = :id LIMIT 1");
    $stmt->execute([':id'=>$id]);
    return $stmt->fetch();
  }

  public static function store(int $userId, array $data): int {
    global $pdo;
    $title    = trim($data['title'] ?? '');
    $desc     = trim($data['description'] ?? '');
    $priority = in_array(($data['priority'] ?? 'Normal'), ['Normal','Urgent']) ? $data['priority'] : 'Normal';
    $requester= trim($data['requester_email'] ?? '');

    if ($title === '') return 0;

    $stmt = $pdo->prepare(
      "INSERT INTO tickets (user_id, title, description, requester_email, priority)
       VALUES (:uid, :t, :d, :r, :p)"
    );
    $stmt->execute([
      ':uid' => $userId ?: null,
      ':t'   => $title,
      ':d'   => $desc,
      ':r'   => ($requester !== '' ? $requester : null),
      ':p'   => $priority
    ]);
    return (int)$pdo->lastInsertId();
  }

  public static function update(int $id, array $data): bool {
    global $pdo;
    $stmt = $pdo->prepare(
      "UPDATE tickets
       SET title=:t, description=:d, requester_email=:r, priority=:p
       WHERE id=:id"
    );
    return $stmt->execute([
      ':t'  => trim($data['title'] ?? ''),
      ':d'  => trim($data['description'] ?? ''),
      ':r'  => trim($data['requester_email'] ?? '') ?: null,
      ':p'  => in_array(($data['priority'] ?? 'Normal'), ['Normal','Urgent']) ? $data['priority'] : 'Normal',
      ':id' => $id
    ]);
  }

  /**
   * Search + pagination for tickets.
   * @return array{rows: array<int,array>, total: int, page: int, perPage: int, q: string}
   */
  public static function searchPaginated(string $q, int $page=1, int $perPage=20): array {
    global $pdo;

    $page    = max(1, (int)$page);
    $perPage = min(100, max(1, (int)$perPage));
    $offset  = ($page - 1) * $perPage;

    $params = [];
    $where  = [];

    if ($q !== '') {
      // Prefer FULLTEXT if available; fall back to LIKE
      $hasFT = self::hasFulltext('tickets', ['title','description']);
      if ($hasFT) {
        $where[] = "MATCH(t.title, t.description) AGAINST (:q IN BOOLEAN MODE)";
        $params[':q'] = self::toBooleanModeQuery($q);
      } else {
        $where[] = "(t.title LIKE :ql OR t.description LIKE :ql)";
        $params[':ql'] = '%'.$q.'%';
      }
    }

    $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

    // total
    $countSql = "SELECT COUNT(*) AS c FROM tickets t $whereSql";
    $stmtC = $pdo->prepare($countSql);
    $stmtC->execute($params);
    $total = (int)$stmtC->fetchColumn();

    // rows
    $sql = "SELECT t.*, u.username
            FROM tickets t
            LEFT JOIN users u ON u.id = t.user_id
            $whereSql
            ORDER BY t.created_at DESC
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
    $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    return ['rows'=>$rows, 'total'=>$total, 'page'=>$page, 'perPage'=>$perPage, 'q'=>$q];
  }

  // ---------- helpers ----------
  private static function hasFulltext(string $table, array $cols): bool {
    // Simple optimistic check; you can hardcode true if you created the FT index
    return true;
  }

  private static function toBooleanModeQuery(string $q): string {
    // Turn "reset password urgent" -> "+reset +password +urgent*"
    $terms = preg_split('/\s+/', trim($q));
    $terms = array_filter(array_map('trim', $terms));
    if (!$terms) return $q;
    return implode(' ', array_map(fn($t)=>'+'.preg_replace('/[^\p{L}\p{N}\*]+/u','',$t).'*', $terms));
  }
}
