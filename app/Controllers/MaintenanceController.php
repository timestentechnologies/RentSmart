<?php

namespace App\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\Tenant;
use App\Models\Property;
use App\Models\Unit;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Account;
use App\Models\LedgerEntry;

class MaintenanceController
{
    private $maintenanceRequest;
    private $tenant;
    private $property;
    private $unit;

    public function __construct()
    {
        $this->maintenanceRequest = new MaintenanceRequest();
        $this->tenant = new Tenant();
        $this->property = new Property();
        $this->unit = new Unit();
    }

    /**
     * Display all maintenance requests for admin
     */
    public function index()
    {
        try {
            $userId = $_SESSION['user_id'];
            $status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
            $priority = isset($_GET['priority']) ? trim((string)$_GET['priority']) : '';
            $category = isset($_GET['category']) ? trim((string)$_GET['category']) : '';

            $allowedStatus = ['pending','in_progress','completed','cancelled'];
            $allowedPriority = ['urgent','high','medium','low'];
            $allowedCategory = ['plumbing','electrical','hvac','appliance','structural','pest_control','cleaning','other','maintenance'];

            $status = in_array($status, $allowedStatus, true) ? $status : null;
            $priority = in_array($priority, $allowedPriority, true) ? $priority : null;
            $category = in_array($category, $allowedCategory, true) ? $category : null;

            $requests = $this->maintenanceRequest->getAllForAdminFiltered($userId, $status, $priority, $category);
            $statistics = $this->maintenanceRequest->getStatistics($userId);
            
            echo view('maintenance/index', [
                'title' => 'Maintenance Requests - RentSmart',
                'requests' => $requests,
                'statistics' => $statistics,
                'filters' => [
                    'status' => $status,
                    'priority' => $priority,
                    'category' => $category,
                ]
            ]);
        } catch (\Exception $e) {
            error_log("Error in MaintenanceController::index: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading maintenance requests';
            $_SESSION['flash_type'] = 'danger';
            redirect('/dashboard');
        }
    }

    /**
     * Show specific maintenance request details
     */
    public function show($id)
    {
        try {
            $userId = $_SESSION['user_id'];
            $request = $this->maintenanceRequest->getById($id, $userId);
            
            if (!$request) {
                throw new \Exception('Maintenance request not found');
            }

            echo view('maintenance/show', [
                'title' => 'Maintenance Request Details - RentSmart',
                'request' => $request
            ]);
        } catch (\Exception $e) {
            error_log("Error in MaintenanceController::show: " . $e->getMessage());
            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            redirect('/maintenance');
        }
    }

    /**
     * Update maintenance request status
     */
    public function updateStatus()
    {
        try {
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
            $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
            $assignedTo = filter_input(INPUT_POST, 'assigned_to', FILTER_SANITIZE_STRING);
            $scheduledDate = filter_input(INPUT_POST, 'scheduled_date', FILTER_SANITIZE_STRING);
            $estimatedCost = filter_input(INPUT_POST, 'estimated_cost', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $actualCost = filter_input(INPUT_POST, 'actual_cost', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $sourceOfFunds = filter_input(INPUT_POST, 'source_of_funds', FILTER_SANITIZE_STRING) ?: null;
            $expensePaymentMethod = filter_input(INPUT_POST, 'expense_payment_method', FILTER_SANITIZE_STRING) ?: null;
            $chargeToTenant = isset($_POST['charge_to_tenant']) ? (($_POST['charge_to_tenant'] == '1' || $_POST['charge_to_tenant'] === 'true' || $_POST['charge_to_tenant'] === true) ? true : false) : false;

            if (!$id || !$status) {
                throw new \Exception('Request ID and status are required');
            }

            $this->maintenanceRequest->updateStatus($id, $status, $notes, $assignedTo, $scheduledDate, $estimatedCost, $actualCost);

            // If actual cost provided, ensure an expense exists and handle funding/tenant charge
            if ($actualCost !== null && $actualCost !== '' && is_numeric($actualCost) && (float)$actualCost > 0) {
                $actualCost = (float)$actualCost;

                // Load request details to get property/unit/tenant
                $userId = $_SESSION['user_id'] ?? null;
                $request = $this->maintenanceRequest->getById($id, $userId);

                // 1) Create or update expense linked to this maintenance
                $expenseModel = new Expense();
                $existing = $expenseModel->findByReference('maintenance', (int)$id);
                $allowedMethods = ['cash','check','bank_transfer','card','mpesa','other'];
                $allowedSources = ['rent_balance','cash','bank','mpesa','owner_funds','other'];
                $expMethod = in_array($expensePaymentMethod, $allowedMethods, true) ? $expensePaymentMethod : 'cash';
                $srcFunds = in_array($sourceOfFunds, $allowedSources, true) ? $sourceOfFunds : ($chargeToTenant ? 'other' : 'cash');

                $expenseData = [
                    'user_id' => $userId,
                    'property_id' => $request['property_id'] ?? null,
                    'unit_id' => $request['unit_id'] ?? null,
                    'category' => 'maintenance',
                    'amount' => $actualCost,
                    'expense_date' => date('Y-m-d'),
                    'payment_method' => $expMethod,
                    'source_of_funds' => $srcFunds,
                    'notes' => 'Maintenance #' . $id . ': ' . ($request['title'] ?? ''),
                    'reference_type' => 'maintenance',
                    'reference_id' => (int)$id,
                ];

                $expenseId = null;
                if ($existing) {
                    $expenseModel->updateExpense((int)$existing['id'], $expenseData);
                    $expenseId = (int)$existing['id'];
                } else {
                    $expenseId = (int)$expenseModel->insertExpense($expenseData);
                }

                // Auto-post expense to ledger (idempotent)
                try {
                    if ($expenseId) {
                        $ledger = new LedgerEntry();
                        if (!$ledger->referenceExists('expense', (int)$expenseId)) {
                            $accModel = new Account();
                            $cash = $accModel->findByCode('1000');
                            $expAcc = $accModel->findByCode('5000');
                            if ($cash && $expAcc) {
                                $desc = 'Expense #' . (int)$expenseId . ' - ' . (string)($expenseData['category'] ?? 'expense');
                                $date = $expenseData['expense_date'] ?? date('Y-m-d');
                                $amount = (float)($expenseData['amount'] ?? 0);
                                $propertyId = $expenseData['property_id'] ?? null;
                                // Debit Expense, Credit Cash
                                $ledger->post([
                                    'entry_date' => $date,
                                    'account_id' => (int)$expAcc['id'],
                                    'description' => $desc,
                                    'debit' => $amount,
                                    'credit' => 0,
                                    'user_id' => $userId,
                                    'property_id' => $propertyId,
                                    'reference_type' => 'expense',
                                    'reference_id' => (int)$expenseId,
                                ]);
                                $ledger->post([
                                    'entry_date' => $date,
                                    'account_id' => (int)$cash['id'],
                                    'description' => $desc,
                                    'debit' => 0,
                                    'credit' => $amount,
                                    'user_id' => $userId,
                                    'property_id' => $propertyId,
                                    'reference_type' => 'expense',
                                    'reference_id' => (int)$expenseId,
                                ]);
                            }
                        }
                    }
                } catch (\Exception $le) {
                    error_log('Maintenance expense ledger post failed: ' . $le->getMessage());
                }

                // 2) Handle tenant charge only: create a negative payment to increase tenant balance
                //    When funded from rent balance, DO NOT create a negative payment; dashboards already adjust using expenses.
                $shouldCreateNegativePayment = false;
                $negativePaymentNoteTag = 'MAINT-' . (int)$id;
                if ($chargeToTenant) {
                    $shouldCreateNegativePayment = true;
                }

                if ($shouldCreateNegativePayment && !empty($request['resolved_tenant_id'])) {
                    $paymentModel = new Payment();
                    // Find active lease for tenant
                    $lease = $paymentModel->getActiveLease((int)$request['resolved_tenant_id'], $userId);
                    if ($lease && !empty($lease['id'])) {
                        // Idempotency: check if a maintenance adjustment already exists
                        try {
                            $db = $paymentModel->getDb();
                            $chk = $db->prepare("SELECT id FROM payments WHERE lease_id = ? AND notes LIKE ? LIMIT 1");
                            $chk->execute([$lease['id'], '%' . $negativePaymentNoteTag . '%']);
                            $existsAdj = $chk->fetch(\PDO::FETCH_ASSOC);
                        } catch (\Exception $e) { $existsAdj = null; }

                        if (!$existsAdj && $shouldCreateNegativePayment) {
                            $paymentModel->createRentPayment([
                                'lease_id' => (int)$lease['id'],
                                'amount' => -abs($actualCost),
                                'payment_date' => date('Y-m-d'),
                                'payment_type' => 'other',
                                'payment_method' => 'other',
                                'notes' => ($chargeToTenant ? 'Maintenance charge to tenant ' : 'Maintenance deduction from rent balance ') . $negativePaymentNoteTag,
                                'status' => 'completed'
                            ]);
                        }
                    }
                }
            }

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Maintenance request updated successfully'
                ]);
                exit;
            }

            $_SESSION['flash_message'] = 'Maintenance request updated successfully';
            $_SESSION['flash_type'] = 'success';
            redirect('/maintenance');
        } catch (\Exception $e) {
            error_log("Error in MaintenanceController::updateStatus: " . $e->getMessage());
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
                exit;
            }

            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            redirect('/maintenance');
        }
    }

    /**
     * Get maintenance request by ID (AJAX)
     */
    public function get($id)
    {
        try {
            $userId = $_SESSION['user_id'];
            $request = $this->maintenanceRequest->getById($id, $userId);
            
            if (!$request) {
                throw new \Exception('Maintenance request not found');
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'request' => $request
            ]);
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Delete maintenance request
     */
    public function delete($id)
    {
        try {
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            $userId = $_SESSION['user_id'];
            $request = $this->maintenanceRequest->getById($id, $userId);
            
            if (!$request) {
                throw new \Exception('Maintenance request not found');
            }

            $this->maintenanceRequest->delete($id);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Maintenance request deleted successfully'
                ]);
                exit;
            }

            $_SESSION['flash_message'] = 'Maintenance request deleted successfully';
            $_SESSION['flash_type'] = 'success';
            redirect('/maintenance');
        } catch (\Exception $e) {
            error_log("Error in MaintenanceController::delete: " . $e->getMessage());
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
                exit;
            }

            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            redirect('/maintenance');
        }
    }
}
