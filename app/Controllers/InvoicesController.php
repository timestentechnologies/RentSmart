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
use App\Models\RealtorClient;
use App\Models\RealtorListing;

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
        $role = strtolower((string)($_SESSION['user_role'] ?? ''));
        $inv = new Invoice();
        $userModel = new User();
        $userModel->find($this->userId);
        $isAdmin = $userModel->isAdmin();
        $scopeUserId = $isAdmin ? null : $this->userId;
        if ($role !== 'realtor') {
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
                        // Also ensure invoices for future months when rent is paid in advance.
                        // This is important for historical payments that already exist in DB.
                        $endDate = $today;
                        try {
                            $leaseId = (int)($L['id'] ?? 0);
                            if ($leaseId > 0) {
                                $payModel = new Payment();
                                $rows = $payModel->query(
                                    "SELECT COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) AS s\n"
                                    . "FROM payments\n"
                                    . "WHERE lease_id = ? AND payment_type = 'rent' AND status IN ('completed','verified')",
                                    [$leaseId]
                                );
                                $paidTotal = (float)($rows[0]['s'] ?? 0);
                                $monthsPaid = (int)floor(($paidTotal / $rent) + 1e-6);
                                if ($monthsPaid > 0) {
                                    $leaseStart = new \DateTime(date('Y-m-01', strtotime((string)$startDate)));
                                    $coveredEnd = clone $leaseStart;
                                    $coveredEnd->modify('+' . max(0, $monthsPaid - 1) . ' month');
                                    $todayMonth = new \DateTime(date('Y-m-01', strtotime((string)$today)));
                                    if ($coveredEnd > $todayMonth) {
                                        $endDate = $coveredEnd->format('Y-m-d');
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            error_log('Advance invoice ensure (index) failed: ' . $e->getMessage());
                        }
                        $inv->ensureInvoicesForLeaseMonths($tenantId, $rent, (string)$startDate, $endDate, $this->userId, 'AUTO');
                    } elseif ($tenantId > 0 && $rent > 0) {
                        $inv->ensureMonthlyRentInvoice($tenantId, $today, $rent, $this->userId, 'AUTO');
                    }
                }
                // Update statuses for current month
                $inv->updateStatusesForMonth($today);
            } catch (\Exception $e) { error_log('Auto-invoice index ensure failed: ' . $e->getMessage()); }
        }
        $filters = [
            'q' => isset($_GET['q']) ? trim((string)$_GET['q']) : null,
            'status' => isset($_GET['status']) ? trim((string)$_GET['status']) : 'all',
            'visibility' => isset($_GET['visibility']) ? trim((string)$_GET['visibility']) : 'active',
            'tenant_id' => isset($_GET['tenant_id']) && $_GET['tenant_id'] !== '' ? (int)$_GET['tenant_id'] : null,
            'date_from' => isset($_GET['date_from']) ? trim((string)$_GET['date_from']) : null,
            'date_to' => isset($_GET['date_to']) ? trim((string)$_GET['date_to']) : null,
        ];
        if ($role === 'realtor') {
            $filters['tenant_id'] = null;
        }
        $invoices = $inv->search($filters, $scopeUserId);

        $tenants = [];
        if ($role !== 'realtor') {
            $tenantModel = new Tenant();
            $tenants = $tenantModel->getAll($this->userId);
        }

        // Enrich realtor invoices with client + listing label (from linked payment or manual tag)
        if ($role === 'realtor') {
            $db = $inv->getDb();
            $paymentIds = [];
            $manualPairs = [];
            $contractIds = [];
            foreach ($invoices as $idx => $r) {
                $notes = (string)($r['notes'] ?? '');
                if (preg_match('/REALTOR_PAYMENT#(\d+)/', $notes, $m)) {
                    $pid = (int)$m[1];
                    if ($pid > 0) $paymentIds[] = $pid;
                    $invoices[$idx]['realtor_payment_id'] = $pid;
                } elseif (preg_match('/REALTOR_MANUAL\s+client_id=(\d+)\s+listing_id=(\d+)/', $notes, $m)) {
                    $cid = (int)$m[1];
                    $lid = (int)$m[2];
                    $manualPairs[] = ['client_id' => $cid, 'listing_id' => $lid, 'idx' => $idx];
                    $invoices[$idx]['realtor_manual_client_id'] = $cid;
                    $invoices[$idx]['realtor_manual_listing_id'] = $lid;
                } elseif (preg_match('/REALTOR_CONTRACT#(\d+)/', $notes, $m)) {
                    $cid = (int)$m[1];
                    if ($cid > 0) {
                        $contractIds[] = $cid;
                        $invoices[$idx]['realtor_contract_id'] = $cid;
                    }
                }
            }

            $paymentIds = array_values(array_unique(array_filter($paymentIds, fn($v) => $v > 0)));
            $map = [];
            if (!empty($paymentIds)) {
                $ph = implode(',', array_fill(0, count($paymentIds), '?'));
                $stmt = $db->prepare(
                    "SELECT p.id AS payment_id, rc.name AS client_name, rl.title AS listing_title\n"
                    . "FROM payments p\n"
                    . "LEFT JOIN realtor_clients rc ON rc.id = p.realtor_client_id\n"
                    . "LEFT JOIN realtor_listings rl ON rl.id = p.realtor_listing_id\n"
                    . "WHERE p.realtor_user_id = ? AND p.id IN ($ph)"
                );
                $stmt->execute(array_merge([(int)$this->userId], $paymentIds));
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as $rr) {
                    $map[(int)$rr['payment_id']] = [
                        'client_name' => (string)($rr['client_name'] ?? ''),
                        'listing_title' => (string)($rr['listing_title'] ?? ''),
                    ];
                }
            }

            foreach ($invoices as $idx => $r) {
                $pid = (int)($r['realtor_payment_id'] ?? 0);
                if ($pid > 0 && isset($map[$pid])) {
                    $invoices[$idx]['realtor_client_name'] = $map[$pid]['client_name'];
                    $invoices[$idx]['realtor_listing_title'] = $map[$pid]['listing_title'];
                }
            }

            $contractIds = array_values(array_unique(array_filter($contractIds, fn($v) => $v > 0)));
            if (!empty($contractIds)) {
                try {
                    $ph = implode(',', array_fill(0, count($contractIds), '?'));
                    $stmt = $db->prepare(
                        "SELECT c.id AS contract_id, rc.name AS client_name, rl.title AS listing_title\n"
                        . "FROM realtor_contracts c\n"
                        . "LEFT JOIN realtor_clients rc ON rc.id = c.realtor_client_id\n"
                        . "LEFT JOIN realtor_listings rl ON rl.id = c.realtor_listing_id\n"
                        . "WHERE c.user_id = ? AND c.id IN ($ph)"
                    );
                    $stmt->execute(array_merge([(int)$this->userId], $contractIds));
                    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                    $cMap = [];
                    foreach ($rows as $rr) {
                        $cMap[(int)($rr['contract_id'] ?? 0)] = [
                            'client_name' => (string)($rr['client_name'] ?? ''),
                            'listing_title' => (string)($rr['listing_title'] ?? ''),
                        ];
                    }
                    foreach ($invoices as $idx => $r) {
                        $cid = (int)($r['realtor_contract_id'] ?? 0);
                        if ($cid > 0 && isset($cMap[$cid])) {
                            if (!isset($invoices[$idx]['realtor_client_name']) || $invoices[$idx]['realtor_client_name'] === '') {
                                $invoices[$idx]['realtor_client_name'] = $cMap[$cid]['client_name'];
                            }
                            if (!isset($invoices[$idx]['realtor_listing_title']) || $invoices[$idx]['realtor_listing_title'] === '') {
                                $invoices[$idx]['realtor_listing_title'] = $cMap[$cid]['listing_title'];
                            }
                        }
                    }
                } catch (\Throwable $e) {
                }
            }

            if (!empty($manualPairs)) {
                $clientIds = array_values(array_unique(array_filter(array_map(fn($p) => (int)$p['client_id'], $manualPairs), fn($v) => $v > 0)));
                $listingIds = array_values(array_unique(array_filter(array_map(fn($p) => (int)$p['listing_id'], $manualPairs), fn($v) => $v > 0)));
                $clientMap = [];
                $listingMap = [];
                if (!empty($clientIds)) {
                    $ph = implode(',', array_fill(0, count($clientIds), '?'));
                    $stmt = $db->prepare("SELECT id, name FROM realtor_clients WHERE user_id = ? AND id IN ($ph)");
                    $stmt->execute(array_merge([(int)$this->userId], $clientIds));
                    foreach (($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) as $rr) {
                        $clientMap[(int)$rr['id']] = (string)($rr['name'] ?? '');
                    }
                }
                if (!empty($listingIds)) {
                    $ph = implode(',', array_fill(0, count($listingIds), '?'));
                    $stmt = $db->prepare("SELECT id, title FROM realtor_listings WHERE user_id = ? AND id IN ($ph)");
                    $stmt->execute(array_merge([(int)$this->userId], $listingIds));
                    foreach (($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) as $rr) {
                        $listingMap[(int)$rr['id']] = (string)($rr['title'] ?? '');
                    }
                }
                foreach ($manualPairs as $p) {
                    $idx = (int)$p['idx'];
                    $cid = (int)$p['client_id'];
                    $lid = (int)$p['listing_id'];
                    $invoices[$idx]['realtor_client_name'] = $clientMap[$cid] ?? '';
                    $invoices[$idx]['realtor_listing_title'] = $listingMap[$lid] ?? '';
                }
            }
        }
        require 'views/invoices/index.php';
    }

    public function create()
    {
        $role = strtolower((string)($_SESSION['user_role'] ?? ''));
        $tenants = [];
        $realtorClients = [];
        $realtorListings = [];

        if ($role === 'realtor') {
            $clientModel = new RealtorClient();
            $listingModel = new RealtorListing();
            $realtorClients = $clientModel->getAll((int)$this->userId);
            $realtorListings = $listingModel->getAll((int)$this->userId);
        } else {
            $tenantModel = new Tenant();
            $tenants = $tenantModel->getAll($this->userId);
        }
        require 'views/invoices/create.php';
    }

    public function store()
    {
        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') throw new \Exception('Invalid request');
            if (!function_exists('verify_csrf_token') || !verify_csrf_token()) throw new \Exception('Invalid CSRF token');

            $role = strtolower((string)($_SESSION['user_role'] ?? ''));

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

            $tenantId = !empty($_POST['tenant_id']) ? (int)$_POST['tenant_id'] : null;
            $notes = $_POST['notes'] ?? null;
            if ($role === 'realtor') {
                $tenantId = null;
                $cid = isset($_POST['realtor_client_id']) ? (int)$_POST['realtor_client_id'] : 0;
                $lid = isset($_POST['realtor_listing_id']) ? (int)$_POST['realtor_listing_id'] : 0;
                if ($cid <= 0) {
                    throw new \Exception('Client is required');
                }
                if ($lid <= 0) {
                    $lid = 0;
                }
                $tag = 'REALTOR_MANUAL client_id=' . (int)$cid . ' listing_id=' . (int)$lid;
                $notes = trim($tag . ' ' . trim((string)$notes));
            }

            $invoiceId = $invModel->createInvoice([
                'tenant_id' => $tenantId,
                'issue_date' => $_POST['issue_date'] ?? date('Y-m-d'),
                'due_date' => $_POST['due_date'] ?? null,
                'status' => 'sent',
                'notes' => $notes,
                'tax_rate' => isset($_POST['tax_rate']) && $_POST['tax_rate'] !== '' ? (float)$_POST['tax_rate'] : null,
                'user_id' => $this->userId,
            ], $items);

            // Post to ledger if requested
            if (!empty($_POST['post_to_ledger'])) {
                $invoice = $invModel->getWithItems($invoiceId);
                $accModel = new Account();
                $ar = $accModel->findByCode('1100');
                $rev = ($role === 'realtor')
                    ? $accModel->ensureByCode('4010', 'Realtor Income', 'revenue')
                    : $accModel->findByCode('4000');
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
        $role = strtolower((string)($_SESSION['user_role'] ?? ''));
        $invModel = new Invoice();
        $invoice = $invModel->getWithItems((int)$id);
        if (!$invoice) { http_response_code(404); echo 'Invoice not found'; exit; }
        // Refresh status based on payments for the issue month
        if ($role !== 'realtor' && !empty($invoice['tenant_id']) && !empty($invoice['issue_date'])) {
            try { $invModel->updateStatusForTenantMonth((int)$invoice['tenant_id'], $invoice['issue_date']); } catch (\Exception $e) { error_log('Invoice status refresh (show) failed: ' . $e->getMessage()); }
            $invoice = $invModel->getWithItems((int)$id);
        }

        $prevInvoiceId = null;
        $nextInvoiceId = null;
        if (!empty($invoice['tenant_id']) && !empty($invoice['issue_date'])) {
            try {
                $tenantId = (int)$invoice['tenant_id'];
                $issueDate = (string)$invoice['issue_date'];
                $prevRows = $invModel->query(
                    "SELECT id FROM invoices WHERE tenant_id = ? AND issue_date < ? AND archived_at IS NULL AND status <> 'void' ORDER BY issue_date DESC, id DESC LIMIT 1",
                    [$tenantId, $issueDate]
                );
                if (!empty($prevRows[0]['id'])) { $prevInvoiceId = (int)$prevRows[0]['id']; }
                $nextRows = $invModel->query(
                    "SELECT id FROM invoices WHERE tenant_id = ? AND issue_date > ? AND archived_at IS NULL AND status <> 'void' ORDER BY issue_date ASC, id ASC LIMIT 1",
                    [$tenantId, $issueDate]
                );
                if (!empty($nextRows[0]['id'])) { $nextInvoiceId = (int)$nextRows[0]['id']; }
            } catch (\Throwable $e) {
            }
        }
        // Compute payment status summary for the invoice's month (rent/utilities)
        $paymentStatus = null;
        $maintenancePayments = [];
        if ($role !== 'realtor' && !empty($invoice['tenant_id']) && !empty($invoice['issue_date'])) {
            $leaseModel = new Lease();
            $lease = $leaseModel->getActiveLeaseByTenant((int)$invoice['tenant_id']);
            if ($lease) {
                $start = date('Y-m-01', strtotime($invoice['issue_date']));
                $end = date('Y-m-t', strtotime($invoice['issue_date']));
                $monthLabel = date('F Y', strtotime($start));
                $payModel = new \App\Models\Payment();

                $maintenancePayments = $payModel->query(
                    "SELECT p.id, p.amount, p.payment_date, p.applies_to_month, p.payment_method, p.status, p.notes, mmp.transaction_code\n"
                    . "FROM payments p\n"
                    . "LEFT JOIN manual_mpesa_payments mmp ON p.id = mmp.payment_id\n"
                    . "WHERE p.lease_id = ?\n"
                    . "  AND p.payment_type = 'other'\n"
                    . "  AND p.status IN ('completed','verified')\n"
                    . "  AND (p.notes LIKE 'Maintenance payment:%' OR p.notes LIKE '%MAINT-%')\n"
                    . "  AND (\n"
                    . "        (p.applies_to_month IS NOT NULL AND p.applies_to_month BETWEEN ? AND ?)\n"
                    . "     OR (p.applies_to_month IS NULL AND p.payment_date BETWEEN ? AND ?)\n"
                    . "  )\n"
                    . "ORDER BY p.payment_date ASC, p.id ASC",
                    [$lease['id'], $start, $end, $start, $end]
                );

                // IMPORTANT: Allocate total rent paid sequentially from lease start month.
                // This prevents later invoices showing "Advance" while older invoices remain due.
                $rentAmount = (float)($lease['rent_amount'] ?? 0.0);
                $paidRent = 0.0;
                if ($rentAmount > 0.0) {
                    $leaseStartMonth = date('Y-m-01', strtotime((string)$lease['start_date']));
                    $invMonth = date('Y-m-01', strtotime($start));
                    $monthIndex = ((int)date('Y', strtotime($invMonth)) - (int)date('Y', strtotime($leaseStartMonth))) * 12
                        + ((int)date('n', strtotime($invMonth)) - (int)date('n', strtotime($leaseStartMonth)));
                    if ($monthIndex < 0) { $monthIndex = 0; }

                    $rentTotalPaidStmt = $payModel->query(
                        "SELECT COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) AS s
                         FROM payments
                         WHERE lease_id = ? AND payment_type = 'rent' AND status IN ('completed','verified')",
                        [$lease['id']]
                    );
                    $rentTotalPaid = (float)($rentTotalPaidStmt[0]['s'] ?? 0.0);

                    // Remaining rent amount available to settle this invoice month after settling previous months
                    $remainingForThisMonth = $rentTotalPaid - ($monthIndex * $rentAmount);
                    $paidRent = max(0.0, $remainingForThisMonth);
                }

                // Utilities are month-scoped using applies_to_month (fallback to payment_date).
                $paidUtilRows = $payModel->query(
                    "SELECT COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) AS s
                     FROM payments
                     WHERE lease_id = ? AND status IN ('completed','verified')
                       AND payment_type = 'utility'
                       AND (
                             (applies_to_month IS NOT NULL AND applies_to_month BETWEEN ? AND ?)
                          OR (applies_to_month IS NULL AND payment_date BETWEEN ? AND ?)
                       )",
                    [$lease['id'], $start, $end, $start, $end]
                );
                $paidUtil = (float)($paidUtilRows[0]['s'] ?? 0.0);

                // Maintenance payments are captured as payment_type='other' and should be month-scoped too.
                $paidMaintRows = $payModel->query(
                    "SELECT COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) AS s
                     FROM payments
                     WHERE lease_id = ? AND status IN ('completed','verified')
                       AND payment_type = 'other'
                       AND (
                             (applies_to_month IS NOT NULL AND applies_to_month BETWEEN ? AND ?)
                          OR (applies_to_month IS NULL AND payment_date BETWEEN ? AND ?)
                       )
                       AND (notes LIKE 'Maintenance payment:%' OR notes LIKE '%MAINT-%')",
                    [$lease['id'], $start, $end, $start, $end]
                );
                $paidMaint = (float)($paidMaintRows[0]['s'] ?? 0.0);
                $utilitiesTotal = 0.0;
                $maintenanceTotal = 0.0;
                foreach (($invoice['items'] ?? []) as $it) {
                    $desc = strtolower((string)($it['description'] ?? ''));
                    if (strpos($desc, 'utility') !== false) {
                        $utilitiesTotal += (float)($it['line_total'] ?? 0);
                    }
                    if (strpos($desc, 'maint') !== false) {
                        $maintenanceTotal += (float)($it['line_total'] ?? 0);
                    }
                }
                $utilitiesDue = max(0.0, $utilitiesTotal - $paidUtil);
                $maintenanceDue = max(0.0, $maintenanceTotal - $paidMaint);
                $rentPaidForMonth = ($rentAmount > 0.0) ? min($paidRent, $rentAmount) : 0.0;
                $rentStatus = 'due';
                if ($rentAmount > 0.0 && $rentPaidForMonth >= $rentAmount - 0.009) { $rentStatus = 'paid'; }
                else if ($rentPaidForMonth > 0.01) { $rentStatus = 'partial'; }
                $utilStatus = 'due';
                if ($utilitiesDue <= 0.009) { $utilStatus = 'paid'; }
                else if ($paidUtil > 0.01) { $utilStatus = 'partial'; }
                $maintStatus = 'due';
                if ($maintenanceDue <= 0.009) { $maintStatus = 'paid'; }
                else if ($paidMaint > 0.01) { $maintStatus = 'partial'; }
                $paymentStatus = [
                    'month_label' => $monthLabel,
                    'rent' => ['status' => $rentStatus, 'paid' => $rentPaidForMonth, 'amount' => $rentAmount],
                    'utilities' => ['status' => $utilStatus, 'paid' => $paidUtil, 'amount' => $utilitiesTotal, 'due' => $utilitiesDue],
                    'maintenance' => ['status' => $maintStatus, 'paid' => $paidMaint, 'amount' => $maintenanceTotal, 'due' => $maintenanceDue],
                ];
            }
        }

        $realtorContext = null;
        $displayNotes = $invoice['notes'] ?? null;
        $hidePostToLedger = !empty($invoice['posted_at']);

        if ($role === 'realtor' && empty($invoice['tenant_id'])) {
            $notes = (string)($invoice['notes'] ?? '');
            $db = $invModel->getDb();

            $clientName = '';
            $clientEmail = '';
            $clientPhone = '';
            $listingTitle = '';
            $listingLocation = '';

            $paymentId = 0;
            $manualClientId = 0;
            $manualListingId = 0;
            $contractId = 0;

            if (preg_match('/REALTOR_PAYMENT#(\d+)/', $notes, $m)) {
                $paymentId = (int)$m[1];
                if ($paymentId > 0) {
                    try {
                        $stmt = $db->prepare(
                            "SELECT rc.name AS client_name, rc.email AS client_email, rc.phone AS client_phone, rl.title AS listing_title, rl.location AS listing_location\n"
                            . "FROM payments p\n"
                            . "LEFT JOIN realtor_clients rc ON rc.id = p.realtor_client_id\n"
                            . "LEFT JOIN realtor_listings rl ON rl.id = p.realtor_listing_id\n"
                            . "WHERE p.id = ? AND p.realtor_user_id = ? LIMIT 1"
                        );
                        $stmt->execute([(int)$paymentId, (int)$this->userId]);
                        $rr = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
                        $clientName = (string)($rr['client_name'] ?? '');
                        $clientEmail = (string)($rr['client_email'] ?? '');
                        $clientPhone = (string)($rr['client_phone'] ?? '');
                        $listingTitle = (string)($rr['listing_title'] ?? '');
                        $listingLocation = (string)($rr['listing_location'] ?? '');
                    } catch (\Throwable $e) {
                    }
                }
            } elseif (preg_match('/REALTOR_MANUAL\s+client_id=(\d+)\s+listing_id=(\d+)/', $notes, $m)) {
                $manualClientId = (int)$m[1];
                $manualListingId = (int)$m[2];
                try {
                    if ($manualClientId > 0) {
                        $stmt = $db->prepare("SELECT name, email, phone FROM realtor_clients WHERE user_id = ? AND id = ? LIMIT 1");
                        $stmt->execute([(int)$this->userId, (int)$manualClientId]);
                        $rr = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
                        $clientName = (string)($rr['name'] ?? '');
                        $clientEmail = (string)($rr['email'] ?? '');
                        $clientPhone = (string)($rr['phone'] ?? '');
                    }
                } catch (\Throwable $e) {
                }
                try {
                    if ($manualListingId > 0) {
                        $stmt = $db->prepare("SELECT title, location FROM realtor_listings WHERE user_id = ? AND id = ? LIMIT 1");
                        $stmt->execute([(int)$this->userId, (int)$manualListingId]);
                        $rr = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
                        $listingTitle = (string)($rr['title'] ?? '');
                        $listingLocation = (string)($rr['location'] ?? '');
                    }
                } catch (\Throwable $e) {
                }
            } elseif (preg_match('/REALTOR_CONTRACT#(\d+)/', $notes, $m)) {
                $contractId = (int)$m[1];
                if ($contractId > 0) {
                    try {
                        $stmt = $db->prepare(
                            "SELECT rc.name AS client_name, rc.email AS client_email, rc.phone AS client_phone, rl.title AS listing_title, rl.location AS listing_location\n"
                            . "FROM realtor_contracts c\n"
                            . "LEFT JOIN realtor_clients rc ON rc.id = c.realtor_client_id\n"
                            . "LEFT JOIN realtor_listings rl ON rl.id = c.realtor_listing_id\n"
                            . "WHERE c.user_id = ? AND c.id = ? LIMIT 1"
                        );
                        $stmt->execute([(int)$this->userId, (int)$contractId]);
                        $rr = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
                        $clientName = (string)($rr['client_name'] ?? '');
                        $clientEmail = (string)($rr['client_email'] ?? '');
                        $clientPhone = (string)($rr['client_phone'] ?? '');
                        $listingTitle = (string)($rr['listing_title'] ?? '');
                        $listingLocation = (string)($rr['listing_location'] ?? '');
                    } catch (\Throwable $e) {
                    }
                }
            }

            if ($clientName !== '' || $clientEmail !== '' || $clientPhone !== '' || $listingTitle !== '' || $listingLocation !== '') {
                $realtorContext = [
                    'client_name' => $clientName,
                    'client_email' => $clientEmail,
                    'client_phone' => $clientPhone,
                    'listing_title' => $listingTitle,
                    'listing_location' => $listingLocation,
                ];
            }

            if ($contractId > 0) {
                $displayNotes = 'Contract #' . (int)$contractId;
                $suffix = [];
                if ($clientName !== '') $suffix[] = $clientName;
                if ($listingTitle !== '') $suffix[] = $listingTitle;
                if ($listingLocation !== '') $suffix[] = $listingLocation;
                if (!empty($suffix)) {
                    $displayNotes .= ' (' . implode(' / ', $suffix) . ')';
                }
                $displayNotes .= "\n" . 'REALTOR_CONTRACT#' . (int)$contractId;
            } elseif ($paymentId > 0) {
                $displayNotes = 'Auto-created from payment #' . (int)$paymentId;
                $suffix = [];
                if ($clientName !== '') $suffix[] = $clientName;
                if ($listingTitle !== '') $suffix[] = $listingTitle;
                if (!empty($suffix)) {
                    $displayNotes .= ' (' . implode(' / ', $suffix) . ')';
                }
            } elseif ($manualClientId > 0) {
                $displayNotes = 'Manual invoice';
                $suffix = [];
                if ($clientName !== '') $suffix[] = $clientName;
                if ($listingTitle !== '') $suffix[] = $listingTitle;
                if (!empty($suffix)) {
                    $displayNotes .= ' (' . implode(' / ', $suffix) . ')';
                }
            }
        }

        // Hide Post-to-ledger button if the ledger already has this invoice posted (or if it came from an already-posted payment)
        try {
            $ledger = new LedgerEntry();
            if ($ledger->referenceExists('invoice', (int)$invoice['id'])) {
                $hidePostToLedger = true;
            }
            if (!$hidePostToLedger) {
                $notesRaw = (string)($invoice['notes'] ?? '');
                if (preg_match('/REALTOR_PAYMENT#(\d+)/', $notesRaw, $m)) {
                    $pid = (int)$m[1];
                    if ($pid > 0 && $ledger->referenceExists('payment', $pid)) {
                        $hidePostToLedger = true;
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        // Make available to the view
        $paymentStatus = $paymentStatus;
        $maintenancePayments = $maintenancePayments;
        require 'views/invoices/show.php';
    }

    public function pdf($id)
    {
        $role = strtolower((string)($_SESSION['user_role'] ?? ''));
        $invModel = new Invoice();
        $invoice = $invModel->getWithItems((int)$id);
        if (!$invoice) { http_response_code(404); echo 'Invoice not found'; exit; }
        // Ensure Dompdf is available
        if (!class_exists('Dompdf\\Dompdf')) {
            require_once __DIR__ . '/../../vendor/dompdf/dompdf/src/Dompdf.php';
        }
        // Company settings and logo (allow per-user branding)
        $settingsModel = new \App\Models\Setting();
        $settings = $settingsModel->getAllAsAssoc();

        $siteName = $settings['site_name'] ?? 'RentSmart';
        $logoFilename = $settings['site_logo'] ?? 'site_logo_1751627446.png';

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $role = strtolower((string)($_SESSION['user_role'] ?? ''));
        if ($userId > 0 && in_array($role, ['manager', 'agent', 'landlord'], true)) {
            $companyNameKey = 'company_name_user_' . $userId;
            $companyLogoKey = 'company_logo_user_' . $userId;
            $companyName = trim((string)($settings[$companyNameKey] ?? ''));
            $companyLogo = trim((string)($settings[$companyLogoKey] ?? ''));
            if ($companyName !== '') {
                $siteName = $companyName;
            }
            if ($companyLogo !== '') {
                $logoFilename = $companyLogo;
            }
        }

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
        // Refresh status before generating
        if ($role !== 'realtor' && !empty($invoice['tenant_id']) && !empty($invoice['issue_date'])) {
            try { $invModel->updateStatusForTenantMonth((int)$invoice['tenant_id'], $invoice['issue_date']); } catch (\Exception $e) { error_log('Invoice status refresh (pdf) failed: ' . $e->getMessage()); }
            $invoice = $invModel->getWithItems((int)$id);
        }
        // Payment status summary (same as show)
        $paymentStatus = null;
        $maintenancePayments = [];
        if ($role !== 'realtor' && !empty($invoice['tenant_id']) && !empty($invoice['issue_date'])) {
            $leaseModel = new Lease();
            $lease = $leaseModel->getActiveLeaseByTenant((int)$invoice['tenant_id']);
            if ($lease) {
                $start = date('Y-m-01', strtotime($invoice['issue_date']));
                $end = date('Y-m-t', strtotime($invoice['issue_date']));
                $monthLabel = date('F Y', strtotime($start));
                $payModel = new \App\Models\Payment();

                $maintenancePayments = $payModel->query(
                    "SELECT p.id, p.amount, p.payment_date, p.applies_to_month, p.payment_method, p.status, p.notes, mmp.transaction_code\n"
                    . "FROM payments p\n"
                    . "LEFT JOIN manual_mpesa_payments mmp ON p.id = mmp.payment_id\n"
                    . "WHERE p.lease_id = ?\n"
                    . "  AND p.payment_type = 'other'\n"
                    . "  AND p.status IN ('completed','verified')\n"
                    . "  AND (p.notes LIKE 'Maintenance payment:%' OR p.notes LIKE '%MAINT-%')\n"
                    . "  AND (\n"
                    . "        (p.applies_to_month IS NOT NULL AND p.applies_to_month BETWEEN ? AND ?)\n"
                    . "     OR (p.applies_to_month IS NULL AND p.payment_date BETWEEN ? AND ?)\n"
                    . "  )\n"
                    . "ORDER BY p.payment_date ASC, p.id ASC",
                    [$lease['id'], $start, $end, $start, $end]
                );

                // IMPORTANT: Allocate total rent paid sequentially from lease start month.
                $rentAmount = (float)($lease['rent_amount'] ?? 0.0);
                $paidRent = 0.0;
                if ($rentAmount > 0.0) {
                    $leaseStartMonth = date('Y-m-01', strtotime((string)$lease['start_date']));
                    $invMonth = date('Y-m-01', strtotime($start));
                    $monthIndex = ((int)date('Y', strtotime($invMonth)) - (int)date('Y', strtotime($leaseStartMonth))) * 12
                        + ((int)date('n', strtotime($invMonth)) - (int)date('n', strtotime($leaseStartMonth)));
                    if ($monthIndex < 0) { $monthIndex = 0; }

                    $rentTotalPaidStmt = $payModel->query(
                        "SELECT COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) AS s
                         FROM payments
                         WHERE lease_id = ? AND payment_type = 'rent' AND status IN ('completed','verified')",
                        [$lease['id']]
                    );
                    $rentTotalPaid = (float)($rentTotalPaidStmt[0]['s'] ?? 0.0);
                    $remainingForThisMonth = $rentTotalPaid - ($monthIndex * $rentAmount);
                    $paidRent = max(0.0, $remainingForThisMonth);
                }

                // Utilities are month-scoped using applies_to_month (fallback to payment_date).
                $paidUtilRows = $payModel->query(
                    "SELECT COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) AS s
                     FROM payments
                     WHERE lease_id = ? AND status IN ('completed','verified')
                       AND payment_type = 'utility'
                       AND (
                             (applies_to_month IS NOT NULL AND applies_to_month BETWEEN ? AND ?)
                          OR (applies_to_month IS NULL AND payment_date BETWEEN ? AND ?)
                       )",
                    [$lease['id'], $start, $end, $start, $end]
                );
                $paidUtil = (float)($paidUtilRows[0]['s'] ?? 0.0);

                // Maintenance payments are captured as payment_type='other' and should be month-scoped too.
                $paidMaintRows = $payModel->query(
                    "SELECT COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) AS s
                     FROM payments
                     WHERE lease_id = ? AND status IN ('completed','verified')
                       AND payment_type = 'other'
                       AND (
                             (applies_to_month IS NOT NULL AND applies_to_month BETWEEN ? AND ?)
                          OR (applies_to_month IS NULL AND payment_date BETWEEN ? AND ?)
                       )
                       AND (notes LIKE 'Maintenance payment:%' OR notes LIKE '%MAINT-%')",
                    [$lease['id'], $start, $end, $start, $end]
                );
                $paidMaint = (float)($paidMaintRows[0]['s'] ?? 0.0);

                $rentPaidForMonth = ($rentAmount > 0.0) ? min($paidRent, $rentAmount) : 0.0;
                $rentStatus = 'due';
                if ($rentAmount > 0.0 && $rentPaidForMonth >= $rentAmount - 0.009) { $rentStatus = 'paid'; }
                else if ($rentPaidForMonth > 0.01) { $rentStatus = 'partial'; }

                $utilitiesTotal = 0.0;
                $maintenanceTotal = 0.0;
                foreach (($invoice['items'] ?? []) as $it) {
                    $desc = strtolower((string)($it['description'] ?? ''));
                    if (strpos($desc, 'utility') !== false) {
                        $utilitiesTotal += (float)($it['line_total'] ?? 0);
                    }
                    if (strpos($desc, 'maint') !== false) {
                        $maintenanceTotal += (float)($it['line_total'] ?? 0);
                    }
                }
                $utilitiesDue = max(0.0, $utilitiesTotal - $paidUtil);
                $maintenanceDue = max(0.0, $maintenanceTotal - $paidMaint);

                $utilStatus = 'due';
                if ($utilitiesDue <= 0.009) { $utilStatus = 'paid'; }
                else if ($paidUtil > 0.01) { $utilStatus = 'partial'; }

                $maintStatus = 'due';
                if ($maintenanceDue <= 0.009) { $maintStatus = 'paid'; }
                else if ($paidMaint > 0.01) { $maintStatus = 'partial'; }

                $paymentStatus = [
                    'month_label' => $monthLabel,
                    'rent' => ['status' => $rentStatus, 'paid' => $rentPaidForMonth, 'amount' => $rentAmount],
                    'utilities' => ['status' => $utilStatus, 'paid' => $paidUtil, 'amount' => $utilitiesTotal, 'due' => $utilitiesDue],
                    'maintenance' => ['status' => $maintStatus, 'paid' => $paidMaint, 'amount' => $maintenanceTotal, 'due' => $maintenanceDue],
                ];
            }
        }

        $realtorContext = null;
        if ($role === 'realtor' && empty($invoice['tenant_id'])) {
            $notes = (string)($invoice['notes'] ?? '');
            $db = $invModel->getDb();
            $clientName = '';
            $clientEmail = '';
            $listingTitle = '';
            $listingLocation = '';
            if (preg_match('/REALTOR_PAYMENT#(\d+)/', $notes, $m)) {
                $pid = (int)$m[1];
                if ($pid > 0) {
                    try {
                        $stmt = $db->prepare(
                            "SELECT rc.name AS client_name, rc.email AS client_email, rl.title AS listing_title, rl.location AS listing_location\n"
                            . "FROM payments p\n"
                            . "LEFT JOIN realtor_clients rc ON rc.id = p.realtor_client_id\n"
                            . "LEFT JOIN realtor_listings rl ON rl.id = p.realtor_listing_id\n"
                            . "WHERE p.id = ? AND p.realtor_user_id = ? LIMIT 1"
                        );
                        $stmt->execute([(int)$pid, (int)$this->userId]);
                        $rr = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
                        $clientName = (string)($rr['client_name'] ?? '');
                        $clientEmail = (string)($rr['client_email'] ?? '');
                        $listingTitle = (string)($rr['listing_title'] ?? '');
                        $listingLocation = (string)($rr['listing_location'] ?? '');
                    } catch (\Throwable $e) {
                    }
                }
            } elseif (preg_match('/REALTOR_MANUAL\s+client_id=(\d+)\s+listing_id=(\d+)/', $notes, $m)) {
                $cid = (int)$m[1];
                $lid = (int)$m[2];
                try {
                    if ($cid > 0) {
                        $stmt = $db->prepare("SELECT name, email FROM realtor_clients WHERE user_id = ? AND id = ? LIMIT 1");
                        $stmt->execute([(int)$this->userId, (int)$cid]);
                        $rr = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
                        $clientName = (string)($rr['name'] ?? '');
                        $clientEmail = (string)($rr['email'] ?? '');
                    }
                } catch (\Throwable $e) {
                }
                try {
                    if ($lid > 0) {
                        $stmt = $db->prepare("SELECT title, location FROM realtor_listings WHERE user_id = ? AND id = ? LIMIT 1");
                        $stmt->execute([(int)$this->userId, (int)$lid]);
                        $rr = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
                        $listingTitle = (string)($rr['title'] ?? '');
                        $listingLocation = (string)($rr['location'] ?? '');
                    }
                } catch (\Throwable $e) {
                }
            }

            $realtorContext = [
                'client_name' => $clientName,
                'client_email' => $clientEmail,
                'listing_title' => $listingTitle,
                'listing_location' => $listingLocation,
            ];
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
            $role = strtolower((string)($_SESSION['user_role'] ?? ''));
            if ($role !== 'realtor' && empty($invoice['tenant_email'])) throw new \Exception('Tenant has no email');

            // Build PDF HTML (allow per-user branding)
            $settingsModel = new \App\Models\Setting();
            $settings = $settingsModel->getAllAsAssoc();

            $siteName = $settings['site_name'] ?? 'RentSmart';
            $logoFilename = $settings['site_logo'] ?? 'site_logo_1751627446.png';

            $userId = (int)($_SESSION['user_id'] ?? 0);
            $role = strtolower((string)($_SESSION['user_role'] ?? ''));
            if ($userId > 0 && in_array($role, ['manager', 'agent', 'landlord'], true)) {
                $companyNameKey = 'company_name_user_' . $userId;
                $companyLogoKey = 'company_logo_user_' . $userId;
                $companyName = trim((string)($settings[$companyNameKey] ?? ''));
                $companyLogo = trim((string)($settings[$companyLogoKey] ?? ''));
                if ($companyName !== '') {
                    $siteName = $companyName;
                }
                if ($companyLogo !== '') {
                    $logoFilename = $companyLogo;
                }
            }

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
            // Ensure Dompdf is available
            if (!class_exists('Dompdf\\Dompdf')) {
                require_once __DIR__ . '/../../vendor/dompdf/dompdf/src/Dompdf.php';
            }
            // Refresh status before generating
            if ($role !== 'realtor' && !empty($invoice['tenant_id']) && !empty($invoice['issue_date'])) {
                try { $invModel->updateStatusForTenantMonth((int)$invoice['tenant_id'], $invoice['issue_date']); } catch (\Exception $e) { error_log('Invoice status refresh (email) failed: ' . $e->getMessage()); }
                $invoice = $invModel->getWithItems((int)$id);
            }

            $realtorContext = null;
            if ($role === 'realtor' && empty($invoice['tenant_id'])) {
                $notes = (string)($invoice['notes'] ?? '');
                $db = $invModel->getDb();
                $clientName = '';
                $clientEmail = '';
                $listingTitle = '';
                $listingLocation = '';
                if (preg_match('/REALTOR_PAYMENT#(\d+)/', $notes, $m)) {
                    $pid = (int)$m[1];
                    if ($pid > 0) {
                        try {
                            $stmt = $db->prepare(
                                "SELECT rc.name AS client_name, rc.email AS client_email, rl.title AS listing_title, rl.location AS listing_location\n"
                                . "FROM payments p\n"
                                . "LEFT JOIN realtor_clients rc ON rc.id = p.realtor_client_id\n"
                                . "LEFT JOIN realtor_listings rl ON rl.id = p.realtor_listing_id\n"
                                . "WHERE p.id = ? AND p.realtor_user_id = ? LIMIT 1"
                            );
                            $stmt->execute([(int)$pid, (int)$this->userId]);
                            $rr = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
                            $clientName = (string)($rr['client_name'] ?? '');
                            $clientEmail = (string)($rr['client_email'] ?? '');
                            $listingTitle = (string)($rr['listing_title'] ?? '');
                            $listingLocation = (string)($rr['listing_location'] ?? '');
                        } catch (\Throwable $e) {
                        }
                    }
                } elseif (preg_match('/REALTOR_MANUAL\s+client_id=(\d+)\s+listing_id=(\d+)/', $notes, $m)) {
                    $cid = (int)$m[1];
                    $lid = (int)$m[2];
                    try {
                        if ($cid > 0) {
                            $stmt = $db->prepare("SELECT name, email FROM realtor_clients WHERE user_id = ? AND id = ? LIMIT 1");
                            $stmt->execute([(int)$this->userId, (int)$cid]);
                            $rr = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
                            $clientName = (string)($rr['name'] ?? '');
                            $clientEmail = (string)($rr['email'] ?? '');
                        }
                    } catch (\Throwable $e) {
                    }
                    try {
                        if ($lid > 0) {
                            $stmt = $db->prepare("SELECT title, location FROM realtor_listings WHERE user_id = ? AND id = ? LIMIT 1");
                            $stmt->execute([(int)$this->userId, (int)$lid]);
                            $rr = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
                            $listingTitle = (string)($rr['title'] ?? '');
                            $listingLocation = (string)($rr['location'] ?? '');
                        }
                    } catch (\Throwable $e) {
                    }
                }
                $realtorContext = [
                    'client_name' => $clientName,
                    'client_email' => $clientEmail,
                    'listing_title' => $listingTitle,
                    'listing_location' => $listingLocation,
                ];
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
            if (!is_string($pdfData) || strlen($pdfData) < 100) {
                throw new \Exception('Failed to generate invoice PDF');
            }

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
            $toEmail = $invoice['tenant_email'] ?? '';
            $toName = $invoice['tenant_name'] ?? 'Tenant';
            if ($role === 'realtor' && $realtorContext) {
                $toEmail = (string)($realtorContext['client_email'] ?? '');
                $toName = (string)($realtorContext['client_name'] ?? 'Client');
            }
            if ($toEmail === '') {
                throw new \Exception('Customer has no email');
            }
            $mail->addAddress($toEmail, $toName);
            $mail->Subject = 'Invoice ' . ($invoice['number'] ?? ('#' . $invoice['id']));
            $mail->isHTML(true);
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $base = defined('BASE_URL') ? BASE_URL : '';
            $siteUrl = rtrim((string)($settings['site_url'] ?? ($scheme . '://' . $host . $base)), '/');
            $logoForEmail = $logoFilename ?? ($settings['site_logo'] ?? '');
            $logoUrl = !empty($logoForEmail) ? ($siteUrl . '/public/assets/images/' . $logoForEmail) : '';
            $footer = '<div style="margin-top:30px;font-size:12px;color:#888;text-align:center;">Powered by <a href="https://timestentechnologies.co.ke" target="_blank" style="color:#888;text-decoration:none;">Timesten Technologies</a></div>';
            $custName = ($role === 'realtor' && $realtorContext) ? ($realtorContext['client_name'] ?? 'Customer') : ($invoice['tenant_name'] ?? 'Customer');
            $plain = 'Dear ' . $custName . ",\n\nPlease find attached your invoice " . ($invoice['number'] ?? ('#' . $invoice['id'])) . ".\nTotal Due: Ksh " . number_format((float)$invoice['total'], 2) . "\nDue Date: " . ($invoice['due_date'] ?? '-') . "\n\nRegards,\n" . $siteName;
            $html =
                '<div style="max-width:520px;margin:auto;border:1px solid #eee;padding:24px;font-family:sans-serif;">'
                . ($logoUrl ? '<div style="text-align:center;margin-bottom:24px;"><img src="' . $logoUrl . '" alt="Logo" style="max-width:180px;max-height:80px;"></div>' : '') .
                '<p style="font-size:16px;">Dear ' . htmlspecialchars($custName) . ',</p>' .
                '<p>Please find attached your invoice ' . htmlspecialchars($invoice['number'] ?? ('#' . $invoice['id'])) . '.</p>' .
                '<p><strong>Total Due:</strong> Ksh ' . number_format((float)$invoice['total'], 2) . '</p>' .
                '<p><strong>Due Date:</strong> ' . htmlspecialchars($invoice['due_date'] ?? '-') . '</p>' .
                '<p>Regards,<br>' . htmlspecialchars($siteName) . '</p>' .
                $footer .
                '</div>';
            $mail->Body = $html;
            $mail->AltBody = $plain;

            $tmpFile = tempnam(sys_get_temp_dir(), 'inv_');
            if (!$tmpFile) {
                $mail->addStringAttachment($pdfData, $filename, 'base64', 'application/pdf');
            } else {
                $pdfPath = $tmpFile . '.pdf';
                @rename($tmpFile, $pdfPath);
                file_put_contents($pdfPath, $pdfData);
                $mail->addAttachment($pdfPath, $filename, 'base64', 'application/pdf');
            }

            $mail->send();

            if (isset($pdfPath) && is_string($pdfPath) && file_exists($pdfPath)) {
                @unlink($pdfPath);
            } else if (isset($tmpFile) && is_string($tmpFile) && file_exists($tmpFile)) {
                @unlink($tmpFile);
            }

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

    public function unarchive($id)
    {
        try {
            $invModel = new Invoice();
            $invModel->unarchiveInvoice((int)$id);
            $_SESSION['flash_message'] = 'Invoice unarchived';
            $_SESSION['flash_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = 'Unarchive failed';
            $_SESSION['flash_type'] = 'danger';
        }
        header('Location: ' . BASE_URL . '/invoices');
        exit;
    }

    public function unvoid($id)
    {
        try {
            $status = isset($_GET['status']) ? trim((string)$_GET['status']) : 'sent';
            if ($status === '') { $status = 'sent'; }
            $allowed = ['draft','sent','partial','paid'];
            if (!in_array($status, $allowed, true)) { $status = 'sent'; }
            $invModel = new Invoice();
            $invModel->unvoidInvoice((int)$id, $status);
            $_SESSION['flash_message'] = 'Invoice restored';
            $_SESSION['flash_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = 'Restore failed';
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
