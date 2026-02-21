<?php

namespace App\Controllers;

use App\Models\AgentContract;
use App\Models\AgentClient;
use App\Models\Property;

class AgentContractsController
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
        $contractModel = new AgentContract();
        $clientModel = new AgentClient();
        $propertyModel = new Property();

        $contracts = $contractModel->getAllWithDetails($this->userId);
        $clients = $clientModel->getAllForUser($this->userId);
        $properties = $propertyModel->getAll($this->userId);

        echo view('agent/contracts', [
            'title' => 'Contracts',
            'contracts' => $contracts,
            'clients' => $clients,
            'properties' => $properties,
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
        $totalAmount = (float)($_POST['total_amount'] ?? 0);
        $durationMonths = (int)($_POST['duration_months'] ?? 0);
        $startMonth = trim((string)($_POST['start_month'] ?? ''));
        $instructions = trim((string)($_POST['instructions'] ?? ''));

        if ($propertyId <= 0 || $clientId <= 0) {
            $_SESSION['flash_message'] = 'Property and client are required';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agent/contracts');
            exit;
        }

        if (!in_array($termsType, ['one_time', 'monthly'], true)) {
            $termsType = 'one_time';
        }

        if ($totalAmount <= 0) {
            $_SESSION['flash_message'] = 'Total amount must be greater than 0';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agent/contracts');
            exit;
        }

        if ($termsType === 'monthly') {
            if ($durationMonths <= 0) {
                $_SESSION['flash_message'] = 'Duration (months) is required for monthly contracts';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/agent/contracts');
                exit;
            }
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

        if ((int)($client['property_id'] ?? 0) !== $propertyId) {
            $_SESSION['flash_message'] = 'Client must belong to the selected property';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agent/contracts');
            exit;
        }

        $monthlyAmount = null;
        $startMonthDate = null;
        $durationToSave = null;

        if ($termsType === 'monthly') {
            $monthlyAmount = round($totalAmount / max(1, $durationMonths), 2);
            $startMonthDate = $startMonth . '-01';
            $durationToSave = (int)$durationMonths;
        }

        try {
            $contractModel = new AgentContract();
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
                'status' => 'active',
            ]);

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
            $totalAmount = array_key_exists('total_amount', $_POST) ? (float)$_POST['total_amount'] : (float)($row['total_amount'] ?? 0);
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
            if ((int)($client['property_id'] ?? 0) !== (int)$propertyId) {
                echo json_encode(['success' => false, 'message' => 'Client must belong to the selected property']);
                exit;
            }

            $monthlyAmount = null;
            $startMonthDate = null;
            $durationToSave = null;
            if ($termsType === 'monthly') {
                if ($durationMonths <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Duration (months) is required for monthly contracts']);
                    exit;
                }
                if ($startMonth === '') {
                    echo json_encode(['success' => false, 'message' => 'Start month is required for monthly contracts']);
                    exit;
                }
                $monthlyAmount = round($totalAmount / max(1, $durationMonths), 2);
                $startMonthDate = $startMonth . '-01';
                $durationToSave = (int)$durationMonths;
            }

            $ok = $contractModel->updateById((int)$id, [
                'property_id' => (int)$propertyId,
                'agent_client_id' => (int)$clientId,
                'terms_type' => (string)$termsType,
                'total_amount' => (float)$totalAmount,
                'monthly_amount' => $monthlyAmount,
                'duration_months' => $durationToSave,
                'start_month' => $startMonthDate,
                'instructions' => $instructions,
                'status' => $status,
            ]);

            echo json_encode(['success' => (bool)$ok]);
        } catch (\Exception $e) {
            error_log('AgentContracts update failed: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to update contract']);
        }
        exit;
    }
}
