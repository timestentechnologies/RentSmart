<?php

namespace App\Controllers;

use App\Models\Message;
use App\Models\Tenant;
use App\Models\Property;
use App\Models\User;

class MessagingController
{
    private $userId;
    private $userRole;

    public function __construct()
    {
        $this->userId = $_SESSION['user_id'] ?? null;
        $this->userRole = $_SESSION['user_role'] ?? null;
    }

    public function index()
    {
        // Build recipients list based on role and accessible properties
        $recipients = [
            'tenants' => [],
            'caretakers' => [],
            'admins' => []
        ];

        try {
            // Tenants (role-filtered)
            $tenantModel = new Tenant();
            $tenants = $tenantModel->getAll($this->userId);
            foreach ($tenants as $t) {
                $recipients['tenants'][] = [
                    'id' => (int)$t['id'],
                    'name' => $t['name'] ?? 'Tenant #'.$t['id'],
                    'property' => $t['property_name'] ?? null,
                    'unit' => $t['unit_number'] ?? null,
                ];
            }

            // Caretakers for accessible properties
            $propertyModel = new Property();
            $properties = $propertyModel->getAll($this->userId);
            $caretakerIds = [];
            foreach ($properties as $p) {
                if (!empty($p['caretaker_user_id'])) {
                    $caretakerIds[(int)$p['caretaker_user_id']] = true;
                }
            }
            if (!empty($caretakerIds)) {
                $ids = array_keys($caretakerIds);
                if (!empty($ids)) {
                    $in = implode(',', array_fill(0, count($ids), '?'));
                    $userModel = new User();
                    $stmt = $userModel->getDb()->prepare("SELECT id, name, role FROM users WHERE id IN ($in)");
                    $stmt->execute($ids);
                    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    foreach ($rows as $u) {
                        $recipients['caretakers'][] = [
                            'id' => (int)$u['id'],
                            'name' => $u['name'] ?? 'User #'.$u['id'],
                            'role' => $u['role'] ?? 'caretaker'
                        ];
                    }
                }
            }

            // Admins
            $userModel = new User();
            $stmt = $userModel->getDb()->prepare("SELECT id, name FROM users WHERE role IN ('admin','administrator') ORDER BY name ASC");
            $stmt->execute();
            $admins = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($admins as $a) {
                $recipients['admins'][] = [
                    'id' => (int)$a['id'],
                    'name' => $a['name'] ?? 'Admin #'.$a['id']
                ];
            }
        } catch (\Exception $e) {
            $recipients = ['tenants'=>[],'caretakers'=>[],'admins'=>[]];
        }

        require 'views/messaging/index.php';
    }

    public function thread()
    {
        header('Content-Type: application/json');
        try {
            $type = ($_GET['type'] ?? 'tenant') === 'user' ? 'user' : 'tenant';
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) throw new \Exception('Invalid recipient');

            $msg = new Message();
            $conv = $msg->getConversation('user', (int)$this->userId, $type, $id, 500);
            echo json_encode(['success'=>true,'messages'=>$conv]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        exit;
    }

    public function send()
    {
        header('Content-Type: application/json');
        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                throw new \Exception('Invalid request');
            }
            if (!function_exists('verify_csrf_token') || !verify_csrf_token()) {
                throw new \Exception('Invalid CSRF token');
            }
            if (!$this->userId) { throw new \Exception('Unauthorized'); }
            $receiverType = ($_POST['receiver_type'] ?? 'tenant') === 'user' ? 'user' : 'tenant';
            $receiverId = (int)($_POST['receiver_id'] ?? 0);
            $body = trim($_POST['body'] ?? '');
            if ($receiverId <= 0 || $body === '') throw new \Exception('Message and recipient are required');

            $msg = new Message();
            $id = $msg->insertMessage([
                'sender_type' => 'user',
                'sender_id' => (int)$this->userId,
                'receiver_type' => $receiverType,
                'receiver_id' => $receiverId,
                'body' => $body
            ]);
            echo json_encode(['success'=>true,'id'=>$id]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        exit;
    }
}
