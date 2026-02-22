<?php

namespace App\Controllers;

use App\Models\RealtorClient;
use App\Models\RealtorContract;
use App\Models\RealtorLead;
use App\Models\RealtorListing;
use App\Models\Invoice;
use App\Database\Connection;

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
        $listings = $listingModel->getAllActive($this->userId);
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

            $listingId = (int)($_POST['realtor_listing_id'] ?? 0);
            $termsType = trim((string)($_POST['terms_type'] ?? 'one_time'));
            $totalAmount = (float)($_POST['total_amount'] ?? 0);
            $durationMonths = (int)($_POST['duration_months'] ?? 0);
            $startMonth = trim((string)($_POST['start_month'] ?? ''));

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

            if ($listingId <= 0) {
                $_SESSION['flash_message'] = 'Listing is required';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/realtor/clients');
                exit;
            }

            if (!in_array($termsType, ['one_time', 'monthly'], true)) {
                $termsType = 'one_time';
            }

            if ($totalAmount <= 0) {
                $_SESSION['flash_message'] = 'Total amount must be greater than 0';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/realtor/clients');
                exit;
            }

            if ($termsType === 'monthly') {
                if ($durationMonths <= 0) {
                    $_SESSION['flash_message'] = 'Duration (months) is required for monthly contracts';
                    $_SESSION['flash_type'] = 'danger';
                    header('Location: ' . BASE_URL . '/realtor/clients');
                    exit;
                }
                if ($startMonth === '') {
                    $_SESSION['flash_message'] = 'Start month is required for monthly contracts';
                    $_SESSION['flash_type'] = 'danger';
                    header('Location: ' . BASE_URL . '/realtor/clients');
                    exit;
                }
            }

            $listingModel = new RealtorListing();
            $listing = $listingModel->getByIdWithAccess((int)$listingId, (int)$this->userId);
            if (!$listing) {
                $_SESSION['flash_message'] = 'Invalid listing';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/realtor/clients');
                exit;
            }

            $model = new RealtorClient();
            $db = Connection::getInstance()->getConnection();
            $db->beginTransaction();

            try {
                $clientId = (int)$model->insert($data);

                if ($clientId <= 0) {
                    throw new \Exception('Failed to create client');
                }

                // Link client to listing for convenience
                try {
                    $model->updateById((int)$clientId, ['realtor_listing_id' => (int)$listingId]);
                } catch (\Throwable $e) {
                }

                $contractId = null;
                $contractModel = new RealtorContract();
                try {
                    $existing = $contractModel->query(
                        "SELECT id FROM realtor_contracts WHERE user_id = ? AND realtor_client_id = ? AND realtor_listing_id = ? ORDER BY id DESC LIMIT 1",
                        [(int)$this->userId, (int)$clientId, (int)$listingId]
                    );
                    if (!empty($existing)) {
                        $contractId = (int)($existing[0]['id'] ?? 0);
                    }
                } catch (\Throwable $e) {
                }

                if (!$contractId) {
                    $monthlyAmount = null;
                    $startMonthDate = null;
                    $durationToSave = null;
                    if ($termsType === 'monthly') {
                        $monthlyAmount = round($totalAmount / max(1, $durationMonths), 2);
                        $startMonthDate = $startMonth . '-01';
                        $durationToSave = (int)$durationMonths;
                    }

                    $contractId = (int)$contractModel->insert([
                        'user_id' => (int)$this->userId,
                        'realtor_client_id' => (int)$clientId,
                        'realtor_listing_id' => (int)$listingId,
                        'terms_type' => (string)$termsType,
                        'total_amount' => (float)$totalAmount,
                        'monthly_amount' => $monthlyAmount,
                        'duration_months' => $durationToSave,
                        'start_month' => $startMonthDate,
                        'status' => 'active',
                    ]);
                }

                if ($contractId > 0) {
                    try {
                        $invModel = new Invoice();
                        $invModel->ensureRealtorContractInvoice(
                            (int)$this->userId,
                            (int)$contractId,
                            (int)$clientId,
                            (int)$listingId,
                            (float)$totalAmount,
                            date('Y-m-d')
                        );
                    } catch (\Throwable $e) {
                    }
                }

                // Mark listing as sold since it is linked to a contract.
                try {
                    $listingModel->updateById((int)$listingId, ['status' => 'sold']);
                } catch (\Throwable $e) {
                }

                // Create a Won lead and link it to this client.
                try {
                    $leadModel = new RealtorLead();
                    $leadId = (int)$leadModel->insert([
                        'user_id' => (int)$this->userId,
                        'realtor_listing_id' => (int)$listingId,
                        'listing_name' => (string)($listing['title'] ?? ''),
                        'address' => (string)($listing['location'] ?? ''),
                        'amount' => (float)$totalAmount,
                        'name' => (string)$data['name'],
                        'phone' => (string)$data['phone'],
                        'email' => (string)$data['email'],
                        'source' => 'manual_client',
                        'status' => 'won',
                        'notes' => (string)$data['notes'],
                        'converted_client_id' => (int)$clientId,
                    ]);
                    if ($leadId > 0) {
                        // no-op
                    }
                } catch (\Throwable $e) {
                }

                $db->commit();

                $_SESSION['flash_message'] = 'Client and contract created successfully';
                $_SESSION['flash_type'] = 'success';

                if ($contractId > 0) {
                    header('Location: ' . BASE_URL . '/realtor/contracts/show/' . (int)$contractId . '?edit=1');
                    exit;
                }
            } catch (\Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                throw $e;
            }
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

            $db = Connection::getInstance()->getConnection();
            $db->beginTransaction();

            try {
                $listingId = !empty($row['realtor_listing_id']) ? (int)$row['realtor_listing_id'] : 0;

                // Delete payments linked to this client (realtor payments)
                try {
                    $stmtPay = $db->prepare("DELETE FROM payments WHERE realtor_user_id = ? AND realtor_client_id = ?");
                    $stmtPay->execute([(int)$this->userId, (int)$id]);
                } catch (\Throwable $e) {
                }

                // Delete payments linked to contracts of this client (in case client_id was not populated)
                try {
                    $stmtPay2 = $db->prepare(
                        "DELETE p FROM payments p\n"
                        . "JOIN realtor_contracts c ON c.id = p.realtor_contract_id\n"
                        . "WHERE c.user_id = ? AND c.realtor_client_id = ?"
                    );
                    $stmtPay2->execute([(int)$this->userId, (int)$id]);
                } catch (\Throwable $e) {
                }

                // Delete contracts for this client
                try {
                    $stmtC = $db->prepare("DELETE FROM realtor_contracts WHERE user_id = ? AND realtor_client_id = ?");
                    $stmtC->execute([(int)$this->userId, (int)$id]);
                } catch (\Throwable $e) {
                }

                // Mark linked lead as lost (and unconvert)
                try {
                    $stmtLead = $db->prepare("UPDATE realtor_leads SET status = 'lost', converted_client_id = NULL WHERE user_id = ? AND converted_client_id = ?");
                    $stmtLead->execute([(int)$this->userId, (int)$id]);
                } catch (\Throwable $e) {
                }

                // Detach listing from any leads before deleting listing
                if ($listingId > 0) {
                    try {
                        $stmtDetach = $db->prepare("UPDATE realtor_leads SET realtor_listing_id = NULL WHERE user_id = ? AND realtor_listing_id = ?");
                        $stmtDetach->execute([(int)$this->userId, (int)$listingId]);
                    } catch (\Throwable $e) {
                    }
                }

                // Delete listing associated with this client (only if not referenced elsewhere)
                if ($listingId > 0) {
                    $refCount = 0;
                    try {
                        $stmtRef1 = $db->prepare("SELECT COUNT(*) AS c FROM realtor_clients WHERE user_id = ? AND realtor_listing_id = ? AND id <> ?");
                        $stmtRef1->execute([(int)$this->userId, (int)$listingId, (int)$id]);
                        $refCount += (int)($stmtRef1->fetch(\PDO::FETCH_ASSOC)['c'] ?? 0);
                    } catch (\Throwable $e) {
                    }
                    try {
                        $stmtRef2 = $db->prepare("SELECT COUNT(*) AS c FROM realtor_contracts WHERE user_id = ? AND realtor_listing_id = ?");
                        $stmtRef2->execute([(int)$this->userId, (int)$listingId]);
                        $refCount += (int)($stmtRef2->fetch(\PDO::FETCH_ASSOC)['c'] ?? 0);
                    } catch (\Throwable $e) {
                    }
                    try {
                        $stmtRef3 = $db->prepare("SELECT COUNT(*) AS c FROM payments WHERE realtor_user_id = ? AND realtor_listing_id = ?");
                        $stmtRef3->execute([(int)$this->userId, (int)$listingId]);
                        $refCount += (int)($stmtRef3->fetch(\PDO::FETCH_ASSOC)['c'] ?? 0);
                    } catch (\Throwable $e) {
                    }

                    if ($refCount <= 0) {
                        try {
                            $stmtDelL = $db->prepare("DELETE FROM realtor_listings WHERE user_id = ? AND id = ?");
                            $stmtDelL->execute([(int)$this->userId, (int)$listingId]);
                        } catch (\Throwable $e) {
                        }
                    }
                }

                // Finally delete the client
                $ok = (bool)$model->deleteById((int)$id);
                if (!$ok) {
                    throw new \Exception('Failed to delete');
                }

                $db->commit();
                echo json_encode(['success' => true, 'message' => 'Deleted']);
            } catch (\Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                echo json_encode(['success' => false, 'message' => 'Failed to delete']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
        exit;
    }
}
