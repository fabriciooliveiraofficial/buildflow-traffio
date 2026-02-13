<?php
/**
 * Email Attachment Handler
 * 
 * Manages file uploads for email attachments
 */

namespace App\Services\Email;

use App\Core\Database;

class AttachmentService
{
    private Database $db;
    private string $uploadPath;
    private int $maxSize = 10485760; // 10MB

    private array $allowedMimes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'image/jpeg',
        'image/png',
        'image/gif',
        'text/plain',
        'text/csv',
    ];

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->uploadPath = ROOT_PATH . '/storage/email-attachments/';

        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    /**
     * Upload attachment from $_FILES
     */
    public function upload(array $file): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('File upload error: ' . $this->getUploadError($file['error']));
        }

        if ($file['size'] > $this->maxSize) {
            throw new \Exception('File too large. Maximum size is 10MB.');
        }

        $mimeType = mime_content_type($file['tmp_name']);
        if (!in_array($mimeType, $this->allowedMimes)) {
            throw new \Exception('File type not allowed: ' . $mimeType);
        }

        // Generate unique filename
        $tenantId = $this->db->getTenantId();
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($file['name'], PATHINFO_FILENAME));
        $uniqueName = $tenantId . '_' . time() . '_' . substr(md5(uniqid()), 0, 8) . '_' . $safeName . '.' . $extension;

        $targetPath = $this->uploadPath . $uniqueName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new \Exception('Failed to save uploaded file');
        }

        return [
            'filename' => $file['name'],
            'path' => $targetPath,
            'relative_path' => 'storage/email-attachments/' . $uniqueName,
            'mime_type' => $mimeType,
            'size' => $file['size'],
        ];
    }

    /**
     * Upload multiple files
     */
    public function uploadMultiple(array $files): array
    {
        $uploaded = [];

        // Handle both single and multiple file uploads
        if (isset($files['name']) && is_array($files['name'])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $single = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i],
                    ];
                    $uploaded[] = $this->upload($single);
                }
            }
        } elseif (isset($files['tmp_name'])) {
            $uploaded[] = $this->upload($files);
        }

        return $uploaded;
    }

    /**
     * Delete attachment file
     */
    public function delete(string $path): bool
    {
        if (file_exists($path) && strpos($path, $this->uploadPath) === 0) {
            return unlink($path);
        }
        return false;
    }

    /**
     * Clean up old temporary attachments (older than 24 hours)
     */
    public function cleanupOld(): int
    {
        $count = 0;
        $cutoff = time() - 86400; // 24 hours ago

        $files = glob($this->uploadPath . '*');
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                if (unlink($file)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Build MIME attachment for email
     */
    public function buildMimeAttachment(array $attachment, string $boundary): string
    {
        $path = $attachment['path'];
        if (!file_exists($path)) {
            return '';
        }

        $content = file_get_contents($path);
        $encoded = chunk_split(base64_encode($content));
        $filename = $attachment['filename'];
        $mimeType = $attachment['mime_type'];

        $mime = "--$boundary\r\n";
        $mime .= "Content-Type: $mimeType; name=\"$filename\"\r\n";
        $mime .= "Content-Disposition: attachment; filename=\"$filename\"\r\n";
        $mime .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $mime .= $encoded . "\r\n";

        return $mime;
    }

    /**
     * Get upload error message
     */
    private function getUploadError(int $code): string
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server limit',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form limit',
            UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'No temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
            UPLOAD_ERR_EXTENSION => 'Blocked by extension',
        ];

        return $errors[$code] ?? 'Unknown error';
    }
}
