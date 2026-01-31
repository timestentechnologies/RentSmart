<?php

namespace App\Controllers;

use App\Helpers\FileUploadHelper;
use App\Models\ActivityLog;
use App\Database\Connection;
use Exception;

class FileController
{
    private $db;
    private $activityLog;

    public function __construct()
    {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Please login to continue';
            $_SESSION['flash_type'] = 'danger';
            redirect('/home');
        }
        $this->db = Connection::getInstance()->getConnection();
        $this->activityLog = new ActivityLog();
    }

    public function index()
    {
        try {
            $q = isset($_GET['q']) ? trim($_GET['q']) : null;
            $entityType = isset($_GET['entity_type']) ? trim($_GET['entity_type']) : null;
            $fileType = isset($_GET['file_type']) ? trim($_GET['file_type']) : null;
            $dateFrom = isset($_GET['date_from']) && $_GET['date_from'] !== '' ? $_GET['date_from'] . ' 00:00:00' : null;
            $dateTo = isset($_GET['date_to']) && $_GET['date_to'] !== '' ? $_GET['date_to'] . ' 23:59:59' : null;

            $sql = "SELECT fu.*, u.name AS uploader_name, u.email AS uploader_email 
                    FROM file_uploads fu 
                    LEFT JOIN users u ON fu.uploaded_by = u.id 
                    WHERE 1=1";
            $params = [];
            if ($q) { $sql .= " AND (fu.original_name LIKE :q OR fu.filename LIKE :q OR fu.entity_type LIKE :q)"; $params['q'] = "%{$q}%"; }
            if ($entityType) { $sql .= " AND fu.entity_type = :entity_type"; $params['entity_type'] = $entityType; }
            if ($fileType) { $sql .= " AND fu.file_type = :file_type"; $params['file_type'] = $fileType; }
            if ($dateFrom) { $sql .= " AND fu.created_at >= :date_from"; $params['date_from'] = $dateFrom; }
            if ($dateTo) { $sql .= " AND fu.created_at <= :date_to"; $params['date_to'] = $dateTo; }
            $sql .= " ORDER BY fu.created_at DESC LIMIT 200";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $files = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            echo view('files/index', [
                'title' => 'Files - RentSmart',
                'files' => $files,
            ]);
        } catch (Exception $e) {
            error_log('FileController@index error: ' . $e->getMessage());
            echo view('errors/500', [ 'title' => '500 Internal Server Error' ]);
        }
    }

    public function upload()
    {
        try {
            if (!verify_csrf_token()) {
                $_SESSION['flash_message'] = 'Invalid security token';
                $_SESSION['flash_type'] = 'danger';
                redirect('/files');
            }
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                redirect('/files');
            }
            $entityType = $_POST['entity_type'] ?? '';
            $entityId = isset($_POST['entity_id']) ? (int)$_POST['entity_id'] : 0;
            $fileType = $_POST['file_type'] ?? 'attachment';
            if (!$entityType || !$entityId || !isset($_FILES['files'])) {
                $_SESSION['flash_message'] = 'Please fill all fields and select files';
                $_SESSION['flash_type'] = 'danger';
                redirect('/files');
            }

            $helper = new FileUploadHelper();
            $result = $helper->uploadFiles($_FILES['files'], $entityType, $entityId, $fileType, $_SESSION['user_id']);

            // Log each uploaded file
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            foreach ($result['uploaded'] as $f) {
                try {
                    $this->activityLog->add(
                        $_SESSION['user_id'],
                        $_SESSION['user_role'] ?? null,
                        'file.upload',
                        'file',
                        (int)$f['id'],
                        null,
                        json_encode(['original_name' => $f['original_name'], 'entity_type' => $entityType, 'entity_id' => $entityId, 'file_type' => $fileType]),
                        $ip,
                        $agent
                    );
                } catch (\Exception $ex) { error_log('file.upload log failed: ' . $ex->getMessage()); }
            }

            $_SESSION['flash_message'] = count($result['uploaded']) . ' file(s) uploaded' . (!empty($result['errors']) ? ' with some errors' : '');
            $_SESSION['flash_type'] = empty($result['errors']) ? 'success' : 'warning';
            redirect('/files');
        } catch (Exception $e) {
            error_log('FileController@upload error: ' . $e->getMessage());
            $_SESSION['flash_message'] = 'Upload failed: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            redirect('/files');
        }
    }

    public function search()
    {
        try {
            $q = isset($_GET['q']) ? trim($_GET['q']) : null;
            $entityType = isset($_GET['entity_type']) ? trim($_GET['entity_type']) : null;
            $fileType = isset($_GET['file_type']) ? trim($_GET['file_type']) : null;
            $dateFrom = isset($_GET['date_from']) && $_GET['date_from'] !== '' ? $_GET['date_from'] . ' 00:00:00' : null;
            $dateTo = isset($_GET['date_to']) && $_GET['date_to'] !== '' ? $_GET['date_to'] . ' 23:59:59' : null;

            $sql = "SELECT fu.*, u.name AS uploader_name, u.email AS uploader_email 
                    FROM file_uploads fu 
                    LEFT JOIN users u ON fu.uploaded_by = u.id 
                    WHERE 1=1";
            $params = [];
            if ($q) { $sql .= " AND (fu.original_name LIKE :q OR fu.filename LIKE :q OR fu.entity_type LIKE :q)"; $params['q'] = "%{$q}%"; }
            if ($entityType) { $sql .= " AND fu.entity_type = :entity_type"; $params['entity_type'] = $entityType; }
            if ($fileType) { $sql .= " AND fu.file_type = :file_type"; $params['file_type'] = $fileType; }
            if ($dateFrom) { $sql .= " AND fu.created_at >= :date_from"; $params['date_from'] = $dateFrom; }
            if ($dateTo) { $sql .= " AND fu.created_at <= :date_to"; $params['date_to'] = $dateTo; }
            $sql .= " ORDER BY fu.created_at DESC LIMIT 500";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $files = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            header('Content-Type: application/json');
            echo json_encode(['data' => $files]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Search failed']);
            exit;
        }
    }

    /**
     * Delete a file
     */
    public function delete($fileId)
    {
        try {
            $fileUploadHelper = new FileUploadHelper();
            
            if ($fileUploadHelper->deleteFile($fileId, $_SESSION['user_id'])) {
                $response = [
                    'success' => true,
                    'message' => 'File deleted successfully'
                ];
                // Log file deletion
                try {
                    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                    $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                    $this->activityLog->add(
                        $_SESSION['user_id'] ?? null,
                        $_SESSION['user_role'] ?? null,
                        'file.delete',
                        'file',
                        (int)$fileId,
                        null,
                        null,
                        $ip,
                        $agent
                    );
                } catch (\Exception $ex) { error_log('file.delete log failed: ' . $ex->getMessage()); }
            } else {
                throw new Exception('Failed to delete file');
            }
        } catch (Exception $e) {
            error_log("Error in FileController::delete: " . $e->getMessage());
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}
