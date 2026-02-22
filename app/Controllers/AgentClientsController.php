<?php

namespace App\Controllers;

use App\Models\AgentClient;
use App\Models\AgentContract;
use App\Models\Property;
use App\Models\Unit;

class AgentClientsController
{
    private $userId;

    public function __construct()
    {
        $this->userId = $_SESSION['user_id'] ?? null;
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Please login to continue';
            $_SESSION['flash_type'] = 'warning';
            header('Location: ' . BASE_URL . '/');
            exit;
        }
        $role = strtolower((string)($_SESSION['user_role'] ?? ''));
        if ($role === 'realtor' || $role === 'landlord' || $role === 'manager') {
            $_SESSION['flash_message'] = 'Access denied';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }

    public function index()
    {
        $clientModel = new AgentClient();

        $clients = $clientModel->getAllForUser($this->userId);
        $properties = $clientModel->getAvailablePropertiesForUser($this->userId);

        echo view('agent/clients', [
            'title' => 'Clients',
            'clients' => $clients,
            'properties' => $properties,
        ]);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/agent/clients');
            exit;
        }

        try {
            if (!verify_csrf_token()) {
                $_SESSION['flash_message'] = 'Invalid security token';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/agent/clients');
                exit;
            }

            $propertyIdsRaw = $_POST['property_ids'] ?? [];
            if (!is_array($propertyIdsRaw)) {
                $propertyIdsRaw = [$propertyIdsRaw];
            }
            $propertyIds = array_values(array_unique(array_filter(array_map('intval', $propertyIdsRaw), fn($v) => $v > 0)));
            $name = trim((string)($_POST['name'] ?? ''));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $notes = trim((string)($_POST['notes'] ?? ''));

            if (empty($propertyIds) || $name === '' || $phone === '') {
                $_SESSION['flash_message'] = 'Property, name and phone are required';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/agent/clients');
                exit;
            }

            $propertyModel = new Property();
            foreach ($propertyIds as $pid) {
                $property = $propertyModel->getById((int)$pid, $this->userId);
                if (!$property) {
                    $_SESSION['flash_message'] = 'Invalid property selected';
                    $_SESSION['flash_type'] = 'danger';
                    header('Location: ' . BASE_URL . '/agent/clients');
                    exit;
                }
            }

            $clientModel = new AgentClient();
            foreach ($propertyIds as $pid) {
                if (!$clientModel->isPropertyAvailableForClient((int)$pid, (int)$this->userId, null)) {
                    $_SESSION['flash_message'] = 'One or more selected properties are already linked to another client';
                    $_SESSION['flash_type'] = 'danger';
                    header('Location: ' . BASE_URL . '/agent/clients');
                    exit;
                }
            }
            $txStarted = false;
            try {
                $txStarted = (bool)$clientModel->beginTransaction();
            } catch (\Throwable $e) {
                $txStarted = false;
            }
            try {
                $clientId = $clientModel->insert([
                    'user_id' => (int)$this->userId,
                    'property_id' => null,
                    'name' => $name,
                    'phone' => $phone,
                    'email' => $email,
                    'notes' => $notes,
                ]);

                $clientModel->syncClientProperties((int)$clientId, (int)$this->userId, $propertyIds);

                $contractModel = new AgentContract();
                $unitModel = new Unit();
                foreach ($propertyIds as $pid) {
                    $existingId = $contractModel->getIdByClientProperty((int)$this->userId, (int)$clientId, (int)$pid);
                    if ($existingId) {
                        continue;
                    }

                    $units = $unitModel->query("SELECT id, rent_amount FROM units WHERE property_id = ? ORDER BY id", [(int)$pid]);
                    $unitIds = [];
                    $rentTotal = 0.0;
                    foreach (($units ?? []) as $u) {
                        $uid = (int)($u['id'] ?? 0);
                        if ($uid <= 0) continue;
                        $unitIds[] = $uid;
                        $rentTotal += (float)($u['rent_amount'] ?? 0);
                    }
                    $commissionPercent = 10.0;
                    $totalAmount = $rentTotal > 0 ? round(($rentTotal * $commissionPercent) / 100, 2) : 0.0;

                    $newContractId = $contractModel->insert([
                        'user_id' => (int)$this->userId,
                        'property_id' => (int)$pid,
                        'agent_client_id' => (int)$clientId,
                        'terms_type' => 'one_time',
                        'total_amount' => (float)$totalAmount,
                        'monthly_amount' => null,
                        'duration_months' => null,
                        'start_month' => null,
                        'instructions' => null,
                        'commission_percent' => (float)$commissionPercent,
                        'rent_total' => (float)$rentTotal,
                        'status' => 'active',
                    ]);
                    if (!empty($unitIds)) {
                        $contractModel->syncContractUnits((int)$newContractId, (int)$this->userId, $unitIds);
                    }
                }
                if ($txStarted && $clientModel->getDb()->inTransaction()) {
                    $clientModel->commit();
                }
            } catch (\Exception $e) {
                if ($txStarted && $clientModel->getDb()->inTransaction()) {
                    $clientModel->rollback();
                }
                throw $e;
            }

            $_SESSION['flash_message'] = 'Client added successfully';
            $_SESSION['flash_type'] = 'success';
        } catch (\Exception $e) {
            error_log('AgentClients store failed: ' . $e->getMessage());
            $_SESSION['flash_message'] = 'Failed to add client';
            $_SESSION['flash_type'] = 'danger';
        }

        header('Location: ' . BASE_URL . '/agent/clients');
        exit;
    }

    public function get($id)
    {
        header('Content-Type: application/json');
        try {
            $clientModel = new AgentClient();
            $row = $clientModel->getByIdWithAccess((int)$id, $this->userId);
            if (!$row) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Client not found']);
                exit;
            }
            $availableProperties = $clientModel->getAvailablePropertiesForUser($this->userId, (int)$id);
            $row['available_properties'] = $availableProperties;
            echo json_encode(['success' => true, 'data' => $row]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
        exit;
    }

    public function update($id)
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

            $clientModel = new AgentClient();
            $row = $clientModel->getByIdWithAccess((int)$id, $this->userId);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Client not found']);
                exit;
            }

            $previousPropertyIds = $clientModel->getClientPropertyIds((int)$id, (int)$this->userId);

            $propertyIdsRaw = $_POST['property_ids'] ?? ($row['property_ids'] ?? []);
            if (!is_array($propertyIdsRaw)) {
                $propertyIdsRaw = [$propertyIdsRaw];
            }
            $propertyIds = array_values(array_unique(array_filter(array_map('intval', $propertyIdsRaw), fn($v) => $v > 0)));
            $name = trim((string)($_POST['name'] ?? ($row['name'] ?? '')));
            $phone = trim((string)($_POST['phone'] ?? ($row['phone'] ?? '')));
            $email = trim((string)($_POST['email'] ?? ($row['email'] ?? '')));
            $notes = trim((string)($_POST['notes'] ?? ($row['notes'] ?? '')));

            if (empty($propertyIds) || $name === '' || $phone === '') {
                echo json_encode(['success' => false, 'message' => 'Property, name and phone are required']);
                exit;
            }

            $propertyModel = new Property();
            foreach ($propertyIds as $pid) {
                $prop = $propertyModel->getById((int)$pid, $this->userId);
                if (!$prop) {
                    echo json_encode(['success' => false, 'message' => 'Invalid property selected']);
                    exit;
                }
            }

            foreach ($propertyIds as $pid) {
                if (!$clientModel->isPropertyAvailableForClient((int)$pid, (int)$this->userId, (int)$id)) {
                    echo json_encode(['success' => false, 'message' => 'One or more selected properties are already linked to another client']);
                    exit;
                }
            }

            $clientModel->beginTransaction();
            try {
                $ok = $clientModel->updateById((int)$id, [
                    'property_id' => null,
                    'name' => $name,
                    'phone' => $phone,
                    'email' => $email,
                    'notes' => $notes,
                ]);
                $clientModel->syncClientProperties((int)$id, (int)$this->userId, $propertyIds);

                $addedPropertyIds = array_values(array_diff($propertyIds, $previousPropertyIds));
                if (!empty($addedPropertyIds)) {
                    try {
                        $contractModel = new AgentContract();
                        $unitModel = new Unit();
                        foreach ($addedPropertyIds as $pid) {
                            $existingId = $contractModel->getIdByClientProperty((int)$this->userId, (int)$id, (int)$pid);
                            if ($existingId) {
                                continue;
                            }

                            $units = $unitModel->query("SELECT id, rent_amount FROM units WHERE property_id = ? ORDER BY id", [(int)$pid]);
                            $unitIds = [];
                            $rentTotal = 0.0;
                            foreach (($units ?? []) as $u) {
                                $uid = (int)($u['id'] ?? 0);
                                if ($uid <= 0) continue;
                                $unitIds[] = $uid;
                                $rentTotal += (float)($u['rent_amount'] ?? 0);
                            }
                            $commissionPercent = 10.0;
                            $totalAmount = $rentTotal > 0 ? round(($rentTotal * $commissionPercent) / 100, 2) : 0.0;

                            $newContractId = $contractModel->insert([
                                'user_id' => (int)$this->userId,
                                'property_id' => (int)$pid,
                                'agent_client_id' => (int)$id,
                                'terms_type' => 'one_time',
                                'total_amount' => (float)$totalAmount,
                                'monthly_amount' => null,
                                'duration_months' => null,
                                'start_month' => null,
                                'instructions' => null,
                                'commission_percent' => (float)$commissionPercent,
                                'rent_total' => (float)$rentTotal,
                                'status' => 'active',
                            ]);
                            if (!empty($unitIds)) {
                                $contractModel->syncContractUnits((int)$newContractId, (int)$this->userId, $unitIds);
                            }
                        }
                    } catch (\Exception $e) {
                        error_log('AgentClients auto-contract create failed: ' . $e->getMessage());
                    }
                }
                $clientModel->commit();
            } catch (\Exception $e) {
                $clientModel->rollback();
                throw $e;
            }

            echo json_encode(['success' => (bool)$ok, 'message' => $ok ? 'Client updated successfully' : 'Failed to update client']);
        } catch (\Exception $e) {
            error_log('AgentClients update failed: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to update client']);
        }
        exit;
    }

    public function delete($id)
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

            $clientModel = new AgentClient();
            $row = $clientModel->getByIdWithAccess((int)$id, $this->userId);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Client not found']);
                exit;
            }

            $contractModel = new AgentContract();

            $clientModel->beginTransaction();
            try {
                // Reset linked agent CRM lead (inquiries) back to 'new' for this client
                // We infer linkage by property + contact details.
                $wonStageKey = 'won';
                try {
                    $stmt = $clientModel->getDb()->prepare(
                        "SELECT stage_key FROM agent_lead_stages WHERE user_id = ? AND is_won = 1 ORDER BY id DESC LIMIT 1"
                    );
                    $stmt->execute([(int)$this->userId]);
                    $rowStage = $stmt->fetch(\PDO::FETCH_ASSOC);
                    if (!empty($rowStage['stage_key'])) {
                        $wonStageKey = (string)$rowStage['stage_key'];
                    }
                } catch (\Exception $e) {
                }

                $clientPhone = trim((string)($row['phone'] ?? ''));
                $clientEmail = trim((string)($row['email'] ?? ''));
                $propertyIds = $clientModel->getClientPropertyIds((int)$id, (int)$this->userId);
                if (!empty($propertyIds) && ($clientPhone !== '' || $clientEmail !== '')) {
                    $stmt = $clientModel->getDb()->prepare(
                        "UPDATE inquiries i\n"
                        . "LEFT JOIN properties p ON p.id = i.property_id\n"
                        . "SET i.crm_stage = 'new'\n"
                        . "WHERE i.property_id = ?\n"
                        . "  AND i.crm_stage = ?\n"
                        . "  AND (i.contact = ? OR i.contact = ?)\n"
                        . "  AND (p.owner_id = ? OR p.manager_id = ? OR p.agent_id = ? OR p.caretaker_user_id = ?)"
                    );
                    foreach ($propertyIds as $propertyId) {
                        $stmt->execute([
                            (int)$propertyId,
                            (string)$wonStageKey,
                            (string)$clientPhone,
                            (string)($clientEmail !== '' ? $clientEmail : $clientPhone),
                            (int)$this->userId,
                            (int)$this->userId,
                            (int)$this->userId,
                            (int)$this->userId,
                        ]);
                    }
                }

                $stmt = $contractModel->getDb()->prepare(
                    "DELETE FROM agent_contracts WHERE agent_client_id = ? AND user_id = ?"
                );
                $stmt->execute([(int)$id, (int)$this->userId]);

                $clientModel->syncClientProperties((int)$id, (int)$this->userId, []);

                $clientModel->deleteById((int)$id);

                $clientModel->commit();
            } catch (\Exception $e) {
                $clientModel->rollback();
                throw $e;
            }

            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            error_log('AgentClients delete failed: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to delete client']);
        }
        exit;
    }
}
