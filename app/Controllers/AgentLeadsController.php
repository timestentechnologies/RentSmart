<?php

namespace App\Controllers;

use App\Models\Inquiry;
use App\Models\Property;
use App\Models\Unit;
use App\Models\AgentClient;
use App\Models\Tenant;
use App\Models\Lease;

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

            $clientId = null;
            $leaseId = null;
            if ($ok && $stage === 'won') {
                try {
                    $conv = $this->maybeConvertInquiryToClientAndLease((int)$id);
                    $clientId = $conv['client_id'] ?? null;
                    $leaseId = $conv['lease_id'] ?? null;
                } catch (\Exception $ex) {
                    error_log('Agent lead win conversion failed (non-blocking): ' . $ex->getMessage());
                }
            }

            echo json_encode([
                'success' => (bool)$ok,
                'stage' => $stage,
                'client_id' => $clientId,
                'lease_id' => $leaseId,
            ]);
        } catch (\Exception $e) {
            error_log('AgentLeads updateStage failed: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Server error']);
        }
        exit;
    }

    private function maybeConvertInquiryToClientAndLease(int $inquiryId): array
    {
        $inquiryModel = new Inquiry();
        $inq = $inquiryModel->getByIdVisibleForUser($inquiryId, (int)$this->userId, $this->role);
        if (!$inq) {
            return ['client_id' => null, 'lease_id' => null];
        }

        $propertyId = (int)($inq['property_id'] ?? 0);
        $unitId = (int)($inq['unit_id'] ?? 0);
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

        $leaseId = null;
        if ($unitId > 0) {
            // If unit already has an active lease, treat it as the "contract".
            $leaseModel = new Lease();
            $existingLease = $leaseModel->query(
                "SELECT l.id FROM leases l WHERE l.unit_id = ? AND l.status = 'active' ORDER BY l.id DESC LIMIT 1",
                [(int)$unitId]
            );
            if (!empty($existingLease)) {
                $leaseId = (int)($existingLease[0]['id'] ?? 0);
                return ['client_id' => $clientId, 'lease_id' => $leaseId];
            }

            // Create tenant (client in property mgmt sense) + new lease.
            $tenantModel = new Tenant();
            $emailGuess = (strpos($contact, '@') !== false) ? $contact : null;
            $phoneGuess = (strpos($contact, '@') !== false) ? '' : $contact;

            $tenantId = null;
            if (!empty($emailGuess)) {
                $t = $tenantModel->findByEmail($emailGuess);
                if (!empty($t['id'])) {
                    $tenantId = (int)$t['id'];
                }
            }

            if (empty($tenantId)) {
                $plainPassword = bin2hex(random_bytes(4));
                $tenantId = (int)$tenantModel->create([
                    'name' => $name !== '' ? $name : $contact,
                    'first_name' => $name !== '' ? $name : $contact,
                    'last_name' => '',
                    'email' => $emailGuess,
                    'phone' => $phoneGuess,
                    'id_type' => null,
                    'id_number' => null,
                    'registered_on' => date('Y-m-d'),
                    'emergency_contact' => null,
                    'notes' => $message !== '' ? $message : null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'property_id' => $propertyId > 0 ? $propertyId : null,
                    'unit_id' => (int)$unitId,
                    'password' => password_hash($plainPassword, PASSWORD_DEFAULT),
                ]);
            }

            $unitModel = new Unit();
            $unit = $unitModel->getById((int)$unitId, (int)$this->userId);
            $rent = (float)($unit['rent_amount'] ?? ($inq['unit_rent_amount'] ?? 0));
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime($startDate . ' +1 year'));
            $leaseId = (int)$leaseModel->create([
                'unit_id' => (int)$unitId,
                'tenant_id' => (int)$tenantId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'rent_amount' => $rent,
                'security_deposit' => $rent,
                'status' => 'active',
                'payment_day' => 1,
                'notes' => $message !== '' ? $message : null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // Mark unit as occupied and attach tenant (keeps UI consistent)
            try {
                $unitModel->update((int)$unitId, [
                    'status' => 'occupied',
                    'tenant_id' => (int)$tenantId,
                    'rent_amount' => $rent,
                ]);
            } catch (\Exception $e) {
            }

            // Auto-create draft invoices for this lease
            try {
                $inv = new \App\Models\Invoice();
                $inv->ensureInvoicesForLeaseMonths((int)$tenantId, (float)$rent, (string)$startDate, date('Y-m-d'), (int)($_SESSION['user_id'] ?? 0), 'AUTO');
            } catch (\Exception $e) {
            }
        }

        return ['client_id' => $clientId, 'lease_id' => $leaseId];
    }
}
