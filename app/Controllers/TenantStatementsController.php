<?php

namespace App\Controllers;

use App\Models\Lease;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Property;
use Dompdf\Dompdf;
use Dompdf\Options;

class TenantStatementsController
{
    public function pdf()
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

        $start = isset($_GET['start']) ? trim((string)$_GET['start']) : '';
        $end = isset($_GET['end']) ? trim((string)$_GET['end']) : '';
        if ($start === '') {
            $start = date('Y-m-01');
        }
        if ($end === '') {
            $end = date('Y-m-d');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) {
            $start = date('Y-m-01');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
            $end = date('Y-m-d');
        }

        if (strtotime($start) === false || strtotime($end) === false) {
            http_response_code(400);
            echo 'Invalid date range.';
            exit;
        }

        if (strtotime($start) > strtotime($end)) {
            $tmp = $start;
            $start = $end;
            $end = $tmp;
        }

        $leaseModel = new Lease();
        $lease = $leaseModel->getActiveLeaseByTenant($tenantId);
        if (!$lease || empty($lease['id'])) {
            http_response_code(404);
            echo 'No active lease found.';
            exit;
        }

        $leaseId = (int)$lease['id'];

        // Load payments for this lease within date range
        $paymentModel = new Payment();
        $db = $paymentModel->getDb();
        $stmt = $db->prepare(
            "SELECT id, payment_date, amount, payment_type, payment_method, reference_number, status, notes\n"
            . "FROM payments\n"
            . "WHERE lease_id = ?\n"
            . "  AND payment_date BETWEEN ? AND ?\n"
            . "ORDER BY payment_date ASC, id ASC"
        );
        $stmt->execute([$leaseId, $start, $end]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $totalPaid = 0.0;
        foreach ($rows as $r) {
            $amt = (float)($r['amount'] ?? 0);
            if ($amt > 0) {
                $totalPaid += $amt;
            }
        }

        // Branding: property manager/owner if available, else system
        $settingsModel = new Setting();
        $settings = $settingsModel->getAllAsAssoc();

        $siteName = $settings['site_name'] ?? 'RentSmart';
        $logoFilename = $settings['site_logo'] ?? '';

        $brandingUserId = 0;
        try {
            $propertyId = 0;
            if (!empty($lease['unit_id'])) {
                $pStmt = $db->prepare("SELECT p.id, p.owner_id, p.manager_id FROM leases l JOIN units u ON l.unit_id = u.id JOIN properties p ON u.property_id = p.id WHERE l.id = ? LIMIT 1");
                $pStmt->execute([$leaseId]);
                $pr = $pStmt->fetch(\PDO::FETCH_ASSOC) ?: [];
                $propertyId = (int)($pr['id'] ?? 0);
                if (!empty($pr['manager_id'])) {
                    $brandingUserId = (int)$pr['manager_id'];
                } elseif (!empty($pr['owner_id'])) {
                    $brandingUserId = (int)$pr['owner_id'];
                }
            }
        } catch (\Throwable $e) {
            $brandingUserId = 0;
        }

        if ($brandingUserId > 0) {
            $companyNameKey = 'company_name_user_' . $brandingUserId;
            $companyLogoKey = 'company_logo_user_' . $brandingUserId;
            $companyName = trim((string)($settings[$companyNameKey] ?? ''));
            $companyLogo = trim((string)($settings[$companyLogoKey] ?? ''));
            if ($companyName !== '') {
                $siteName = $companyName;
            }
            if ($companyLogo !== '') {
                $logoFilename = $companyLogo;
            }
        }

        $logoDataUri = null;
        if (!empty($logoFilename)) {
            $logoPath = __DIR__ . '/../../public/assets/images/' . $logoFilename;
            if (is_file($logoPath)) {
                $bytes = @file_get_contents($logoPath);
                if ($bytes !== false) {
                    $ext = strtolower((string)pathinfo($logoPath, PATHINFO_EXTENSION));
                    $mime = 'image/png';
                    if ($ext === 'jpg' || $ext === 'jpeg') { $mime = 'image/jpeg'; }
                    else if ($ext === 'gif') { $mime = 'image/gif'; }
                    else if ($ext === 'webp') { $mime = 'image/webp'; }
                    else if ($ext === 'svg') { $mime = 'image/svg+xml'; }
                    $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode($bytes);
                }
            }
        }

        $rowsHtml = '';
        foreach ($rows as $r) {
            $date = (string)($r['payment_date'] ?? '');
            $ref = (string)($r['reference_number'] ?? ('PAY-' . (int)($r['id'] ?? 0)));
            $type = ucfirst((string)($r['payment_type'] ?? 'payment'));
            $method = (string)($r['payment_method'] ?? '-');
            $status = ucfirst((string)($r['status'] ?? '-'));
            $notes = trim((string)($r['notes'] ?? ''));
            $amt = (float)($r['amount'] ?? 0);

            $rowsHtml .= '<tr>'
                . '<td>' . htmlspecialchars($date) . '</td>'
                . '<td>' . htmlspecialchars($ref) . '</td>'
                . '<td>' . htmlspecialchars($type) . '</td>'
                . '<td>' . htmlspecialchars($method) . '</td>'
                . '<td>' . htmlspecialchars($status) . '</td>'
                . '<td>' . htmlspecialchars($notes) . '</td>'
                . '<td style="text-align:right">' . number_format($amt, 2) . '</td>'
                . '</tr>';
        }

        $headerHtml = '<div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">'
            . ($logoDataUri ? ('<img src="' . $logoDataUri . '" style="height:40px;" />') : '')
            . '<div>'
            . '<div style="font-size:16px; font-weight:bold;">' . htmlspecialchars((string)$siteName) . '</div>'
            . '<div style="font-size:12px; color:#555;">Tenant Statement</div>'
            . '</div>'
            . '</div>';

        $html = '<html><head><meta charset="utf-8">'
            . '<style>'
            . 'body{font-family: DejaVu Sans, sans-serif; font-size:12px; color:#111;}'
            . 'h2{margin:0 0 6px 0;}'
            . '.meta{color:#555; margin-bottom:10px;}'
            . 'table{width:100%; border-collapse:collapse;}'
            . 'th,td{border:1px solid #ddd; padding:6px; vertical-align:top;}'
            . 'th{background:#f3f5f7; text-align:left;}'
            . '.totals td{font-weight:bold; background:#fafafa;}'
            . '</style>'
            . '</head><body>'
            . $headerHtml
            . '<h2>Statement of Payments</h2>'
            . '<div class="meta">Period: ' . htmlspecialchars($start) . ' to ' . htmlspecialchars($end) . '</div>'
            . '<div class="meta">Total Paid: ' . number_format($totalPaid, 2) . '</div>'
            . '<table>'
            . '<thead><tr>'
            . '<th style="width:10%">Date</th>'
            . '<th style="width:14%">Reference</th>'
            . '<th style="width:10%">Type</th>'
            . '<th style="width:12%">Method</th>'
            . '<th style="width:10%">Status</th>'
            . '<th>Description</th>'
            . '<th style="width:12%; text-align:right">Amount</th>'
            . '</tr></thead>'
            . '<tbody>'
            . ($rowsHtml !== '' ? $rowsHtml : '<tr><td colspan="7" style="text-align:center; color:#777; padding:14px;">No payments found for this period</td></tr>')
            . '<tr class="totals">'
            . '<td colspan="6">Total Paid</td>'
            . '<td style="text-align:right">' . number_format($totalPaid, 2) . '</td>'
            . '</tr>'
            . '</tbody>'
            . '</table>'
            . '</body></html>';

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html);
        $dompdf->render();

        $filename = 'tenant_statement_' . $start . '_to_' . $end . '.pdf';
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }
}
