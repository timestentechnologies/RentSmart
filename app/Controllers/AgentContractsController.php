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
        if (strtolower((string)($_SESSION['user_role'] ?? '')) === 'realtor') {
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
}
