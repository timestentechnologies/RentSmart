<?php

namespace App\Controllers;

use App\Helpers\FileUploadHelper;
use App\Models\ActivityLog;
use App\Database\Connection;
use App\Models\Tenant;
use App\Models\Property;
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
        $this->ensureShareTable();
    }

    private function ensureShareTable()
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS file_shares (
                id INT AUTO_INCREMENT PRIMARY KEY,
                file_id INT NOT NULL,
                recipient_type ENUM('user','tenant') NOT NULL,
                recipient_id INT NOT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_share (file_id, recipient_type, recipient_id),
                INDEX idx_file (file_id),
                INDEX idx_recipient (recipient_type, recipient_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            $this->db->exec($sql);
        } catch (Exception $e) {
            // ignore
        }
    }

    public function debugFiles()
    {
        // Auth is already enforced by requireAuth() via protectedRoutes, and constructor checks session.
        // Additional guard: require a key that matches the user's CSRF token.
        $key = $_GET['key'] ?? null;
        if (!$key || !isset($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$key)) {
            header('HTTP/1.0 403 Forbidden');
            header('Content-Type: text/plain; charset=utf-8');
            echo "Forbidden\n";
            echo "Provide ?key=<csrf_token>\n";
            exit;
        }

        header('Content-Type: text/plain; charset=utf-8');
        echo "debug/files\n";
        echo "time=" . date('c') . "\n";
        echo "user_id=" . (string)($_SESSION['user_id'] ?? '') . "\n";
        echo "user_role=" . (string)($_SESSION['user_role'] ?? '') . "\n";
        echo "php=" . PHP_VERSION . "\n\n";

        try {
            // Execute the same logic as index() but without rendering HTML views
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

            $me = new User();
            $me->find($_SESSION['user_id']);
            if (!$me->isAdmin()) {
                $sql .= " AND (fu.uploaded_by = :me1 OR EXISTS (
                            SELECT 1 FROM file_shares fs
                            WHERE fs.file_id = fu.id AND fs.recipient_type = 'user' AND fs.recipient_id = :me2
                        ))";
                $params['me1'] = (int)$_SESSION['user_id'];
                $params['me2'] = (int)$_SESSION['user_id'];
            }
            $sql .= " ORDER BY fu.created_at DESC LIMIT 200";

            echo "SQL:\n{$sql}\n\n";
            echo "Params:\n" . print_r($params, true) . "\n";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $files = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            echo "Files count: " . count($files) . "\n";

            // Try building recipients too (this is where landlord-only crashes often happen)
            $recipients = [ 'tenants'=>[], 'caretakers'=>[], 'admins'=>[], 'users'=>[] ];
            $tenantModel = new Tenant();
            $tenants = $tenantModel->getAll($_SESSION['user_id']) ?? [];
            echo "Tenants count: " . count($tenants) . "\n";

            $caretakerIds = [];
            $propertyIds = [];
            try {
                $propertyIds = $me->getAccessiblePropertyIds();
            } catch (\Exception $e) {
                $propertyIds = [];
            }
            echo "Accessible property IDs: " . json_encode($propertyIds) . "\n";
            if (!empty($propertyIds)) {
                $inProps = implode(',', array_fill(0, count($propertyIds), '?'));
                $stmtP = $this->db->prepare("SELECT caretaker_user_id FROM properties WHERE id IN ($inProps)");
                $stmtP->execute(array_map('intval', $propertyIds));
                foreach ($stmtP->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                    if (!empty($row['caretaker_user_id'])) {
                        $caretakerIds[(int)$row['caretaker_user_id']] = true;
                    }
                }
            }
            echo "Caretaker IDs: " . json_encode(array_keys($caretakerIds)) . "\n";

            echo "\nOK\n";
        } catch (\Throwable $e) {
            echo "\nEXCEPTION\n";
            echo get_class($e) . ": " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
            echo $e->getTraceAsString() . "\n";
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

            // Restrict visibility for non-admins: own uploads or files shared to them
            $me = new User();
            $me->find($_SESSION['user_id']);
            if (!$me->isAdmin()) {
                $sql .= " AND (fu.uploaded_by = :me1 OR EXISTS (
                            SELECT 1 FROM file_shares fs
                            WHERE fs.file_id = fu.id AND fs.recipient_type = 'user' AND fs.recipient_id = :me2
                        ))";
                $params['me1'] = (int)$_SESSION['user_id'];
                $params['me2'] = (int)$_SESSION['user_id'];
            }
            $sql .= " ORDER BY fu.created_at DESC LIMIT 200";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $files = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Build share recipient lists
            $recipients = [ 'tenants'=>[], 'caretakers'=>[], 'admins'=>[], 'users'=>[] ];
            try {
                // Tenants for this user
                $tenantModel = new Tenant();
                foreach (($tenantModel->getAll($_SESSION['user_id']) ?? []) as $t) {
                    $recipients['tenants'][] = [
                        'id' => (int)$t['id'],
                        'name' => $t['name'] ?? ('Tenant #'.$t['id']),
                        'property' => $t['property_name'] ?? null,
                        'unit' => $t['unit_number'] ?? null,
                    ];
                }
                // Caretakers for accessible properties
                $caretakerIds = [];
                $propertyIds = [];
                try {
                    // Use lightweight property scope from User model (avoids heavy GROUP BY queries)
                    $propertyIds = $me->getAccessiblePropertyIds();
                } catch (\Exception $e) {
                    $propertyIds = [];
                }
                if (!empty($propertyIds)) {
                    $inProps = implode(',', array_fill(0, count($propertyIds), '?'));
                    $stmtP = $this->db->prepare("SELECT caretaker_user_id FROM properties WHERE id IN ($inProps)");
                    $stmtP->execute(array_map('intval', $propertyIds));
                    foreach ($stmtP->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                        if (!empty($row['caretaker_user_id'])) {
                            $caretakerIds[(int)$row['caretaker_user_id']] = true;
                        }
                    }
                }
                if (!empty($caretakerIds)) {
                    $ids = array_keys($caretakerIds);
                    $in = implode(',', array_fill(0, count($ids), '?'));
                    $userModel = new User();
                    $stmtU = $userModel->getDb()->prepare("SELECT id, name, role FROM users WHERE id IN ($in)");
                    $stmtU->execute($ids);
                    foreach ($stmtU->fetchAll(\PDO::FETCH_ASSOC) as $u) {
                        $recipients['caretakers'][] = [ 'id'=>(int)$u['id'], 'name'=>$u['name'] ?? ('User #'.$u['id']), 'role'=>$u['role'] ?? 'caretaker' ];
                    }
                }
                // Admins
                $userModel = new User();
                $stmtA = $userModel->getDb()->prepare("SELECT id, name FROM users WHERE role IN ('admin','administrator') ORDER BY name ASC");
                $stmtA->execute();
                foreach ($stmtA->fetchAll(\PDO::FETCH_ASSOC) as $a) { $recipients['admins'][] = [ 'id'=>(int)$a['id'], 'name'=>$a['name'] ?? ('Admin #'.$a['id']) ]; }
                // If admin, all users
                if ($me->isAdmin()) {
                    $stmtAll = $userModel->getDb()->prepare("SELECT id, name, role FROM users WHERE id <> ? ORDER BY name ASC");
                    $stmtAll->execute([(int)$_SESSION['user_id']]);
                    foreach ($stmtAll->fetchAll(\PDO::FETCH_ASSOC) as $u) { $recipients['users'][] = [ 'id'=>(int)$u['id'], 'name'=>$u['name'] ?? ('User #'.$u['id']), 'role'=>$u['role'] ?? null ]; }
                }
            } catch (Exception $e) { /* ignore */ }

            echo view('files/index', [
                'title' => 'Files - RentSmart',
                'files' => $files,
                'shareRecipients' => $recipients,
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
            // Restrict for non-admin
            $me = new User();
            $me->find($_SESSION['user_id']);
            if (!$me->isAdmin()) {
                $sql .= " AND (fu.uploaded_by = :me1 OR EXISTS (
                            SELECT 1 FROM file_shares fs
                            WHERE fs.file_id = fu.id AND fs.recipient_type = 'user' AND fs.recipient_id = :me2
                        ))";
                $params['me1'] = (int)$_SESSION['user_id'];
                $params['me2'] = (int)$_SESSION['user_id'];
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

    /**
     * Share a file with a user or tenant
     */
    public function share()
    {
        header('Content-Type: application/json');
        try {
            if (!function_exists('verify_csrf_token') || !verify_csrf_token()) {
                throw new Exception('Invalid security token');
            }
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                throw new Exception('Invalid request');
            }
            $fileId = (int)($_POST['file_id'] ?? 0);
            $category = trim($_POST['recipient_category'] ?? ''); // tenants, caretakers, admins, users
            $recipientId = (int)($_POST['recipient_id'] ?? 0);
            if ($fileId <= 0 || $recipientId <= 0 || $category === '') {
                throw new Exception('Missing fields');
            }

            // Load file and check ownership or admin
            $stmt = $this->db->prepare("SELECT * FROM file_uploads WHERE id = ? LIMIT 1");
            $stmt->execute([$fileId]);
            $file = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$file) { throw new Exception('File not found'); }

            $userModel = new User();
            $me = $userModel->find($_SESSION['user_id']);
            $userModel->find($_SESSION['user_id']);
            $isOwner = ((int)($file['uploaded_by'] ?? 0) === (int)($_SESSION['user_id'] ?? 0));
            $isAdmin = $userModel->isAdmin();
            if (!$isOwner && !$isAdmin) {
                throw new Exception('Only the uploader or admin can share this file');
            }

            // Map category to recipient_type
            $recipientType = ($category === 'tenants') ? 'tenant' : 'user';

            // Role-based restrictions
            if (!$isAdmin) {
                if ($recipientType === 'tenant') {
                    // Ensure tenant is accessible
                    $t = (new Tenant())->getById($recipientId, (int)$_SESSION['user_id']);
                    if (empty($t)) throw new Exception('You cannot share with this tenant');
                } else {
                    // recipient is user; enforce no owner-to-owner share and scope
                    $target = (new User())->find($recipientId);
                    if (!$target) throw new Exception('Recipient not found');
                    $senderRole = strtolower($me['role'] ?? '');
                    $targetRole = strtolower($target['role'] ?? '');
                    if ($senderRole === 'landlord' && $targetRole === 'landlord') {
                        throw new Exception('Property owners cannot share files with other property owners');
                    }
                    if (in_array($senderRole, ['landlord','manager','agent'], true)) {
                        if ($targetRole === 'caretaker') {
                            // caretaker must be assigned to one of sender's properties
                            $ids = $userModel->getAccessiblePropertyIds();
                            if (empty($ids)) throw new Exception('No accessible properties to validate share');
                            $in = implode(',', array_fill(0, count($ids), '?'));
                            $stmtC = $userModel->getDb()->prepare("SELECT COUNT(*) AS c FROM properties WHERE caretaker_user_id = ? AND id IN ($in)");
                            $params = array_merge([$recipientId], $ids);
                            $stmtC->execute($params);
                            $row = $stmtC->fetch(\PDO::FETCH_ASSOC);
                            if ((int)($row['c'] ?? 0) <= 0) throw new Exception('You can only share with caretakers assigned to your properties');
                        } else {
                            // Block sharing to other L/M/A
                            if (in_array($targetRole, ['landlord','manager','agent'], true)) {
                                throw new Exception('You cannot share files with other property owners or managers');
                            }
                        }
                    }
                    if ($senderRole === 'caretaker') {
                        if (in_array($targetRole, ['admin','administrator'], true)) {
                            // ok
                        } elseif (in_array($targetRole, ['landlord','manager','agent'], true)) {
                            // must be tied to caretaker's properties
                            $ids = $userModel->getAccessiblePropertyIds();
                            if (empty($ids)) throw new Exception('No accessible properties to validate share');
                            $in = implode(',', array_fill(0, count($ids), '?'));
                            $stmtLM = $userModel->getDb()->prepare("SELECT COUNT(*) AS c FROM properties WHERE id IN ($in) AND (owner_id = ? OR manager_id = ? OR agent_id = ?)");
                            $params = array_merge($ids, [$recipientId, $recipientId, $recipientId]);
                            $stmtLM->execute($params);
                            $row = $stmtLM->fetch(\PDO::FETCH_ASSOC);
                            if ((int)($row['c'] ?? 0) <= 0) throw new Exception('You can only share with landlords/managers/agents of your properties');
                        } else {
                            throw new Exception('Caretakers can only share with admins, landlords, managers, or agents');
                        }
                    }
                }
            }

            // Insert share if not exists
            $check = $this->db->prepare("SELECT id FROM file_shares WHERE file_id = ? AND recipient_type = ? AND recipient_id = ? LIMIT 1");
            $check->execute([$fileId, $recipientType, $recipientId]);
            if (!$check->fetch()) {
                $ins = $this->db->prepare("INSERT INTO file_shares (file_id, recipient_type, recipient_id) VALUES (?, ?, ?)");
                $ins->execute([$fileId, $recipientType, $recipientId]);
            }

            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
            exit;
        }
    }
}
