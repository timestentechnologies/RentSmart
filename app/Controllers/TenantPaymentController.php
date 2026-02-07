<?php

namespace App\Controllers;

use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Lease;
use App\Models\Utility;
use App\Models\PaymentMethod;

class TenantPaymentController
{
    private $payment;
    private $tenant;
    private $lease;
    private $utility;
    private $paymentMethod;

    public function __construct()
    {
        $this->payment = new Payment();
        $this->tenant = new Tenant();
        $this->lease = new Lease();
        $this->utility = new Utility();
        $this->paymentMethod = new PaymentMethod();
    }

    /**
     * Process tenant payment
     */
    public function process()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['tenant_id'])) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        try {
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            $tenantId = $_SESSION['tenant_id'];
            $paymentType = isset($_POST['payment_type']) ? trim($_POST['payment_type']) : '';
            $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
            $paymentMethodId = isset($_POST['payment_method_id']) ? intval($_POST['payment_method_id']) : 0;
            
            if (!$paymentType || !$amount || !$paymentMethodId) {
                throw new \Exception('Missing required payment information');
            }
            
            // Get payment method details
            $paymentMethodData = $this->paymentMethod->getById($paymentMethodId);
            if (!$paymentMethodData) {
                throw new \Exception('Invalid payment method selected');
            }
            
            // Get additional payment details based on method
            $paymentDetails = $this->getPaymentDetails($paymentMethodData['type'], $paymentMethodData['details']);
            
            // Check M-Pesa transaction code uniqueness if it's an M-Pesa payment
            if (($paymentMethodData['type'] === 'mpesa_manual' || $paymentMethodData['type'] === 'mpesa_stk') && 
                !empty($paymentDetails['mpesa_transaction_code'])) {
                if ($this->isMpesaTransactionCodeExists($paymentDetails['mpesa_transaction_code'])) {
                    throw new \Exception('This M-Pesa transaction code has already been used. Please use a different transaction code.');
                }
            }

            // Get tenant info
            $tenant = $this->tenant->find($tenantId);
            if (!$tenant) {
                throw new \Exception('Tenant not found');
            }

            // Get the active lease for this tenant
            $lease = $this->lease->getActiveLeaseByTenant($tenantId);
            if (!$lease) {
                throw new \Exception('No active lease found for this tenant');
            }
            
            // Authorize payment method against property's owner/manager/agent and linkage to property
            try {
                $db = $this->payment->getDb();
                $propStmt = $db->prepare('SELECT p.id AS property_id, p.owner_id, p.manager_id, p.agent_id
                                          FROM leases l
                                          JOIN units u ON l.unit_id = u.id
                                          JOIN properties p ON u.property_id = p.id
                                          WHERE l.id = ?');
                $propStmt->execute([$lease['id']]);
                $prop = $propStmt->fetch(\PDO::FETCH_ASSOC) ?: [];
                $allowedOwners = array_values(array_filter([
                    (int)($prop['owner_id'] ?? 0),
                    (int)($prop['manager_id'] ?? 0),
                    (int)($prop['agent_id'] ?? 0)
                ]));
                $methodOwnerId = (int)($paymentMethodData['owner_user_id'] ?? 0);
                if (empty($methodOwnerId) || !in_array($methodOwnerId, $allowedOwners, true)) {
                    throw new \Exception('Selected payment method is not available for this property');
                }
                // Enforce linkage of payment method to the tenant's property
                $propertyId = (int)($prop['property_id'] ?? 0);
                if ($propertyId <= 0 || !$this->paymentMethod->isLinkedToProperty((int)$paymentMethodId, $propertyId)) {
                    throw new \Exception('Selected payment method is not available for this property');
                }
            } catch (\Exception $e) {
                throw $e; // propagate as validation error
            }

            // Determine payment status based on method type
            $paymentStatus = 'completed';
            if ($paymentMethodData['type'] === 'mpesa_manual' || $paymentMethodData['type'] === 'mpesa_stk') {
                $paymentStatus = 'pending_verification';
            }

            // Handle different payment types
            if ($paymentType === 'utility') {
                // For utility payments, we need to get the utilities and create separate payments
                $utilities = $this->utility->getTenantUtilities($tenantId);
                $utilityPayments = [];
                $totalUtilityAmount = 0;
                
                foreach ($utilities as $utility) {
                    $netAmount = max(0, $utility['net_amount'] ?? $utility['amount'] ?? 0);
                    if ($netAmount > 0) {
                        $utilityPayments[] = [
                            'utility' => $utility,
                            'amount' => $netAmount
                        ];
                        $totalUtilityAmount += $netAmount;
                    }
                }
                
                if (empty($utilityPayments)) {
                    throw new \Exception('No outstanding utility payments found');
                }
                
                if (abs($amount - $totalUtilityAmount) > 0.01) {
                    throw new \Exception('Payment amount does not match total utility amount due');
                }
                
                // Create payment records for each utility
                $paymentIds = [];
                foreach ($utilityPayments as $utilityPayment) {
                    $paymentData = [
                        'lease_id' => $lease['id'],
                        'utility_id' => $utilityPayment['utility']['id'],
                        'amount' => $utilityPayment['amount'],
                        'payment_date' => date('Y-m-d'),
                        'payment_type' => 'utility',
                        'payment_method' => $paymentMethodData['type'], // Use 'type' instead of 'name' to match enum values
                        'notes' => 'Payment via ' . $paymentMethodData['name'] . ' - ' . ucfirst($utilityPayment['utility']['utility_type']) . ' - ' . ($paymentDetails['mpesa_notes'] ?? ''),
                        'status' => $paymentStatus
                    ];
                    
                    $paymentId = $this->payment->createUtilityPayment($paymentData);
                    $paymentIds[] = $paymentId;
                    
                    // If it's an M-Pesa payment, save to manual_mpesa_payments table
                    if ($paymentMethodData['type'] === 'mpesa_manual' || $paymentMethodData['type'] === 'mpesa_stk') {
                        $this->saveMpesaTransaction($paymentId, $paymentDetails, $utilityPayment['amount']);
                    }
                }
                
                $paymentId = $paymentIds[0]; // Use first payment ID for response

                // Ensure invoice exists and update statuses for this month (utility payments should affect invoices)
                try {
                    $invModel = new \App\Models\Invoice();
                    $invModel->ensureMonthlyRentInvoice((int)$lease['tenant_id'], date('Y-m-d'), (float)($lease['rent_amount'] ?? 0), null, 'AUTO');
                    $invModel->updateStatusForTenantMonth((int)$lease['tenant_id'], date('Y-m-d'));
                } catch (\Exception $e) {
                    error_log('Auto-invoice (tenant utility) failed: ' . $e->getMessage());
                }
            } else if ($paymentType === 'maintenance') {
                $maintenanceOutstanding = $this->payment->getMaintenanceOutstandingByLeaseId((int)$lease['id']);
                if ($maintenanceOutstanding <= 0.0) {
                    throw new \Exception('No outstanding maintenance charges found');
                }
                if (abs($amount - $maintenanceOutstanding) > 0.01) {
                    throw new \Exception('Payment amount does not match maintenance total');
                }

                $maintenancePaymentData = [
                    'lease_id' => $lease['id'],
                    'amount' => $maintenanceOutstanding,
                    'payment_date' => date('Y-m-d'),
                    'payment_type' => 'other',
                    'payment_method' => $paymentMethodData['type'],
                    'notes' => 'Maintenance payment: Payment via ' . $paymentMethodData['name'] . ' - ' . ($paymentDetails['mpesa_notes'] ?? ''),
                    'status' => $paymentStatus
                ];
                $paymentId = $this->payment->createRentPayment($maintenancePaymentData);

                if ($paymentMethodData['type'] === 'mpesa_manual' || $paymentMethodData['type'] === 'mpesa_stk') {
                    $maintDetails = $paymentDetails;
                    if (!empty($maintDetails['mpesa_transaction_code'])) {
                        $maintDetails['mpesa_transaction_code'] = $maintDetails['mpesa_transaction_code'] . '-M';
                    }
                    $this->saveMpesaTransaction($paymentId, $maintDetails, $maintenanceOutstanding);
                }

            } else if ($paymentType === 'both' || $paymentType === 'all') {
                // Combined payment:
                // both = rent + utilities
                // all  = rent + utilities + maintenance
                // 1) Calculate rent due now using missed months and coverage
                $missedMonths = $this->payment->getTenantMissedRentMonths($tenantId);
                $overdueAmount = 0.0;
                foreach ($missedMonths as $mm) {
                    $overdueAmount += isset($mm['amount']) ? (float)$mm['amount'] : 0.0;
                }
                $coverage = $this->payment->getTenantRentCoverage($tenantId);
                $dueNow = is_array($coverage) && isset($coverage['due_now']) ? (bool)$coverage['due_now'] : true;
                $totalRentAmount = $dueNow ? max(0.0, $overdueAmount) : 0.0;

                $maintenanceOutstanding = 0.0;
                if ($paymentType === 'all') {
                    $maintenanceOutstanding = $this->payment->getMaintenanceOutstandingByLeaseId((int)$lease['id']);
                }

                // 2) Calculate utilities due
                $utilities = $this->utility->getTenantUtilities($tenantId);
                $utilityPayments = [];
                $totalUtilityAmount = 0.0;
                foreach ($utilities as $utility) {
                    $netAmount = max(0, $utility['net_amount'] ?? $utility['amount'] ?? 0);
                    if ($netAmount > 0) {
                        $utilityPayments[] = [
                            'utility' => $utility,
                            'amount' => $netAmount
                        ];
                        $totalUtilityAmount += $netAmount;
                    }
                }

                // 3) Validate combined amount
                $expectedTotal = $totalRentAmount + $totalUtilityAmount + $maintenanceOutstanding;
                if (abs($amount - $expectedTotal) > 0.01) {
                    throw new \Exception('Payment amount does not match total due');
                }

                // 4) Create maintenance payment first (all)
                if ($maintenanceOutstanding > 0.0) {
                    $maintenancePaymentData = [
                        'lease_id' => $lease['id'],
                        'amount' => $maintenanceOutstanding,
                        'payment_date' => date('Y-m-d'),
                        'payment_type' => 'other',
                        'payment_method' => $paymentMethodData['type'],
                        'notes' => 'Maintenance payment: Payment via ' . $paymentMethodData['name'] . ' - ' . ($paymentDetails['mpesa_notes'] ?? ''),
                        'status' => $paymentStatus
                    ];
                    $maintPaymentId = $this->payment->createRentPayment($maintenancePaymentData);
                    if ($paymentMethodData['type'] === 'mpesa_manual') {
                        $maintDetails = $paymentDetails;
                        if (!empty($maintDetails['mpesa_transaction_code'])) {
                            $maintDetails['mpesa_transaction_code'] = $maintDetails['mpesa_transaction_code'] . '-M';
                        }
                        $this->saveMpesaTransaction($maintPaymentId, $maintDetails, $maintenanceOutstanding);
                    }
                }

                // 5) Create rent payment only if there is rent due now
                $rentPaymentId = null;
                if ($totalRentAmount > 0.0) {
                    $rentPaymentData = [
                        'lease_id' => $lease['id'],
                        'amount' => $totalRentAmount,
                        'payment_date' => date('Y-m-d'),
                        'payment_type' => 'rent',
                        // Use enum value for method
                        'payment_method' => $paymentMethodData['type'],
                        'notes' => 'Combined Rent+Utilities via ' . $paymentMethodData['name'] . ' - ' . ($paymentDetails['mpesa_notes'] ?? ''),
                        'status' => $paymentStatus
                    ];
                    $rentPaymentId = $this->payment->createRentPayment($rentPaymentData);
                    try {
                        // Ensure a monthly rent invoice exists for this tenant for this month
                        $invModel = new \App\Models\Invoice();
                        $invModel->ensureMonthlyRentInvoice((int)$lease['tenant_id'], $rentPaymentData['payment_date'], (float)$lease['rent_amount'], null, 'AUTO');
                    } catch (\Exception $e) { error_log('Auto-invoice (both) failed: ' . $e->getMessage()); }
                    if ($paymentMethodData['type'] === 'mpesa_manual') {
                        $this->saveMpesaTransaction($rentPaymentId, $paymentDetails, $totalRentAmount);
                    }
                }

                // 6) Create utility payments
                $firstUtilityPaymentId = null;
                foreach ($utilityPayments as $idx => $utilityPayment) {
                    $paymentData = [
                        'lease_id' => $lease['id'],
                        'utility_id' => $utilityPayment['utility']['id'],
                        'amount' => $utilityPayment['amount'],
                        'payment_date' => date('Y-m-d'),
                        'payment_type' => 'utility',
                        'payment_method' => $paymentMethodData['type'], // keep consistent with utility branch
                        'notes' => 'Combined via ' . $paymentMethodData['name'] . ' - ' . ucfirst($utilityPayment['utility']['utility_type']) . ' - ' . ($paymentDetails['mpesa_notes'] ?? ''),
                        'status' => $paymentStatus
                    ];
                    $utilPaymentId = $this->payment->createUtilityPayment($paymentData);
                    if ($firstUtilityPaymentId === null) { $firstUtilityPaymentId = $utilPaymentId; }
                    if ($paymentMethodData['type'] === 'mpesa_manual') {
                        // Avoid duplicate transaction_code conflicts by suffixing for each utility split
                        $utilDetails = $paymentDetails;
                        if (!empty($utilDetails['mpesa_transaction_code'])) {
                            $utilDetails['mpesa_transaction_code'] = $utilDetails['mpesa_transaction_code'] . '-U' . ($idx + 1);
                        }
                        $this->saveMpesaTransaction($utilPaymentId, $utilDetails, $utilityPayment['amount']);
                    }
                }

                // Use the rent payment ID for response when present; otherwise first utility payment
                if (!empty($rentPaymentId)) {
                    $paymentId = $rentPaymentId;
                } else if (!empty($firstUtilityPaymentId)) {
                    $paymentId = $firstUtilityPaymentId;
                } else {
                    throw new \Exception('No payable items found for combined payment');
                }

                // Update invoice status after combined payment
                try {
                    $invModel = new \App\Models\Invoice();
                    $invModel->ensureMonthlyRentInvoice((int)$lease['tenant_id'], date('Y-m-d'), (float)($lease['rent_amount'] ?? 0), null, 'AUTO');
                    $invModel->updateStatusForTenantMonth((int)$lease['tenant_id'], date('Y-m-d'));
                } catch (\Exception $e) {
                    error_log('Auto-invoice (tenant both) failed: ' . $e->getMessage());
                }
            } else {
                // For rent payments, auto-split any outstanding maintenance charges (MAINT-) for this month
                $today = date('Y-m-d');
                $monthStart = date('Y-m-01', strtotime($today));
                $monthEnd = date('Y-m-t', strtotime($today));

                $maintenanceOutstanding = 0.0;
                try {
                    $db = $this->payment->getDb();
                    $maintChargesStmt = $db->prepare(
                        "SELECT COALESCE(SUM(ABS(amount)),0) AS s\n"
                        . "FROM payments\n"
                        . "WHERE lease_id = ?\n"
                        . "  AND payment_type = 'rent'\n"
                        . "  AND amount < 0\n"
                        . "  AND notes LIKE ?\n"
                        . "  AND status IN ('completed','verified')\n"
                        . "  AND payment_date BETWEEN ? AND ?"
                    );
                    $maintChargesStmt->execute([(int)$lease['id'], '%MAINT-%', $monthStart, $monthEnd]);
                    $charged = (float)($maintChargesStmt->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);

                    $maintPaidStmt = $db->prepare(
                        "SELECT COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) AS s\n"
                        . "FROM payments\n"
                        . "WHERE lease_id = ?\n"
                        . "  AND payment_type = 'other'\n"
                        . "  AND status IN ('completed','verified')\n"
                        . "  AND payment_date BETWEEN ? AND ?\n"
                        . "  AND (notes LIKE 'Maintenance payment:%' OR notes LIKE '%MAINT-%')"
                    );
                    $maintPaidStmt->execute([(int)$lease['id'], $monthStart, $monthEnd]);
                    $paid = (float)($maintPaidStmt->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);

                    $maintenanceOutstanding = max(0.0, $charged - $paid);
                } catch (\Exception $e) {
                    $maintenanceOutstanding = 0.0;
                }

                $remainingAmount = (float)$amount;
                $createdPaymentId = null;

                // 1) Maintenance payment part
                if ($maintenanceOutstanding > 0.0 && $remainingAmount > 0.0) {
                    $maintPayAmount = min($remainingAmount, $maintenanceOutstanding);
                    if ($maintPayAmount > 0.0) {
                        $maintenancePaymentData = [
                            'lease_id' => $lease['id'],
                            'amount' => $maintPayAmount,
                            'payment_date' => $today,
                            'payment_type' => 'other',
                            'payment_method' => $paymentMethodData['type'],
                            'notes' => 'Maintenance payment: Payment via ' . $paymentMethodData['name'] . ' - ' . ($paymentDetails['mpesa_notes'] ?? ''),
                            'status' => $paymentStatus
                        ];
                        $createdPaymentId = $this->payment->createRentPayment($maintenancePaymentData);
                        $remainingAmount -= $maintPayAmount;

                        if ($paymentMethodData['type'] === 'mpesa_manual' || $paymentMethodData['type'] === 'mpesa_stk') {
                            $maintDetails = $paymentDetails;
                            if (!empty($maintDetails['mpesa_transaction_code'])) {
                                $maintDetails['mpesa_transaction_code'] = $maintDetails['mpesa_transaction_code'] . '-M';
                            }
                            $this->saveMpesaTransaction($createdPaymentId, $maintDetails, $maintPayAmount);
                        }
                    }
                }

                // 2) Rent payment part
                if ($remainingAmount > 0.0) {
                    $paymentData = [
                        'lease_id' => $lease['id'],
                        'amount' => $remainingAmount,
                        'payment_date' => $today,
                        'payment_type' => $paymentType,
                        'payment_method' => $paymentMethodData['type'],
                        'notes' => 'Payment via ' . $paymentMethodData['name'] . ' - ' . ($paymentDetails['mpesa_notes'] ?? ''),
                        'status' => $paymentStatus
                    ];
                    $createdPaymentId = $this->payment->createRentPayment($paymentData);

                    if ($paymentMethodData['type'] === 'mpesa_manual' || $paymentMethodData['type'] === 'mpesa_stk') {
                        $this->saveMpesaTransaction($createdPaymentId, $paymentDetails, $remainingAmount);
                    }
                }

                $paymentId = $createdPaymentId;
                try {
                    // Ensure a monthly rent invoice exists for this tenant for this month
                    $invModel = new \App\Models\Invoice();
                    $invModel->ensureMonthlyRentInvoice((int)$lease['tenant_id'], $today, (float)$lease['rent_amount'], null, 'AUTO');
                    $invModel->updateStatusForTenantMonth((int)$lease['tenant_id'], $today);
                } catch (\Exception $e) { error_log('Auto-invoice (rent) failed: ' . $e->getMessage()); }
            }

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment submitted successfully. You will receive a confirmation shortly.',
                    'payment_id' => $paymentId,
                    'payment_details' => [
                        'payment_type' => $paymentType,
                        'amount' => $amount,
                        'payment_method' => $paymentMethodData['name'],
                        'mpesa_transaction_code' => $paymentDetails['mpesa_transaction_code'] ?? null,
                        'mpesa_number' => $paymentDetails['mpesa_number'] ?? null,
                        'status' => $paymentStatus
                    ]
                ]);
                exit;
            }

            $_SESSION['flash_message'] = 'Payment submitted successfully';
            $_SESSION['flash_type'] = 'success';
            redirect('/tenant/dashboard');

        } catch (\Exception $e) {
            error_log("Error in TenantPaymentController::process: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Payment processing failed: ' . $e->getMessage()
                ]);
                exit;
            }

            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            redirect('/tenant/dashboard');
        } catch (\Error $e) {
            error_log("Fatal error in TenantPaymentController::process: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Payment processing failed due to a system error'
                ]);
                exit;
            }

            $_SESSION['flash_message'] = 'Payment processing failed due to a system error';
            $_SESSION['flash_type'] = 'danger';
            redirect('/tenant/dashboard');
        }
    }

    /**
     * Get payment details based on payment method
     */
    private function getPaymentDetails($paymentMethodType, $methodDetails)
    {
        $details = [];
        $methodDetails = json_decode($methodDetails, true) ?: [];

        switch ($paymentMethodType) {
            case 'mpesa_manual':
                if (isset($methodDetails['mpesa_method'])) {
                    $details['mpesa_method'] = $methodDetails['mpesa_method'];
                    if ($methodDetails['mpesa_method'] === 'paybill') {
                        $details['paybill_number'] = $methodDetails['paybill_number'] ?? '';
                        $details['account_number'] = $methodDetails['account_number'] ?? '';
                    } elseif ($methodDetails['mpesa_method'] === 'till') {
                        $details['till_number'] = $methodDetails['till_number'] ?? '';
                    }
                }
                $details['mpesa_number'] = isset($_POST['mpesa_number']) ? trim($_POST['mpesa_number']) : '';
                $details['mpesa_transaction_code'] = isset($_POST['mpesa_transaction_code']) ? trim($_POST['mpesa_transaction_code']) : '';
                $details['mpesa_notes'] = isset($_POST['mpesa_notes']) ? trim($_POST['mpesa_notes']) : '';
                break;
                
            case 'mpesa_stk':
                $details['consumer_key'] = $methodDetails['consumer_key'] ?? '';
                $details['consumer_secret'] = $methodDetails['consumer_secret'] ?? '';
                $details['shortcode'] = $methodDetails['shortcode'] ?? '';
                $details['passkey'] = $methodDetails['passkey'] ?? '';
                $details['mpesa_number'] = isset($_POST['mpesa_number']) ? trim($_POST['mpesa_number']) : '';
                break;
                
            case 'bank_transfer':
                $details['bank_name'] = isset($_POST['bank_name']) ? trim($_POST['bank_name']) : '';
                $details['account_number'] = isset($_POST['account_number']) ? trim($_POST['account_number']) : '';
                $details['transaction_id'] = isset($_POST['transaction_id']) ? trim($_POST['transaction_id']) : '';
                break;
                
            case 'cash':
            case 'cheque':
                $details['payment_reference'] = isset($_POST['payment_reference']) ? trim($_POST['payment_reference']) : '';
                $details['payment_notes'] = isset($_POST['payment_notes']) ? trim($_POST['payment_notes']) : '';
                break;
        }

        return $details;
    }

    /**
     * Save M-Pesa transaction details
     */
    private function saveMpesaTransaction($paymentId, $paymentDetails, $amount)
    {
        try {
            $phoneNumber = $paymentDetails['mpesa_number'] ?? '';
            $transactionCode = $paymentDetails['mpesa_transaction_code'] ?? '';
            $notes = $paymentDetails['mpesa_notes'] ?? '';
            
            if (empty($phoneNumber) || empty($transactionCode)) {
                throw new \Exception('M-Pesa phone number and transaction code are required');
            }
            
            $sql = "INSERT INTO manual_mpesa_payments (payment_id, phone_number, transaction_code, amount, verification_status, verification_notes, created_at) VALUES (?, ?, ?, ?, 'pending', ?, NOW())";
            
            $stmt = $this->payment->getDb()->prepare($sql);
            $stmt->execute([$paymentId, $phoneNumber, $transactionCode, $amount, $notes]);
            
        } catch (\Exception $e) {
            error_log("Error saving M-Pesa transaction: " . $e->getMessage());
            // Don't throw exception here to avoid breaking the payment flow
        }
    }

    /**
     * Check if M-Pesa transaction code already exists
     */
    private function isMpesaTransactionCodeExists($transactionCode)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM manual_mpesa_payments WHERE transaction_code = ?";
            $stmt = $this->payment->getDb()->prepare($sql);
            $stmt->execute([$transactionCode]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (\Exception $e) {
            error_log("Error checking M-Pesa transaction code uniqueness: " . $e->getMessage());
            return false; // Allow payment to proceed if check fails
        }
    }

    /**
     * Update payment status
     */
    private function updatePaymentStatus($paymentId, $status)
    {
        try {
            $sql = "UPDATE payments SET status = ? WHERE id = ?";
            $stmt = $this->payment->getDb()->prepare($sql);
            $stmt->execute([$status, $paymentId]);
        } catch (\Exception $e) {
            error_log("Error updating payment status: " . $e->getMessage());
        }
    }

    /**
     * Initiate M-Pesa STK Push for tenant payment
     */
    public function initiateSTK()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['tenant_id'])) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        try {
            $tenantId = $_SESSION['tenant_id'];
            $phoneNumber = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
            $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
            $paymentType = isset($_POST['payment_type']) ? trim($_POST['payment_type']) : '';
            $paymentMethodId = isset($_POST['payment_method_id']) ? intval($_POST['payment_method_id']) : 0;
            
            if (!$phoneNumber || !$amount || !$paymentType || !$paymentMethodId) {
                throw new \Exception('Missing required payment information');
            }
            
            // Validate phone number format
            $phoneNumber = $this->formatPhoneNumber($phoneNumber);
            if (!$phoneNumber) {
                throw new \Exception('Invalid phone number format. Please use format: 254XXXXXXXXX or 07XXXXXXXX');
            }
            
            // Get payment method details
            $paymentMethodData = $this->paymentMethod->getById($paymentMethodId);
            if (!$paymentMethodData || $paymentMethodData['type'] !== 'mpesa_stk') {
                throw new \Exception('Invalid payment method selected');
            }
            
            // Parse payment method details
            $methodDetails = [];
            if (!empty($paymentMethodData['details'])) {
                $methodDetails = json_decode($paymentMethodData['details'], true) ?: [];
            }
            
            // Log for debugging
            error_log("STK Push - Payment Method ID: " . $paymentMethodId);
            error_log("STK Push - Method Details: " . print_r($methodDetails, true));
            
            // Get tenant info
            $tenant = $this->tenant->find($tenantId);
            if (!$tenant) {
                throw new \Exception('Tenant not found');
            }

            // Get the active lease for this tenant
            $lease = $this->lease->getActiveLeaseByTenant($tenantId);
            if (!$lease) {
                throw new \Exception('No active lease found for this tenant');
            }
            
            // Create a pending payment record
            $paymentData = [
                'lease_id' => $lease['id'],
                'amount' => $amount,
                'payment_date' => date('Y-m-d'),
                'payment_type' => $paymentType,
                'payment_method' => $paymentMethodData['type'],
                'notes' => 'M-Pesa STK Push Payment - Awaiting confirmation',
                'status' => 'pending'
            ];

            if ($paymentType === 'utility') {
                $paymentId = $this->payment->createUtilityPayment($paymentData);
            } else {
                $paymentId = $this->payment->createRentPayment($paymentData);
            }
            
            // Initiate STK Push
            $stkResult = $this->sendSTKPush(
                $phoneNumber,
                $amount,
                $methodDetails,
                $paymentId,
                $tenant['name']
            );
            
            if (!$stkResult['success']) {
                // Delete the payment record if STK push failed
                $this->payment->delete($paymentId);
                throw new \Exception($stkResult['message']);
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'STK push sent successfully. Please check your phone and enter your M-Pesa PIN.',
                'payment_id' => $paymentId,
                'checkout_request_id' => $stkResult['checkout_request_id']
            ]);
            exit;

        } catch (\Exception $e) {
            error_log("Error in TenantPaymentController::initiateSTK: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to initiate payment: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    
    /**
     * Format phone number to Safaricom format (254XXXXXXXXX)
     */
    private function formatPhoneNumber($phoneNumber)
    {
        // Remove any spaces, dashes, or plus signs
        $phoneNumber = preg_replace('/[\s\-\+]/', '', $phoneNumber);
        
        // If starts with 0, replace with 254
        if (substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = '254' . substr($phoneNumber, 1);
        }
        
        // If starts with 7 or 1, add 254
        if (substr($phoneNumber, 0, 1) === '7' || substr($phoneNumber, 0, 1) === '1') {
            $phoneNumber = '254' . $phoneNumber;
        }
        
        // Validate format (should be 254XXXXXXXXX, 12 digits total)
        if (!preg_match('/^254[0-9]{9}$/', $phoneNumber)) {
            return false;
        }
        
        return $phoneNumber;
    }
    
    /**
     * Send STK Push to M-Pesa API
     */
    private function sendSTKPush($phoneNumber, $amount, $methodDetails, $paymentId, $accountReference)
    {
        try {
            $consumerKey = $methodDetails['consumer_key'] ?? '';
            $consumerSecret = $methodDetails['consumer_secret'] ?? '';
            $shortcode = $methodDetails['shortcode'] ?? '';
            $passkey = $methodDetails['passkey'] ?? '';
            $accountNumber = $methodDetails['account_number'] ?? ''; // For Paybill
            
            // Check if it's a Till or Paybill
            $isTill = empty($passkey); // Till numbers don't have passkey
            
            if (!$consumerKey || !$consumerSecret || !$shortcode) {
                return [
                    'success' => false,
                    'message' => 'M-Pesa STK Push is not properly configured. Please contact the administrator.'
                ];
            }
            
            // Generate access token
            $accessToken = $this->generateAccessToken($consumerKey, $consumerSecret);
            if (!$accessToken) {
                return [
                    'success' => false,
                    'message' => 'Failed to authenticate with M-Pesa. Please try again later.'
                ];
            }
            
            // Prepare STK push request
            $timestamp = date('YmdHis');
            
            // For Till: use shortcode as password base. For Paybill: use passkey
            if ($isTill) {
                // Till numbers use the shortcode itself for password generation
                $password = base64_encode($shortcode . $shortcode . $timestamp);
            } else {
                // Paybill uses the passkey
                $password = base64_encode($shortcode . $passkey . $timestamp);
            }
            
            // For localhost testing, use a generic callback URL
            // In production, use your actual domain
            if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
                // Use a generic callback for testing - Safaricom will send callbacks here
                // You can view callbacks at: https://webhook.site/#!/view/your-unique-id
                $callbackUrl = 'https://webhook.site/8b5c7e2d-4a3f-4b1e-9c6d-1a2b3c4d5e6f';
            } else {
                $callbackUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
                              "://" . $_SERVER['HTTP_HOST'] . BASE_URL . '/tenant/payment/stk-callback';
            }
            
            $stkUrl = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
            
            // Note: Sandbox environment only supports CustomerPayBillOnline
            // For production Till numbers, use CustomerBuyGoodsOnline
            $transactionType = 'CustomerPayBillOnline'; // Works for both Paybill and Till in sandbox
            
            // Create custom account reference and description
            // Note: Max 13 characters per field due to M-Pesa limits
            $accountRef = 'TimestenRentS'; // 13 chars max - shows as business name (Timesten RentSmart truncated)
            $description = 'RentSmart'; // 13 chars max - shows in description
            
            // Try to get tenant and property info for better description
            if (isset($_SESSION['tenant_id'])) {
                try {
                    $tenantId = $_SESSION['tenant_id'];
                    $tenant = $this->tenant->find($tenantId);
                    $lease = $this->lease->getActiveLeaseByTenant($tenantId);
                    
                    if ($tenant && $lease) {
                        // Get property/unit name
                        $unitSql = "SELECT u.unit_number, p.name as property_name 
                                   FROM units u 
                                   LEFT JOIN properties p ON u.property_id = p.id 
                                   WHERE u.id = ?";
                        $stmt = $this->payment->getDb()->prepare($unitSql);
                        $stmt->execute([$lease['unit_id']]);
                        $unit = $stmt->fetch(\PDO::FETCH_ASSOC);
                        
                        if ($unit) {
                            // Account Reference: Shows as "Pay to [AccountReference]" (max 13 chars)
                            $accountRef = 'TimestenRentS';
                            
                            // Transaction Description: Shows in message (max 13 chars)
                            // Combine RentSmart + Unit number within limit
                            $unitNum = $unit['unit_number'];
                            if (strlen($unitNum) <= 8) {
                                $description = 'RS-' . $unitNum; // e.g., "RS-A1", "RS-101"
                            } else {
                                $description = 'RentSmart'; // Fallback if unit number too long
                            }
                        }
                    }
                } catch (\Exception $e) {
                    error_log("Error getting tenant info for STK: " . $e->getMessage());
                    // Use defaults if error
                }
            }
            
            $postData = [
                'BusinessShortCode' => $shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => $transactionType,
                'Amount' => (int)$amount,
                'PartyA' => $phoneNumber,
                'PartyB' => $shortcode,
                'PhoneNumber' => $phoneNumber,
                'CallBackURL' => $callbackUrl,
                'AccountReference' => $accountRef,
                'TransactionDesc' => $description
            ];
            
            error_log("STK Push Request: " . json_encode($postData));
            
            // Make STK push request
            $ch = curl_init($stkUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                error_log("STK Push cURL Error: " . $error);
                return [
                    'success' => false,
                    'message' => 'Network error. Please check your internet connection.'
                ];
            }
            curl_close($ch);
            
            error_log("STK Push Response (HTTP $httpCode): " . $response);
            
            // Parse response
            $result = json_decode($response, true);
            
            if ($httpCode !== 200 || !isset($result['ResponseCode']) || $result['ResponseCode'] !== '0') {
                $errorMessage = $result['errorMessage'] ?? $result['ResponseDescription'] ?? 'Failed to initiate payment';
                return [
                    'success' => false,
                    'message' => $errorMessage
                ];
            }
            
            // Save STK push details
            $this->saveSTKTransaction(
                $paymentId,
                $result['MerchantRequestID'] ?? '',
                $result['CheckoutRequestID'] ?? '',
                $phoneNumber,
                $amount
            );
            
            return [
                'success' => true,
                'checkout_request_id' => $result['CheckoutRequestID'] ?? '',
                'merchant_request_id' => $result['MerchantRequestID'] ?? ''
            ];
            
        } catch (\Exception $e) {
            error_log("STK Push Exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while initiating payment. Please try again.'
            ];
        }
    }
    
    /**
     * Generate M-Pesa access token
     */
    private function generateAccessToken($consumerKey, $consumerSecret)
    {
        try {
            $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Basic ' . base64_encode($consumerKey . ':' . $consumerSecret)
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                error_log("Access Token cURL Error: " . curl_error($ch));
                curl_close($ch);
                return false;
            }
            curl_close($ch);
            
            $result = json_decode($response, true);
            return $result['access_token'] ?? false;
            
        } catch (\Exception $e) {
            error_log("Access Token Exception: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Save STK transaction details
     */
    private function saveSTKTransaction($paymentId, $merchantRequestId, $checkoutRequestId, $phoneNumber, $amount)
    {
        try {
            $sql = "INSERT INTO mpesa_transactions (payment_id, merchant_request_id, checkout_request_id, phone_number, amount, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
            
            $stmt = $this->payment->getDb()->prepare($sql);
            $stmt->execute([$paymentId, $merchantRequestId, $checkoutRequestId, $phoneNumber, $amount]);
            
        } catch (\Exception $e) {
            error_log("Error saving STK transaction: " . $e->getMessage());
        }
    }
    
    /**
     * Handle M-Pesa STK callback
     */
    public function handleSTKCallback()
    {
        try {
            $callbackData = file_get_contents('php://input');
            error_log("STK Callback Received: " . $callbackData);
            
            $data = json_decode($callbackData, true);
            
            if (!$data) {
                error_log("Invalid callback data");
                return;
            }
            
            $resultCode = $data['Body']['stkCallback']['ResultCode'] ?? null;
            $checkoutRequestId = $data['Body']['stkCallback']['CheckoutRequestID'] ?? null;
            
            if ($resultCode === null || !$checkoutRequestId) {
                error_log("Missing required callback data");
                return;
            }
            
            // Get payment ID from STK transaction
            $sql = "SELECT payment_id FROM mpesa_transactions WHERE checkout_request_id = ?";
            $stmt = $this->payment->getDb()->prepare($sql);
            $stmt->execute([$checkoutRequestId]);
            $transaction = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$transaction) {
                error_log("Transaction not found for checkout request: " . $checkoutRequestId);
                return;
            }
            
            $paymentId = $transaction['payment_id'];
            
            if ($resultCode == 0) {
                // Payment successful
                $callbackMetadata = $data['Body']['stkCallback']['CallbackMetadata']['Item'] ?? [];
                $mpesaReceiptNumber = '';
                $transactionDate = '';
                
                foreach ($callbackMetadata as $item) {
                    if ($item['Name'] === 'MpesaReceiptNumber') {
                        $mpesaReceiptNumber = $item['Value'];
                    }
                    if ($item['Name'] === 'TransactionDate') {
                        $transactionDate = $item['Value'];
                    }
                }
                
                // Convert transaction date from format: 20250115123045 to timestamp
                if ($transactionDate) {
                    $year = substr($transactionDate, 0, 4);
                    $month = substr($transactionDate, 4, 2);
                    $day = substr($transactionDate, 6, 2);
                    $hour = substr($transactionDate, 8, 2);
                    $minute = substr($transactionDate, 10, 2);
                    $second = substr($transactionDate, 12, 2);
                    $transactionDate = "$year-$month-$day $hour:$minute:$second";
                }
                
                // Update payment status
                $updateSql = "UPDATE payments SET status = 'completed', notes = CONCAT(notes, ' - M-Pesa Receipt: ', ?) WHERE id = ?";
                $updateStmt = $this->payment->getDb()->prepare($updateSql);
                $updateStmt->execute([$mpesaReceiptNumber, $paymentId]);
                
                // Update M-Pesa transaction
                $updateTxnSql = "UPDATE mpesa_transactions SET status = 'completed', mpesa_receipt_number = ?, transaction_date = ?, result_code = '0', result_description = 'Success', updated_at = NOW() WHERE checkout_request_id = ?";
                $updateTxnStmt = $this->payment->getDb()->prepare($updateTxnSql);
                $updateTxnStmt->execute([$mpesaReceiptNumber, $transactionDate, $checkoutRequestId]);
                
                error_log("Payment $paymentId completed successfully with receipt: $mpesaReceiptNumber");
                
            } else {
                // Payment failed or cancelled
                $resultDesc = $data['Body']['stkCallback']['ResultDesc'] ?? 'Payment failed';
                
                // Update payment status
                $updateSql = "UPDATE payments SET status = 'failed', notes = CONCAT(notes, ' - Failed: ', ?) WHERE id = ?";
                $updateStmt = $this->payment->getDb()->prepare($updateSql);
                $updateStmt->execute([$resultDesc, $paymentId]);
                
                // Update M-Pesa transaction
                $updateTxnSql = "UPDATE mpesa_transactions SET status = 'failed', result_code = ?, result_description = ?, updated_at = NOW() WHERE checkout_request_id = ?";
                $updateTxnStmt = $this->payment->getDb()->prepare($updateTxnSql);
                $updateTxnStmt->execute([$resultCode, $resultDesc, $checkoutRequestId]);
                
                error_log("Payment $paymentId failed: $resultDesc");
            }
            
            // Send success response to Safaricom
            header('Content-Type: application/json');
            echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
            
        } catch (\Exception $e) {
            error_log("Error in STK callback: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
        }
    }

    /**
     * Get tenant payment history
     */
    public function history()
    {
        session_start();
        if (!isset($_SESSION['tenant_id'])) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        try {
            $tenantId = $_SESSION['tenant_id'];
            $payments = $this->payment->getByTenant($tenantId);
            
            echo view('tenant/payment_history', [
                'title' => 'Payment History - RentSmart',
                'payments' => $payments
            ]);

        } catch (\Exception $e) {
            error_log("Error in TenantPaymentController::history: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading payment history';
            $_SESSION['flash_type'] = 'danger';
            redirect('/tenant/dashboard');
        }
    }
    
    /**
     * Check STK Push payment status
     */
    public function checkSTKStatus()
    {
        header('Content-Type: application/json');
        
        try {
            $checkoutRequestId = $_POST['checkout_request_id'] ?? '';
            $merchantRequestId = $_POST['merchant_request_id'] ?? '';
            
            if (!$checkoutRequestId) {
                echo json_encode(['status' => 'error', 'message' => 'Missing checkout request ID']);
                return;
            }
            
            // Check transaction status in database
            $sql = "SELECT * FROM mpesa_transactions WHERE checkout_request_id = ? ORDER BY id DESC LIMIT 1";
            $stmt = $this->payment->getDb()->prepare($sql);
            $stmt->execute([$checkoutRequestId]);
            $transaction = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$transaction) {
                echo json_encode(['status' => 'pending', 'message' => 'Transaction not found yet']);
                return;
            }
            
            // Return transaction status
            $response = [
                'status' => $transaction['status'],
                'message' => $transaction['result_desc'] ?? '',
                'receipt_number' => $transaction['mpesa_receipt_number'] ?? '',
                'transaction_date' => $transaction['transaction_date'] ?? '',
                'result_desc' => $transaction['result_desc'] ?? ''
            ];
            
            echo json_encode($response);
            
        } catch (\Exception $e) {
            error_log("Error checking STK status: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error checking payment status']);
        }
    }
    
    /**
     * Generate payment receipt PDF for tenant
     */
    public function receipt($id)
    {
        // Clear any output buffers to prevent "headers already sent" errors
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        if (!isset($_SESSION['tenant_id'])) {
            http_response_code(403);
            die('Access denied. No tenant session found.');
        }
        
        try {
            $tenantId = $_SESSION['tenant_id'];
            
            // Get payment details with tenant verification
            $sql = "SELECT p.*, 
                           t.name as tenant_name, t.email as tenant_email, t.phone as tenant_phone,
                           u.unit_number,
                           pr.name as property_name, pr.address as property_address, 
                           pr.city as property_city, pr.state as property_state, pr.zip_code as property_zip,
                           pr.owner_id as property_owner_id, pr.manager_id as property_manager_id
                    FROM payments p
                    INNER JOIN leases l ON p.lease_id = l.id
                    INNER JOIN tenants t ON l.tenant_id = t.id
                    INNER JOIN units u ON l.unit_id = u.id
                    INNER JOIN properties pr ON u.property_id = pr.id
                    WHERE p.id = ? AND t.id = ?";
            
            $stmt = $this->payment->getDb()->prepare($sql);
            $stmt->execute([$id, $tenantId]);
            $payment = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$payment) {
                http_response_code(404);
                die('Payment not found or access denied.');
            }
            
            // Fetch owner and manager names
            $ownerName = null;
            $managerName = null;
            if (!empty($payment['property_owner_id'])) {
                $ownerSql = "SELECT name FROM users WHERE id = ?";
                $ownerStmt = $this->payment->getDb()->prepare($ownerSql);
                $ownerStmt->execute([$payment['property_owner_id']]);
                $owner = $ownerStmt->fetch(\PDO::FETCH_ASSOC);
                $ownerName = $owner['name'] ?? null;
            }
            if (!empty($payment['property_manager_id'])) {
                $managerSql = "SELECT name FROM users WHERE id = ?";
                $managerStmt = $this->payment->getDb()->prepare($managerSql);
                $managerStmt->execute([$payment['property_manager_id']]);
                $manager = $managerStmt->fetch(\PDO::FETCH_ASSOC);
                $managerName = $manager['name'] ?? null;
            }
            $payment['property_owner_name'] = $ownerName;
            $payment['property_manager_name'] = $managerName;
            
            // Get logo path and embed as base64 data URI for dompdf
            $logoPath = __DIR__ . '/../../public/assets/images/site_logo_1751627446.png';
            $logoDataUri = null;
            if (file_exists($logoPath)) {
                $imageData = file_get_contents($logoPath);
                $base64 = base64_encode($imageData);
                $logoDataUri = 'data:image/png;base64,' . $base64;
            }
            
            $siteName = 'RentSmart';
            
            // Generate PDF
            ob_start();
            include __DIR__ . '/../../views/payments/receipt_pdf.php';
            $html = ob_get_clean();
            
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            $filename = 'payment_receipt_' . $payment['id'] . '.pdf';
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $filename . '"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            echo $dompdf->output();
            exit;
            
        } catch (\Exception $e) {
            error_log("Error in TenantPaymentController::receipt: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            echo 'Error generating receipt. Please contact support.';
            exit;
        }
    }
}

