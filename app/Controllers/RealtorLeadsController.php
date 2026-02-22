<?php

namespace App\Controllers;

use App\Models\RealtorClient;
use App\Models\RealtorContract;
use App\Models\RealtorLead;
use App\Models\RealtorLeadStage;
use App\Models\RealtorListing;
use App\Models\Invoice;
use App\Models\Subscription;

class RealtorLeadsController
{
    private $userId;

    private function ensureLeadHasListingId(array $lead, RealtorLead $leadModel): array
    {
        if (!empty($lead['realtor_listing_id'])) {
            return $lead;
        }

        $listingName = trim((string)($lead['listing_name'] ?? ''));
        if ($listingName === '') {
            return $lead;
        }

        try {
            $listingModel = new RealtorListing();
            $location = trim((string)($lead['address'] ?? ''));
            if ($location === '') {
                $location = $listingName;
            }
            $price = (float)($lead['amount'] ?? 0);

            $listingId = (int)$listingModel->insert([
                'user_id' => (int)$this->userId,
                'title' => $listingName,
                'listing_type' => 'plot',
                'location' => $location,
                'price' => $price,
                'status' => 'active',
                'description' => '',
            ]);
            if ($listingId > 0) {
                $leadModel->updateById((int)($lead['id'] ?? 0), [
                    'realtor_listing_id' => (int)$listingId,
                ]);
                $lead['realtor_listing_id'] = (int)$listingId;
            }
        } catch (\Exception $e) {
        }

        return $lead;
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

        if (strtolower((string)($_SESSION['user_role'] ?? '')) !== 'realtor') {
            $_SESSION['flash_message'] = 'Access denied';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }

    public function winCreateListing($id)
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

            // Plan limits: enforce listing limit per subscription plan (dynamic from DB). Blank/0/NULL => unlimited.
            try {
                $subModel = new Subscription();
                $sub = $subModel->getUserSubscription((int)$this->userId);
                $listingLimit = null;
                if (isset($sub['listing_limit']) && $sub['listing_limit'] !== null && $sub['listing_limit'] !== '') {
                    $listingLimit = (int)$sub['listing_limit'];
                    if ($listingLimit <= 0) {
                        $listingLimit = null;
                    }
                }
                $listingModelCount = new RealtorListing();
                $currentCount = (int)$listingModelCount->countAll((int)$this->userId);
                if ($listingLimit !== null && $currentCount >= $listingLimit) {
                    $msg = 'You have reached your plan limit of ' . $listingLimit . ' listings. Please upgrade to add more.';
                    echo json_encode([
                        'success' => false,
                        'over_limit' => true,
                        'type' => 'listing',
                        'limit' => $listingLimit,
                        'current' => $currentCount,
                        'plan' => $sub['name'] ?? ($sub['plan_type'] ?? ''),
                        'upgrade_url' => BASE_URL . '/subscription/renew',
                        'message' => $msg,
                    ]);
                    exit;
                }
            } catch (\Exception $e) {
                // ignore; do not block listing creation if subscription tables are not available
            }

            $leadModel = new RealtorLead();
            $lead = $leadModel->getByIdWithAccess((int)$id, $this->userId);
            if (!$lead) {
                echo json_encode(['success' => false, 'message' => 'Lead not found']);
                exit;
            }

            $lead = $this->ensureLeadHasListingId($lead, $leadModel);

            $stageModel = new RealtorLeadStage();
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

            $title = trim((string)($lead['listing_name'] ?? ''));
            $location = trim((string)($lead['address'] ?? ''));
            if ($title === '') {
                echo json_encode(['success' => false, 'message' => 'Listing name is required to create a listing']);
                exit;
            }
            if ($location === '') {
                $location = $title;
            }
            $price = (float)($lead['amount'] ?? 0);

            $listingModel = new RealtorListing();
            $listingId = (int)$listingModel->insert([
                'user_id' => (int)$this->userId,
                'title' => $title,
                'listing_type' => 'plot',
                'location' => $location,
                'price' => $price,
                'status' => 'active',
                'description' => '',
            ]);
            if ($listingId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Failed to create listing']);
                exit;
            }

            // Link lead to the newly created listing and mark as won
            $leadModel->updateById((int)$id, [
                'realtor_listing_id' => (int)$listingId,
                'status' => $wonKey,
            ]);

            // Convert to client + contract
            $conv = $this->maybeConvertToClient((int)$id);

            $redirectUrl = BASE_URL . '/realtor/listings?edit=' . (int)$listingId;
            if (!empty($conv['contract_id'])) {
                $redirectUrl = BASE_URL . '/realtor/contracts?edit=' . (int)$conv['contract_id'];
            }

            echo json_encode([
                'success' => true,
                'listing_id' => (int)$listingId,
                'client_id' => $conv['client_id'] ?? null,
                'contract_id' => $conv['contract_id'] ?? null,
                'redirect_url' => $redirectUrl,
            ]);
        } catch (\Exception $e) {
            error_log('RealtorLeads winCreateListing failed: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Server error']);
        }
        exit;
    }

    public function index()
    {
        try {
            $model = new RealtorLead();
            $leads = $model->getAll($this->userId);
            $stageModel = new RealtorLeadStage();
            $stages = $stageModel->getAll($this->userId);

            $listingModel = new RealtorListing();
            $listings = $listingModel->getAllActive($this->userId);
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

        $lead = $this->ensureLeadHasListingId($lead, $leadModel);

        if (!empty($lead['converted_client_id'])) {
            $contractId = null;
            $totalForInvoice = null;
            try {
                $contractModel = new RealtorContract();
                $existing = $contractModel->query(
                    "SELECT id FROM realtor_contracts WHERE user_id = ? AND realtor_client_id = ? AND realtor_listing_id <=> ? ORDER BY id DESC LIMIT 1",
                    [(int)$this->userId, (int)$lead['converted_client_id'], !empty($lead['realtor_listing_id']) ? (int)$lead['realtor_listing_id'] : null]
                );
                if (!empty($existing)) {
                    $contractId = (int)($existing[0]['id'] ?? 0);
                }
            } catch (\Exception $e) {
            }

            if (!empty($lead['realtor_listing_id'])) {
                try {
                    $listingModel = new RealtorListing();
                    $listingModel->updateById((int)$lead['realtor_listing_id'], ['status' => 'sold']);

                    try {
                        $listing = $listingModel->getByIdWithAccess((int)$lead['realtor_listing_id'], (int)$this->userId);
                        $totalForInvoice = (float)($listing['price'] ?? 0);
                        if ($totalForInvoice <= 0) {
                            $totalForInvoice = null;
                        }
                    } catch (\Throwable $e) {
                        $totalForInvoice = null;
                    }
                } catch (\Exception $e) {
                }
            }

            if ($contractId > 0) {
                if ($totalForInvoice === null) {
                    $totalForInvoice = (float)($lead['amount'] ?? 0);
                }
                if ($totalForInvoice > 0) {
                    try {
                        $invModel = new Invoice();
                        $invModel->ensureRealtorContractInvoice(
                            (int)$this->userId,
                            (int)$contractId,
                            (int)$lead['converted_client_id'],
                            (int)($lead['realtor_listing_id'] ?? 0),
                            (float)$totalForInvoice,
                            date('Y-m-d')
                        );
                    } catch (\Throwable $e) {
                    }
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
        try {
            $listingId = !empty($lead['realtor_listing_id']) ? (int)$lead['realtor_listing_id'] : null;
            $total = null;
            if ($listingId) {
                try {
                    $listingModel = new RealtorListing();
                    $listing = $listingModel->getByIdWithAccess((int)$listingId, $this->userId);
                    $total = (float)($listing['price'] ?? 0);
                } catch (\Exception $e) {
                    $total = null;
                }
            }
            if ($total === null) {
                $total = (float)($lead['amount'] ?? 0);
            }

            $contractModel = new RealtorContract();
            $existing = $contractModel->query(
                "SELECT id FROM realtor_contracts WHERE user_id = ? AND realtor_client_id = ? AND realtor_listing_id <=> ? ORDER BY id DESC LIMIT 1",
                [(int)$this->userId, (int)$clientId, $listingId]
            );
            if (empty($existing)) {
                $contractId = $contractModel->insert([
                    'user_id' => (int)$this->userId,
                    'realtor_client_id' => (int)$clientId,
                    'realtor_listing_id' => $listingId,
                    'terms_type' => 'one_time',
                    'total_amount' => (float)$total,
                    'monthly_amount' => null,
                    'duration_months' => null,
                    'start_month' => null,
                    'status' => 'active',
                ]);
            } else {
                $contractId = (int)($existing[0]['id'] ?? 0);
            }

            if (!empty($contractId) && (float)$total > 0) {
                try {
                    $invModel = new Invoice();
                    $invModel->ensureRealtorContractInvoice(
                        (int)$this->userId,
                        (int)$contractId,
                        (int)$clientId,
                        (int)($listingId ?? 0),
                        (float)$total,
                        date('Y-m-d')
                    );
                } catch (\Throwable $e) {
                }
            }

            if (!empty($listingId)) {
                try {
                    $listingModel2 = new RealtorListing();
                    $listingModel2->updateById((int)$listingId, ['status' => 'sold']);
                } catch (\Exception $e) {
                }
            }
        } catch (\Exception $e) {
            // ignore contract creation errors
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

            $payloadListingId = isset($_POST['realtor_listing_id']) ? (int)$_POST['realtor_listing_id'] : 0;
            $listingId = $payloadListingId > 0 ? $payloadListingId : null;
            $listingName = trim((string)($_POST['listing_name'] ?? ''));
            if ($listingId !== null) {
                try {
                    $listingModel = new RealtorListing();
                    $ls = $listingModel->getByIdWithAccess((int)$listingId, (int)$this->userId);
                    if ($ls) {
                        $listingName = trim((string)($ls['title'] ?? $listingName));
                    } else {
                        $listingId = null;
                    }
                } catch (\Exception $e) {
                    $listingId = null;
                }
            }

            $data = [
                'user_id' => $this->userId,
                'realtor_listing_id' => $listingId,
                'listing_name' => $listingName,
                'address' => trim((string)($_POST['address'] ?? '')),
                'amount' => array_key_exists('amount', $_POST) && $_POST['amount'] !== '' ? (float)$_POST['amount'] : null,
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

            $payloadListingId = array_key_exists('realtor_listing_id', $_POST) ? (int)$_POST['realtor_listing_id'] : 0;
            $listingId = $payloadListingId > 0 ? $payloadListingId : null;
            $listingName = trim((string)($_POST['listing_name'] ?? ($row['listing_name'] ?? '')));
            if ($listingId !== null) {
                try {
                    $listingModel = new RealtorListing();
                    $ls = $listingModel->getByIdWithAccess((int)$listingId, (int)$this->userId);
                    if ($ls) {
                        $listingName = trim((string)($ls['title'] ?? $listingName));
                    } else {
                        $listingId = null;
                    }
                } catch (\Exception $e) {
                    $listingId = null;
                }
            }

            $data = [
                'realtor_listing_id' => $listingId,
                'listing_name' => $listingName,
                'address' => trim((string)($_POST['address'] ?? ($row['address'] ?? ''))),
                'amount' => array_key_exists('amount', $_POST) ? (($_POST['amount'] !== '' && $_POST['amount'] !== null) ? (float)$_POST['amount'] : null) : ($row['amount'] ?? null),
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

            // If already converted, ensure status is won and return existing contract if any.
            if (!empty($lead['converted_client_id'])) {
                $leadModel->updateById((int)$id, ['status' => 'won']);

                $contractId = null;
                $totalForInvoice = null;
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

                    try {
                        $listingModel = new RealtorListing();
                        $listing = $listingModel->getByIdWithAccess((int)$lead['realtor_listing_id'], (int)$this->userId);
                        $totalForInvoice = (float)($listing['price'] ?? 0);
                        if ($totalForInvoice <= 0) {
                            $totalForInvoice = null;
                        }
                    } catch (\Throwable $e) {
                        $totalForInvoice = null;
                    }
                }

                if ($contractId > 0) {
                    if ($totalForInvoice === null) {
                        $totalForInvoice = (float)($lead['amount'] ?? 0);
                    }
                    if ($totalForInvoice > 0) {
                        try {
                            $invModel = new Invoice();
                            $invModel->ensureRealtorContractInvoice(
                                (int)$this->userId,
                                (int)$contractId,
                                (int)$lead['converted_client_id'],
                                (int)($lead['realtor_listing_id'] ?? 0),
                                (float)$totalForInvoice,
                                date('Y-m-d')
                            );
                        } catch (\Throwable $e) {
                        }
                    }
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'Already converted',
                    'client_id' => (int)$lead['converted_client_id'],
                    'contract_id' => $contractId,
                ]);
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
                    $contractModel = new RealtorContract();
                    $existing = $contractModel->query(
                        "SELECT id FROM realtor_contracts WHERE user_id = ? AND realtor_client_id = ? AND realtor_listing_id = ? ORDER BY id DESC LIMIT 1",
                        [(int)$this->userId, (int)$clientId, (int)$lead['realtor_listing_id']]
                    );

                    if (!empty($existing)) {
                        $contractId = (int)($existing[0]['id'] ?? 0);
                    } else {
                        $total = 0.0;
                        try {
                            $listingModel = new RealtorListing();
                            $listing = $listingModel->getByIdWithAccess((int)$lead['realtor_listing_id'], $this->userId);
                            $total = (float)($listing['price'] ?? 0);
                        } catch (\Exception $e) {
                            $total = 0.0;
                        }

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

                    if (!empty($contractId) && (float)$total > 0) {
                        try {
                            $invModel = new Invoice();
                            $invModel->ensureRealtorContractInvoice(
                                (int)$this->userId,
                                (int)$contractId,
                                (int)$clientId,
                                (int)$lead['realtor_listing_id'],
                                (float)$total,
                                date('Y-m-d')
                            );
                        } catch (\Throwable $e) {
                        }
                    }
                } catch (\Exception $e) {
                }
            }

            $leadModel->updateById((int)$id, [
                'status' => 'won',
                'converted_client_id' => (int)$clientId,
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Converted to client',
                'client_id' => (int)$clientId,
                'contract_id' => $contractId,
            ]);
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

            $transferTo = strtolower(trim((string)($_POST['transfer_to'] ?? '')));
            $leadModel = new RealtorLead();
            $countRows = $leadModel->query(
                "SELECT COUNT(*) AS c FROM realtor_leads WHERE user_id = ? AND status = ?",
                [(int)$this->userId, (string)($row['stage_key'] ?? '')]
            );
            $leadCount = (int)($countRows[0]['c'] ?? 0);
            if ($leadCount > 0) {
                if ($transferTo === '') {
                    echo json_encode(['success' => false, 'message' => 'This stage has leads. Choose "Move Leads To" first.']);
                    exit;
                }
                $targetStage = $stageModel->getByKey($this->userId, $transferTo);
                if (!$targetStage) {
                    echo json_encode(['success' => false, 'message' => 'Target stage not found']);
                    exit;
                }
                $leadModel->query(
                    "UPDATE realtor_leads SET status = ? WHERE user_id = ? AND status = ?",
                    [(string)$transferTo, (int)$this->userId, (string)($row['stage_key'] ?? '')]
                );
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
