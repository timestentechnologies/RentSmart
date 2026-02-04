<?php

namespace App\Controllers;

use App\Models\ESignRequest;
use App\Models\Tenant;
use App\Models\User;

class ESignController
{
    private $userId;

    public function __construct()
    {
        $this->userId = $_SESSION['user_id'] ?? null;
    }

    public function index()
    {
        if (!$this->userId) { $_SESSION['flash_message'] = 'Please login'; redirect('/'); return; }
        $model = new ESignRequest();
        $requests = $model->listForUser((int)$this->userId);
        require 'views/esign/index.php';
    }

    public function create()
    {
        if (!$this->userId) { $_SESSION['flash_message'] = 'Please login'; redirect('/'); return; }
        $tenantModel = new Tenant();
        $tenants = $tenantModel->getAll($this->userId);
        // Users by selected roles
        $userModel = new User();
        $db = $userModel->getDb();
        $stmt = $db->prepare("SELECT id, name, role, email FROM users WHERE role IN ('admin','administrator','manager','agent','landlord','caretaker') ORDER BY name ASC");
        $stmt->execute();
        $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $entity_type = $_GET['entity_type'] ?? null;
        $entity_id = isset($_GET['entity_id']) ? (int)$_GET['entity_id'] : null;
        require 'views/esign/create.php';
    }

    public function store()
    {
        if (!$this->userId) { $_SESSION['flash_message'] = 'Please login'; redirect('/'); return; }
        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') throw new \Exception('Invalid request');
            if (!function_exists('verify_csrf_token') || !verify_csrf_token()) throw new \Exception('Invalid CSRF token');
            $title = trim($_POST['title'] ?? '');
            $message = trim($_POST['message'] ?? '');
            $recipient_type = ($_POST['recipient_type'] ?? '') === 'tenant' ? 'tenant' : 'user';
            $recipient_id = (int)($_POST['recipient_id'] ?? 0);
            $entity_type = $_POST['entity_type'] ?? null;
            $entity_id = !empty($_POST['entity_id']) ? (int)$_POST['entity_id'] : null;
            $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] . ' 23:59:59' : null;
            if ($title === '' || $recipient_id <= 0) throw new \Exception('Title and recipient are required');

            // Handle optional document upload
            $document_path = null;
            if (!empty($_FILES['document']) && is_uploaded_file($_FILES['document']['tmp_name'])) {
                $err = $_FILES['document']['error'] ?? UPLOAD_ERR_OK;
                if ($err !== UPLOAD_ERR_OK) throw new \Exception('Upload failed');
                $allowed = ['application/pdf','image/png','image/jpeg'];
                $mime = mime_content_type($_FILES['document']['tmp_name']);
                if (!in_array($mime, $allowed)) throw new \Exception('Only PDF/PNG/JPG allowed');
                $ext = pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION) ?: ($mime === 'application/pdf' ? 'pdf' : ($mime === 'image/png' ? 'png' : 'jpg'));
                $dir = __DIR__ . '/../../public/uploads/esign';
                if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
                $fname = 'doc_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest = $dir . '/' . $fname;
                if (!move_uploaded_file($_FILES['document']['tmp_name'], $dest)) throw new \Exception('Failed to save upload');
                $document_path = 'uploads/esign/' . $fname;
            }
            $model = new ESignRequest();
            $result = $model->createRequest([
                'title' => $title,
                'message' => $message,
                'requester_user_id' => (int)$this->userId,
                'recipient_type' => $recipient_type,
                'recipient_id' => $recipient_id,
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'expires_at' => $expires_at,
                'document_path' => $document_path,
            ]);
            $_SESSION['flash_message'] = 'Signature request created';
            $_SESSION['flash_type'] = 'success';
            redirect('/esign/show/' . (int)$result['id']);
            return;
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            redirect('/esign/create');
            return;
        }
    }

    public function show($id)
    {
        if (!$this->userId) { $_SESSION['flash_message'] = 'Please login'; redirect('/'); return; }
        $model = new ESignRequest();
        $req = $model->getById((int)$id);
        if (!$req) { http_response_code(404); echo 'Request not found'; return; }
        $signUrl = BASE_URL . '/esign/sign/' . $req['token'];
        require 'views/esign/show.php';
    }

    // Public sign page
    public function sign($token)
    {
        $model = new ESignRequest();
        $req = $model->getByToken($token);
        $invalid = false;
        if (!$req) { $invalid = true; }
        if (!$invalid && $req['status'] !== 'pending') { $invalid = true; }
        if (!$invalid && !empty($req['expires_at']) && strtotime($req['expires_at']) < time()) { $invalid = true; }
        require 'views/esign/sign.php';
    }

    public function submit($token)
    {
        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') throw new \Exception('Invalid request');
            $name = trim($_POST['signer_name'] ?? '');
            $method = $_POST['method'] ?? 'draw';
            if ($name === '') throw new \Exception('Name is required');
            $data = '';
            $sigType = null;
            $initials = null;

            if ($method === 'draw') {
                $data = $_POST['signature_data'] ?? '';
                if ($data === '') throw new \Exception('Please draw your signature');
                if (strpos($data, 'base64,') !== false) { $data = substr($data, strpos($data, 'base64,') + 7); }
                $sigType = 'draw';
            } elseif ($method === 'upload') {
                if (empty($_FILES['signature_file']) || !is_uploaded_file($_FILES['signature_file']['tmp_name'])) throw new \Exception('Upload a signature image');
                $err = $_FILES['signature_file']['error'] ?? UPLOAD_ERR_OK;
                if ($err !== UPLOAD_ERR_OK) throw new \Exception('Signature upload failed');
                $allowed = ['image/png','image/jpeg'];
                $mime = mime_content_type($_FILES['signature_file']['tmp_name']);
                if (!in_array($mime, $allowed)) throw new \Exception('Only PNG/JPG signature allowed');
                $bin = file_get_contents($_FILES['signature_file']['tmp_name']);
                $data = base64_encode($bin);
                $sigType = 'upload';
            } elseif ($method === 'initials') {
                $initials = trim($_POST['signature_initials'] ?? '');
                if ($initials === '') throw new \Exception('Enter your initials');
                $data = '';
                $sigType = 'initials';
            } else {
                throw new \Exception('Invalid method');
            }
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $model = new ESignRequest();
            $ok = $model->markSigned($token, $name, $data, $ip, $ua, $sigType, $initials);
            if (!$ok) throw new \Exception('Unable to save signature');
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Signature Saved</title><style>body{font-family:Arial;padding:24px}</style></head><body><h2>Thank you!</h2><p>Your signature has been recorded.</p></body></html>';
            return;
        } catch (\Exception $e) {
            http_response_code(400);
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Error</title><style>body{font-family:Arial;padding:24px;color:#a00}</style></head><body><h3>Error</h3><p>' . htmlspecialchars($e->getMessage()) . '</p></body></html>';
            return;
        }
    }

    public function decline($token)
    {
        $model = new ESignRequest();
        $model->markDeclined($token);
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Declined</title><style>body{font-family:Arial;padding:24px}</style></head><body><h2>Request Declined</h2><p>You have declined to sign this request.</p></body></html>';
    }
}
