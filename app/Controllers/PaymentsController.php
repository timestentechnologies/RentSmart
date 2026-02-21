<?php

namespace App\Controllers;

use App\Models\Payment;
use App\Models\Tenant;
use App\Helpers\FileUploadHelper;
use App\Models\ActivityLog;
use App\Models\RealtorClient;
use App\Models\RealtorListing;
use App\Models\RealtorContract;

class PaymentsController
{
    private $userId;
    private $activityLog;

    public function __construct()
    {
        // Get current user ID from session
        $this->userId = $_SESSION['user_id'] ?? null;
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Please login to access payments';
            $_SESSION['flash_type'] = 'warning';
            header("Location: " . BASE_URL . "/");
            exit;
        }

        $this->activityLog = new ActivityLog();
    }

    public function export($format = 'csv')
    {
        try {
            $paymentModel = new Payment();
            $userId = $_SESSION['user_id'] ?? null;
            $payments = $paymentModel->getPaymentsWithTenantInfo($userId);

            if ($format === 'csv') {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="payments.csv"');
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Tenant','Amount','Date','Type','Method','Transaction Code','Phone','Status','Notes']);
                foreach ($payments as $p) {
                    fputcsv($out, [
                        $p['tenant_name'] ?? 'Unknown',
                        $p['amount'] ?? 0,
                        $p['payment_date'] ?? '',
                        $p['payment_type'] ?? '',
                        $p['payment_method'] ?? '',
                        $p['transaction_code'] ?? '',
                        $p['phone_number'] ?? '',
                        $p['status'] ?? 'completed',
                        $p['notes'] ?? ''
                    ]);
                }
                fclose($out);
                exit;
            }

            if ($format === 'xlsx') {
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment; filename="payments.xls"');
                echo "<table border='1'>";
                echo '<tr><th>Tenant</th><th>Amount</th><th>Date</th><th>Type</th><th>Method</th><th>Transaction Code</th><th>Phone</th><th>Status</th><th>Notes</th></tr>';
                foreach ($payments as $p) {
                    echo '<tr>'
                        .'<td>'.htmlspecialchars($p['tenant_name'] ?? 'Unknown').'</td>'
                        .'<td>'.htmlspecialchars($p['amount'] ?? 0).'</td>'
                        .'<td>'.htmlspecialchars($p['payment_date'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($p['payment_type'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($p['payment_method'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($p['transaction_code'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($p['phone_number'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($p['status'] ?? 'completed').'</td>'
                        .'<td>'.htmlspecialchars($p['notes'] ?? '').'</td>'
                        .'</tr>';
                }
                echo '</table>';
                exit;
            }

            if ($format === 'pdf') {
                $html = '<h3>Payments</h3><table width="100%" border="1" cellspacing="0" cellpadding="4">'
                    .'<tr><th>Tenant</th><th>Amount</th><th>Date</th><th>Type</th><th>Method</th><th>Transaction Code</th><th>Phone</th><th>Status</th><th>Notes</th></tr>';
                foreach ($payments as $p) {
                    $html .= '<tr>'
                        .'<td>'.htmlspecialchars($p['tenant_name'] ?? 'Unknown').'</td>'
                        .'<td>'.htmlspecialchars($p['amount'] ?? 0).'</td>'
                        .'<td>'.htmlspecialchars($p['payment_date'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($p['payment_type'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($p['payment_method'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($p['transaction_code'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($p['phone_number'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($p['status'] ?? 'completed').'</td>'
                        .'<td>'.htmlspecialchars($p['notes'] ?? '').'</td>'
                        .'</tr>';
                }
                $html .= '</table>';
                $dompdf = new \Dompdf\Dompdf();
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="payments.pdf"');
                echo $dompdf->output();
                exit;
            }

            http_response_code(400);
            echo 'Unsupported format';
        } catch (\Exception $e) {
            http_response_code(500);
            echo 'Export error';
        }
        exit;
    }

    public function template()
    {
        $templateFile = __DIR__ . '/../../public/templates/payments_template.csv';
        
        if (file_exists($templateFile)) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="payments_template.csv"');
            readfile($templateFile);
            exit;
        }
        
        // Fallback to empty template if file doesn't exist
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="payments_template.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['tenant_email','amount','payment_date','payment_type','payment_method','reference_number','status','notes']);
        fclose($out);
        exit;
    }

    public function import()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
                http_response_code(400);
                echo 'No file uploaded';
                return;
            }
            $tmp = $_FILES['file']['tmp_name'];
            if (!is_uploaded_file($tmp)) {
                throw new \Exception('Invalid upload');
            }
            $paymentModel = new Payment();
            $tenantModel = new Tenant();
            $userId = $_SESSION['user_id'] ?? null;
            $handle = fopen($tmp, 'r');
            if ($handle === false) throw new \Exception('Cannot open uploaded file');
            $header = fgetcsv($handle);
            $created = 0;
            $skipped = 0;
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, $row);
                if (empty($data['tenant_email']) || empty($data['amount'])) continue;
                $tenant = $tenantModel->findByEmail($data['tenant_email'], $userId) ?? $tenantModel->findByEmail($data['tenant_email']);
                if (!$tenant) continue;
                $lease = $paymentModel->getActiveLease($tenant['id'], $userId);
                if (!$lease) continue;
                $paymentData = [
                    'lease_id' => $lease['id'],
                    'amount' => (float)$data['amount'],
                    'payment_date' => $data['payment_date'] ?? date('Y-m-d'),
                    'payment_type' => $data['payment_type'] ?? 'rent',
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'reference_number' => $data['reference_number'] ?? null,
                    'status' => $data['status'] ?? 'completed',
                    'notes' => $data['notes'] ?? ''
                ];
                $id = $paymentModel->createRentPayment($paymentData);
                if ($id) $created++;
            }
            fclose($handle);
            $_SESSION['flash_message'] = "Imported {$created} payments";
            // Activity log: payment.import summary
            try {
                $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                $this->activityLog->add(
                    $_SESSION['user_id'] ?? null,
                    $_SESSION['user_role'] ?? null,
                    'payment.import',
                    'payment',
                    null,
                    null,
                    json_encode(['created' => (int)$created, 'skipped' => (int)$skipped]),
                    $ip,
                    $agent
                );
            } catch (\Exception $ex) { error_log('payment.import log failed: ' . $ex->getMessage()); }
            $_SESSION['flash_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = 'Import failed: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
        // Add timestamp to force page reload
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        redirect('/payments?t=' . time());
    }
    public function index()
    {
        $paymentModel = new Payment();
        $role = strtolower((string)($_SESSION['user_role'] ?? 'guest'));

        if ($role === 'realtor') {
            $clientModel = new RealtorClient();
            $listingModel = new RealtorListing();
            $contractModel = new RealtorContract();

            $payments = $paymentModel->getPaymentsForRealtor($this->userId);
            $clients = $clientModel->getAll($this->userId);
            $listings = $listingModel->getAll($this->userId);
            $contracts = $contractModel->getAllWithDetails($this->userId);
            $tenants = []; // Set to empty array for Realtor role
            $pendingPaymentsCount = 0; // Set to 0 for Realtor role

            require 'views/payments/index.php';
            return;
        }

        $tenantModel = new Tenant();
        $utilityModel = new \App\Models\Utility();
        $utilityReadingModel = new \App\Models\UtilityReading();
        
        // Get payments with tenant information (with role-based access)
        $payments = $paymentModel->getPaymentsWithTenantInfo($this->userId);
        
        // Get tenants with active leases (with role-based access)
        $tenants = $tenantModel->getActiveLeases($this->userId);

        // Attach fully-paid rent months per lease for UI validation
        try {
            foreach ($tenants as &$t) {
                $leaseId = (int)($t['lease_id'] ?? 0);
                $t['paid_rent_months'] = $leaseId > 0 ? $paymentModel->getFullyPaidRentMonthsByLease($leaseId) : [];
            }
            unset($t);
        } catch (\Throwable $e) {
            // ignore
        }
        
        // Enhance tenant data with payment and utility information
        foreach ($tenants as &$tenant) {
            $leaseId = $tenant['lease_id'] ?? null;
            $dueAmount = 0;
            $lease = null;
            try {
                $lease = $paymentModel->getActiveLease((int)($tenant['id'] ?? 0), $this->userId);
            } catch (\Exception $e) {
                $lease = null;
            }

            $resolvedLeaseId = (int)($lease['id'] ?? ($leaseId ?? 0));
            $resolvedUnitId = (int)($lease['unit_id'] ?? ($tenant['unit_id'] ?? 0));

            if ($resolvedLeaseId) {
                $dueAmount = $paymentModel->getDueAmountForLease($resolvedLeaseId);
            }
            $tenant['due_amount'] = $dueAmount;

            // Get utilities with balances due for this lease/unit (for manual utility payments dropdown)
            $tenant['utility_readings'] = [];
            try {
                if ($resolvedUnitId > 0 && $resolvedLeaseId > 0) {
                    $utilities = $utilityModel->getUtilitiesByUnit($resolvedUnitId);
                    $paidStmt = $paymentModel->getDb()->prepare(
                        "SELECT COALESCE(SUM(amount),0) AS s\n"
                        . "FROM payments\n"
                        . "WHERE lease_id = ?\n"
                        . "  AND utility_id = ?\n"
                        . "  AND payment_type = 'utility'\n"
                        . "  AND status IN ('completed','verified')"
                    );

                    $utilityReadings = [];
                    foreach (($utilities ?: []) as $u) {
                        $cost = 0.0;
                        if (!empty($u['is_metered'])) {
                            $cost = (float)($u['latest_cost'] ?? 0);
                        } else {
                            $cost = (float)($u['flat_rate'] ?? 0);
                        }

                        $paid = 0.0;
                        try {
                            $paidStmt->execute([$resolvedLeaseId, (int)$u['id']]);
                            $paid = (float)($paidStmt->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);
                        } catch (\Exception $e) {
                            $paid = 0.0;
                        }

                        $net = max($cost - $paid, 0.0);
                        if ($net <= 0.009) {
                            continue;
                        }

                        $utilityReadings[] = [
                            'utility_id' => (int)($u['id'] ?? 0),
                            'utility_type' => $u['utility_type'] ?? '',
                            'current_reading' => $u['latest_reading'] ?? null,
                            'current_reading_date' => $u['latest_reading_date'] ?? null,
                            'previous_reading' => $u['previous_reading'] ?? null,
                            'previous_reading_date' => $u['previous_reading_date'] ?? null,
                            'cost' => $net,
                            'rate' => (float)($u['flat_rate'] ?? 0),
                            'is_metered' => (int)($u['is_metered'] ?? 0)
                        ];
                    }
                    $tenant['utility_readings'] = $utilityReadings;
                }
            } catch (\Exception $e) {
                $tenant['utility_readings'] = [];
            }
        }
        unset($tenant);
        
        // Get count of tenants with overdue payments
        $delinquentTenants = $paymentModel->getDelinquentTenants();
        $pendingPaymentsCount = is_array($delinquentTenants) ? count($delinquentTenants) : 0;
        
        require 'views/payments/index.php';
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $paymentModel = new Payment();

                $role = strtolower((string)($_SESSION['user_role'] ?? 'guest'));
                if ($role === 'realtor') {
                    try {
                        if (!verify_csrf_token()) {
                            $_SESSION['flash_message'] = 'Invalid security token';
                            $_SESSION['flash_type'] = 'danger';
                            header('Location: ' . BASE_URL . '/payments');
                            exit;
                        }

                        $contractId = (int)($_POST['realtor_contract_id'] ?? 0);
                        if ($contractId <= 0) {
                            $_SESSION['flash_message'] = 'Contract is required';
                            $_SESSION['flash_type'] = 'danger';
                            header('Location: ' . BASE_URL . '/payments');
                            exit;
                        }

                        $clientId = 0;
                        $listingId = 0;
                        $paymentType = 'mortgage';
                        $amount = (float)($_POST['amount'] ?? 0);
                        $paymentDate = trim((string)($_POST['payment_date'] ?? date('Y-m-d')));
                        $paymentMethod = trim((string)($_POST['payment_method'] ?? 'cash'));
                        $reference = trim((string)($_POST['reference_number'] ?? ''));
                        $status = trim((string)($_POST['status'] ?? 'completed'));
                        $notes = trim((string)($_POST['notes'] ?? ''));
                        $appliesToMonth = trim((string)($_POST['applies_to_month'] ?? ''));

                        $contractTermsType = null;
                        $contractModel = new RealtorContract();
                        $contract = $contractModel->getByIdWithAccess($contractId, $this->userId);
                        if (!$contract) {
                            $_SESSION['flash_message'] = 'Invalid contract selected';
                            $_SESSION['flash_type'] = 'danger';
                            header('Location: ' . BASE_URL . '/payments');
                            exit;
                        }
                        $clientId = (int)($contract['realtor_client_id'] ?? 0);
                        $listingId = (int)($contract['realtor_listing_id'] ?? 0);
                        $contractTermsType = (string)($contract['terms_type'] ?? 'one_time');

                        // Normalize payment type based on contract terms.
                        if ($contractTermsType === 'monthly') {
                            $paymentType = 'mortgage_monthly';
                        } else {
                            $paymentType = 'mortgage';
                            $appliesToMonth = '';
                        }

                        if ($clientId <= 0 || $listingId <= 0) {
                            $_SESSION['flash_message'] = 'Client and Listing are required';
                            $_SESSION['flash_type'] = 'danger';
                            header('Location: ' . BASE_URL . '/payments');
                            exit;
                        }

                        // For monthly payments like mortgage, prevent double-entry for the same month
                        if ($appliesToMonth !== null && in_array($paymentType, ['mortgage', 'mortgage_monthly'], true)) {
                            if ($paymentModel->realtorMonthAlreadyPaid($this->userId, $clientId, $listingId, $appliesToMonth, $paymentType)) {
                                $_SESSION['flash_message'] = 'Selected month is already marked as paid for this mortgage. Please select another month.';
                                $_SESSION['flash_type'] = 'warning';
                                header('Location: ' . BASE_URL . '/payments');
                                exit;
                            }
                        }

                        $paymentId = $paymentModel->createRealtorPayment([
                            'realtor_user_id' => (int)$this->userId,
                            'realtor_client_id' => (int)$clientId,
                            'realtor_listing_id' => (int)$listingId,
                            'realtor_contract_id' => ($contractId > 0) ? (int)$contractId : null,
                            'amount' => (float)$amount,
                            'payment_date' => (string)$paymentDate,
                            'applies_to_month' => ($appliesToMonth !== '') ? (string)$appliesToMonth . '-01' : null,
                            'payment_type' => (string)$paymentType,
                            'payment_method' => (string)$paymentMethod,
                            'reference_number' => ($reference !== '') ? (string)$reference : null,
                            'status' => (string)$status,
                            'notes' => (string)$notes,
                        ]);

                        // Save M-Pesa transaction details if applicable
                        if ($paymentMethod === 'mpesa_manual' && !empty($_POST['mpesa_phone']) && !empty($_POST['mpesa_transaction_code'])) {
                            $this->saveMpesaTransaction($paymentId, $_POST);
                        }

                        if (!empty($_FILES['payment_attachments']['name'][0])) {
                            $this->handlePaymentAttachments($paymentId);
                        }

                        $_SESSION['flash_message'] = 'Payment added successfully!';
                        $_SESSION['flash_type'] = 'success';
                        header('Location: ' . BASE_URL . '/payments');
                        exit;
                    } catch (\Exception $e) {
                        $_SESSION['flash_message'] = 'Payment failed: ' . $e->getMessage();
                        $_SESSION['flash_type'] = 'danger';
                        header('Location: ' . BASE_URL . '/payments');
                        exit;
                    }
                }

                $appliesToMonthRaw = trim((string)($_POST['applies_to_month'] ?? ''));
                $appliesToMonth = null;
                if ($appliesToMonthRaw !== '') {
                    // Accept YYYY-MM or YYYY-MM-DD; store as YYYY-MM-01
                    if (preg_match('/^\d{4}-\d{2}$/', $appliesToMonthRaw)) {
                        $appliesToMonth = $appliesToMonthRaw . '-01';
                    } else if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $appliesToMonthRaw)) {
                        $appliesToMonth = substr($appliesToMonthRaw, 0, 7) . '-01';
                    }
                }
                
                // Get the lease_id from the tenant's active lease (with role-based access)
                $lease = $paymentModel->getActiveLease($_POST['tenant_id'], $this->userId);
                
                if (!$lease) {
                    $_SESSION['flash_message'] = 'Error: No active lease found for this tenant.';
                    $_SESSION['flash_type'] = 'danger';
                    header('Location: ' . BASE_URL . '/payments');
                    exit;
                }
                
                // Verify user has access to this lease
                if (!$paymentModel->userHasAccessToLease($lease['id'], $this->userId)) {
                    $_SESSION['flash_message'] = 'Error: You do not have permission to add payments for this lease.';
                    $_SESSION['flash_type'] = 'danger';
                    header('Location: ' . BASE_URL . '/payments');
                    exit;
                }

                $paymentTypes = $_POST['payment_types'] ?? [];
                $successMessages = [];

                // Prevent recording rent against a month already fully paid
                if (in_array('rent', $paymentTypes, true) && $appliesToMonth !== null) {
                    if ($paymentModel->isRentMonthFullyPaidByLease((int)$lease['id'], $appliesToMonth)) {
                        $_SESSION['flash_message'] = 'Selected month is already fully paid. Please select another month.';
                        $_SESSION['flash_type'] = 'warning';
                        header('Location: ' . BASE_URL . '/payments');
                        exit;
                    }
                }

                // Process rent payment if selected
                if (in_array('rent', $paymentTypes) && !empty($_POST['rent_amount'])) {
                    $rentData = [
                        'lease_id' => $lease['id'],
                        'amount' => $_POST['rent_amount'],
                        'payment_date' => $_POST['payment_date'],
                        'applies_to_month' => $appliesToMonth,
                        'payment_type' => 'rent',
                        'payment_method' => $_POST['payment_method'],
                        'reference_number' => $_POST['reference_number'] ?? null,
                        'notes' => 'Rent payment: ' . ($_POST['notes'] ?? '')
                        ,
                        'status' => $_POST['status'] ?? 'completed'
                    ];
                    
                    // Add M-Pesa specific data if it's an M-Pesa payment
                    if ($_POST['payment_method'] === 'mpesa_manual') {
                        $rentData['mpesa_phone'] = $_POST['mpesa_phone'] ?? '';
                        $rentData['mpesa_transaction_code'] = $_POST['mpesa_transaction_code'] ?? '';
                        $rentData['mpesa_verification_status'] = $_POST['mpesa_verification_status'] ?? 'pending';
                    }
                    
                    $paymentId = $paymentModel->createRentPayment($rentData);
                    // Auto-create monthly rent invoice (idempotent)
                    try {
                        $invModel = new \App\Models\Invoice();
                        $invModel->ensureMonthlyRentInvoice((int)$lease['tenant_id'], $rentData['payment_date'], (float)$lease['rent_amount'], (int)($_SESSION['user_id'] ?? 0), 'AUTO');
                    } catch (\Exception $e) { error_log('Auto-invoice (admin rent) failed: ' . $e->getMessage()); }
                    
                    // Handle file uploads for rent payment
                    if (!empty($_FILES['payment_attachments']['name'][0])) {
                        $this->handlePaymentAttachments($paymentId);
                    }
                    
                    // Save M-Pesa transaction details if applicable
                    if ($_POST['payment_method'] === 'mpesa_manual' && !empty($_POST['mpesa_phone']) && !empty($_POST['mpesa_transaction_code'])) {
                        $this->saveMpesaTransaction($paymentId, $_POST);
                    }
                    
                    $successMessages[] = 'Rent payment added successfully!';

                    // Activity log: payment.create (rent)
                    try {
                        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                        $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                        $p = $paymentModel->getById($paymentId, $this->userId);
                        $this->activityLog->add(
                            $_SESSION['user_id'] ?? null,
                            $_SESSION['user_role'] ?? null,
                            'payment.create',
                            'payment',
                            (int)$paymentId,
                            isset($p['property_id']) ? (int)$p['property_id'] : null,
                            json_encode([
                                'amount' => (float)$rentData['amount'],
                                'payment_date' => $rentData['payment_date'],
                                'payment_type' => $rentData['payment_type'],
                                'payment_method' => $rentData['payment_method']
                            ]),
                            $ip,
                            $agent
                        );
                    } catch (\Exception $ex) { error_log('payment.create log failed: ' . $ex->getMessage()); }

                    // Send email notifications for rent payment
                    try {
                        $settingModel = new \App\Models\Setting();
                        $settings = $settingModel->getAllAsAssoc();
                        $userModel = new \App\Models\User();

                        // Get payment details with tenant and property info
                        $paymentDetails = $paymentModel->getById($paymentId);

                        if ($paymentDetails) {
                            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                            $mail->isSMTP();
                            $mail->Host = $settings['smtp_host'] ?? '';
                            $mail->Port = $settings['smtp_port'] ?? 587;
                            $mail->SMTPAuth = true;
                            $mail->Username = $settings['smtp_user'] ?? '';
                            $mail->Password = $settings['smtp_pass'] ?? '';
                            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->setFrom($settings['smtp_user'] ?? '', $settings['site_name'] ?? 'RentSmart');
                            $mail->isHTML(true);
                            $siteUrl = rtrim($settings['site_url'] ?? 'https://rentsmart.co.ke', '/');
                            $logoUrl = isset($settings['site_logo']) && $settings['site_logo'] ? ($siteUrl . '/public/assets/images/' . $settings['site_logo']) : '';
                            $footer = '<div style="margin-top:30px;font-size:12px;color:#888;text-align:center;">Powered by <a href="https://timestentechnologies.co.ke" target="_blank" style="color:#888;text-decoration:none;">Timesten Technologies</a></div>';

                            // Email to tenant
                            if ($paymentDetails['tenant_email']) {
                                $mail->clearAddresses();
                                $mail->addAddress($paymentDetails['tenant_email'], $paymentDetails['tenant_name']);
                                $mail->Subject = 'Payment Confirmation - ' . $paymentDetails['property_name'];
                                $mail->Body =
                                    '<div style="max-width:500px;margin:auto;border:1px solid #eee;padding:24px;font-family:sans-serif;">'
                                    . ($logoUrl ? '<div style="text-align:center;margin-bottom:24px;"><img src="' . $logoUrl . '" alt="Logo" style="max-width:180px;max-height:80px;"></div>' : '') .
                                    '<p>Dear ' . htmlspecialchars($paymentDetails['tenant_name']) . ',</p>' .
                                    '<p>Your payment has been received and recorded successfully.</p>' .
                                    '<p>Details:</p>' .
                                    '<ul>' .
                                        '<li>Property: ' . htmlspecialchars($paymentDetails['property_name']) . '</li>' .
                                        '<li>Unit: ' . htmlspecialchars($paymentDetails['unit_number']) . '</li>' .
                                        '<li>Amount: Ksh ' . htmlspecialchars($rentData['amount']) . '</li>' .
                                        '<li>Payment Date: ' . htmlspecialchars($rentData['payment_date']) . '</li>' .
                                        '<li>Payment Method: ' . htmlspecialchars($rentData['payment_method']) . '</li>' .
                                    '</ul>' .
                                    '<p>Thank you for your payment.</p>' .
                                    $footer .
                                    '</div>';
                                $mail->send();
                            }

                            // Email to property owner
                            if ($paymentDetails['property_owner_id']) {
                                $owner = $userModel->find($paymentDetails['property_owner_id']);
                                if ($owner && $owner['email']) {
                                    $mail->clearAddresses();
                                    $mail->addAddress($owner['email'], $owner['name']);
                                    $mail->Subject = 'New Payment Received - ' . $paymentDetails['property_name'];
                                    $mail->Body =
                                        '<div style="max-width:500px;margin:auto;border:1px solid #eee;padding:24px;font-family:sans-serif;">'
                                        . ($logoUrl ? '<div style="text-align:center;margin-bottom:24px;"><img src="' . $logoUrl . '" alt="Logo" style="max-width:180px;max-height:80px;"></div>' : '') .
                                        '<p>Dear ' . htmlspecialchars($owner['name']) . ',</p>' .
                                        '<p>A new payment has been received for your property.</p>' .
                                        '<p>Details:</p>' .
                                        '<ul>' .
                                            '<li>Property: ' . htmlspecialchars($paymentDetails['property_name']) . '</li>' .
                                            '<li>Unit: ' . htmlspecialchars($paymentDetails['unit_number']) . '</li>' .
                                            '<li>Tenant: ' . htmlspecialchars($paymentDetails['tenant_name']) . '</li>' .
                                            '<li>Amount: Ksh ' . htmlspecialchars($rentData['amount']) . '</li>' .
                                            '<li>Payment Date: ' . htmlspecialchars($rentData['payment_date']) . '</li>' .
                                            '<li>Payment Method: ' . htmlspecialchars($rentData['payment_method']) . '</li>' .
                                        '</ul>' .
                                        $footer .
                                        '</div>';
                                    $mail->send();
                                }
                            }

                            // Email to property manager
                            if ($paymentDetails['property_manager_id']) {
                                $manager = $userModel->find($paymentDetails['property_manager_id']);
                                if ($manager && $manager['email']) {
                                    $mail->clearAddresses();
                                    $mail->addAddress($manager['email'], $manager['name']);
                                    $mail->Subject = 'New Payment Received - ' . $paymentDetails['property_name'];
                                    $mail->Body =
                                        '<div style="max-width:500px;margin:auto;border:1px solid #eee;padding:24px;font-family:sans-serif;">'
                                        . ($logoUrl ? '<div style="text-align:center;margin-bottom:24px;"><img src="' . $logoUrl . '" alt="Logo" style="max-width:180px;max-height:80px;"></div>' : '') .
                                        '<p>Dear ' . htmlspecialchars($manager['name']) . ',</p>' .
                                        '<p>A new payment has been received for the property you manage.</p>' .
                                        '<p>Details:</p>' .
                                        '<ul>' .
                                            '<li>Property: ' . htmlspecialchars($paymentDetails['property_name']) . '</li>' .
                                            '<li>Unit: ' . htmlspecialchars($paymentDetails['unit_number']) . '</li>' .
                                            '<li>Tenant: ' . htmlspecialchars($paymentDetails['tenant_name']) . '</li>' .
                                            '<li>Amount: Ksh ' . htmlspecialchars($rentData['amount']) . '</li>' .
                                            '<li>Payment Date: ' . htmlspecialchars($rentData['payment_date']) . '</li>' .
                                            '<li>Payment Method: ' . htmlspecialchars($rentData['payment_method']) . '</li>' .
                                        '</ul>' .
                                        $footer .
                                        '</div>';
                                    $mail->send();
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        error_log('Failed to send payment notification emails: ' . $e->getMessage());
                    }
                }

                // Process utility payment if selected
                if (in_array('utility', $paymentTypes) && !empty($_POST['utility_amount'])) {
                    $selectedUtilityIds = $_POST['utility_ids'] ?? [];
                    if (!is_array($selectedUtilityIds)) {
                        $selectedUtilityIds = [$selectedUtilityIds];
                    }
                    $selectedUtilityIds = array_values(array_filter(array_map('intval', $selectedUtilityIds)));

                    $utilityAmountsById = $_POST['utility_amounts'] ?? [];
                    if (!is_array($utilityAmountsById)) {
                        $utilityAmountsById = [];
                    }

                    // Backward-compat: if only one selected but no utility_amounts provided, use utility_amount
                    if (empty($utilityAmountsById) && count($selectedUtilityIds) === 1) {
                        $utilityAmountsById[(int)$selectedUtilityIds[0]] = (float)($_POST['utility_amount'] ?? 0);
                    }

                    if (!empty($selectedUtilityIds)) {
                        $firstId = (int)$selectedUtilityIds[0];
                        $createdAny = false;

                        foreach ($selectedUtilityIds as $utilityId) {
                            $amount = isset($utilityAmountsById[$utilityId]) ? (float)$utilityAmountsById[$utilityId] : 0.0;
                            if ($amount <= 0.0) {
                                continue;
                            }

                            $utilityData = [
                                'lease_id' => $lease['id'],
                                'utility_id' => $utilityId,
                                'amount' => $amount,
                                'payment_date' => $_POST['payment_date'],
                                'applies_to_month' => $appliesToMonth,
                                'payment_type' => 'utility',
                                'payment_method' => $_POST['payment_method'],
                                'reference_number' => $_POST['reference_number'] ?? null,
                                'notes' => 'Utility payment: ' . ($_POST['notes'] ?? ''),
                                'status' => $_POST['status'] ?? 'completed'
                            ];

                            $utilityPaymentId = $paymentModel->createUtilityPayment($utilityData);
                            $createdAny = true;

                            // Activity log: payment.create (utility)
                            try {
                                $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                                $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                                $p = $paymentModel->getById($utilityPaymentId, $this->userId);
                                $this->activityLog->add(
                                    $_SESSION['user_id'] ?? null,
                                    $_SESSION['user_role'] ?? null,
                                    'payment.create',
                                    'payment',
                                    (int)$utilityPaymentId,
                                    isset($p['property_id']) ? (int)$p['property_id'] : null,
                                    json_encode([
                                        'amount' => (float)$amount,
                                        'payment_date' => $_POST['payment_date'],
                                        'payment_type' => 'utility',
                                        'payment_method' => $_POST['payment_method'],
                                        'utility_id' => (int)$utilityId
                                    ]),
                                    $ip,
                                    $agent
                                );
                            } catch (\Exception $ex) { error_log('payment.create log failed: ' . $ex->getMessage()); }

                            // Attachments only once to avoid duplicates
                            if ($utilityId === $firstId && !empty($_FILES['payment_attachments']['name'][0])) {
                                $this->handlePaymentAttachments($utilityPaymentId);
                            }
                        }

                        if ($createdAny) {
                            $successMessages[] = count($selectedUtilityIds) > 1 ? 'Utility payments added successfully!' : 'Utility payment added successfully!';

                            // Ensure invoice exists and update statuses for this month (utility payments should affect invoices)
                            try {
                                $invModel = new \App\Models\Invoice();
                                $invModel->ensureMonthlyRentInvoice((int)$lease['tenant_id'], $_POST['payment_date'], (float)($lease['rent_amount'] ?? 0), (int)($_SESSION['user_id'] ?? 0), 'AUTO');
                                $invModel->updateStatusForTenantMonth((int)$lease['tenant_id'], $_POST['payment_date']);
                            } catch (\Exception $e) {
                                error_log('Auto-invoice (admin utility) failed: ' . $e->getMessage());
                            }
                        }
                    }
                }

                // Process maintenance payment if selected
                if (in_array('maintenance', $paymentTypes) && !empty($_POST['maintenance_amount'])) {
                    $maintData = [
                        'lease_id' => $lease['id'],
                        'amount' => $_POST['maintenance_amount'],
                        'payment_date' => $_POST['payment_date'],
                        'applies_to_month' => $appliesToMonth,
                        'payment_type' => 'other',
                        'payment_method' => $_POST['payment_method'],
                        'reference_number' => $_POST['reference_number'] ?? null,
                        'notes' => 'Maintenance payment: ' . ($_POST['notes'] ?? '')
                        ,
                        'status' => $_POST['status'] ?? 'completed'
                    ];

                    if ($_POST['payment_method'] === 'mpesa_manual') {
                        $maintData['mpesa_phone'] = $_POST['mpesa_phone'] ?? '';
                        $maintData['mpesa_transaction_code'] = $_POST['mpesa_transaction_code'] ?? '';
                        $maintData['mpesa_verification_status'] = $_POST['mpesa_verification_status'] ?? 'pending';
                    }

                    $maintenancePaymentId = $paymentModel->createRentPayment($maintData);

                    // Attachments
                    if (!empty($_FILES['payment_attachments']['name'][0])) {
                        $this->handlePaymentAttachments($maintenancePaymentId);
                    }

                    // Save M-Pesa transaction details if applicable
                    if ($_POST['payment_method'] === 'mpesa_manual' && !empty($_POST['mpesa_phone']) && !empty($_POST['mpesa_transaction_code'])) {
                        $this->saveMpesaTransaction($maintenancePaymentId, $_POST);
                    }

                    $successMessages[] = 'Maintenance payment added successfully!';

                    // Activity log: payment.create (maintenance)
                    try {
                        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                        $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                        $p = $paymentModel->getById($maintenancePaymentId, $this->userId);
                        $this->activityLog->add(
                            $_SESSION['user_id'] ?? null,
                            $_SESSION['user_role'] ?? null,
                            'payment.create',
                            'payment',
                            (int)$maintenancePaymentId,
                            isset($p['property_id']) ? (int)$p['property_id'] : null,
                            json_encode([
                                'amount' => (float)$maintData['amount'],
                                'payment_date' => $maintData['payment_date'],
                                'payment_type' => 'maintenance',
                                'payment_method' => $maintData['payment_method']
                            ]),
                            $ip,
                            $agent
                        );
                    } catch (\Exception $ex) { error_log('payment.create log failed: ' . $ex->getMessage()); }

                    // Ensure invoice exists and update statuses for this month (maintenance payments should affect invoices)
                    try {
                        $invModel = new \App\Models\Invoice();
                        $invModel->ensureMonthlyRentInvoice((int)$lease['tenant_id'], $_POST['payment_date'], (float)($lease['rent_amount'] ?? 0), (int)($_SESSION['user_id'] ?? 0), 'AUTO');
                        $invModel->updateStatusForTenantMonth((int)$lease['tenant_id'], $_POST['payment_date']);
                    } catch (\Exception $e) {
                        error_log('Auto-invoice (admin maintenance) failed: ' . $e->getMessage());
                    }
                }

                if (!empty($successMessages)) {
                    $_SESSION['flash_message'] = implode(' ', $successMessages);
                    $_SESSION['flash_type'] = 'success';
                } else {
                    $_SESSION['flash_message'] = 'No payment amounts provided.';
                    $_SESSION['flash_type'] = 'warning';
                }
            } catch (\Exception $e) {
                error_log('Payment creation error: ' . $e->getMessage());
                $_SESSION['flash_message'] = 'Error creating payment. Please try again.';
                $_SESSION['flash_type'] = 'danger';
            }
            
            header('Location: ' . BASE_URL . '/payments');
            exit;
        }
    }

    public function receipt($id)
    {
        require_once __DIR__ . '/../../vendor/dompdf/dompdf/src/Dompdf.php';
        $paymentModel = new \App\Models\Payment();
        $userId = $_SESSION['user_id'] ?? null;
        $payment = $paymentModel->getById($id, $userId);
        if (!$payment) {
            http_response_code(404);
            echo 'Payment not found.';
            exit;
        }
        // Company settings and logo (allow per-user branding)
        $settingsModel = new \App\Models\Setting();
        $settings = $settingsModel->getAllAsAssoc();

        $siteName = $settings['site_name'] ?? 'RentSmart';
        $logoFilename = $settings['site_logo'] ?? 'site_logo_1751627446.png';

        $role = strtolower((string)($_SESSION['user_role'] ?? ''));
        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid > 0 && in_array($role, ['manager', 'agent', 'landlord'], true)) {
            $companyNameKey = 'company_name_user_' . $uid;
            $companyLogoKey = 'company_logo_user_' . $uid;
            $companyName = trim((string)($settings[$companyNameKey] ?? ''));
            $companyLogo = trim((string)($settings[$companyLogoKey] ?? ''));
            if ($companyName !== '') {
                $siteName = $companyName;
            }
            if ($companyLogo !== '') {
                $logoFilename = $companyLogo;
            }
        }

        // Embed logo as base64 data URI for dompdf
        $logoPath = __DIR__ . '/../../public/assets/images/' . $logoFilename;
        $logoDataUri = null;
        if (file_exists($logoPath)) {
            $imageData = file_get_contents($logoPath);
            $base64 = base64_encode($imageData);
            $ext = strtolower((string)pathinfo($logoPath, PATHINFO_EXTENSION));
            $mime = 'image/png';
            if ($ext === 'jpg' || $ext === 'jpeg') { $mime = 'image/jpeg'; }
            else if ($ext === 'gif') { $mime = 'image/gif'; }
            else if ($ext === 'webp') { $mime = 'image/webp'; }
            else if ($ext === 'svg') { $mime = 'image/svg+xml'; }
            $logoDataUri = 'data:' . $mime . ';base64,' . $base64;
        }
        ob_start();
        include __DIR__ . '/../../views/payments/receipt_pdf.php';
        $html = ob_get_clean();
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $filename = 'payment_receipt_' . $payment['id'] . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $dompdf->output();
        exit;
    }

    public function get($id)
    {
        try {
            $paymentModel = new Payment();
            $userId = $_SESSION['user_id'] ?? null;
            $payment = $paymentModel->getById($id, $userId);
            
            if ($payment) {
                // Add success field and include all payment data
                $response = $payment;
                $response['success'] = true;
                
                header('Content-Type: application/json');
                echo json_encode($response);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Payment not found']);
            }
        } catch (Exception $e) {
            error_log("Error in PaymentsController::get: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Internal server error']);
        }
        exit;
    }

    public function update($id)
    {
        $paymentModel = new Payment();
        $userId = $_SESSION['user_id'] ?? null;
        $payment = $paymentModel->getById($id, $userId);
        if (!$payment) {
            echo json_encode(['success' => false, 'message' => 'Payment not found']);
            exit;
        }
        $data = [
            'amount' => $_POST['amount'],
            'payment_date' => $_POST['payment_date'],
            'payment_method' => $_POST['payment_method'],
            'reference_number' => $_POST['reference_number'] ?? null,
            'status' => $_POST['status'] ?? 'completed',
            'notes' => $_POST['notes'] ?? null
        ];
        $success = $paymentModel->updatePayment($id, $data);
        
        // Handle file uploads
        $uploadErrors = [];
        if ($success && !empty($_FILES['payment_attachments']['name'][0])) {
            try {
                $this->handlePaymentAttachments($id);
            } catch (Exception $e) {
                $uploadErrors[] = 'File upload error: ' . $e->getMessage();
            }
        }
        
        // If this is an M-Pesa payment and status is being updated, also update M-Pesa verification status
        if ($success && ($data['payment_method'] === 'mpesa_manual' || $data['payment_method'] === 'mpesa_stk')) {
            $this->updateMpesaVerificationStatus($id, $data);
        }
        
        $message = $success ? 'Payment updated!' : 'Failed to update payment.';
        if (!empty($uploadErrors)) {
            $message .= ' File upload issues: ' . implode(', ', $uploadErrors);
        }
        // Activity log: payment.update
        try {
            if ($success) {
                $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                $pPropId = $payment['property_id'] ?? null;
                $this->activityLog->add(
                    $_SESSION['user_id'] ?? null,
                    $_SESSION['user_role'] ?? null,
                    'payment.update',
                    'payment',
                    (int)$id,
                    $pPropId ? (int)$pPropId : null,
                    json_encode($data),
                    $ip,
                    $agent
                );
            }
        } catch (\Exception $ex) { error_log('payment.update log failed: ' . $ex->getMessage()); }
        
        echo json_encode(['success' => $success, 'message' => $message, 'upload_errors' => $uploadErrors]);
        exit;
    }

    public function getMpesaTransaction($id)
    {
        try {
            $paymentModel = new Payment();
            $userId = $_SESSION['user_id'] ?? null;
            
            // Check if payment exists and user has access
            $payment = $paymentModel->getById($id, $userId);
            if (!$payment) {
                http_response_code(404);
                echo json_encode(['error' => 'Payment not found']);
                exit;
            }

            // Get M-Pesa transaction details
            $sql = "SELECT * FROM manual_mpesa_payments WHERE payment_id = ?";
            $stmt = $paymentModel->getDb()->prepare($sql);
            $stmt->execute([$id]);
            $mpesaData = $stmt->fetch();
            
            if ($mpesaData) {
                echo json_encode($mpesaData);
            } else {
                echo json_encode(['error' => 'No M-Pesa transaction found']);
            }
        } catch (\Exception $e) {
            error_log('M-Pesa transaction fetch error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
        exit;
    }

    /**
     * Update M-Pesa verification status
     */
    private function updateMpesaVerificationStatus($paymentId, $data)
    {
        try {
            $paymentModel = new Payment();
            $db = $paymentModel->getDb();
            
            // Determine verification status based on payment status
            $verificationStatus = 'pending';
            if ($data['status'] === 'completed') {
                $verificationStatus = 'verified';
            } elseif ($data['status'] === 'failed') {
                $verificationStatus = 'rejected';
            }
            
            // Update M-Pesa verification status
            $sql = "UPDATE manual_mpesa_payments SET 
                        verification_status = :verification_status,
                        verified_at = CASE WHEN :verification_status = 'verified' THEN NOW() ELSE verified_at END
                    WHERE payment_id = :payment_id";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'verification_status' => $verificationStatus,
                'payment_id' => $paymentId
            ]);
            
        } catch (\Exception $e) {
            error_log('M-Pesa verification status update error: ' . $e->getMessage());
        }
    }

    /**
     * Delete payment
     */
    public function delete($id)
    {
        try {
            $paymentModel = new Payment();
            $userId = $_SESSION['user_id'] ?? null;
            
            // Check if payment exists and user has access
            $payment = $paymentModel->getById($id, $userId);
            if (!$payment) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Payment not found']);
                exit;
            }

            // Delete the payment
            $success = $paymentModel->delete($id);
            
            if ($success) {
                // Activity log: payment.delete
                try {
                    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                    $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                    $pPropId = $payment['property_id'] ?? null;
                    $this->activityLog->add(
                        $_SESSION['user_id'] ?? null,
                        $_SESSION['user_role'] ?? null,
                        'payment.delete',
                        'payment',
                        (int)$id,
                        $pPropId ? (int)$pPropId : null,
                        null,
                        $ip,
                        $agent
                    );
                } catch (\Exception $ex) { error_log('payment.delete log failed: ' . $ex->getMessage()); }
                echo json_encode(['success' => true, 'message' => 'Payment deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete payment']);
            }
        } catch (\Exception $e) {
            error_log('Payment deletion error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
        exit;
    }

    /**
     * Handle payment attachments upload
     */
    private function handlePaymentAttachments($paymentId)
    {
        try {
            $fileUploadHelper = new FileUploadHelper();
            
            $result = $fileUploadHelper->uploadFiles(
                $_FILES['payment_attachments'], 
                'payment', 
                $paymentId, 
                'attachment', 
                $_SESSION['user_id']
            );
            
            if (!empty($result['errors'])) {
                error_log('Payment attachment upload errors: ' . implode(', ', $result['errors']));
            }
            
            // Update payment with file references
            $fileUploadHelper->updateEntityFiles('payment', $paymentId);
            
        } catch (\Exception $e) {
            error_log('Error uploading payment attachments: ' . $e->getMessage());
        }
    }

    /**
     * Save M-Pesa transaction details
     */
    private function saveMpesaTransaction($paymentId, $postData)
    {
        try {
            $phoneNumber = $postData['mpesa_phone'] ?? '';
            $transactionCode = $postData['mpesa_transaction_code'] ?? '';
            $verificationStatus = $postData['mpesa_verification_status'] ?? 'pending';
            $notes = $postData['notes'] ?? '';
            
            if (empty($phoneNumber) || empty($transactionCode)) {
                return;
            }
            
            $sql = "INSERT INTO manual_mpesa_payments (payment_id, phone_number, transaction_code, amount, verification_status, verification_notes, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";

            $amount = null;
            foreach (['rent_amount', 'utility_amount', 'maintenance_amount'] as $k) {
                if (isset($postData[$k]) && $postData[$k] !== '') {
                    $amount = (float)$postData[$k];
                    break;
                }
            }
            if ($amount === null) {
                $amount = 0.0;
            }
            
            $paymentModel = new Payment();
            $db = $paymentModel->getDb();
            $stmt = $db->prepare($sql);
            $stmt->execute([$paymentId, $phoneNumber, $transactionCode, $amount, $verificationStatus, $notes]);
            
        } catch (\Exception $e) {
            error_log("Error saving M-Pesa transaction: " . $e->getMessage());
        }
    }

    /**
     * Get files for a payment
     */
    public function getFiles($id)
    {
        try {
            $paymentModel = new Payment();
            $userId = $_SESSION['user_id'] ?? null;
            
            // Check if payment exists and user has access
            $payment = $paymentModel->getById($id, $userId);
            if (!$payment) {
                throw new Exception('Payment not found or access denied');
            }

            $fileUploadHelper = new FileUploadHelper();
            $attachments = $fileUploadHelper->getEntityFiles('payment', $id, 'attachment');

            $response = [
                'success' => true,
                'attachments' => $attachments
            ];
        } catch (Exception $e) {
            error_log("Error in PaymentsController::getFiles: " . $e->getMessage());
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
} 