<?php
// controllers/ExportController.php
require_once __DIR__ . '/../models/DB.php';

class ExportController {
    private $pdo;
    public function __construct() { $this->pdo = DB::getInstance(); }

    public function exportTicketsCSV() {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="tickets_export_'.date('Ymd_His').'.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['id','subject','description','status','created_by','created_at']);
        $stmt = $this->pdo->query("SELECT id, subject, description, status, created_by, created_at FROM tickets ORDER BY created_at DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($out, [$row['id'],$row['subject'],$row['description'],$row['status'],$row['created_by'],$row['created_at']]);
        }
        fclose($out);
        exit;
    }
}
