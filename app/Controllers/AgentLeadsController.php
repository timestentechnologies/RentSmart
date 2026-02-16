<?php

namespace App\Controllers;

use App\Models\Inquiry;
use App\Models\Property;
use App\Models\Unit;

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

        $propertyModel = new Property();
        $properties = $propertyModel->getAll($this->userId);

        $unitModel = new Unit();
        $units = $unitModel->getAll($this->userId);

        echo view('agent/leads', [
            'title' => 'CRM - Leads',
            'inquiries' => $inquiries,
            'properties' => $properties,
            'units' => $units,
        ]);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/agent/leads');
            exit;
        }

        try {
            if (!verify_csrf_token()) {
                $_SESSION['flash_message'] = 'Invalid security token';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/agent/leads');
                exit;
            }

            $propertyId = (int)($_POST['property_id'] ?? 0);
            $unitId = (int)($_POST['unit_id'] ?? 0);
            $name = trim((string)($_POST['name'] ?? ''));
            $contact = trim((string)($_POST['contact'] ?? ''));
            $message = trim((string)($_POST['message'] ?? ''));

            if ($propertyId <= 0 || $name === '' || $contact === '') {
                $_SESSION['flash_message'] = 'Property, name and contact are required';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/agent/leads');
                exit;
            }

            $propertyModel = new Property();
            $property = $propertyModel->getById($propertyId, $this->userId);
            if (!$property) {
                $_SESSION['flash_message'] = 'Invalid property selected';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/agent/leads');
                exit;
            }

            $resolvedUnitId = null;
            if ($unitId > 0) {
                $unitModel = new Unit();
                $unit = $unitModel->getById($unitId, $this->userId);
                if ($unit && (int)($unit['property_id'] ?? 0) === $propertyId) {
                    $resolvedUnitId = (int)$unitId;
                }
            }

            $inquiryModel = new Inquiry();
            $inquiryId = $inquiryModel->create([
                'unit_id' => $resolvedUnitId,
                'property_id' => $propertyId,
                'name' => $name,
                'contact' => $contact,
                'message' => $message,
                'source' => 'agent_crm',
            ]);

            try {
                $inquiryModel->updateCrmStageWithAccess((int)$inquiryId, (int)$this->userId, $this->role, 'new');
            } catch (\Exception $e) {
            }

            $_SESSION['flash_message'] = 'Lead added successfully';
            $_SESSION['flash_type'] = 'success';
        } catch (\Exception $e) {
            error_log('AgentLeads store failed: ' . $e->getMessage());
            $_SESSION['flash_message'] = 'Failed to add lead';
            $_SESSION['flash_type'] = 'danger';
        }

        header('Location: ' . BASE_URL . '/agent/leads');
        exit;
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
