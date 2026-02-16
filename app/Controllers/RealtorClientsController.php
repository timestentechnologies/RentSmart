<?php

namespace App\Controllers;

use App\Models\RealtorClient;
use App\Models\RealtorListing;

class RealtorClientsController
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
        if (strtolower((string)($_SESSION['user_role'] ?? '')) !== 'realtor') {
            $_SESSION['flash_message'] = 'Access denied';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }

    public function index()
    {
        $model = new RealtorClient();
        $clients = $model->getAll($this->userId);

        $listingModel = new RealtorListing();
        $listings = $listingModel->getAll($this->userId);
        echo view('realtor/clients', [
            'title' => 'Clients',
            'clients' => $clients,
            'listings' => $listings,
        ]);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/realtor/clients');
            exit;
        }
        try {
            if (!verify_csrf_token()) {
                $_SESSION['flash_message'] = 'Invalid security token';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/realtor/clients');
                exit;
            }

            $data = [
                'user_id' => $this->userId,
                'name' => trim((string)($_POST['name'] ?? '')),
                'phone' => trim((string)($_POST['phone'] ?? '')),
                'email' => trim((string)($_POST['email'] ?? '')),
                'notes' => trim((string)($_POST['notes'] ?? '')),
            ];

            if ($data['name'] === '' || $data['phone'] === '') {
                $_SESSION['flash_message'] = 'Name and phone are required';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/realtor/clients');
                exit;
            }

            $model = new RealtorClient();
            $model->insert($data);

            $_SESSION['flash_message'] = 'Client added successfully';
            $_SESSION['flash_type'] = 'success';
        } catch (\Exception $e) {
            error_log('RealtorClients store failed: ' . $e->getMessage());
            $_SESSION['flash_message'] = 'Failed to add client';
            $_SESSION['flash_type'] = 'danger';
        }

        header('Location: ' . BASE_URL . '/realtor/clients');
        exit;
    }

    public function get($id)
    {
        try {
            $model = new RealtorClient();
            $row = $model->getByIdWithAccess((int)$id, $this->userId);
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }
        try {
            $model = new RealtorClient();
            $row = $model->getByIdWithAccess((int)$id, $this->userId);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Client not found']);
                exit;
            }

            $data = [
                'name' => trim((string)($_POST['name'] ?? ($row['name'] ?? ''))),
                'phone' => trim((string)($_POST['phone'] ?? ($row['phone'] ?? ''))),
                'email' => trim((string)($_POST['email'] ?? ($row['email'] ?? ''))),
                'notes' => trim((string)($_POST['notes'] ?? ($row['notes'] ?? ''))),
            ];

            $ok = $model->updateById((int)$id, $data);
            echo json_encode(['success' => (bool)$ok, 'message' => $ok ? 'Updated' : 'Failed to update']);
        } catch (\Exception $e) {
            error_log('RealtorClients update failed: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error updating client']);
        }
        exit;
    }

    public function delete($id)
    {
        try {
            $model = new RealtorClient();
            $row = $model->getByIdWithAccess((int)$id, $this->userId);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Client not found']);
                exit;
            }
            $ok = $model->deleteById((int)$id);
            echo json_encode(['success' => (bool)$ok, 'message' => $ok ? 'Deleted' : 'Failed to delete']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
        exit;
    }
}
