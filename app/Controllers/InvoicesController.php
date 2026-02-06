<?php
namespace App\Controllers;

use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\Lease;
use App\Models\Setting;
use App\Models\Payment;
use App\Models\User;

class InvoicesController
{
    private $userId;

    public function __construct()
    {
        $this->userId = $_SESSION['user_id'] ?? null;
        if (!$this->userId) {
            // Allow cron-safe rollover without session when token is valid or running via CLI
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $isRollover = (strpos($uri, '/invoices/rollover') !== false);
            $allowCron = false;
            if (PHP_SAPI === 'cli' || $isRollover) {
                try {
                    $settingsModel = new \App\Models\Setting();
                    $settings = $settingsModel->getAllAsAssoc();
                } catch (\Exception $e) { $settings = []; }
                $token = $_GET['token'] ?? null;
                if (PHP_SAPI === 'cli' || ($token && !empty($settings['cron_token']) && hash_equals((string)$settings['cron_token'], (string)$token))) {
                    $allowCron = true;
                }
            }
            if (!$allowCron) {
                $_SESSION['flash_message'] = 'Please login to continue';
                $_SESSION['flash_type'] = 'warning';
                header('Location: ' . BASE_URL . '/');
                exit;
            }
        }
    }

    public function index()
    {
        $inv = new Invoice();
        $userModel = new User();
        $userModel->find($this->userId);
        $isAdmin = $userModel->isAdmin();
        $scopeUserId = $isAdmin ? null : $this->userId;
        // Idempotently ensure rent invoices exist for each active lease from start month through current month
        try {
            $leaseModel = new Lease();
            $leases = $leaseModel->getActiveLeases($scopeUserId);
            $today = date('Y-m-d');
            foreach ($leases as $L) {
                $tenantId = (int)($L['tenant_id'] ?? 0);
                $rent = (float)($L['rent_amount'] ?? 0);
                $startDate = $L['start_date'] ?? null;
                if ($tenantId > 0 && $rent > 0 && !empty($startDate)) {
                    $inv->ensureInvoicesForLeaseMonths($tenantId, $rent, (string)$startDate, $today, $this->userId, 'AUTO');
                } elseif ($tenantId > 0 && $rent > 0) {
                    $inv->ensureMonthlyRentInvoice($tenantId, $today, $rent, $this->userId, 'AUTO');
                }
            }
            // Update statuses for current month
            $inv->updateStatusesForMonth($today);
        } catch (\Exception $e) { error_log('Auto-invoice index ensure failed: ' . $e->getMessage()); }
        $filters = [
            'q' => isset($_GET['q']) ? trim((string)$_GET['q']) : null,
            'status' => isset($_GET['status']) ? trim((string)$_GET['status']) : 'all',
            'visibility' => isset($_GET['visibility']) ? trim((string)$_GET['visibility']) : 'active',
            'tenant_id' => isset($_GET['tenant_id']) && $_GET['tenant_id'] !== '' ? (int)$_GET['tenant_id'] : null,
            'date_from' => isset($_GET['date_from']) ? trim((string)$_GET['date_from']) : null,
            'date_to' => isset($_GET['date_to']) ? trim((string)$_GET['date_to']) : null,
        ];
        $invoices = $inv->search($filters, $scopeUserId);
        $tenantModel = new Tenant();
        $tenants = $tenantModel->getAll($this->userId);
        require 'views/invoices/index.php';
    }

    public function create()
    {
        $tenantModel = new Tenant();
        $tenants = $tenantModel->getAll($this->userId);
        require 'views/invoices/create.php';
    }

    public function store()
    {
        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') throw new \Exception('Invalid request');
            if (!function_exists('verify_csrf_token') || !verify_csrf_token()) throw new \Exception('Invalid CSRF token');

            $items = [];
            $descs = $_POST['item_desc'] ?? [];
            $qtys = $_POST['item_qty'] ?? [];
            $prices = $_POST['item_price'] ?? [];
            foreach ($descs as $i => $d) {
                $d = trim($d);
                if ($d === '') continue;
                $qty = isset($qtys[$i]) ? (float)$qtys[$i] : 1;
                $price = isset($prices[$i]) ? (float)$prices[$i] : 0;
                $items[] = ['description'=>$d,'quantity'=>$qty,'unit_price'=>$price];
            }
            if (empty($items)) throw new \Exception('Add at least one item');

            $invModel = new Invoice();
            $invoiceId = $invModel->createInvoice([
                'tenant_id' => !empty($_POST['tenant_id']) ? (int)$_POST['tenant_id'] : null,
                'issue_date' => $_POST['issue_date'] ?? date('Y-m-d'),
                'due_date' => $_POST['due_date'] ?? null,
                'status' => 'sent',
                'notes' => $_POST['notes'] ?? null,
                'tax_rate' => isset($_POST['tax_rate']) && $_POST['tax_rate'] !== '' ? (float)$_POST['tax_rate'] : null,
                'user_id' => $this->userId,
            ], $items);

            // Post to ledger if requested
            if (!empty($_POST['post_to_ledger'])) {
                $invoice = $invModel->getWithItems($invoiceId);
                $accModel = new Account();
                $ar = $accModel->findByCode('1100');
                $rev = $accModel->findByCode('4000');
                if (!$ar || !$rev) throw new \Exception('Missing default accounts (1100 AR, 4000 Revenue)');
                $ledger = new LedgerEntry();
                $desc = 'Invoice ' . ($invoice['number'] ?? ('#' . $invoiceId));
                $ledger->postDoubleEntry($invoice['issue_date'], $desc, (int)$ar['id'], (int)$rev['id'], (float)$invoice['total'], $this->userId, 'invoice', $invoiceId);
                $invModel->markPosted($invoiceId);
            }

            $_SESSION['flash_message'] = 'Invoice created successfully';
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . BASE_URL . '/invoices');
            exit;
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/invoices/create');
            exit;
        }
    }

    public function show($id)
    {
        $invModel = new Invoice();
        $invoice = $invModel->getWithItems((int)$id);
        if (!$invoice) { http_response_code(404); echo 'Invoice not found'; exit; }
        // Refresh status based on payments for the issue month
        if (!empty($invoice['tenant_id']) && !empty($invoice['issue_date'])) {
            try { $invModel->updateStatusForTenantMonth((int)$invoice['tenant_id'], $invoice['issue_date']); } catch (\Exception $e) { error_log('Invoice status refresh (show) failed: ' . $e->getMessage()); }
            $invoice = $invModel->getWithItems((int)$id);
        }
        // Compute payment status summary for the invoice's month (rent/utilities)
        $paymentStatus = null;
        if (!empty($invoice['tenant_id']) && !empty($invoice['issue_date'])) {
            $leaseModel = new Lease();
            $lease = $leaseModel->getActiveLeaseByTenant((int)$invoice['tenant_id']);
            if ($lease) {
                $start = date('Y-m-01', strtotime($invoice['issue_date']));
                $end = date('Y-m-t', strtotime($invoice['issue_date']));
                $monthLabel = date('F Y', strtotime($start));
                $payModel = new \App\Models\Payment();
                $rows = $payModel->query(
                    "SELECT payment_type, COALESCE(SUM(amount),0) AS total_paid
                     FROM payments
                     WHERE lease_id = ? AND status IN ('completed','verified')
                       AND payment_date BETWEEN ? AND ?
                     GROUP BY payment_type",
                    [$lease['id'], $start, $end]
                );
                $paidRent = 0.0; $paidUtil = 0.0;
                foreach ($rows as $r) {
                    if (($r['payment_type'] ?? '') === 'rent') { $paidRent = (float)$r['total_paid']; }
                    if (($r['payment_type'] ?? '') === 'utility') { $paidUtil = (float)$r['total_paid']; }
                }
                $rentAmount = (float)($lease['rent_amount'] ?? 0.0);
                $rentStatus = 'due';
                if ($paidRent > $rentAmount + 0.009) { $rentStatus = 'advance'; }
                else if ($paidRent >= $rentAmount - 0.009) { $rentStatus = 'paid'; }
                $utilStatus = $paidUtil > 0.0 ? 'paid' : 'due';
                $paymentStatus = [
                    'month_label' => $monthLabel,
                    'rent' => ['status' => $rentStatus, 'paid' => $paidRent, 'amount' => $rentAmount],
                    'utilities' => ['status' => $utilStatus, 'paid' => $paidUtil],
                ];
            }
        }
        // Make available to the view
        $paymentStatus = $paymentStatus;
        require 'views/invoices/show.php';
    }

    public function pdf($id)
    {
        $invModel = new Invoice();
        $invoice = $invModel->getWithItems((int)$id);
        if (!$invoice) { http_response_code(404); echo 'Invoice not found'; exit; }
        // Ensure Dompdf is available
        if (!class_exists('Dompdf\\Dompdf')) {
            require_once __DIR__ . '/../../vendor/dompdf/dompdf/src/Dompdf.php';
        }
        // Company settings and logo
        $settingsModel = new \App\Models\Setting();
        $settings = $settingsModel->getAllAsAssoc();
        $siteName = $settings['site_name'] ?? 'RentSmart';
        $logoPath = __DIR__ . '/../../public/assets/images/' . ($settings['site_logo'] ?? 'site_logo_1751627446.png');
        $logoDataUri = null;
        if (file_exists($logoPath)) {
            $imageData = file_get_contents($logoPath);
            $base64 = base64_encode($imageData);
            $mime = 'image/' . (pathinfo($logoPath, PATHINFO_EXTENSION) ?: 'png');
            $logoDataUri = 'data:' . $mime . ';base64,' . $base64;
        }
        // Refresh status before generating
        if (!empty($invoice['tenant_id']) && !empty($invoice['issue_date'])) {
            try { $invModel->updateStatusForTenantMonth((int)$invoice['tenant_id'], $invoice['issue_date']); } catch (\Exception $e) { error_log('Invoice status refresh (pdf) failed: ' . $e->getMessage()); }
            $invoice = $invModel->getWithItems((int)$id);
        }
        // Payment status summary (same as show)
        $paymentStatus = null;
        if (!empty($invoice['tenant_id']) && !empty($invoice['issue_date'])) {
            $leaseModel = new Lease();
            $lease = $leaseModel->getActiveLeaseByTenant((int)$invoice['tenant_id']);
            if ($lease) {
                $start = date('Y-m-01', strtotime($invoice['issue_date']));
                $end = date('Y-m-t', strtotime($invoice['issue_date']));
                $monthLabel = date('F Y', strtotime($start));
                $payModel = new \App\Models\Payment();
                $rows = $payModel->query(
                    "SELECT payment_type, COALESCE(SUM(amount),0) AS total_paid
                     FROM payments
                     WHERE lease_id = ? AND status IN ('completed','verified')
                       AND payment_date BETWEEN ? AND ?
                     GROUP BY payment_type",
                    [$lease['id'], $start, $end]
                );
                $paidRent = 0.0; $paidUtil = 0.0;
                foreach ($rows as $r) {
                    if (($r['payment_type'] ?? '') === 'rent') { $paidRent = (float)$r['total_paid']; }
                    if (($r['payment_type'] ?? '') === 'utility') { $paidUtil = (float)$r['total_paid']; }
                }
                $rentAmount = (float)($lease['rent_amount'] ?? 0.0);
                $rentStatus = 'due';
                if ($paidRent > $rentAmount + 0.009) { $rentStatus = 'advance'; }
                else if ($paidRent >= $rentAmount - 0.009) { $rentStatus = 'paid'; }
                $utilStatus = $paidUtil > 0.0 ? 'paid' : 'due';
                $paymentStatus = [
                    'month_label' => $monthLabel,
                    'rent' => ['status' => $rentStatus, 'paid' => $paidRent, 'amount' => $rentAmount],
                    'utilities' => ['status' => $utilStatus, 'paid' => $paidUtil],
                ];
            }
        }
        ob_start();
        include __DIR__ . '/../../views/invoices/invoice_pdf.php';
        $html = ob_get_clean();
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $filename = 'invoice_' . ($invoice['number'] ?? $invoice['id']) . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $dompdf->output();
        exit;
    }

    public function email($id)
    {
        try {
            $invModel = new Invoice();
            $invoice = $invModel->getWithItems((int)$id);
            if (!$invoice) throw new \Exception('Invoice not found');
            if (empty($invoice['tenant_email'])) throw new \Exception('Tenant has no email');

            // Build PDF HTML
            $settingsModel = new \App\Models\Setting();
            $settings = $settingsModel->getAllAsAssoc();
            $siteName = $settings['site_name'] ?? 'RentSmart';
            $logoPath = __DIR__ . '/../../public/assets/images/' . ($settings['site_logo'] ?? 'site_logo_1751627446.png');
            $logoDataUri = null;
            if (file_exists($logoPath)) {
                $imageData = file_get_contents($logoPath);
                $base64 = base64_encode($imageData);
                $mime = 'image/' . (pathinfo($logoPath, PATHINFO_EXTENSION) ?: 'png');
                $logoDataUri = 'data:' . $mime . ';base64,' . $base64;
            }
            // Ensure Dompdf is available
            if (!class_exists('Dompdf\\Dompdf')) {
                require_once __DIR__ . '/../../vendor/dompdf/dompdf/src/Dompdf.php';
            }
            // Refresh status before generating
            if (!empty($invoice['tenant_id']) && !empty($invoice['issue_date'])) {
                try { $invModel->updateStatusForTenantMonth((int)$invoice['tenant_id'], $invoice['issue_date']); } catch (\Exception $e) { error_log('Invoice status refresh (email) failed: ' . $e->getMessage()); }
                $invoice = $invModel->getWithItems((int)$id);
            }
            ob_start();
            include __DIR__ . '/../../views/invoices/invoice_pdf.php';
            $html = ob_get_clean();

            // Render PDF
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdfData = $dompdf->output();
            $filename = 'invoice_' . ($invoice['number'] ?? $invoice['id']) . '.pdf';

            // Send email
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $settings['smtp_host'] ?? '';
            $mail->Port = (int)($settings['smtp_port'] ?? 587);
            $mail->SMTPAuth = true;
            $mail->Username = $settings['smtp_user'] ?? '';
            $mail->Password = $settings['smtp_pass'] ?? '';
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->setFrom($settings['smtp_user'] ?? '', $siteName);
            $mail->addAddress($invoice['tenant_email'], $invoice['tenant_name'] ?? 'Tenant');
            $mail->Subject = 'Invoice ' . ($invoice['number'] ?? ('#' . $invoice['id']));
            $mail->isHTML(true);
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $base = defined('BASE_URL') ? BASE_URL : '';
            $siteUrl = rtrim((string)($settings['site_url'] ?? ($scheme . '://' . $host . $base)), '/');
            $logoUrl = isset($settings['site_logo']) && $settings['site_logo'] ? ($siteUrl . '/public/assets/images/' . $settings['site_logo']) : '';
            $footer = '<div style="margin-top:30px;font-size:12px;color:#888;text-align:center;">Powered by <a href="https://timestentechnologies.co.ke" target="_blank" style="color:#888;text-decoration:none;">Timesten Technologies</a></div>';
            $plain = 'Dear ' . ($invoice['tenant_name'] ?? 'Customer') . ",\n\nPlease find attached your invoice " . ($invoice['number'] ?? ('#' . $invoice['id'])) . ".\nTotal Due: Ksh " . number_format((float)$invoice['total'], 2) . "\nDue Date: " . ($invoice['due_date'] ?? '-') . "\n\nRegards,\n" . $siteName;
            $html =
                '<div style="max-width:520px;margin:auto;border:1px solid #eee;padding:24px;font-family:sans-serif;">'
                . ($logoUrl ? '<div style="text-align:center;margin-bottom:24px;"><img src="' . $logoUrl . '" alt="Logo" style="max-width:180px;max-height:80px;"></div>' : '') .
                '<p style="font-size:16px;">Dear ' . htmlspecialchars($invoice['tenant_name'] ?? 'Customer') . ',</p>' .
                '<p>Please find attached your invoice ' . htmlspecialchars($invoice['number'] ?? ('#' . $invoice['id'])) . '.</p>' .
                '<p><strong>Total Due:</strong> Ksh ' . number_format((float)$invoice['total'], 2) . '</p>' .
                '<p><strong>Due Date:</strong> ' . htmlspecialchars($invoice['due_date'] ?? '-') . '</p>' .
                '<p>Regards,<br>' . htmlspecialchars($siteName) . '</p>' .
                $footer .
                '</div>';
            $mail->Body = $html;
            $mail->AltBody = $plain;
            $mail->addStringAttachment($pdfData, $filename, 'base64', 'application/pdf');
            $mail->send();

            $_SESSION['flash_message'] = 'Invoice emailed to tenant';
            $_SESSION['flash_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = 'Email failed: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
        header('Location: ' . BASE_URL . '/invoices/show/' . (int)$id);
        exit;
    }

    public function delete($id)
    {
        try {
            $invModel = new Invoice();
            $invoice = $invModel->getWithItems((int)$id);
            $notes = is_array($invoice) ? (string)($invoice['notes'] ?? '') : '';
            // Auto-created monthly rent invoices will be recreated immediately if deleted.
            // Void them instead so the ensure logic finds them and does not recreate.
            $isAuto = (stripos($notes, 'Auto-created') !== false) || (stripos($notes, 'AUTO') !== false);
            if ($isAuto) {
                $invModel->voidAndArchive((int)$id);
                $_SESSION['flash_message'] = 'Invoice voided & archived';
            } else {
                $invModel->archiveInvoice((int)$id);
                $_SESSION['flash_message'] = 'Invoice archived';
            }
            $_SESSION['flash_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = 'Delete failed';
            $_SESSION['flash_type'] = 'danger';
        }
        header('Location: ' . BASE_URL . '/invoices');
        exit;
    }

    public function post($id)
    {
        try {
            $invModel = new Invoice();
            $invoice = $invModel->getWithItems((int)$id);
            if (!$invoice) throw new \Exception('Invoice not found');
            if (!empty($invoice['posted_at'])) throw new \Exception('Already posted');
            $accModel = new Account();
            $ar = $accModel->findByCode('1100');
            $rev = $accModel->findByCode('4000');
            if (!$ar || !$rev) throw new \Exception('Missing default accounts');
            $ledger = new LedgerEntry();
            $desc = 'Invoice ' . ($invoice['number'] ?? ('#' . $invoice['id']));
            $ledger->postDoubleEntry($invoice['issue_date'], $desc, (int)$ar['id'], (int)$rev['id'], (float)$invoice['total'], $this->userId, 'invoice', (int)$id);
            $invModel->markPosted((int)$id);
            $_SESSION['flash_message'] = 'Invoice posted to ledger';
            $_SESSION['flash_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
        header('Location: ' . BASE_URL . '/invoices/show/' . (int)$id);
        exit;
    }

    /**
     * Cron-safe endpoint: ensure current-month invoices and update statuses.
     * Use CLI or provide settings['cron_token'] as ?token=...
     */
    public function rollover()
    {
        header('Content-Type: application/json');
        $today = date('Y-m-d');
        $leaseModel = new Lease();
        $inv = new Invoice();
        $processed = 0;
        try {
            $leases = $leaseModel->getActiveLeases(null);
            foreach ($leases as $L) {
                $tenantId = (int)($L['tenant_id'] ?? 0);
                $rent = (float)($L['rent_amount'] ?? 0);
                if ($tenantId > 0 && $rent > 0) {
                    $inv->ensureMonthlyRentInvoice($tenantId, $today, $rent, null, 'CRON');
                    $processed++;
                }
            }
            $inv->updateStatusesForMonth($today);
            echo json_encode(['success' => true, 'processed_leases' => $processed]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Backfill invoices for prior months per lease (max 36 months), optionally filtered by tenant_id
     */
    public function backfill()
    {
        try {
            $tenantId = isset($_GET['tenant_id']) ? (int)$_GET['tenant_id'] : null;
            $leaseModel = new Lease();
            $inv = new Invoice();
            $leases = [];
            if ($tenantId) {
                $L = $leaseModel->getActiveLeaseByTenant($tenantId);
                if ($L) { $leases = [$L]; }
            } else {
                $leases = $leaseModel->getActiveLeases($this->userId);
            }
            $count = 0;
            foreach ($leases as $L) {
                $inv->ensureInvoicesForLeaseMonths((int)$L['tenant_id'], (float)$L['rent_amount'], $L['start_date'], $L['end_date'] ?? null, $this->userId, 'BACKFILL');
                $count++;
            }
            $_SESSION['flash_message'] = 'Backfill complete for ' . $count . ' lease(s)';
            $_SESSION['flash_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = 'Backfill failed: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
        header('Location: ' . BASE_URL . '/invoices');
        exit;
    }
}
