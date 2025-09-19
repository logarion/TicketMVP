<?php
class Project {
  public static function createFromTicket(int $ticketId): int {
    global $pdo;

    // Already has a project?
    $stmt = $pdo->prepare("SELECT id FROM projects WHERE ticket_id = :tid LIMIT 1");
    $stmt->execute([':tid'=>$ticketId]);
    if ($row = $stmt->fetch()) return (int)$row['id'];

    // Get ticket
    $t = $pdo->prepare("SELECT * FROM tickets WHERE id = :id LIMIT 1");
    $t->execute([':id'=>$ticketId]);
    $ticket = $t->fetch();
    if (!$ticket) return 0;

    $name = 'Project: ' . mb_substr($ticket['title'], 0, 120);
    $desc = $ticket['description'] ?? null;

    $ins = $pdo->prepare("INSERT INTO projects (ticket_id, name, description) VALUES (:tid, :n, :d)");
    $ins->execute([':tid'=>$ticketId, ':n'=>$name, ':d'=>$desc]);
    return (int)$pdo->lastInsertId();
  }

  public static function getByTicketId(int $ticketId): ?array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE ticket_id = :tid LIMIT 1");
    $stmt->execute([':tid'=>$ticketId]);
    $row = $stmt->fetch();
    return $row ?: null;
  }

  public static function getById(int $id): ?array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT p.*, t.title AS ticket_title FROM projects p JOIN tickets t ON t.id=p.ticket_id WHERE p.id=:id LIMIT 1");
    $stmt->execute([':id'=>$id]);
    $row = $stmt->fetch();
    return $row ?: null;
  }

  public static function getAll(): array {
    global $pdo;
    $sql = "SELECT p.*, t.title AS ticket_title FROM projects p JOIN tickets t ON t.id=p.ticket_id ORDER BY p.created_at DESC";
    return $pdo->query($sql)->fetchAll();
  }

  public static function searchPaginated(string $q, int $page=1, int $perPage=20): array {
    global $pdo;
    $page    = max(1,(int)$page);
    $perPage = min(100, max(1,(int)$perPage));
    $offset  = ($page-1)*$perPage;

    $params = [];
    $where  = [];
    if ($q !== '') {
      $where[] = "(p.name LIKE :ql OR p.description LIKE :ql)";
      $params[':ql'] = '%'.$q.'%'; // switch to MATCH() if you created FT index
    }
    $whereSql = $where ? 'WHERE '.implode(' AND ', $where) : '';

    $stmtC = $pdo->prepare("SELECT COUNT(*) FROM projects p $whereSql");
    $stmtC->execute($params);
    $total = (int)$stmtC->fetchColumn();

    $sql = "SELECT p.*, t.title AS ticket_title
            FROM projects p
            JOIN tickets t ON t.id=p.ticket_id
            $whereSql
            ORDER BY p.created_at DESC
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k=>$v) { $stmt->bindValue($k,$v); }
    $stmt->bindValue(':limit',$perPage,PDO::PARAM_INT);
    $stmt->bindValue(':offset',$offset,PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    return ['rows'=>$rows, 'total'=>$total, 'page'=>$page, 'perPage'=>$perPage, 'q'=>$q];
  }
  
}
