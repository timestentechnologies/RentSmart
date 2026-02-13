<?php

namespace App\Helpers;

use App\Database\Connection;
use Exception;

class FileUploadHelper
{
    private $db;
    private $allowedImageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    private $allowedDocumentTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'text/csv'
    ];
    private $allowedAttachmentTypes = [
        // Images
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/tiff',
        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',
        'text/rtf',
        // Archives
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
        // Other common types
        'application/json',
        'application/xml',
        'text/xml'
    ];
    private $maxFileSize = 10485760; // 10MB
    private $maxImageSize = 5242880; // 5MB

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }

    /**
     * Ensure the file_uploads table exists (creates it if missing)
     */
    private function ensureUploadsTableExists()
    {
        try {
            $this->db->query("DESCRIBE file_uploads");
        } catch (\PDOException $e) {
            // Create table if it doesn't exist
            $createSql = "
                CREATE TABLE IF NOT EXISTS file_uploads (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    filename VARCHAR(255) NOT NULL,
                    original_name VARCHAR(255) NOT NULL,
                    file_type VARCHAR(50) NOT NULL,
                    mime_type VARCHAR(100) NOT NULL,
                    file_size INT NOT NULL,
                    entity_type VARCHAR(50) NOT NULL,
                    entity_id INT NOT NULL,
                    uploaded_by INT NULL,
                    upload_path VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_entity (entity_type, entity_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            $this->db->exec($createSql);
        }
    }

    /**
     * Upload multiple files for an entity
     */
    public function uploadFiles($files, $entityType, $entityId, $fileType = 'image', $userId = null)
    {
        if (!$userId) {
            $userId = $_SESSION['user_id'] ?? null;
        }

        if (!$userId) {
            throw new Exception('User ID is required for file uploads');
        }

        $uploadedFiles = [];
        $errors = [];

        // Handle both single file and multiple files
        if (!is_array($files['name'])) {
            $files = [
                'name' => [$files['name']],
                'type' => [$files['type']],
                'tmp_name' => [$files['tmp_name']],
                'error' => [$files['error']],
                'size' => [$files['size']]
            ];
        }

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue; // Skip empty file inputs
            }

            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                $errors[] = "Upload error for file: " . $files['name'][$i];
                continue;
            }

            try {
                $uploadedFile = $this->uploadSingleFile([
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ], $entityType, $entityId, $fileType, $userId);

                $uploadedFiles[] = $uploadedFile;
            } catch (Exception $e) {
                $errors[] = "Error uploading " . $files['name'][$i] . ": " . $e->getMessage();
            }
        }

        return [
            'uploaded' => $uploadedFiles,
            'errors' => $errors
        ];
    }

    /**
     * Upload a single file
     */
    private function uploadSingleFile($file, $entityType, $entityId, $fileType, $userId)
    {
        // Validate file
        $this->validateFile($file, $fileType);

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid($entityType . '_' . $entityId . '_') . '.' . $extension;
        
        // Determine upload path
        $uploadDir = $this->getUploadDirectory($entityType);
        $uploadPath = $uploadDir . '/' . $filename;
        $fullPath = __DIR__ . '/../../public/' . $uploadPath;

        // Create directory if it doesn't exist
        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        // Move uploaded file with fallbacks and diagnostics
        $moved = false;
        if (is_uploaded_file($file['tmp_name'])) {
            $moved = @move_uploaded_file($file['tmp_name'], $fullPath);
            if (!$moved) {
                error_log('move_uploaded_file failed to: ' . $fullPath . ' (tmp: ' . $file['tmp_name'] . ')');
            }
        }
        if (!$moved && file_exists($file['tmp_name'])) {
            $moved = @rename($file['tmp_name'], $fullPath);
            if (!$moved) {
                error_log('rename fallback failed to: ' . $fullPath . ' (tmp: ' . $file['tmp_name'] . ')');
            }
        }
        if (!$moved) {
            throw new Exception('Failed to move uploaded file');
        }

        // Save file record to database
        $fileId = $this->saveFileRecord([
            'filename' => $filename,
            'original_name' => $file['name'],
            'file_type' => $fileType,
            'mime_type' => $file['type'],
            'file_size' => $file['size'],
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'uploaded_by' => $userId,
            'upload_path' => $uploadPath
        ]);

        return [
            'id' => $fileId,
            'filename' => $filename,
            'original_name' => $file['name'],
            'file_type' => $fileType,
            'upload_path' => $uploadPath,
            'url' => BASE_URL . '/public/' . ltrim($uploadPath, '/')
        ];
    }

    /**
     * Validate uploaded file
     */
    private function validateFile($file, $fileType)
    {
        // Check file size
        $maxSize = ($fileType === 'image') ? $this->maxImageSize : $this->maxFileSize;
        if ($file['size'] > $maxSize) {
            throw new Exception('File size exceeds maximum allowed size');
        }

        // Check file type
        if ($fileType === 'image') {
            $allowedTypes = $this->allowedImageTypes;
        } elseif ($fileType === 'document') {
            $allowedTypes = $this->allowedDocumentTypes;
        } elseif ($fileType === 'attachment') {
            $allowedTypes = $this->allowedAttachmentTypes;
        } else {
            $allowedTypes = array_merge($this->allowedImageTypes, $this->allowedDocumentTypes);
        }
        
        // Normalize and validate posted MIME
        $normalizeMime = function ($mime) {
            if ($mime === 'image/jpg') return 'image/jpeg';
            if ($mime === 'image/pjpeg') return 'image/jpeg';
            if ($mime === 'image/x-png') return 'image/png';
            return $mime;
        };

        $postedMime = $normalizeMime($file['type'] ?? '');
        if (!in_array($postedMime, array_map($normalizeMime, $allowedTypes))) {
            throw new Exception('File type not allowed: ' . $postedMime);
        }

        // Additional security checks with graceful fallback
        $actualMimeType = null;
        try {
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $actualMimeType = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);
                }
            } elseif (function_exists('mime_content_type')) {
                $actualMimeType = @mime_content_type($file['tmp_name']);
            }
        } catch (\Throwable $t) {
            error_log('File MIME detection failed: ' . $t->getMessage());
        }

        if ($actualMimeType) {
            $actualMimeType = $normalizeMime($actualMimeType);
            if ($actualMimeType !== $postedMime) {
                // allow if both are images
                if (!(strpos($actualMimeType, 'image/') === 0 && strpos($postedMime, 'image/') === 0)) {
                    throw new Exception('File type mismatch detected');
                }
            }
            if (!in_array($actualMimeType, array_map($normalizeMime, $allowedTypes))) {
                throw new Exception('Detected MIME not allowed: ' . $actualMimeType);
            }
        }
    }

    /**
     * Get upload directory for entity type
     */
    private function getUploadDirectory($entityType)
    {
        switch ($entityType) {
            case 'property':
                return 'uploads/properties';
            case 'unit':
                return 'uploads/units';
            case 'realtor_listing':
                return 'uploads/realtor_listings';
            case 'payment':
                return 'uploads/payments';
            case 'expense':
                return 'uploads/expenses';
            default:
                throw new Exception('Invalid entity type');
        }
    }

    /**
     * Save file record to database
     */
    private function saveFileRecord($data)
    {
        $this->ensureUploadsTableExists();
        $sql = "INSERT INTO file_uploads (
                    filename, original_name, file_type, mime_type, file_size,
                    entity_type, entity_id, uploaded_by, upload_path
                ) VALUES (
                    :filename, :original_name, :file_type, :mime_type, :file_size,
                    :entity_type, :entity_id, :uploaded_by, :upload_path
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        
        return $this->db->lastInsertId();
    }

    /**
     * Get files for an entity
     */
    public function getEntityFiles($entityType, $entityId, $fileType = null)
    {
        // If table doesn't exist, return empty
        try {
            $this->ensureUploadsTableExists();
        } catch (\Exception $e) {
            error_log("FileUploadHelper::getEntityFiles - Table doesn't exist: " . $e->getMessage());
            return [];
        }

        $sql = "SELECT * FROM file_uploads WHERE entity_type = :entity_type AND entity_id = :entity_id";
        $params = [
            'entity_type' => $entityType,
            'entity_id' => $entityId
        ];

        if ($fileType) {
            $sql .= " AND file_type = :file_type";
            $params['file_type'] = $fileType;
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $files = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Add full URL to each file
        foreach ($files as &$file) {
            $baseUrl = defined('BASE_URL') ? BASE_URL : '';
            $uploadPath = $file['upload_path'];
            
            // Ensure path starts with public/
            if (strpos($uploadPath, 'public/') === 0) {
                // Already has public/ prefix
                $file['url'] = $baseUrl . '/' . $uploadPath;
            } elseif (strpos($uploadPath, 'uploads/') === 0) {
                // Has uploads/ prefix, add public/
                $file['url'] = $baseUrl . '/public/' . $uploadPath;
            } else {
                // No prefix, add public/uploads/
                $file['url'] = $baseUrl . '/public/uploads/' . ltrim($uploadPath, '/');
            }
            // original_name already exists in the database, no need to rename
        }

        return $files;
    }

    /**
     * Delete a file
     */
    public function deleteFile($fileId, $userId = null)
    {
        $this->ensureUploadsTableExists();
        if (!$userId) {
            $userId = $_SESSION['user_id'] ?? null;
        }

        // Get file record
        $sql = "SELECT * FROM file_uploads WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $fileId]);
        $file = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$file) {
            throw new Exception('File not found');
        }

        // Check permissions (only uploader or admin can delete)
        if ($file['uploaded_by'] != $userId && !$this->isAdmin($userId)) {
            throw new Exception('Permission denied');
        }

        // Delete physical file
        $fullPath = __DIR__ . '/../../public/' . $file['upload_path'];
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        // Delete database record
        $sql = "DELETE FROM file_uploads WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute(['id' => $fileId]);

        if ($result) {
            // Update entity files JSON
            $this->updateEntityFiles($file['entity_type'], $file['entity_id']);
        }

        return $result;
    }

    /**
     * Check if user is admin
     */
    private function isAdmin($userId)
    {
        $sql = "SELECT role FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $user && $user['role'] === 'admin';
    }

    /**
     * Update entity files in JSON format (for backward compatibility)
     */
    public function updateEntityFiles($entityType, $entityId)
    {
        // If uploads table doesn't exist yet, skip without error
        try {
            $this->ensureUploadsTableExists();
        } catch (\Exception $e) {
            return;
        }

        $files = $this->getEntityFiles($entityType, $entityId);
        
        $images = [];
        $documents = [];
        $attachments = [];

        foreach ($files as $file) {
            $fileData = [
                'id' => $file['id'],
                'filename' => $file['filename'],
                'original_name' => $file['original_name'],
                'url' => $file['url']
            ];

            if ($file['file_type'] === 'image') {
                $images[] = $fileData;
            } elseif ($file['file_type'] === 'document') {
                $documents[] = $fileData;
            } elseif ($file['file_type'] === 'attachment') {
                $attachments[] = $fileData;
            }
        }

        // Update the entity table with JSON data
        $table = $entityType === 'property' ? 'properties' : 
                ($entityType === 'unit' ? 'units' : ($entityType === 'expense' ? 'expenses' : 'payments'));
        
        $updateFields = [];
        $params = ['id' => $entityId];

        if ($entityType === 'payment' || $entityType === 'expense') {
            $updateFields[] = "attachments = :attachments";
            $params['attachments'] = json_encode($attachments);
        } else {
            $updateFields[] = "images = :images";
            $updateFields[] = "documents = :documents";
            $params['images'] = json_encode($images);
            $params['documents'] = json_encode($documents);
        }

        if (!empty($updateFields)) {
            // Check if the columns exist before trying to update
            try {
                // First check if the columns exist
                $checkSql = "SHOW COLUMNS FROM {$table}";
                $checkStmt = $this->db->prepare($checkSql);
                $checkStmt->execute();
                $columns = $checkStmt->fetchAll(\PDO::FETCH_COLUMN);
                
                // Only update fields that exist
                $existingFields = [];
                $existingParams = ['id' => $entityId];
                
                if ($entityType === 'payment' || $entityType === 'expense') {
                    if (in_array('attachments', $columns)) {
                        $existingFields[] = "attachments = :attachments";
                        $existingParams['attachments'] = json_encode($attachments);
                    }
                } else {
                    if (in_array('images', $columns)) {
                        $existingFields[] = "images = :images";
                        $existingParams['images'] = json_encode($images);
                    }
                    if (in_array('documents', $columns)) {
                        $existingFields[] = "documents = :documents";
                        $existingParams['documents'] = json_encode($documents);
                    }
                }
                
                if (!empty($existingFields)) {
                    $sql = "UPDATE {$table} SET " . implode(', ', $existingFields) . " WHERE id = :id";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute($existingParams);
                } else {
                    error_log("FileUploadHelper::updateEntityFiles - No JSON columns found in {$table} table");
                }
            } catch (\PDOException $e) {
                // If columns don't exist or other error, log the error but don't fail
                error_log("FileUploadHelper::updateEntityFiles - Error updating {$table} table: " . $e->getMessage());
            }
        }
    }

}
