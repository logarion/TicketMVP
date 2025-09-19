<?php
// models/Attachment.php
class Attachment {
  public static function listByTicket(int $ticketId): array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM attachments WHERE ticket_id=:t ORDER BY id DESC");
    $stmt->execute([':t'=>$ticketId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function download(int $id): void {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM attachments WHERE id=:id LIMIT 1");
    $stmt->execute([':id'=>$id]);
    $a = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$a) { http_response_code(404); echo 'Attachment record not found'; exit; }

    $path = rtrim(UPLOAD_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $a['stored_name'];
    if (!is_file($path)) { http_response_code(404); echo 'File not found at: ' . htmlspecialchars($path); exit; }

    header('Content-Description: File Transfer');
    header('Content-Type: ' . ($a['mime_type'] ?: 'application/octet-stream'));
    header('Content-Disposition: attachment; filename="' . $a['original_name'] . '"');
    header('Content-Length: ' . (string)$a['size_bytes']);
    readfile($path);
    exit;
  }

  // === The method your route is calling ===
  public static function uploadFromForm(int $ticketId, array $file): int {
    if (!isset($file['error']) || is_array($file['error'])) {
      throw new RuntimeException('Invalid file parameters');
    }

    switch ($file['error']) {
      case UPLOAD_ERR_OK: break;
      case UPLOAD_ERR_NO_FILE: throw new RuntimeException('No file uploaded');
      case UPLOAD_ERR_INI_SIZE:
      case UPLOAD_ERR_FORM_SIZE: throw new RuntimeException('File too large (PHP limit)');
      default: throw new RuntimeException('Upload error code: ' . $file['error']);
    }

    $size = (int)($file['size'] ?? 0);
    if ($size <= 0) throw new RuntimeException('Empty upload');
    if ($size > 50 * 1024 * 1024) throw new RuntimeException('File exceeds 50MB');

    // Clean original filename
    $original = $file['name'] ?? 'file';
    $original = preg_replace('/[^\w\.\-\s]/u', '_', $original);

    // Generate stored filename
    $stored = bin2hex(random_bytes(8)) . '_' . $original;
    $dest   = rtrim(UPLOAD_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $stored;

    if (!is_uploaded_file($file['tmp_name'])) {
      throw new RuntimeException('Not an uploaded file');
    }
    if (!@move_uploaded_file($file['tmp_name'], $dest)) {
      throw new RuntimeException('Failed to move file (permissions?) to ' . $dest);
    }
    if (!is_file($dest)) {
      throw new RuntimeException('Moved, but file missing at ' . $dest);
    }

    // Detect mime
    $mime = null;
    if (function_exists('finfo_open')) {
      $f = finfo_open(FILEINFO_MIME_TYPE);
      $mime = finfo_file($f, $dest);
      finfo_close($f);
    } elseif (function_exists('mime_content_type')) {
      $mime = mime_content_type($dest);
    }

    // Persist row
    global $pdo;
    $stmt = $pdo->prepare("
      INSERT INTO attachments (ticket_id, original_name, stored_name, mime_type, size_bytes)
      VALUES (:t, :o, :s, :m, :z)
    ");
    $stmt->execute([
      ':t'=>$ticketId,
      ':o'=>$original,
      ':s'=>$stored,
      ':m'=>$mime,
      ':z'=>$size
    ]);

    return (int)$pdo->lastInsertId();
  }

  // Optional helper if importing from disk (e.g., IMAP)
  public static function saveFromPath(int $ticketId, string $originalName, string $absolutePath): void {
    $orig = preg_replace('/[^\w\.\-\s]/u', '_', $originalName);
    $stored = bin2hex(random_bytes(8)) . '_' . $orig;
    $dest = rtrim(UPLOAD_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $stored;

    if (!copy($absolutePath, $dest)) throw new RuntimeException("Failed to copy attachment from path");
    $mime = function_exists('mime_content_type') ? mime_content_type($dest) : null;
    $size = filesize($dest);

    global $pdo;
    $pdo->prepare("
      INSERT INTO attachments (ticket_id, original_name, stored_name, mime_type, size_bytes)
      VALUES (:t,:o,:s,:m,:z)
    ")->execute([':t'=>$ticketId, ':o'=>$orig, ':s'=>$stored, ':m'=>$mime, ':z'=>$size]);
  }
  public static function saveFiles(int $ticketId, array $files): void {
    // supports both <input name="file"> and <input name="files[]"> (multiple)
    // normalize to an array of file entries like $_FILES['x'] for single
    $batches = [];
  
    if (isset($files['name']) && is_array($files['name'])) {
      // multiple: iterate indices
      $count = count($files['name']);
      for ($i=0; $i<$count; $i++) {
        $batches[] = [
          'name'     => $files['name'][$i] ?? null,
          'type'     => $files['type'][$i] ?? null,
          'tmp_name' => $files['tmp_name'][$i] ?? null,
          'error'    => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
          'size'     => $files['size'][$i] ?? 0,
        ];
      }
    } else {
      // single file input
      $batches[] = $files;
    }
  
    foreach ($batches as $f) {
      // skip empty slots
      if (!isset($f['error']) || $f['error'] === UPLOAD_ERR_NO_FILE) continue;
      self::uploadFromForm($ticketId, $f);
    }
  }
}
