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
            $data = $_POST['signature_data'] ?? '';
            if ($name === '' || $data === '') throw new \Exception('Name and signature are required');
            // Expect data URL like data:image/png;base64,XXXX
            if (strpos($data, 'base64,') !== false) {
                $data = substr($data, strpos($data, 'base64,') + 7);
            }
            // Keep raw base64 in DB
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $model = new ESignRequest();
            $ok = $model->markSigned($token, $name, $data, $ip, $ua);
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
