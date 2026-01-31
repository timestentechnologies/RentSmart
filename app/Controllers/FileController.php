<?php

namespace App\Controllers;

use App\Helpers\FileUploadHelper;
use App\Models\ActivityLog;
use App\Database\Connection;
use App\Models\User;
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

    public function bulkDelete()
    {
        try {
            $payload = file_get_contents('php://input');
            $data = json_decode($payload, true);
            $ids = isset($data['ids']) && is_array($data['ids']) ? array_filter($data['ids'], 'is_numeric') : [];
            if (empty($ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'No files selected']);
                return; 
            }
            $helper = new FileUploadHelper();
            $deleted = 0; $failed = 0;
            foreach ($ids as $fid) {
                try {
                    if ($helper->deleteFile((int)$fid, $_SESSION['user_id'])) { $deleted++; }
                    else { $failed++; }
                } catch (\Exception $e) { $failed++; }
            }
            // Log bulk delete summary
            try {
                $ip = $_SERVER['REMOTE_ADDR'] ?? null; $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                $this->activityLog->add($_SESSION['user_id'] ?? null, $_SESSION['user_role'] ?? null, 'file.bulk_delete', 'file', null, null, json_encode(['deleted' => $deleted, 'failed' => $failed]), $ip, $agent);
            } catch (\Exception $ex) { }
            echo json_encode(['success' => true, 'deleted' => $deleted, 'failed' => $failed]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Bulk delete failed']);
        }
        exit;
    }

    public function findEntities()
    {
        try {
            $type = isset($_GET['entity_type']) ? trim($_GET['entity_type']) : '';
            $term = isset($_GET['term']) ? trim($_GET['term']) : '';
            $limit = 20;
            $items = [];
            $uid = $_SESSION['user_id'];
            $role = strtolower($_SESSION['user_role'] ?? '');

            // Build property scope for non-admins
            $userModel = new User();
            $userModel->find($uid);
            $propIds = $userModel->getAccessiblePropertyIds();
            $inPlaceholders = [];
            $params = [];
            foreach ($propIds as $i => $pid) { $inPlaceholders[] = ":pp{$i}"; $params["pp{$i}"] = (int)$pid; }
            $inList = !empty($inPlaceholders) ? implode(',', $inPlaceholders) : '';

            if ($type === 'property') {
                $sql = "SELECT id, name FROM properties WHERE 1=1";
                if (!empty($term)) { $sql .= " AND name LIKE :t"; $params['t'] = "%{$term}%"; }
                if (!empty($propIds)) { $sql .= " AND id IN ($inList)"; }
                $sql .= " ORDER BY name LIMIT {$limit}";
                $stmt = $this->db->prepare($sql); $stmt->execute($params);
                while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) { $items[] = ['id' => (int)$r['id'], 'text' => $r['name']]; }
            } elseif ($type === 'unit') {
                $sql = "SELECT u.id, u.unit_number, p.name AS property_name FROM units u JOIN properties p ON u.property_id = p.id WHERE 1=1";
                if (!empty($term)) { $sql .= " AND (u.unit_number LIKE :t OR p.name LIKE :t)"; $params['t'] = "%{$term}%"; }
                if (!empty($propIds)) { $sql .= " AND u.property_id IN ($inList)"; }
                $sql .= " ORDER BY p.name, u.unit_number LIMIT {$limit}";
                $stmt = $this->db->prepare($sql); $stmt->execute($params);
                while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) { $items[] = ['id' => (int)$r['id'], 'text' => ($r['property_name'] . ' - ' . $r['unit_number'])]; }
            } elseif ($type === 'payment') {
                $sql = "SELECT p.id, p.amount, p.payment_date, pr.name AS property_name FROM payments p JOIN leases l ON p.lease_id = l.id JOIN units u ON l.unit_id = u.id JOIN properties pr ON u.property_id = pr.id WHERE 1=1";
                if (!empty($term)) { $sql .= " AND (pr.name LIKE :t)"; $params['t'] = "%{$term}%"; }
                if (!empty($propIds)) { $sql .= " AND pr.id IN ($inList)"; }
                $sql .= " ORDER BY p.payment_date DESC LIMIT {$limit}";
                $stmt = $this->db->prepare($sql); $stmt->execute($params);
                while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) { $items[] = ['id' => (int)$r['id'], 'text' => ($r['property_name'] . ' - ' . number_format($r['amount'],2) . ' on ' . $r['payment_date'])]; }
            } elseif ($type === 'expense') {
                $sql = "SELECT e.id, e.category, e.amount, e.expense_date, COALESCE(p.name, pr.name) AS property_name FROM expenses e LEFT JOIN properties p ON e.property_id = p.id LEFT JOIN units u ON e.unit_id = u.id LEFT JOIN properties pr ON u.property_id = pr.id WHERE 1=1";
                if (!empty($term)) { $sql .= " AND (e.category LIKE :t OR COALESCE(p.name, pr.name) LIKE :t)"; $params['t'] = "%{$term}%"; }
                if (!empty($propIds)) { $sql .= " AND ((e.property_id IS NOT NULL AND e.property_id IN ($inList)) OR (u.property_id IN ($inList)))"; }
                $sql .= " ORDER BY e.expense_date DESC LIMIT {$limit}";
                $stmt = $this->db->prepare($sql); $stmt->execute($params);
                while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) { $items[] = ['id' => (int)$r['id'], 'text' => (($r['property_name'] ?? 'Property') . ' - ' . $r['category'] . ' ' . number_format($r['amount'],2) . ' on ' . $r['expense_date'])]; }
            }

            header('Content-Type: application/json');
            echo json_encode(['results' => $items]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['results' => []]);
        }
        exit;
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

            // Role scoping: admins see all; others see own uploads or files linked to properties they manage
            $role = strtolower($_SESSION['user_role'] ?? '');
            $isAdmin = in_array($role, ['admin','administrator']);
            if (!$isAdmin) {
                $userModel = new User();
                $userModel->find($_SESSION['user_id']);
                $propIds = $userModel->getAccessiblePropertyIds();
                $sql .= " AND (fu.uploaded_by = :scoped_uid";
                $params['scoped_uid'] = (int)$_SESSION['user_id'];
                if (!empty($propIds)) {
                    $inPlaceholders = [];
                    foreach ($propIds as $i => $pid) { $inPlaceholders[] = ":pid{$i}"; $params["pid{$i}"] = (int)$pid; }
                    $inList = implode(',', $inPlaceholders);
                    $sql .= " OR (fu.entity_type = 'property' AND fu.entity_id IN ($inList))";
                    $sql .= " OR (fu.entity_type = 'unit' AND EXISTS (SELECT 1 FROM units un WHERE un.id = fu.entity_id AND un.property_id IN ($inList)))";
                    $sql .= " OR (fu.entity_type = 'payment' AND EXISTS (SELECT 1 FROM payments p JOIN leases l ON p.lease_id = l.id JOIN units u ON l.unit_id = u.id WHERE p.id = fu.entity_id AND u.property_id IN ($inList)))";
                    $sql .= " OR (fu.entity_type = 'expense' AND EXISTS (SELECT 1 FROM expenses e LEFT JOIN units uu ON e.unit_id = uu.id WHERE e.id = fu.entity_id AND ((e.property_id IS NOT NULL AND e.property_id IN ($inList)) OR (uu.property_id IN ($inList)))))";
                }
                $sql .= ")";
            }

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

            // Role scoping: admins see all; others see own uploads or files linked to properties they manage
            $role = strtolower($_SESSION['user_role'] ?? '');
            $isAdmin = in_array($role, ['admin','administrator']);
            if (!$isAdmin) {
                $userModel = new User();
                $userModel->find($_SESSION['user_id']);
                $propIds = $userModel->getAccessiblePropertyIds();
                $sql .= " AND (fu.uploaded_by = :scoped_uid";
                $params['scoped_uid'] = (int)$_SESSION['user_id'];
                if (!empty($propIds)) {
                    $inPlaceholders = [];
                    foreach ($propIds as $i => $pid) { $inPlaceholders[] = ":pid{$i}"; $params["pid{$i}"] = (int)$pid; }
                    $inList = implode(',', $inPlaceholders);
                    $sql .= " OR (fu.entity_type = 'property' AND fu.entity_id IN ($inList))";
                    $sql .= " OR (fu.entity_type = 'unit' AND EXISTS (SELECT 1 FROM units un WHERE un.id = fu.entity_id AND un.property_id IN ($inList)))";
                    $sql .= " OR (fu.entity_type = 'payment' AND EXISTS (SELECT 1 FROM payments p JOIN leases l ON p.lease_id = l.id JOIN units u ON l.unit_id = u.id WHERE p.id = fu.entity_id AND u.property_id IN ($inList)))";
                    $sql .= " OR (fu.entity_type = 'expense' AND EXISTS (SELECT 1 FROM expenses e LEFT JOIN units uu ON e.unit_id = uu.id WHERE e.id = fu.entity_id AND ((e.property_id IS NOT NULL AND e.property_id IN ($inList)) OR (uu.property_id IN ($inList)))))";
                }
                $sql .= ")";
            }

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
