<?php

namespace App\Controllers;

use App\Models\Invoice;
use App\Models\Setting;
use App\Models\Lease;

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
        $logoFilename = $settings['site_logo'] ?? 'site_logo_1751627446.png';

        // Prefer property branding (owner/manager/agent/caretaker) for the tenant's lease at invoice time
        try {
            $issueDate = (string)($invoice['issue_date'] ?? '');
            if ($issueDate === '') { $issueDate = date('Y-m-d'); }

            $db = $invModel->getDb();

            // First, attempt active lease (most reliable for tenant-side view)
            $leaseStmt = $db->prepare(
                "SELECT l.id AS lease_id, p.owner_id, p.manager_id, p.agent_id, p.caretaker_user_id\n"
                . "FROM leases l\n"
                . "JOIN units u ON l.unit_id = u.id\n"
                . "JOIN properties p ON u.property_id = p.id\n"
                . "WHERE l.tenant_id = ?\n"
                . "  AND l.status = 'active'\n"
                . "ORDER BY l.start_date DESC, l.id DESC\n"
                . "LIMIT 1"
            );
            $leaseStmt->execute([(int)$tenantId]);
            $lr = $leaseStmt->fetch(\PDO::FETCH_ASSOC) ?: [];

            // Fallback: lease around invoice issue date
            if (empty($lr)) {
            $leaseStmt = $db->prepare(
                "SELECT l.id AS lease_id, p.owner_id, p.manager_id, p.agent_id, p.caretaker_user_id\n"
                . "FROM leases l\n"
                . "JOIN units u ON l.unit_id = u.id\n"
                . "JOIN properties p ON u.property_id = p.id\n"
                . "WHERE l.tenant_id = ?\n"
                . "  AND l.start_date <= ?\n"
                . "  AND (l.end_date IS NULL OR l.end_date = '' OR l.end_date >= ? OR l.status = 'active')\n"
                . "ORDER BY l.start_date DESC, l.id DESC\n"
                . "LIMIT 1"
            );
            $leaseStmt->execute([(int)$tenantId, $issueDate, $issueDate]);
            $lr = $leaseStmt->fetch(\PDO::FETCH_ASSOC) ?: [];
            }

            $brandingUserId = 0;
            foreach (['manager_id','owner_id','agent_id','caretaker_user_id'] as $k) {
                if (!empty($lr[$k])) {
                    $uid = (int)$lr[$k];
                    $nameKey = 'company_name_user_' . $uid;
                    $logoKey = 'company_logo_user_' . $uid;
                    $companyName = trim((string)($settings[$nameKey] ?? ''));
                    $companyLogo = trim((string)($settings[$logoKey] ?? ''));
                    if ($companyName !== '' || $companyLogo !== '') {
                        $brandingUserId = $uid;
                        if ($companyName !== '') { $siteName = $companyName; }
                        if ($companyLogo !== '') { $logoFilename = $companyLogo; }
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore branding issues; fallback to system branding
        }

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
