<?php

namespace App\Controllers;

use App\Models\Inquiry;
use App\Models\Property;
use App\Models\Unit;
use App\Models\AgentClient;
use App\Models\AgentContract;
use App\Models\AgentLeadStage;
use App\Models\Subscription;

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

        $stageModel = new AgentLeadStage();
        $stages = $stageModel->getAll($this->userId);

        echo view('agent/leads', [
            'title' => 'CRM - Leads',
            'inquiries' => $inquiries,
            'stages' => $stages,
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

            $propertyName = trim((string)($_POST['property_name'] ?? ''));
            $address = trim((string)($_POST['address'] ?? ''));
            $name = trim((string)($_POST['name'] ?? ''));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $contact = '';
            $message = trim((string)($_POST['message'] ?? ''));

            if ($contact === '') {
                $parts = [];
                if ($phone !== '') { $parts[] = $phone; }
                if ($email !== '') { $parts[] = $email; }
                $contact = implode(' / ', $parts);
            }

            if ($name === '' || $contact === '') {
                $_SESSION['flash_message'] = 'Name and phone/email are required';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/agent/leads');
                exit;
            }

            $inquiryModel = new Inquiry();
            $inquiryId = $inquiryModel->create([
                'unit_id' => null,
                'property_id' => null,
                'property_name' => $propertyName !== '' ? $propertyName : null,
                'address' => $address !== '' ? $address : null,
                'crm_user_id' => (int)$this->userId,
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

    public function winCreateProperty($id)
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }

        try {
            if (!verify_csrf_token()) {
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                exit;
            }

            $stageModel = new AgentLeadStage();
            $stages = $stageModel->getAll($this->userId);
            $wonKey = null;
            foreach (($stages ?? []) as $s) {
                if ((int)($s['is_won'] ?? 0) === 1) {
                    $wonKey = strtolower((string)($s['stage_key'] ?? 'won'));
                    break;
                }
            }
            if ($wonKey === null || $wonKey === '') {
                $wonKey = 'won';
            }

            $inquiryModel = new Inquiry();
            $inq = $inquiryModel->getByIdVisibleForUser((int)$id, (int)$this->userId, $this->role);
            if (!$inq) {
                echo json_encode(['success' => false, 'message' => 'Lead not found']);
                exit;
            }

            $propertyName = trim((string)($inq['property_name'] ?? ''));
            $leadAddress = trim((string)($inq['address'] ?? ''));

            if ($propertyName === '') {
                echo json_encode(['success' => false, 'message' => 'Property name is required to create a property']);
                exit;
            }

            // Enforce subscription property limits (same logic as PropertyController)
            $subModel = new Subscription();
            $sub = $subModel->getUserSubscription((int)$this->userId);
            $planName = strtolower($sub['name'] ?? ($sub['plan_type'] ?? ''));
            $propertyLimit = null;
            if (isset($sub['property_limit']) && $sub['property_limit'] !== null && $sub['property_limit'] !== '') {
                $propertyLimit = (int)$sub['property_limit'];
                if ($propertyLimit <= 0) { $propertyLimit = null; }
            } else {
                if ($planName === 'basic') { $propertyLimit = 10; }
                elseif ($planName === 'professional') { $propertyLimit = 50; }
                elseif ($planName === 'enterprise') { $propertyLimit = null; }
            }

            $propertyModel = new Property();
            $currentProps = $propertyModel->getAll((int)$this->userId);
            $propCount = is_array($currentProps) ? count($currentProps) : 0;
            if ($propertyLimit !== null && $propCount >= $propertyLimit) {
                $msg = 'You have reached your plan limit of ' . $propertyLimit . ' properties. Please upgrade to add more.';
                echo json_encode([
                    'success' => false,
                    'over_limit' => true,
                    'type' => 'property',
                    'limit' => $propertyLimit,
                    'current' => $propCount,
                    'plan' => $sub['name'] ?? ($sub['plan_type'] ?? ''),
                    'upgrade_url' => BASE_URL . '/subscription/renew',
                    'message' => $msg,
                ]);
                exit;
            }

            $db = $inquiryModel->getDb();
            $db->beginTransaction();
            try {
                $ok = $inquiryModel->updateCrmStageWithAccess((int)$id, (int)$this->userId, $this->role, $wonKey);
                if (!$ok) {
                    throw new \Exception('Failed to mark lead as won');
                }

                $propData = [
                    'name' => $propertyName,
                    'address' => $leadAddress !== '' ? $leadAddress : $propertyName,
                    'city' => 'Nairobi',
                    'state' => 'Kenya',
                    'zip_code' => '00000',
                    'property_type' => 'apartment',
                    'description' => null,
                    'year_built' => null,
                    'total_area' => null,
                ];

                if ($this->role === 'landlord') {
                    $propData['owner_id'] = (int)$this->userId;
                } elseif ($this->role === 'manager') {
                    $propData['manager_id'] = (int)$this->userId;
                } elseif ($this->role === 'agent') {
                    $propData['agent_id'] = (int)$this->userId;
                }

                $propertyId = (int)$propertyModel->create($propData);
                if ($propertyId <= 0) {
                    throw new \Exception('Failed to create property');
                }

                $db->commit();
            } catch (\Exception $ex) {
                $db->rollBack();
                throw $ex;
            }

            echo json_encode([
                'success' => true,
                'property_id' => (int)$propertyId,
                'redirect_url' => BASE_URL . '/properties/edit/' . (int)$propertyId,
            ]);
        } catch (\Exception $e) {
            error_log('AgentLeads winCreateProperty failed: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Server error']);
        }
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

            $stageModel = new AgentLeadStage();
            $stages = $stageModel->getAll($this->userId);
            $allowedKeys = [];
            $wonKey = null;
            foreach (($stages ?? []) as $s) {
                $k = strtolower((string)($s['stage_key'] ?? ''));
                if ($k === '') {
                    continue;
                }
                $allowedKeys[] = $k;
                if ((int)($s['is_won'] ?? 0) === 1 && $wonKey === null) {
                    $wonKey = $k;
                }
            }
            if (!in_array($stage, $allowedKeys, true)) {
                $stage = in_array('new', $allowedKeys, true) ? 'new' : (($allowedKeys[0] ?? '') ?: 'new');
            }

            $model = new Inquiry();
            $ok = $model->updateCrmStageWithAccess((int)$id, (int)$this->userId, $this->role, $stage);

            $clientId = null;
            $contractId = null;
            if ($ok && ($wonKey !== null && $stage === $wonKey)) {
                try {
                    $conv = $this->maybeConvertInquiryToClientAndContract((int)$id);
                    $clientId = $conv['client_id'] ?? null;
                    $contractId = $conv['contract_id'] ?? null;
                } catch (\Exception $ex) {
                    error_log('Agent lead win conversion failed (non-blocking): ' . $ex->getMessage());
                }
            }

            echo json_encode([
                'success' => (bool)$ok,
                'stage' => $stage,
                'client_id' => $clientId,
                'contract_id' => $contractId,
            ]);
        } catch (\Exception $e) {
            error_log('AgentLeads updateStage failed: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Server error']);
        }
        exit;
    }

    public function stages()
    {
        header('Content-Type: application/json');
        try {
            $stageModel = new AgentLeadStage();
            $stages = $stageModel->getAll($this->userId);
            echo json_encode(['success' => true, 'data' => $stages]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to load stages']);
        }
        exit;
    }

    public function storeStage()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }
        try {
            if (!verify_csrf_token()) {
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                exit;
            }
            $stageKey = strtolower(trim((string)($_POST['stage_key'] ?? '')));
            $label = trim((string)($_POST['label'] ?? ''));
            $colorClass = trim((string)($_POST['color_class'] ?? 'secondary'));
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            $isWon = (int)($_POST['is_won'] ?? 0) === 1 ? 1 : 0;
            $isLost = (int)($_POST['is_lost'] ?? 0) === 1 ? 1 : 0;

            if ($stageKey === '' || $label === '') {
                echo json_encode(['success' => false, 'message' => 'Stage key and label are required']);
                exit;
            }

            $stageModel = new AgentLeadStage();
            $id = $stageModel->insert([
                'user_id' => $this->userId,
                'stage_key' => $stageKey,
                'label' => $label,
                'color_class' => $colorClass,
                'sort_order' => $sortOrder,
                'is_won' => $isWon,
                'is_lost' => $isLost,
            ]);
            echo json_encode(['success' => true, 'id' => (int)$id]);
        } catch (\Exception $e) {
            error_log('Agent storeStage failed: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to add stage']);
        }
        exit;
    }

    public function updateStageMeta($id)
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }
        try {
            if (!verify_csrf_token()) {
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                exit;
            }

            $stageModel = new AgentLeadStage();
            $row = $stageModel->getByIdWithAccess((int)$id, $this->userId);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Stage not found']);
                exit;
            }

            $data = [
                'label' => trim((string)($_POST['label'] ?? ($row['label'] ?? ''))),
                'color_class' => trim((string)($_POST['color_class'] ?? ($row['color_class'] ?? 'secondary'))),
                'sort_order' => (int)($_POST['sort_order'] ?? ($row['sort_order'] ?? 0)),
                'is_won' => (int)($_POST['is_won'] ?? ($row['is_won'] ?? 0)) === 1 ? 1 : 0,
                'is_lost' => (int)($_POST['is_lost'] ?? ($row['is_lost'] ?? 0)) === 1 ? 1 : 0,
            ];

            $ok = $stageModel->updateById((int)$id, $data);
            echo json_encode(['success' => (bool)$ok]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to update stage']);
        }
        exit;
    }

    public function deleteStage($id)
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }
        try {
            if (!verify_csrf_token()) {
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                exit;
            }

            $stageModel = new AgentLeadStage();
            $row = $stageModel->getByIdWithAccess((int)$id, $this->userId);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Stage not found']);
                exit;
            }

            if (in_array((string)($row['stage_key'] ?? ''), ['new', 'contacted', 'qualified', 'won', 'lost'], true)) {
                echo json_encode(['success' => false, 'message' => 'Default stages cannot be deleted']);
                exit;
            }

            $transferTo = strtolower(trim((string)($_POST['transfer_to'] ?? '')));
            $inquiryModel = new Inquiry();
            $countRows = $inquiryModel->query(
                "SELECT COUNT(*) AS c FROM inquiries i LEFT JOIN properties p ON p.id = i.property_id WHERE ((p.owner_id = ? OR p.manager_id = ? OR p.agent_id = ? OR p.caretaker_user_id = ?) OR (i.crm_user_id = ? AND i.source = 'agent_crm')) AND i.crm_stage = ?",
                [(int)$this->userId, (int)$this->userId, (int)$this->userId, (int)$this->userId, (int)$this->userId, (string)($row['stage_key'] ?? '')]
            );
            $inqCount = (int)($countRows[0]['c'] ?? 0);

            if ($inqCount > 0) {
                if ($transferTo === '') {
                    echo json_encode(['success' => false, 'message' => 'This stage has leads. Choose "Move Leads To" first.']);
                    exit;
                }
                $targetStage = $stageModel->getByKey($this->userId, $transferTo);
                if (!$targetStage) {
                    echo json_encode(['success' => false, 'message' => 'Target stage not found']);
                    exit;
                }

                // Transfer visible inquiries only (same scope as allVisibleForUser for non-admin)
                $inquiryModel->query(
                    "UPDATE inquiries i LEFT JOIN properties p ON p.id = i.property_id SET i.crm_stage = ? WHERE ((p.owner_id = ? OR p.manager_id = ? OR p.agent_id = ? OR p.caretaker_user_id = ?) OR (i.crm_user_id = ? AND i.source = 'agent_crm')) AND i.crm_stage = ?",
                    [(string)$transferTo, (int)$this->userId, (int)$this->userId, (int)$this->userId, (int)$this->userId, (int)$this->userId, (string)($row['stage_key'] ?? '')]
                );
            }

            $ok = $stageModel->deleteById((int)$id);
            echo json_encode(['success' => (bool)$ok]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to delete stage']);
        }
        exit;
    }

    private function maybeConvertInquiryToClientAndContract(int $inquiryId): array
    {
        $inquiryModel = new Inquiry();
        $inq = $inquiryModel->getByIdVisibleForUser($inquiryId, (int)$this->userId, $this->role);
        if (!$inq) {
            return ['client_id' => null, 'contract_id' => null];
        }

        $propertyId = (int)($inq['property_id'] ?? 0);
        $name = trim((string)($inq['name'] ?? ''));
        $contact = trim((string)($inq['contact'] ?? ''));
        $message = trim((string)($inq['message'] ?? ''));

        $clientId = null;
        if ($propertyId > 0 && ($name !== '' || $contact !== '')) {
            $clientModel = new AgentClient();
            $existing = $clientModel->query(
                "SELECT id FROM agent_clients WHERE user_id = ? AND property_id = ? AND (phone = ? OR email = ?) ORDER BY id DESC LIMIT 1",
                [(int)$this->userId, (int)$propertyId, (string)$contact, (string)$contact]
            );
            if (!empty($existing)) {
                $clientId = (int)($existing[0]['id'] ?? 0);
            } else {
                $clientId = (int)$clientModel->insert([
                    'user_id' => (int)$this->userId,
                    'property_id' => (int)$propertyId,
                    'name' => $name !== '' ? $name : $contact,
                    'phone' => $contact,
                    'email' => (strpos($contact, '@') !== false) ? $contact : null,
                    'notes' => $message !== '' ? $message : null,
                ]);
            }
        }

        $contractId = null;
        if ($clientId && $propertyId > 0) {
            try {
                $contractModel = new AgentContract();
                $existing = $contractModel->query(
                    "SELECT id FROM agent_contracts WHERE user_id = ? AND property_id = ? AND agent_client_id = ? ORDER BY id DESC LIMIT 1",
                    [(int)$this->userId, (int)$propertyId, (int)$clientId]
                );
                if (!empty($existing)) {
                    $contractId = (int)($existing[0]['id'] ?? 0);
                } else {
                    $contractId = (int)$contractModel->insert([
                        'user_id' => (int)$this->userId,
                        'property_id' => (int)$propertyId,
                        'agent_client_id' => (int)$clientId,
                        'terms_type' => 'one_time',
                        'total_amount' => 0,
                        'monthly_amount' => null,
                        'duration_months' => null,
                        'start_month' => null,
                        'instructions' => $message !== '' ? $message : null,
                        'status' => 'active',
                    ]);
                }
            } catch (\Exception $e) {
                // ignore (non-blocking)
            }
        }

        return ['client_id' => $clientId, 'contract_id' => $contractId];
    }
}
