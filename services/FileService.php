<?php
// services/FileService.php
class FileService {
    private $uploadPath;
    private $allowedExtensions = ['png','jpg','jpeg','gif','pdf','doc','docx','xls','xlsx','txt','zip'];
    private $maxBytes = 20 * 1024 * 1024; // 20 MB per file

    public function __construct($uploadPath = null) {
        $this->uploadPath = $uploadPath ?? (defined('UPLOAD_PATH') ? UPLOAD_PATH : __DIR__ . '/../uploads');
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    private function sanitizeFileName($name) {
        // remove dangerous chars
        $name = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $name);
        return $name;
    }

    private function randomPrefix() {
        return bin2hex(random_bytes(8));
    }

    public function isAllowed($filename, $size) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($size > $this->maxBytes) return false;
        if (!in_array($ext, $this->allowedExtensions)) return false;
        return true;
    }

    public function saveUploadedFile(array $file) {
        // $file is one element of $_FILES['attachments']
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Upload error code: " . $file['error']);
        }
        if (!$this->isAllowed($file['name'], $file['size'])) {
            throw new Exception("File not allowed or too big: " . $file['name']);
        }
        $orig = $this->sanitizeFileName($file['name']);
        $stored = $this->randomPrefix() . '_' . $orig;
        $dest = rtrim($this->uploadPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $stored;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new Exception("Failed to move uploaded file");
        }

        return [
            'original_filename' => $orig,
            'stored_filename' => $stored,
            'file_size' => filesize($dest),
            'mime_type' => mime_content_type($dest)
        ];
    }

    public function getFilePath($storedFilename) {
        $f = rtrim($this->uploadPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $storedFilename;
        if (!file_exists($f)) return null;
        return $f;
    }

    public function serveFile($storedFilename, $downloadName = null) {
        $path = $this->getFilePath($storedFilename);
        if (!$path) {
            http_response_code(404);
            echo "File not found";
            exit;
        }
        if (!is_readable($path)) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
        $downloadName = $downloadName ?? basename($path);
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($downloadName) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}
