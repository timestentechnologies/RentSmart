<?php

namespace App\Controllers;

use App\Models\Invoice;
use App\Models\Setting;

class TenantInvoicesController
{
    public function pdf($id)
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        if (!isset($_SESSION['tenant_id'])) {
            http_response_code(403);
            echo 'Access denied.';
            exit;
        }

        $tenantId = (int)$_SESSION['tenant_id'];
        $invoiceId = (int)$id;

        $invModel = new Invoice();
        $invoice = $invModel->getWithItems($invoiceId);
        if (!$invoice || (int)($invoice['tenant_id'] ?? 0) !== $tenantId) {
            http_response_code(404);
            echo 'Invoice not found.';
            exit;
        }

        $settingsModel = new Setting();
        $settings = $settingsModel->getAllAsAssoc();

        $siteName = $settings['site_name'] ?? 'RentSmart';
        $logoFilename = $settings['site_logo'] ?? '';

        $logoDataUri = null;
        if (!empty($logoFilename)) {
            $logoPath = __DIR__ . '/../../public/assets/images/' . $logoFilename;
            if (file_exists($logoPath)) {
                $imageData = file_get_contents($logoPath);
                $ext = strtolower((string)pathinfo($logoPath, PATHINFO_EXTENSION));
                $mime = 'image/png';
                if ($ext === 'jpg' || $ext === 'jpeg') { $mime = 'image/jpeg'; }
                else if ($ext === 'gif') { $mime = 'image/gif'; }
                else if ($ext === 'webp') { $mime = 'image/webp'; }
                else if ($ext === 'svg') { $mime = 'image/svg+xml'; }
                $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode($imageData);
            }
        }

        if (!class_exists('Dompdf\\Dompdf')) {
            require_once __DIR__ . '/../../vendor/dompdf/dompdf/src/Dompdf.php';
        }

        $paymentStatus = null;
        $maintenancePayments = [];

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
}
