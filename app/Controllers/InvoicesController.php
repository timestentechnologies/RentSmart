<?php

namespace App\Controllers;

use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\Account;
use App\Models\LedgerEntry;

class InvoicesController
{
    private $userId;

    public function __construct()
    {
        $this->userId = $_SESSION['user_id'] ?? null;
        if (!$this->userId) {
            $_SESSION['flash_message'] = 'Please login to continue';
            $_SESSION['flash_type'] = 'warning';
            header('Location: ' . BASE_URL . '/');
            exit;
        }
    }

    public function index()
    {
        $inv = new Invoice();
        $invoices = $inv->getAll($this->userId);
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
        require 'views/invoices/show.php';
    }

    public function pdf($id)
    {
        $invModel = new Invoice();
        $invoice = $invModel->getWithItems((int)$id);
        if (!$invoice) { http_response_code(404); echo 'Invoice not found'; exit; }
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
            $body = "Dear " . ($invoice['tenant_name'] ?? 'Customer') . ",\n\nPlease find attached your invoice " . ($invoice['number'] ?? ('#' . $invoice['id'])) . ".\nTotal Due: Ksh " . number_format((float)$invoice['total'], 2) . "\nDue Date: " . ($invoice['due_date'] ?? '-') . "\n\nRegards,\n" . $siteName;
            $mail->Body = $body;
            $mail->AltBody = $body;
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
            $invModel->deleteInvoice((int)$id);
            $_SESSION['flash_message'] = 'Invoice deleted';
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
}
