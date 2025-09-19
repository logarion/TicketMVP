<?php
// models/Ticket.php
require_once __DIR__ . '/DB.php';
class Ticket {
    private $db;
    public function __construct() {
        $this->db = DB::getInstance();
    }

    public function create($subject, $description, $created_by = null) {
        $sql = "INSERT INTO tickets (subject, description, created_by) VALUES (:subject, :description, :created_by)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':subject' => $subject,
            ':description' => $description,
            ':created_by' => $created_by
        ]);
        return $this->db->lastInsertId();
    }

    public function getById($id) {
        $sql = "SELECT t.*, u.email as creator_email, u.name as creator_name
                FROM tickets t
                LEFT JOIN users u ON u.id = t.created_by
                WHERE t.id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id'=>$id]);
        return $stmt->fetch();
    }

    public function searchList($q = null, $status = null, $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];

        if ($q) {
            // use fulltext if available, fallback to LIKE
            $where[] = "(MATCH(subject, description) AGAINST (:q IN NATURAL LANGUAGE MODE) OR subject LIKE :likeq OR description LIKE :likeq)";
            $params[':q'] = $q;
            $params[':likeq'] = "%$q%";
        }
        if ($status) {
            $where[] = "status = :status";
            $params[':status'] = $status;
        }

        $whereSql = count($where) ? "WHERE " . implode(' AND ', $where) : "";

        // total
        $countSql = "SELECT COUNT(*) FROM tickets $whereSql";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // list
        $sql = "SELECT t.*, u.email as creator_email
                FROM tickets t
                LEFT JOIN users u ON u.id = t.created_by
                $whereSql
                ORDER BY created_at DESC
                LIMIT :offset, :limit";
        $stmt = $this->db->prepare($sql);

        // bind dynamic params then offset/limit
        foreach ($params as $k=>$v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        return ['total'=>$total, 'rows'=>$rows, 'page'=>$page, 'per_page'=>$perPage];
    }

    public function updateStatus($id, $status) {
        $sql = "UPDATE tickets SET status = :status WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':status'=>$status, ':id'=>$id]);
    }
}
