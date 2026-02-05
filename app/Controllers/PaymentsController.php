<?php

namespace App\Controllers;

use App\Models\Payment;
use App\Models\Tenant;
use App\Helpers\FileUploadHelper;
use App\Models\ActivityLog;

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
        $tenantModel = new Tenant();
        $utilityModel = new \App\Models\Utility();
        $utilityReadingModel = new \App\Models\UtilityReading();
        
        // Get payments with tenant information (with role-based access)
        $payments = $paymentModel->getPaymentsWithTenantInfo($this->userId);
        
        // Get tenants with active leases (with role-based access)
        $tenants = $tenantModel->getActiveLeases($this->userId);
        
        // Enhance tenant data with payment and utility information
        foreach ($tenants as &$tenant) {
            $leaseId = $tenant['lease_id'] ?? null;
            $dueAmount = 0;
            if ($leaseId) {
                $dueAmount = $paymentModel->getDueAmountForLease($leaseId);
            }
            $tenant['due_amount'] = $dueAmount;

            // Get utility readings for the tenant's unit
            if (!empty($tenant['unit_id'])) {
                $utilities = $utilityModel->getUtilitiesByUnit($tenant['unit_id']);
                $utilityReadings = [];
                foreach ($utilities as $utility) {
                    if ($utility['latest_reading'] !== null) {
                        $utilityReadings[] = [
                            'utility_id' => $utility['id'],
                            'utility_type' => $utility['utility_type'],
                            'current_reading' => $utility['latest_reading'],
                            'current_reading_date' => $utility['latest_reading_date'],
                            'previous_reading' => $utility['previous_reading'],
                            'previous_reading_date' => $utility['previous_reading_date'],
                            'cost' => $utility['latest_cost'] ?? 0,
                            'rate' => $utility['flat_rate'],
                            'is_metered' => $utility['is_metered']
                        ];
                    }
                }
                $tenant['utility_readings'] = $utilityReadings;
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

                // Process rent payment if selected
                if (in_array('rent', $paymentTypes) && !empty($_POST['rent_amount'])) {
                    $rentData = [
                        'lease_id' => $lease['id'],
                        'amount' => $_POST['rent_amount'],
                        'payment_date' => $_POST['payment_date'],
                        'payment_type' => 'rent',
                        'payment_method' => $_POST['payment_method'],
                        'notes' => 'Rent payment: ' . ($_POST['notes'] ?? '')
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
                    // Get all utilities for this unit
                    $utilityModel = new \App\Models\Utility();
                    $utilities = $utilityModel->getUtilitiesByUnit($lease['unit_id']);
                    
                    if (!empty($utilities)) {
                        // Calculate the amount per utility based on their individual amounts
                        $totalUtilityAmount = 0;
                        $utilityAmounts = [];
                        
                        foreach ($utilities as $utility) {
                            $amount = 0;
                            if ($utility['is_metered']) {
                                // For metered utilities, get the latest reading cost
                                $latestReading = $utilityModel->getLatestReading($utility['id']);
                                $amount = $latestReading ? $latestReading['cost'] : 0;
                            } else {
                                // For flat rate utilities, use the flat rate
                                $amount = $utility['flat_rate'] ?? 0;
                            }
                            
                            if ($amount > 0) {
                                $utilityAmounts[$utility['id']] = $amount;
                                $totalUtilityAmount += $amount;
                            }
                        }
                        
                        // If the payment amount matches the total utility amount, create separate payments
                        if (abs($_POST['utility_amount'] - $totalUtilityAmount) < 0.01) {
                            foreach ($utilityAmounts as $utilityId => $amount) {
                                $utilityData = [
                                    'lease_id' => $lease['id'],
                                    'utility_id' => $utilityId,
                                    'amount' => $amount,
                                    'payment_date' => $_POST['payment_date'],
                                    'payment_type' => 'utility',
                                    'payment_method' => $_POST['payment_method'],
                                    'notes' => 'Utility payment: ' . ($_POST['notes'] ?? '')
                                ];
                                $utilityPaymentId = $paymentModel->createUtilityPayment($utilityData);
                                // Activity log: payment.create (utility split)
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
                                
                                // Handle file uploads for utility payment (only for the first one to avoid duplicates)
                                if ($utilityId === array_key_first($utilityAmounts) && !empty($_FILES['payment_attachments']['name'][0])) {
                                    $this->handlePaymentAttachments($utilityPaymentId);
                                }
                            }
                            $successMessages[] = 'Utility payments added successfully!';
                        } else {
                            // If amounts don't match, create a single payment for the specified utility type
                            if (!empty($_POST['utility_type'])) {
                                $utilityId = null;
                                foreach ($utilities as $utility) {
                                    if ($utility['utility_type'] === $_POST['utility_type']) {
                                        $utilityId = $utility['id'];
                                        break;
                                    }
                                }
                                
                                $utilityData = [
                                    'lease_id' => $lease['id'],
                                    'utility_id' => $utilityId,
                                    'amount' => $_POST['utility_amount'],
                                    'payment_date' => $_POST['payment_date'],
                                    'payment_type' => 'utility',
                                    'payment_method' => $_POST['payment_method'],
                                    'notes' => ucfirst($_POST['utility_type']) . ' utility payment: ' . ($_POST['notes'] ?? '')
                                ];
                                $utilityPaymentId = $paymentModel->createUtilityPayment($utilityData);
                                // Activity log: payment.create (utility single)
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
                                            'amount' => (float)$_POST['utility_amount'],
                                            'payment_date' => $_POST['payment_date'],
                                            'payment_type' => 'utility',
                                            'payment_method' => $_POST['payment_method'],
                                            'utility_type' => $_POST['utility_type'] ?? null
                                        ]),
                                        $ip,
                                        $agent
                                    );
                                } catch (\Exception $ex) { error_log('payment.create log failed: ' . $ex->getMessage()); }
                                
                                // Handle file uploads for utility payment
                                if (!empty($_FILES['payment_attachments']['name'][0])) {
                                    $this->handlePaymentAttachments($utilityPaymentId);
                                }
                                
                                $successMessages[] = 'Utility payment added successfully!';
                            }
                        }
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
        // Get logo path and embed as base64 data URI for dompdf
        $logoPath = __DIR__ . '/../../public/assets/images/site_logo_1751627446.png';
        $logoDataUri = null;
        if (file_exists($logoPath)) {
            $imageData = file_get_contents($logoPath);
            $base64 = base64_encode($imageData);
            $logoDataUri = 'data:image/png;base64,' . $base64;
        }
        $siteName = 'RentSmart';
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
            
            $paymentModel = new Payment();
            $db = $paymentModel->getDb();
            $stmt = $db->prepare($sql);
            $stmt->execute([$paymentId, $phoneNumber, $transactionCode, $postData['rent_amount'], $verificationStatus, $notes]);
            
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