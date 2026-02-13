<?php
/**
 * Document API Controller
 * Handles file uploads for projects
 */

namespace App\Controllers\Api;

use App\Core\Controller;

class DocumentController extends Controller
{
    /**
     * List documents for a project
     */
    public function index(): array
    {
        $params = $this->getQueryParams();
        $projectId = $params['project_id'] ?? null;

        $tenantId = $this->db->getTenantId();
        $conditions = ["tenant_id = ?"];
        $bindings = [$tenantId];

        if ($projectId) {
            $conditions[] = "project_id = ?";
            $bindings[] = $projectId;
        }

        $where = implode(' AND ', $conditions);

        $documents = $this->db->fetchAll(
            "SELECT * FROM documents WHERE $where ORDER BY created_at DESC",
            $bindings
        );

        return $this->success($documents);
    }

    /**
     * Upload a new document
     */
    public function store(): array
    {
        // Validate required fields
        if (empty($_POST['name'])) {
            return $this->error('Document name is required', 422);
        }

        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            return $this->error('File is required', 422);
        }

        $projectId = $_POST['project_id'] ?? null;
        if (!$projectId) {
            return $this->error('Project ID is required', 422);
        }

        $file = $_FILES['file'];
        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];

        if (!in_array($file['type'], $allowedTypes)) {
            return $this->error('Invalid file type', 422);
        }

        // Max 10MB
        if ($file['size'] > 10 * 1024 * 1024) {
            return $this->error('File too large (max 10MB)', 422);
        }

        // Create upload directory
        $tenantId = $this->db->getTenantId();
        $uploadDir = ROOT_PATH . '/uploads/documents/' . $tenantId . '/' . $projectId;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $ext;
        $filepath = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return $this->error('Failed to save file', 500);
        }

        // Save to database
        $user = $_SESSION['user'] ?? null;
        $documentId = $this->db->insert('documents', [
            'project_id' => $projectId,
            'name' => $_POST['name'],
            'original_name' => $file['name'],
            'path' => '/uploads/documents/' . $tenantId . '/' . $projectId . '/' . $filename,
            'mime_type' => $file['type'],
            'size' => $file['size'],
            'description' => $_POST['description'] ?? null,
            'uploaded_by' => $user['id'] ?? null,
        ]);

        $document = $this->db->fetch("SELECT * FROM documents WHERE id = ?", [$documentId]);

        return $this->success($document, 'Document uploaded');
    }

    /**
     * Get a single document
     */
    public function show($id): array
    {
        $tenantId = $this->db->getTenantId();
        $document = $this->db->fetch(
            "SELECT * FROM documents WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$document) {
            return $this->error('Document not found', 404);
        }

        return $this->success($document);
    }

    /**
     * Delete a document
     */
    public function destroy($id): array
    {
        $tenantId = $this->db->getTenantId();
        $document = $this->db->fetch(
            "SELECT * FROM documents WHERE id = ? AND tenant_id = ?",
            [$id, $tenantId]
        );

        if (!$document) {
            return $this->error('Document not found', 404);
        }

        // Delete file
        $filepath = ROOT_PATH . $document['path'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }

        // Delete from database
        $this->db->delete('documents', ['id' => $id]);

        return $this->success(null, 'Document deleted');
    }
}
