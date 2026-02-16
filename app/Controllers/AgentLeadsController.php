<?php

namespace App\Controllers;

use App\Models\Inquiry;

class AgentLeadsController
{
    private $userId;
    private $role;

    public function __construct()
    {
        $this->userId = $_SESSION['user_id'] ?? null;
        $this->role = strtolower((string)($_SESSION['user_role'] ?? 'guest'));

        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Please login to continue';
            $_SESSION['flash_type'] = 'warning';
            header('Location: ' . BASE_URL . '/');
            exit;
        }
        if ($this->role === 'realtor') {
            $_SESSION['flash_message'] = 'Access denied';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }

    public function index()
    {
        $inquiryModel = new Inquiry();
        $inquiries = $inquiryModel->allVisibleForUser($this->userId, $this->role);

        echo view('agent/leads', [
            'title' => 'CRM - Leads',
            'inquiries' => $inquiries,
        ]);
    }

    public function updateStage($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }

        try {
            if (!verify_csrf_token()) {
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                exit;
            }

            $payloadStage = $_POST['stage'] ?? '';
            $stage = strtolower(trim((string)$payloadStage));

            $allowed = ['new', 'contacted', 'qualified', 'won', 'lost'];
            if (!in_array($stage, $allowed, true)) {
                $stage = 'new';
            }

            $model = new Inquiry();
            $ok = $model->updateCrmStageWithAccess((int)$id, (int)$this->userId, $this->role, $stage);
            echo json_encode(['success' => (bool)$ok, 'stage' => $stage]);
        } catch (\Exception $e) {
            error_log('AgentLeads updateStage failed: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Server error']);
        }
        exit;
    }
}
