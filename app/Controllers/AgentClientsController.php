<?php

namespace App\Controllers;

use App\Models\AgentClient;
use App\Models\AgentContract;
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

            $propertyId = array_key_exists('property_id', $_POST)
                ? (($_POST['property_id'] !== '' && $_POST['property_id'] !== null) ? (int)$_POST['property_id'] : null)
                : (int)($row['property_id'] ?? 0);
            $name = trim((string)($_POST['name'] ?? ($row['name'] ?? '')));
            $phone = trim((string)($_POST['phone'] ?? ($row['phone'] ?? '')));
            $email = trim((string)($_POST['email'] ?? ($row['email'] ?? '')));
            $notes = trim((string)($_POST['notes'] ?? ($row['notes'] ?? '')));

            if (!$propertyId || $name === '' || $phone === '') {
                echo json_encode(['success' => false, 'message' => 'Property, name and phone are required']);
                exit;
            }

            $propertyModel = new Property();
            $prop = $propertyModel->getById((int)$propertyId, $this->userId);
            if (!$prop) {
                echo json_encode(['success' => false, 'message' => 'Invalid property selected']);
                exit;
            }

            $ok = $clientModel->updateById((int)$id, [
                'property_id' => (int)$propertyId,
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'notes' => $notes,
            ]);

            echo json_encode(['success' => (bool)$ok]);
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
                $stmt = $contractModel->getDb()->prepare(
                    "DELETE FROM agent_contracts WHERE agent_client_id = ? AND user_id = ?"
                );
                $stmt->execute([(int)$id, (int)$this->userId]);

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
