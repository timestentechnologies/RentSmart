<?php

namespace App\Controllers;

use App\Models\AgentClient;
use App\Models\Property;

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
        if (strtolower((string)($_SESSION['user_role'] ?? '')) === 'realtor') {
            $_SESSION['flash_message'] = 'Access denied';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }

    public function index()
    {
        $clientModel = new AgentClient();
        $propertyModel = new Property();

        $clients = $clientModel->getAllForUser($this->userId);
        $properties = $propertyModel->getAll($this->userId);

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

            $propertyId = (int)($_POST['property_id'] ?? 0);
            $name = trim((string)($_POST['name'] ?? ''));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $notes = trim((string)($_POST['notes'] ?? ''));

            if ($propertyId <= 0 || $name === '' || $phone === '') {
                $_SESSION['flash_message'] = 'Property, name and phone are required';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/agent/clients');
                exit;
            }

            $propertyModel = new Property();
            $property = $propertyModel->getById($propertyId, $this->userId);
            if (!$property) {
                $_SESSION['flash_message'] = 'Invalid property selected';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/agent/clients');
                exit;
            }

            $clientModel = new AgentClient();
            $clientModel->insert([
                'user_id' => (int)$this->userId,
                'property_id' => (int)$propertyId,
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'notes' => $notes,
            ]);

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
}
