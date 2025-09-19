<?php
class Attachment {
  // Allowed extensions + max size (20MB per file)
  private static array $allowed = ['png','jpg','jpeg','gif','pdf','doc','docx','xls','xlsx','csv','txt','zip'];
  private static int $maxBytes = 20 * 1024 * 1024;

  public static function listByTicket(int $ticketId): array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM attachments WHERE ticket_id = :t ORDER BY uploaded_at DESC");
    $stmt->execute([':t'=>$ticketId]);
    return $stmt->fetchAll();
  }

  public static function getById(int $id): ?array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM attachments WHERE id = :id LIMIT 1");
    $stmt->execute([':id'=>$id]);
    $row = $stmt->fetch();
    return $row ?: null;
  }

  public static function saveFiles(int $ticketId, ?array $files): void {
    if (!$files || empty($files['name'])) return;

    // Normalize multiple files array
    $count = is_array($files['name']) ? count($files['name']) : 0;
    for ($i = 0; $i < $count; $i++) {
      $one = [
        'name'     => $files['name'][$i],
        'type'     => $files['type'][$i],
        'tmp_name' => $files['tmp_name'][$i],
        'error'    => $files['error'][$i],
        'size'     => $files['size'][$i],
      ];
      try {
        self::saveOne($ticketId, $one);
      } catch (Throwable $e) {
        error_log("Attachment upload failed: " . $e->getMessage());
        // keep going with other files
      }
    }
  }

  private static function saveOne(int $ticketId, array $file): void {
    if ($file['error'] !== UPLOAD_ERR_OK) {
      throw new RuntimeException("Upload error code: " . $file['error']);
    }
    if ($file['size'] > self::$maxBytes) {
      throw new RuntimeException("File too large");
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, self::$allowed, true)) {
      throw new RuntimeException("Extension not allowed: " . $ext);
    }

    // Sanitize original name
    $orig = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file['name']);
    // Unique stored name
    $stored = bin2hex(random_bytes(8)) . '_' . $orig;
    $dest = rtrim(UPLOAD_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $stored;

    if (!is_uploaded_file($file['tmp_name']) || !move_uploaded_file($file['tmp_name'], $dest)) {
      throw new RuntimeException("Failed to move uploaded file");
    }

    $mime = function_exists('mime_content_type') ? mime_content_type($dest) : ($file['type'] ?? null);
    $size = filesize($dest);

    global $pdo;
    $stmt = $pdo->prepare("
      INSERT INTO attachments (ticket_id, original_name, stored_name, mime_type, size_bytes)
      VALUES (:t, :o, :s, :m, :z)
    ");
    $stmt->execute([
      ':t' => $ticketId,
      ':o' => $orig,
      ':s' => $stored,
      ':m' => $mime,
      ':z' => $size,
    ]);
  }

  public static function download(int $attachmentId): void {
    $att = self::getById($attachmentId);
    if (!$att) { http_response_code(404); echo "Not found"; exit; }

    $path = rtrim(UPLOAD_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $att['stored_name'];
    if (!is_file($path) || !is_readable($path)) { http_response_code(404); echo "File missing"; exit; }

    // Send safe download headers
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($att['original_name']).'"');
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');

    // Stream the file
    readfile($path);
    exit;
  }

  public static function saveFromPath(int $ticketId, string $originalName, string $absolutePath): void {
    $orig = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $originalName);
    $stored = bin2hex(random_bytes(8)) . '_' . $orig;
    $dest = rtrim(UPLOAD_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $stored;
  
    if (!copy($absolutePath, $dest)) {
      throw new RuntimeException("Failed to copy attachment from path");
    }
    $mime = function_exists('mime_content_type') ? mime_content_type($dest) : null;
    $size = filesize($dest);
  
    global $pdo;
    $stmt = $pdo->prepare("
      INSERT INTO attachments (ticket_id, original_name, stored_name, mime_type, size_bytes)
      VALUES (:t, :o, :s, :m, :z)
    ");
    $stmt->execute([':t'=>$ticketId, ':o'=>$orig, ':s'=>$stored, ':m'=>$mime, ':z'=>$size]);
  }  

}
