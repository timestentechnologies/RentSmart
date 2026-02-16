<?php

namespace App\Controllers;

use App\Models\RealtorLead;
use App\Models\RealtorClient;
use App\Models\RealtorLeadStage;
use App\Models\RealtorListing;
use App\Models\RealtorContract;

class RealtorLeadsController
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
        try {
            $model = new RealtorLead();
            $leads = $model->getAll($this->userId);
            $stageModel = new RealtorLeadStage();
            $stages = $stageModel->getAll($this->userId);

            $listingModel = new RealtorListing();
            $listings = $listingModel->getAll($this->userId);
            echo view('realtor/leads', [
                'title' => 'CRM - Leads',
                'leads' => $leads,
                'stages' => $stages,
                'listings' => $listings,
            ]);
        } catch (\Throwable $e) {
            $msg = 'RealtorLeads index failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
            error_log($msg);
            try {
                $logFile = __DIR__ . '/../../views/logs/php_errors.log';
                @file_put_contents($logFile, '[' . date('d-M-Y H:i:s') . ' UTC] ' . $msg . "\n", FILE_APPEND);
            } catch (\Throwable $e2) {
            }

            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || (!empty($_SERVER['HTTP_ACCEPT']) && stripos((string)$_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

            if (!headers_sent()) {
                http_response_code(500);
            }

            if ($isAjax) {
                header('Content-Type: application/json');
                $debug = !empty($_GET['debug']);
                echo json_encode([
                    'success' => false,
                    'message' => 'Server error',
                    'debug' => $debug ? $msg : null,
                ]);
                exit;
            }

            if (!empty($_GET['debug'])) {
                echo '<pre style="white-space:pre-wrap;">' . htmlspecialchars($msg) . '</pre>';
                exit;
            }

            echo view('errors/500', ['title' => '500 Internal Server Error']);
        }
    }

    private function maybeConvertToClient($leadId)
    {
        $leadModel = new RealtorLead();
        $lead = $leadModel->getByIdWithAccess((int)$leadId, $this->userId);
        if (!$lead) {
            return ['converted' => false, 'client_id' => null];
        }

        if (!empty($lead['converted_client_id'])) {
            $contractId = null;
            if (!empty($lead['realtor_listing_id'])) {
                try {
                    $contractModel = new RealtorContract();
                    $existing = $contractModel->query(
                        "SELECT id FROM realtor_contracts WHERE user_id = ? AND realtor_client_id = ? AND realtor_listing_id = ? ORDER BY id DESC LIMIT 1",
                        [(int)$this->userId, (int)$lead['converted_client_id'], (int)$lead['realtor_listing_id']]
                    );
                    if (!empty($existing)) {
                        $contractId = (int)($existing[0]['id'] ?? 0);
                    }
                } catch (\Exception $e) {
                }
            }
            return ['converted' => false, 'client_id' => (int)$lead['converted_client_id'], 'contract_id' => $contractId];
        }

        $clientModel = new RealtorClient();
        $clientId = $clientModel->insert([
            'user_id' => $this->userId,
            'realtor_listing_id' => !empty($lead['realtor_listing_id']) ? (int)$lead['realtor_listing_id'] : null,
            'name' => $lead['name'] ?? '',
            'phone' => $lead['phone'] ?? '',
            'email' => $lead['email'] ?? '',
            'notes' => $lead['notes'] ?? '',
        ]);

        $contractId = null;
        if (!empty($lead['realtor_listing_id'])) {
            try {
                $listingModel = new RealtorListing();
                $listing = $listingModel->getByIdWithAccess((int)$lead['realtor_listing_id'], $this->userId);
                $total = (float)($listing['price'] ?? 0);

                $contractModel = new RealtorContract();
                $existing = $contractModel->query(
                    "SELECT id FROM realtor_contracts WHERE user_id = ? AND realtor_client_id = ? AND realtor_listing_id = ? ORDER BY id DESC LIMIT 1",
                    [(int)$this->userId, (int)$clientId, (int)$lead['realtor_listing_id']]
                );
                if (empty($existing)) {
                    if ($total > 0) {
                        $contractId = $contractModel->insert([
                            'user_id' => (int)$this->userId,
                            'realtor_client_id' => (int)$clientId,
                            'realtor_listing_id' => (int)$lead['realtor_listing_id'],
                            'terms_type' => 'one_time',
                            'total_amount' => (float)$total,
                            'monthly_amount' => null,
                            'duration_months' => null,
                            'start_month' => null,
                            'status' => 'active',
                        ]);
                    }
                } else {
                    $contractId = (int)($existing[0]['id'] ?? 0);
                }
            } catch (\Exception $e) {
                // ignore contract creation errors
            }
        }

        $leadModel->updateById((int)$leadId, [
            'converted_client_id' => (int)$clientId,
        ]);

        return ['converted' => true, 'client_id' => (int)$clientId, 'contract_id' => $contractId];
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/realtor/leads');
            exit;
        }
        try {
            if (!verify_csrf_token()) {
                $_SESSION['flash_message'] = 'Invalid security token';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/realtor/leads');
                exit;
            }

            $data = [
                'user_id' => $this->userId,
                'realtor_listing_id' => !empty($_POST['realtor_listing_id']) ? (int)$_POST['realtor_listing_id'] : null,
                'name' => trim((string)($_POST['name'] ?? '')),
                'phone' => trim((string)($_POST['phone'] ?? '')),
                'email' => trim((string)($_POST['email'] ?? '')),
                'source' => trim((string)($_POST['source'] ?? '')),
                'status' => trim((string)($_POST['status'] ?? 'new')),
                'notes' => trim((string)($_POST['notes'] ?? '')),
            ];

            if ($data['name'] === '' || $data['phone'] === '') {
                $_SESSION['flash_message'] = 'Name and phone are required';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/realtor/leads');
                exit;
            }

            $model = new RealtorLead();
            $model->insert($data);

            $_SESSION['flash_message'] = 'Lead captured successfully';
            $_SESSION['flash_type'] = 'success';
        } catch (\Exception $e) {
            error_log('RealtorLeads store failed: ' . $e->getMessage());
            $_SESSION['flash_message'] = 'Failed to capture lead';
            $_SESSION['flash_type'] = 'danger';
        }

        header('Location: ' . BASE_URL . '/realtor/leads');
        exit;
    }

    public function get($id)
    {
        try {
            $model = new RealtorLead();
            $row = $model->getByIdWithAccess((int)$id, $this->userId);
            if (!$row) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Lead not found']);
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
            $model = new RealtorLead();
            $row = $model->getByIdWithAccess((int)$id, $this->userId);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Lead not found']);
                exit;
            }

            $data = [
                'realtor_listing_id' => array_key_exists('realtor_listing_id', $_POST) ? (($_POST['realtor_listing_id'] !== '' && $_POST['realtor_listing_id'] !== null) ? (int)$_POST['realtor_listing_id'] : null) : ($row['realtor_listing_id'] ?? null),
                'name' => trim((string)($_POST['name'] ?? ($row['name'] ?? ''))),
                'phone' => trim((string)($_POST['phone'] ?? ($row['phone'] ?? ''))),
                'email' => trim((string)($_POST['email'] ?? ($row['email'] ?? ''))),
                'source' => trim((string)($_POST['source'] ?? ($row['source'] ?? ''))),
                'status' => trim((string)($_POST['status'] ?? ($row['status'] ?? 'new'))),
                'notes' => trim((string)($_POST['notes'] ?? ($row['notes'] ?? ''))),
            ];

            $ok = $model->updateById((int)$id, $data);

            // Auto-convert when moved to a Won stage
            $stageModel = new RealtorLeadStage();
            $stage = $stageModel->getByKey($this->userId, (string)$data['status']);
            if ($stage && (int)($stage['is_won'] ?? 0) === 1) {
                $conv = $this->maybeConvertToClient((int)$id);
                echo json_encode([
                    'success' => (bool)$ok,
                    'message' => $ok ? 'Updated' : 'Failed to update',
                    'converted' => (bool)($conv['converted'] ?? false),
                    'client_id' => $conv['client_id'] ?? null,
                    'contract_id' => $conv['contract_id'] ?? null,
                ]);
                exit;
            }
            echo json_encode(['success' => (bool)$ok, 'message' => $ok ? 'Updated' : 'Failed to update']);
        } catch (\Exception $e) {
            error_log('RealtorLeads update failed: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error updating lead']);
        }
        exit;
    }

    public function convert($id)
    {
        if ($_SE$contractId = null;
                if (!empty($lead['realtor_listing_id'])) {
                    try {
                        $contractModel = new RealtorContract();
                        $existing = $contractModel->query(
                            "SELECT id FROM realtor_contracts WHERE user_id = ? AND realtor_client_id = ? AND realtor_listing_id = ? ORDER BY id DESC LIMIT 1",
                            [(int)$this->userId, (int)$lead['converted_client_id'], (int)$lead['realtor_listing_id']]
                        );
                        if (!empty($Rxisting)) {
                            $VontractId = (int)($existing[0]['id'] ?? 0);
                        }
                    } catcE (\ExceptiRn $e) {
                    }
                }
[               echo 'REQUEST_METHOD'] !== 'POST') {, 'contract_id' => $contractId
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }

        try {
            $leadModel = new RealtorLead();
            $lead = $leadModel->getByIdWithAccess((int)$id, $this->userId);
            if (!$lead) {
                echo json_encode(['success' => false, 'message' => 'Lead not found']);
                exit;
            }

            if (!empty($lead['converted_client_id'])) {
                $leadModel->updateById((int)$id, [ 'status' => 'won' ]);
                echo json_encode(['success' => true, 'message' => 'Already converted', 'client_id' => (int)$lead['converted_client_id']]);
                exit;
            }

            $clientModel = new RealtorClient();
            $clientId = $clientModel->insert([
                'user_id' => $this->userId,
                'realtor_listing_id' => !empty($lead['realtor_listing_id']) ? (int)$lead['realtor_listing_id'] : null,
                'name' => $lead['name'] ?? '',
                'phone' => $lead['phone'] ?? '',
                'email' => $lead['email'] ?? '',
                'notes' => $lead['notes'] ?? '',
            ]);

            $contractId = null;
            if (!empty($lead['realtor_listing_id'])) {
                try {
                    $listingModel = new RealtorListing();
                    $listing = $listingModel->getByIdWithAccess((int)$lead['realtor_listing_id'], $this->userId);
                    $total = (float)($listing['price'] ?? 0);

                    $contractModel = new RealtorContract();
                    $existing = $contractModel->query(
                        "SELECT id FROM realtor_contracts WHERE user_id = ? AND realtor_client_id = ? AND realtor_listing_id = ? ORDER BY id DESC LIMIT 1",
                        [(int)$this->userId, (int)$clientId, (int)$lead['realtor_listing_id']]
                    );
                    if (empty($existing)) {
                        if ($total > 0) {
                            $contractId = $contractModel->insert([
                                'user_id' => (int)$this->userId,
                                'realtor_client_id' => (int)$clientId,
                                'realtor_listing_id' => (int)$lead['realtor_listing_id'],
                                'terms_type' => 'one_time',
                                'total_amount' => (float)$total,
                                'monthly_amount' => null,
                                'duration_months' => null,
                                'start_month' => null,
                                'status' => 'active',
                            ]);
                        }
                    } else {
                        $contractId = (int)($existing[0]['id'] ?? 0);
                    }
                } catch (\Exception $e) {
                }
            }

            $leadModel->updateById((int)$id, [
                'status' => 'won',
                'converted_client_id' => (int)$clientId,
            ]);

            echo json_encode(['success' => true, 'message' => 'Converted to client', 'client_id' => (int)$clientId, 'contract_id' => $contractId]);
        } catch (\Exception $e) {
            error_log('RealtorLeads convert failed: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to convert lead']);
        }
        exit;
    }

    public function stages()
    {
        header('Content-Type: application/json');
        try {
            $stageModel = new RealtorLeadStage();
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
            $stageModel = new RealtorLeadStage();
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
            error_log('storeStage failed: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to add stage']);
        }
        exit;
    }

    public function updateStage($id)
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }
        try {
            $stageModel = new RealtorLeadStage();
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
            $stageModel = new RealtorLeadStage();
            $row = $stageModel->getByIdWithAccess((int)$id, $this->userId);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Stage not found']);
                exit;
            }
            if (in_array((string)($row['stage_key'] ?? ''), ['new','contacted','won','lost'], true)) {
                echo json_encode(['success' => false, 'message' => 'Default stages cannot be deleted']);
                exit;
            }
            $ok = $stageModel->deleteById((int)$id);
            echo json_encode(['success' => (bool)$ok]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to delete stage']);
        }
        exit;
    }

    public function delete($id)
    {
        try {
            $model = new RealtorLead();
            $row = $model->getByIdWithAccess((int)$id, $this->userId);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Lead not found']);
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
