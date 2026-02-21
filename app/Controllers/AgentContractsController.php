<?php

namespace App\Controllers;

use App\Models\AgentContract;
use App\Models\AgentClient;
use App\Models\Property;
use App\Models\Unit;

class AgentContractsController
{
    private $userId;

    private function monthsElapsedFromStart(?string $startMonthDate): int
    {
        if (!$startMonthDate) {
            return 0;
        }
        try {
            $start = new \DateTime(substr((string)$startMonthDate, 0, 10));
            $now = new \DateTime('first day of this month');
            $start->modify('first day of this month');
            if ($start > $now) {
                return 0;
            }
            $diff = $start->diff($now);
            $months = ((int)$diff->y * 12) + (int)$diff->m;
            return max(0, $months + 1);
        } catch (\Exception $e) {
            return 0;
        }
    }

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
        $contractModel = new AgentContract();
        $clientModel = new AgentClient();
        $propertyModel = new Property();

        $contracts = $contractModel->getAllWithDetails($this->userId);
        $clients = $clientModel->getAllForUser($this->userId);
        $properties = $propertyModel->getAll($this->userId);

        $totalContractValue = 0.0;
        foreach (($contracts ?? []) as &$c) {
            $displayTotal = (float)($c['total_amount'] ?? 0);
            if (($c['terms_type'] ?? '') === 'monthly') {
                $monthsElapsed = $this->monthsElapsedFromStart($c['start_month'] ?? null);
                $duration = ($c['duration_months'] ?? null) !== null ? (int)$c['duration_months'] : null;
                if ($duration !== null && $duration > 0) {
                    $monthsElapsed = min($monthsElapsed, $duration);
                }
                $displayTotal = round(((float)($c['monthly_amount'] ?? 0)) * (float)$monthsElapsed, 2);
            }
            $c['display_total_amount'] = $displayTotal;
            $totalContractValue += $displayTotal;
        }
        unset($c);

        echo view('agent/contracts', [
            'title' => 'Contracts',
            'contracts' => $contracts,
            'clients' => $clients,
            'properties' => $properties,
            'stats' => [
                'total_clients' => is_array($clients) ? count($clients) : 0,
                'total_properties' => is_array($properties) ? count($properties) : 0,
                'total_contract_value' => $totalContractValue,
            ],
        ]);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/agent/contracts');
            exit;
        }

        if (!verify_csrf_token()) {
            $_SESSION['flash_message'] = 'Invalid security token';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agent/contracts');
            exit;
        }

        $propertyId = (int)($_POST['property_id'] ?? 0);
        $clientId = (int)($_POST['agent_client_id'] ?? 0);
        $termsType = trim((string)($_POST['terms_type'] ?? 'one_time'));
        $commissionPercent = ($_POST['commission_percent'] ?? '') !== '' ? (float)$_POST['commission_percent'] : null;
        $unitIdsRaw = $_POST['unit_ids'] ?? [];
        if (!is_array($unitIdsRaw)) {
            $unitIdsRaw = [$unitIdsRaw];
        }
        $unitIds = array_values(array_unique(array_filter(array_map('intval', $unitIdsRaw), fn($v) => $v > 0)));
        $durationMonths = (int)($_POST['duration_months'] ?? 0);
        $startMonth = trim((string)($_POST['start_month'] ?? ''));
        $instructions = trim((string)($_POST['instructions'] ?? ''));
        $manualTotalAmount = (float)($_POST['total_amount'] ?? 0);

        if ($propertyId <= 0 || $clientId <= 0) {
            $_SESSION['flash_message'] = 'Property and client are required';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agent/contracts');
            exit;
        }

        if (!in_array($termsType, ['one_time', 'monthly'], true)) {
            $termsType = 'one_time';
        }

        $totalAmount = $manualTotalAmount;
        $rentTotal = null;
        $commissionToSave = null;

        if ($commissionPercent !== null && $commissionPercent > 0) {
            $unitModel = new Unit();
            $rentTotalCalc = 0.0;

            if (empty($unitIds)) {
                $units = $unitModel->query("SELECT id, rent_amount, property_id FROM units WHERE property_id = ? ORDER BY id", [(int)$propertyId]);
                foreach (($units ?? []) as $u) {
                    $uid = (int)($u['id'] ?? 0);
                    if ($uid <= 0) continue;
                    $unitIds[] = $uid;
                    $rentTotalCalc += (float)($u['rent_amount'] ?? 0);
                }
            } else {
                foreach ($unitIds as $uid) {
                    $u = $unitModel->find((int)$uid);
                    if (empty($u) || (int)($u['property_id'] ?? 0) !== (int)$propertyId) {
                        $_SESSION['flash_message'] = 'Invalid unit selection';
                        $_SESSION['flash_type'] = 'danger';
                        header('Location: ' . BASE_URL . '/agent/contracts');
                        exit;
                    }
                    $rentTotalCalc += (float)($u['rent_amount'] ?? 0);
                }
            }

            $totalAmount = round(($rentTotalCalc * $commissionPercent) / 100, 2);
            $rentTotal = (float)$rentTotalCalc;
            $commissionToSave = (float)$commissionPercent;
        } else {
            // If units were provided without commission, still validate unit-property relationship.
            if (!empty($unitIds)) {
                $unitModel = new Unit();
                foreach ($unitIds as $uid) {
                    $u = $unitModel->find((int)$uid);
                    if (empty($u) || (int)($u['property_id'] ?? 0) !== (int)$propertyId) {
                        $_SESSION['flash_message'] = 'Invalid unit selection';
                        $_SESSION['flash_type'] = 'danger';
                        header('Location: ' . BASE_URL . '/agent/contracts');
                        exit;
                    }
                }
            }
        }

        if ($termsType === 'monthly') {
            if ($startMonth === '') {
                $_SESSION['flash_message'] = 'Start month is required for monthly contracts';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/agent/contracts');
                exit;
            }
        }

        $propertyModel = new Property();
        $property = $propertyModel->getById($propertyId, $this->userId);
        if (!$property) {
            $_SESSION['flash_message'] = 'Invalid property selected';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agent/contracts');
            exit;
        }

        $clientModel = new AgentClient();
        $client = $clientModel->getByIdWithAccess($clientId, $this->userId);
        if (!$client) {
            $_SESSION['flash_message'] = 'Invalid client selected';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agent/contracts');
            exit;
        }

        if (!$clientModel->isPropertyLinkedToClient((int)$clientId, (int)$propertyId, (int)$this->userId)) {
            $_SESSION['flash_message'] = 'Client must belong to the selected property';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agent/contracts');
            exit;
        }

        // totalAmount can be manual (including 0) or auto-calculated above.

        $monthlyAmount = null;
        $startMonthDate = null;
        $durationToSave = null;

        if ($termsType === 'monthly') {
            $monthlyAmount = (float)$totalAmount;
            $startMonthDate = $startMonth . '-01';
            $durationToSave = $durationMonths > 0 ? (int)$durationMonths : null;
        }

        try {
            $contractModel = new AgentContract();
            $contractModel->beginTransaction();
            try {
                $contractId = $contractModel->insert([
                    'user_id' => (int)$this->userId,
                    'property_id' => (int)$propertyId,
                    'agent_client_id' => (int)$clientId,
                    'terms_type' => (string)$termsType,
                    'total_amount' => (float)$totalAmount,
                    'monthly_amount' => $monthlyAmount,
                    'duration_months' => $durationToSave,
                    'start_month' => $startMonthDate,
                    'instructions' => $instructions,
                    'commission_percent' => $commissionToSave,
                    'rent_total' => $rentTotal,
                    'status' => 'active',
                ]);
                $contractModel->syncContractUnits((int)$contractId, (int)$this->userId, $unitIds);
                $contractModel->commit();
            } catch (\Exception $e) {
                $contractModel->rollback();
                throw $e;
            }

            $_SESSION['flash_message'] = 'Contract created successfully';
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . BASE_URL . '/agent/contracts');
            exit;
        } catch (\Exception $e) {
            error_log('AgentContracts store failed: ' . $e->getMessage());
            $_SESSION['flash_message'] = 'Failed to create contract';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agent/contracts');
            exit;
        }
    }

    public function get($id)
    {
        header('Content-Type: application/json');
        try {
            $contractModel = new AgentContract();
            $row = $contractModel->getByIdWithAccess((int)$id, $this->userId);
            if (!$row) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Contract not found']);
                exit;
            }
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

            $contractModel = new AgentContract();
            $row = $contractModel->getByIdWithAccess((int)$id, $this->userId);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Contract not found']);
                exit;
            }

            $propertyId = array_key_exists('property_id', $_POST)
                ? (($_POST['property_id'] !== '' && $_POST['property_id'] !== null) ? (int)$_POST['property_id'] : null)
                : (int)($row['property_id'] ?? 0);
            $clientId = array_key_exists('agent_client_id', $_POST)
                ? (($_POST['agent_client_id'] !== '' && $_POST['agent_client_id'] !== null) ? (int)$_POST['agent_client_id'] : null)
                : (int)($row['agent_client_id'] ?? 0);
            $termsType = trim((string)($_POST['terms_type'] ?? ($row['terms_type'] ?? 'one_time')));
            $commissionPercent = array_key_exists('commission_percent', $_POST)
                ? (($_POST['commission_percent'] !== '' && $_POST['commission_percent'] !== null) ? (float)$_POST['commission_percent'] : null)
                : (($row['commission_percent'] ?? null) !== null ? (float)$row['commission_percent'] : null);
            $unitIdsRaw = $_POST['unit_ids'] ?? ($row['unit_ids'] ?? []);
            if (!is_array($unitIdsRaw)) {
                $unitIdsRaw = [$unitIdsRaw];
            }
            $unitIds = array_values(array_unique(array_filter(array_map('intval', $unitIdsRaw), fn($v) => $v > 0)));
            $manualTotalAmount = array_key_exists('total_amount', $_POST) ? (float)$_POST['total_amount'] : (float)($row['total_amount'] ?? 0);
            $totalAmount = $manualTotalAmount;
            $durationMonths = (int)($_POST['duration_months'] ?? ($row['duration_months'] ?? 0));
            $startMonth = trim((string)($_POST['start_month'] ?? ''));
            $instructions = trim((string)($_POST['instructions'] ?? ($row['instructions'] ?? '')));
            $status = trim((string)($_POST['status'] ?? ($row['status'] ?? 'active')));

            if (!$propertyId || !$clientId) {
                echo json_encode(['success' => false, 'message' => 'Property and client are required']);
                exit;
            }
            if (!in_array($termsType, ['one_time', 'monthly'], true)) {
                $termsType = 'one_time';
            }
            if (!in_array($status, ['active', 'completed', 'cancelled'], true)) {
                $status = 'active';
            }

            $propertyModel = new Property();
            $property = $propertyModel->getById((int)$propertyId, $this->userId);
            if (!$property) {
                echo json_encode(['success' => false, 'message' => 'Invalid property selected']);
                exit;
            }

            $clientModel = new AgentClient();
            $client = $clientModel->getByIdWithAccess((int)$clientId, $this->userId);
            if (!$client) {
                echo json_encode(['success' => false, 'message' => 'Invalid client selected']);
                exit;
            }
            if (!$clientModel->isPropertyLinkedToClient((int)$clientId, (int)$propertyId, (int)$this->userId)) {
                echo json_encode(['success' => false, 'message' => 'Client must belong to the selected property']);
                exit;
            }

            $rentTotal = null;
            $commissionToSave = null;

            if ($commissionPercent !== null && $commissionPercent > 0) {
                $unitModel = new Unit();
                $rentTotalCalc = 0.0;

                if (empty($unitIds)) {
                    $units = $unitModel->query("SELECT id, rent_amount, property_id FROM units WHERE property_id = ? ORDER BY id", [(int)$propertyId]);
                    foreach (($units ?? []) as $u) {
                        $uid = (int)($u['id'] ?? 0);
                        if ($uid <= 0) continue;
                        $unitIds[] = $uid;
                        $rentTotalCalc += (float)($u['rent_amount'] ?? 0);
                    }
                } else {
                    foreach ($unitIds as $uid) {
                        $u = $unitModel->find((int)$uid);
                        if (empty($u) || (int)($u['property_id'] ?? 0) !== (int)$propertyId) {
                            echo json_encode(['success' => false, 'message' => 'Invalid unit selection']);
                            exit;
                        }
                        $rentTotalCalc += (float)($u['rent_amount'] ?? 0);
                    }
                }

                $totalAmount = round(($rentTotalCalc * $commissionPercent) / 100, 2);
                $rentTotal = (float)$rentTotalCalc;
                $commissionToSave = (float)$commissionPercent;
            } else {
                // Units can be saved without commission; validate if provided.
                if (!empty($unitIds)) {
                    $unitModel = new Unit();
                    foreach ($unitIds as $uid) {
                        $u = $unitModel->find((int)$uid);
                        if (empty($u) || (int)($u['property_id'] ?? 0) !== (int)$propertyId) {
                            echo json_encode(['success' => false, 'message' => 'Invalid unit selection']);
                            exit;
                        }
                    }
                }
            }

            $monthlyAmount = null;
            $startMonthDate = null;
            $durationToSave = null;
            if ($termsType === 'monthly') {
                if ($startMonth === '') {
                    echo json_encode(['success' => false, 'message' => 'Start month is required for monthly contracts']);
                    exit;
                }
                $monthlyAmount = (float)$totalAmount;
                $startMonthDate = $startMonth . '-01';
                $durationToSave = $durationMonths > 0 ? (int)$durationMonths : null;
            }

            $contractModel->beginTransaction();
            try {
                $ok = $contractModel->updateById((int)$id, [
                    'property_id' => (int)$propertyId,
                    'agent_client_id' => (int)$clientId,
                    'terms_type' => (string)$termsType,
                    'total_amount' => (float)$totalAmount,
                    'monthly_amount' => $monthlyAmount,
                    'duration_months' => $durationToSave,
                    'start_month' => $startMonthDate,
                    'instructions' => $instructions,
                    'commission_percent' => $commissionToSave,
                    'rent_total' => $rentTotal,
                    'status' => $status,
                ]);
                $contractModel->syncContractUnits((int)$id, (int)$this->userId, $unitIds);
                $contractModel->commit();
            } catch (\Exception $e) {
                $contractModel->rollback();
                throw $e;
            }

            echo json_encode(['success' => (bool)$ok]);
        } catch (\Exception $e) {
            error_log('AgentContracts update failed: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to update contract']);
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

            $contractModel = new AgentContract();
            $row = $contractModel->getByIdWithAccess((int)$id, $this->userId);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Contract not found']);
                exit;
            }

            $clientId = (int)($row['agent_client_id'] ?? 0);

            $db = $contractModel->getDb();
            $inTransaction = $db->inTransaction();
            if (!$inTransaction) {
                $db->beginTransaction();
            }

            try {
                // Delete unit links
                $stmt = $db->prepare("DELETE FROM agent_contract_units WHERE agent_contract_id = ? AND user_id = ?");
                $stmt->execute([(int)$id, (int)$this->userId]);

                // Delete contract
                $stmt = $db->prepare("DELETE FROM agent_contracts WHERE id = ? AND user_id = ?");
                $stmt->execute([(int)$id, (int)$this->userId]);

                // Delete client only if unused (no other contracts and no linked properties)
                if ($clientId > 0) {
                    $stmt = $db->prepare("SELECT COUNT(*) FROM agent_contracts WHERE agent_client_id = ? AND user_id = ?");
                    $stmt->execute([(int)$clientId, (int)$this->userId]);
                    $remainingContracts = (int)$stmt->fetchColumn();

                    $stmt = $db->prepare("SELECT COUNT(*) FROM agent_client_properties WHERE agent_client_id = ? AND user_id = ?");
                    $stmt->execute([(int)$clientId, (int)$this->userId]);
                    $remainingProps = (int)$stmt->fetchColumn();

                    if ($remainingContracts <= 0 && $remainingProps <= 0) {
                        $stmt = $db->prepare("DELETE FROM agent_clients WHERE id = ? AND user_id = ?");
                        $stmt->execute([(int)$clientId, (int)$this->userId]);
                    }
                }

                if (!$inTransaction) {
                    $db->commit();
                }
            } catch (\Exception $e) {
                if (!$inTransaction && $db->inTransaction()) {
                    $db->rollBack();
                }
                throw $e;
            }

            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            error_log('AgentContracts delete failed: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to delete contract']);
        }
        exit;
    }
}
