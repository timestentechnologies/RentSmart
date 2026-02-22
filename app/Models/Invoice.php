<?php

namespace App\Models;

class Invoice extends Model
{
    protected $table = 'invoices';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTables();
    }

    /**
     * Ensure invoices exist for each month from start to end (or now if end null), capped to 36 months.
     */
    public function ensureInvoicesForLeaseMonths(int $tenantId, float $rentAmount, string $startDate, ?string $endDate = null, ?int $userId = null, ?string $noteTag = 'AUTO')
    {
        if (empty($tenantId) || $rentAmount <= 0 || empty($startDate)) return;

        $startDate = trim((string)$startDate);
        $startTs = strtotime($startDate);
        if ($startDate === '' || $startTs === false) {
            $startTs = time();
        }

        // Prevent accidental backfilling from epoch/ancient dates due to empty strings.
        // Only clamp clearly-invalid/ancient timestamps (e.g. 1970-01-01).
        $minTs = strtotime('1980-01-01');
        if ($startTs !== false && $startTs < $minTs) {
            $startTs = time();
        }

        $start = new \DateTime(date('Y-m-01', $startTs));
        $endBoundary = $endDate ? new \DateTime($endDate) : new \DateTime();
        // Use the month of end boundary
        $end = new \DateTime(date('Y-m-01', $endBoundary->getTimestamp()));
        $months = 0;
        while ($start <= $end && $months < 36) {
            $issueDate = $start->format('Y-m-01');
            $this->ensureMonthlyRentInvoice($tenantId, $issueDate, $rentAmount, $userId, $noteTag);
            try {
                $this->syncMaintenanceChargesForTenantMonth($tenantId, $issueDate);
                $this->syncUtilityChargesForTenantMonth($tenantId, $issueDate);
            } catch (\Exception $e) {
                error_log('Sync maintenance charges failed: ' . $e->getMessage());
            }
            $start->modify('+1 month');
            $months++;
        }
    }

    private function ensureTables()
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS invoices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            number VARCHAR(50) NOT NULL UNIQUE,
            tenant_id INT NULL,
            issue_date DATE NOT NULL,
            due_date DATE NULL,
            status ENUM('draft','sent','paid','void') NOT NULL DEFAULT 'sent',
            notes TEXT NULL,
            subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            tax_rate DECIMAL(5,2) NULL,
            tax_amount DECIMAL(15,2) NULL,
            total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            posted_at DATETIME NULL,
            archived_at DATETIME NULL,
            user_id INT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_tenant (tenant_id),
            INDEX idx_issue_date (issue_date),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $this->db->exec("CREATE TABLE IF NOT EXISTS invoice_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_id INT NOT NULL,
            description VARCHAR(255) NOT NULL,
            quantity DECIMAL(15,2) NOT NULL DEFAULT 1.00,
            unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            line_total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_invoice (invoice_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        // Ensure 'partial' status exists in enum (safe no-op if already present)
        try {
            $this->db->exec("ALTER TABLE invoices MODIFY status ENUM('draft','sent','partial','paid','void') NOT NULL DEFAULT 'sent'");
        } catch (\Exception $e) {}

        // Ensure archived_at exists (for older deployments)
        try {
            $this->db->exec("ALTER TABLE invoices ADD COLUMN archived_at DATETIME NULL");
        } catch (\Exception $e) {}
        try {
            $this->db->exec("ALTER TABLE invoices ADD INDEX idx_archived_at (archived_at)");
        } catch (\Exception $e) {}
    }

    public function generateNumber(?string $issueDate = null)
    {
        $prefix = 'INV-' . date('Ymd', $issueDate ? strtotime($issueDate) : time()) . '-';
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE number LIKE ?");
        $stmt->execute([$prefix . '%']);
        $count = (int)$stmt->fetchColumn();
        return $prefix . str_pad((string)($count + 1), 3, '0', STR_PAD_LEFT);
    }

    public function createInvoice(array $data, array $items)
    {
        $this->db->beginTransaction();
        try {
            $number = $this->generateNumber($data['issue_date'] ?? null);
            $subtotal = 0.0;
            foreach ($items as $it) {
                $qty = (float)($it['quantity'] ?? 1);
                $price = (float)($it['unit_price'] ?? 0);
                $subtotal += $qty * $price;
            }
            $taxRate = isset($data['tax_rate']) ? (float)$data['tax_rate'] : null;
            $taxAmount = $taxRate !== null ? round($subtotal * ($taxRate/100), 2) : 0.0;
            $total = $subtotal + $taxAmount;

            $stmt = $this->db->prepare("INSERT INTO {$this->table} (number, tenant_id, issue_date, due_date, status, notes, subtotal, tax_rate, tax_amount, total, user_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $number,
                $data['tenant_id'] ?? null,
                $data['issue_date'] ?? date('Y-m-d'),
                $data['due_date'] ?? null,
                $data['status'] ?? 'sent',
                $data['notes'] ?? null,
                $subtotal,
                $taxRate,
                $taxAmount,
                $total,
                $data['user_id'] ?? null
            ]);
            $invoiceId = (int)$this->db->lastInsertId();

            $ins = $this->db->prepare("INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, line_total) VALUES (?,?,?,?,?)");
            foreach ($items as $it) {
                $qty = (float)($it['quantity'] ?? 1);
                $price = (float)($it['unit_price'] ?? 0);
                $line = round($qty * $price, 2);
                if ($line <= 0) continue;
                $ins->execute([$invoiceId, $it['description'] ?? 'Item', $qty, $price, $line]);
            }

            $this->db->commit();
            return $invoiceId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function ensureRealtorContractInvoice(int $userId, int $contractId, int $clientId, int $listingId, float $totalAmount, ?string $issueDate = null): int
    {
        if ($userId <= 0 || $contractId <= 0 || $totalAmount <= 0) return 0;
        $tag = 'REALTOR_CONTRACT#' . (int)$contractId;
        try {
            $chk = $this->db->prepare("SELECT id FROM {$this->table} WHERE user_id = ? AND notes LIKE ? ORDER BY id DESC LIMIT 1");
            $chk->execute([(int)$userId, '%' . $tag . '%']);
            $row = $chk->fetch(\PDO::FETCH_ASSOC);
            if (!empty($row['id'])) {
                return (int)$row['id'];
            }
        } catch (\Exception $e) {
        }

        $clientName = '';
        $listingTitle = '';
        try {
            if ($clientId > 0) {
                $s = $this->db->prepare("SELECT name FROM realtor_clients WHERE id = ? LIMIT 1");
                $s->execute([(int)$clientId]);
                $clientName = (string)($s->fetch(\PDO::FETCH_ASSOC)['name'] ?? '');
            }
        } catch (\Exception $e) {
        }
        try {
            if ($listingId > 0) {
                $s = $this->db->prepare("SELECT title FROM realtor_listings WHERE id = ? LIMIT 1");
                $s->execute([(int)$listingId]);
                $listingTitle = (string)($s->fetch(\PDO::FETCH_ASSOC)['title'] ?? '');
            }
        } catch (\Exception $e) {
        }

        $desc = 'Contract Invoice';
        $suffixParts = [];
        $suffixParts[] = 'Contract #' . (int)$contractId;
        if ($clientName !== '') $suffixParts[] = $clientName;
        if ($listingTitle !== '') $suffixParts[] = $listingTitle;
        if (!empty($suffixParts)) {
            $desc .= ' - ' . implode(' / ', $suffixParts);
        }

        $issue = $issueDate ? (string)$issueDate : date('Y-m-d');
        try {
            return (int)$this->createInvoice([
                'tenant_id' => null,
                'issue_date' => $issue,
                'due_date' => null,
                'status' => 'sent',
                'notes' => trim($tag . ' client_id=' . (int)$clientId . ' listing_id=' . (int)$listingId),
                'user_id' => (int)$userId,
            ], [[
                'description' => $desc,
                'quantity' => 1,
                'unit_price' => (float)$totalAmount,
            ]]);
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function updateStatusForRealtorContract(int $userId, int $contractId): ?string
    {
        if ($userId <= 0 || $contractId <= 0) return null;
        $tag = 'REALTOR_CONTRACT#' . (int)$contractId;

        $inv = null;
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = ? AND notes LIKE ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([(int)$userId, '%' . $tag . '%']);
            $inv = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $inv = null;
        }
        if (!$inv || empty($inv['id'])) return null;

        $total = (float)($inv['total'] ?? 0);
        if ($total <= 0) return null;

        $paid = 0.0;
        try {
            $pStmt = $this->db->prepare(
                "SELECT COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) AS s\n"
                . "FROM payments\n"
                . "WHERE realtor_user_id = ? AND realtor_contract_id = ? AND status IN ('completed','verified')"
            );
            $pStmt->execute([(int)$userId, (int)$contractId]);
            $paid = (float)(($pStmt->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0));
        } catch (\Exception $e) {
            $paid = 0.0;
        }

        $newStatus = null;
        if ($paid + 0.00001 >= $total) {
            $newStatus = 'paid';
        } elseif ($paid > 0.01) {
            $newStatus = 'partial';
        } else {
            $newStatus = ($inv['status'] === 'draft') ? 'draft' : 'sent';
        }

        if ((string)($inv['status'] ?? '') !== $newStatus) {
            try {
                $upd = $this->db->prepare("UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?");
                $upd->execute([(string)$newStatus, (int)$inv['id']]);
            } catch (\Exception $e) {
            }
        }
        return $newStatus;
    }

    public function recalculateTotals(int $invoiceId): void
    {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(line_total),0) AS s FROM invoice_items WHERE invoice_id = ?");
        $stmt->execute([(int)$invoiceId]);
        $subtotal = (float)($stmt->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);

        $taxStmt = $this->db->prepare("SELECT tax_rate FROM {$this->table} WHERE id = ? LIMIT 1");
        $taxStmt->execute([(int)$invoiceId]);
        $taxRateRaw = $taxStmt->fetch(\PDO::FETCH_ASSOC);
        $taxRate = isset($taxRateRaw['tax_rate']) ? (float)$taxRateRaw['tax_rate'] : null;
        $taxAmount = $taxRate !== null ? round($subtotal * ($taxRate / 100), 2) : 0.0;
        $total = $subtotal + $taxAmount;

        $upd = $this->db->prepare("UPDATE {$this->table} SET subtotal = ?, tax_amount = ?, total = ?, updated_at = NOW() WHERE id = ?");
        $upd->execute([$subtotal, $taxAmount, $total, (int)$invoiceId]);
    }

    public function syncMaintenanceChargesForTenantMonth(int $tenantId, string $issueDate): void
    {
        $inv = $this->findByTenantAndIssueMonth($tenantId, $issueDate);
        if (!$inv || empty($inv['id'])) return;

        $leaseStmt = $this->db->prepare("SELECT id FROM leases WHERE tenant_id = ? AND status = 'active' LIMIT 1");
        $leaseStmt->execute([(int)$tenantId]);
        $leaseId = (int)($leaseStmt->fetch(\PDO::FETCH_ASSOC)['id'] ?? 0);
        if ($leaseId <= 0) return;

        $start = date('Y-m-01', strtotime($issueDate));
        $end = date('Y-m-t', strtotime($issueDate));

        $pStmt = $this->db->prepare(
            "SELECT id, amount, payment_date, notes\n"
            . "FROM payments\n"
            . "WHERE lease_id = ?\n"
            . "  AND payment_type = 'rent'\n"
            . "  AND amount < 0\n"
            . "  AND status IN ('completed','verified')\n"
            . "  AND payment_date BETWEEN ? AND ?\n"
            . "  AND notes LIKE ?\n"
            . "ORDER BY payment_date ASC, id ASC"
        );
        $pStmt->execute([(int)$leaseId, $start, $end, '%MAINT-%']);
        $rows = $pStmt->fetchAll(\PDO::FETCH_ASSOC);
        if (empty($rows)) return;

        $ins = $this->db->prepare("INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, line_total) VALUES (?,?,?,?,?)");
        $addedAny = false;
        foreach ($rows as $r) {
            $notes = (string)($r['notes'] ?? '');
            if ($notes === '') continue;

            $tag = null;
            if (preg_match('/(MAINT-\d+)/i', $notes, $m)) {
                $tag = strtoupper($m[1]);
            }
            $desc = $tag ? ('Maintenance Charge - ' . $tag) : ('Maintenance Charge - Payment #' . (int)$r['id']);

            $chk = $this->db->prepare("SELECT id FROM invoice_items WHERE invoice_id = ? AND description LIKE ? LIMIT 1");
            $chk->execute([(int)$inv['id'], '%' . $desc . '%']);
            $exists = $chk->fetch(\PDO::FETCH_ASSOC);
            if ($exists) continue;

            $amount = abs((float)($r['amount'] ?? 0));
            if ($amount <= 0) continue;
            $line = round($amount, 2);
            $ins->execute([(int)$inv['id'], $desc, 1, $line, $line]);
            $addedAny = true;
        }

        if ($addedAny) {
            $this->recalculateTotals((int)$inv['id']);
        }
    }

    public function syncUtilityChargesForTenantMonth(int $tenantId, string $issueDate): void
    {
        $inv = $this->findByTenantAndIssueMonth($tenantId, $issueDate);
        if (!$inv || empty($inv['id'])) return;

        $leaseStmt = $this->db->prepare("SELECT id, unit_id FROM leases WHERE tenant_id = ? AND status = 'active' LIMIT 1");
        $leaseStmt->execute([(int)$tenantId]);
        $lease = $leaseStmt->fetch(\PDO::FETCH_ASSOC);
        $leaseId = (int)($lease['id'] ?? 0);
        $unitId = (int)($lease['unit_id'] ?? 0);
        if ($leaseId <= 0 || $unitId <= 0) return;

        $start = date('Y-m-01', strtotime($issueDate));
        $end = date('Y-m-t', strtotime($issueDate));
        $ym = date('Ym', strtotime($issueDate));

        $uStmt = $this->db->prepare("SELECT id, utility_type, is_metered, flat_rate FROM utilities WHERE unit_id = ? ORDER BY utility_type");
        $uStmt->execute([(int)$unitId]);
        $utils = $uStmt->fetchAll(\PDO::FETCH_ASSOC);
        if (empty($utils)) return;

        $ins = $this->db->prepare("INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, line_total) VALUES (?,?,?,?,?)");
        $addedAny = false;

        foreach ($utils as $u) {
            $utilityId = (int)($u['id'] ?? 0);
            if ($utilityId <= 0) continue;
            $type = (string)($u['utility_type'] ?? 'utility');
            $isMetered = !empty($u['is_metered']);

            $charge = 0.0;
            if ($isMetered) {
                $rStmt = $this->db->prepare(
                    "SELECT cost FROM utility_readings\n"
                    . "WHERE utility_id = ? AND reading_date BETWEEN ? AND ?\n"
                    . "ORDER BY reading_date DESC, id DESC LIMIT 1"
                );
                $rStmt->execute([$utilityId, $start, $end]);
                $charge = (float)($rStmt->fetch(\PDO::FETCH_ASSOC)['cost'] ?? 0);
            } else {
                $charge = (float)($u['flat_rate'] ?? 0);
            }

            if ($charge <= 0) continue;

            $tag = 'UTIL-' . $utilityId . '-' . $ym;
            $desc = 'Utility - ' . ucfirst($type) . ' - ' . $tag;

            $chk = $this->db->prepare("SELECT id FROM invoice_items WHERE invoice_id = ? AND description LIKE ? LIMIT 1");
            $chk->execute([(int)$inv['id'], '%' . $tag . '%']);
            $exists = $chk->fetch(\PDO::FETCH_ASSOC);
            if ($exists) continue;

            $line = round($charge, 2);
            $ins->execute([(int)$inv['id'], $desc, 1, $line, $line]);
            $addedAny = true;
        }

        if ($addedAny) {
            $this->recalculateTotals((int)$inv['id']);
        }
    }

    public function getAll($userId = null)
    {
        return $this->search([
            'visibility' => 'active',
        ], $userId);
    }

    public function search(array $filters = [], $userId = null)
    {
        $sql = "SELECT i.*, t.name AS tenant_name
                FROM {$this->table} i
                LEFT JOIN tenants t ON i.tenant_id = t.id";
        $where = [];
        $params = [];

        if (!empty($userId)) {
            $where[] = "(i.user_id = ? OR i.user_id IS NULL)";
            $params[] = (int)$userId;
        }

        $visibility = $filters['visibility'] ?? 'active';
        if ($visibility === 'archived') {
            $where[] = "i.archived_at IS NOT NULL";
        } elseif ($visibility === 'all') {
            // no filter
        } else {
            $where[] = "i.archived_at IS NULL";
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $where[] = "i.status = ?";
            $params[] = (string)$filters['status'];
        }

        if (!empty($filters['tenant_id'])) {
            $where[] = "i.tenant_id = ?";
            $params[] = (int)$filters['tenant_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "i.issue_date >= ?";
            $params[] = (string)$filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "i.issue_date <= ?";
            $params[] = (string)$filters['date_to'];
        }

        if (!empty($filters['q'])) {
            $q = '%' . trim((string)$filters['q']) . '%';
            $where[] = "(i.number LIKE ? OR t.name LIKE ? OR i.notes LIKE ?)";
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY i.issue_date DESC, i.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getWithItems($id)
    {
        $stmt = $this->db->prepare("SELECT i.*, t.name AS tenant_name, t.email AS tenant_email
                                     FROM {$this->table} i
                                     LEFT JOIN tenants t ON i.tenant_id = t.id
                                     WHERE i.id = ? LIMIT 1");
        $stmt->execute([(int)$id]);
        $invoice = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$invoice) return null;
        $it = $this->db->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id ASC");
        $it->execute([(int)$id]);
        $invoice['items'] = $it->fetchAll(\PDO::FETCH_ASSOC);
        return $invoice;
    }

    public function listForTenant(int $tenantId, int $limit = 10)
    {
        $limit = max(1, min(100, (int)$limit));
        $stmt = $this->db->prepare("SELECT id, number, issue_date, due_date, status, total
                                    FROM {$this->table}
                                    WHERE tenant_id = ?
                                    ORDER BY issue_date DESC, id DESC
                                    LIMIT {$limit}");
        $stmt->execute([(int)$tenantId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function markPosted($id)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET posted_at = NOW() WHERE id = ?");
        return $stmt->execute([(int)$id]);
    }

    public function deleteInvoice($id)
    {
        $this->db->beginTransaction();
        try {
            $del = $this->db->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
            $del->execute([(int)$id]);
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
            $stmt->execute([(int)$id]);
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function voidInvoice($id)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = 'void', updated_at = NOW() WHERE id = ?");
        return $stmt->execute([(int)$id]);
    }

    public function archiveInvoice($id)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET archived_at = NOW(), updated_at = NOW() WHERE id = ?");
        return $stmt->execute([(int)$id]);
    }

    public function voidAndArchive($id)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = 'void', archived_at = NOW(), updated_at = NOW() WHERE id = ?");
        return $stmt->execute([(int)$id]);
    }

    public function unarchiveInvoice($id)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET archived_at = NULL, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([(int)$id]);
    }

    public function unvoidInvoice($id, ?string $restoreStatus = 'sent')
    {
        $restoreStatus = $restoreStatus ?: 'sent';
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([(string)$restoreStatus, (int)$id]);
    }

    /**
     * Find an existing invoice for a tenant within the same issue month
     */
    public function findByTenantAndIssueMonth(int $tenantId, string $issueDate)
    {
        $start = date('Y-m-01', strtotime($issueDate));
        $end = date('Y-m-t', strtotime($issueDate));
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE tenant_id = ? AND issue_date BETWEEN ? AND ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$tenantId, $start, $end]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Ensure a monthly rent invoice exists for the tenant for the given issue date.
     * Creates one with a single line item if missing. Returns the invoice id.
     */
    public function ensureMonthlyRentInvoice(int $tenantId, string $issueDate, float $rentAmount, ?int $userId = null, ?string $noteTag = null)
    {
        $existing = $this->findByTenantAndIssueMonth($tenantId, $issueDate);
        if ($existing && (!empty($existing['id']))) {
            try {
                $this->syncMaintenanceChargesForTenantMonth($tenantId, $issueDate);
                $this->syncUtilityChargesForTenantMonth($tenantId, $issueDate);
            } catch (\Exception $e) {
                error_log('Sync maintenance charges (existing) failed: ' . $e->getMessage());
            }
            return (int)$existing['id'];
        }
        $monthLabel = date('F Y', strtotime($issueDate));
        $items = [[
            'description' => 'Monthly Rent - ' . $monthLabel,
            'quantity' => 1,
            'unit_price' => $rentAmount,
        ]];
        $invoiceId = (int)$this->createInvoice([
            'tenant_id' => $tenantId,
            'issue_date' => $issueDate,
            'due_date' => $issueDate,
            'status' => 'draft',
            'notes' => trim(($noteTag ? ($noteTag . ' ') : '') . 'Auto-created draft rent invoice for ' . $monthLabel),
            'user_id' => $userId,
        ], $items);
        try {
            $this->syncMaintenanceChargesForTenantMonth($tenantId, $issueDate);
            $this->syncUtilityChargesForTenantMonth($tenantId, $issueDate);
        } catch (\Exception $e) {
            error_log('Sync maintenance charges (new) failed: ' . $e->getMessage());
        }
        return $invoiceId;
    }

    /**
     * Update invoice status for a tenant's invoice in the given issue month based on rent payments in that month.
     * Marks as 'paid' if monthly payments >= invoice total, otherwise 'sent'. Returns the new status or null if none.
     */
    public function updateStatusForTenantMonth(int $tenantId, string $issueDate)
    {
        $inv = $this->findByTenantAndIssueMonth($tenantId, $issueDate);
        if (!$inv) return null;
        // IMPORTANT: handle advance rent. We allocate total rent paid sequentially from lease start month.
        $leaseStmt = $this->db->prepare("SELECT id, start_date, rent_amount FROM leases WHERE tenant_id = ? AND status = 'active' LIMIT 1");
        $leaseStmt->execute([(int)$tenantId]);
        $lease = $leaseStmt->fetch(\PDO::FETCH_ASSOC);
        $rent = (float)($lease['rent_amount'] ?? 0);
        $total = (float)$inv['total'];
        if (!$lease || $rent <= 0 || $total <= 0) {
            $newStatus = ($inv['status'] === 'draft') ? 'draft' : 'sent';
        } else {
            $startMonth = date('Y-m-01', strtotime($lease['start_date']));
            $invMonth = date('Y-m-01', strtotime($inv['issue_date']));
            $monthIndex = ((int)date('Y', strtotime($invMonth)) - (int)date('Y', strtotime($startMonth))) * 12
                + ((int)date('n', strtotime($invMonth)) - (int)date('n', strtotime($startMonth)));
            if ($monthIndex < 0) { $monthIndex = 0; }

            // Only positive payments count toward rent coverage; negative rows are adjustments/discounts.
            $payStmt = $this->db->prepare("SELECT COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) AS s FROM payments WHERE lease_id = ? AND payment_type = 'rent' AND status IN ('completed','verified')");
            $payStmt->execute([(int)$lease['id']]);
            $paidTotal = (float)(($payStmt->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0));

            $monthStart = date('Y-m-01', strtotime($inv['issue_date']));
            $monthEnd = date('Y-m-t', strtotime($inv['issue_date']));
            $utilStmt = $this->db->prepare(
                "SELECT COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) AS s\n"
                . "FROM payments\n"
                . "WHERE lease_id = ?\n"
                . "  AND payment_type = 'utility'\n"
                . "  AND status IN ('completed','verified')\n"
                . "  AND (\n"
                . "        (applies_to_month IS NOT NULL AND applies_to_month BETWEEN ? AND ?)\n"
                . "     OR (applies_to_month IS NULL AND payment_date BETWEEN ? AND ?)\n"
                . "  )"
            );
            $utilStmt->execute([(int)$lease['id'], $monthStart, $monthEnd, $monthStart, $monthEnd]);
            $utilityPaidInMonth = (float)(($utilStmt->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0));

            // Maintenance payments are captured as payment_type='other' and should count towards settling invoice items.
            $maintStmt = $this->db->prepare(
                "SELECT COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) AS s\n"
                . "FROM payments\n"
                . "WHERE lease_id = ?\n"
                . "  AND payment_type = 'other'\n"
                . "  AND status IN ('completed','verified')\n"
                . "  AND (\n"
                . "        (applies_to_month IS NOT NULL AND applies_to_month BETWEEN ? AND ?)\n"
                . "     OR (applies_to_month IS NULL AND payment_date BETWEEN ? AND ?)\n"
                . "  )\n"
                . "  AND (notes LIKE 'Maintenance payment:%' OR notes LIKE '%MAINT-%')"
            );
            $maintStmt->execute([(int)$lease['id'], $monthStart, $monthEnd, $monthStart, $monthEnd]);
            $maintenancePaidInMonth = (float)((($maintStmt->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0)));

            $remainingForThisMonth = $paidTotal - ($monthIndex * $rent);
            $availableToSettle = $remainingForThisMonth + $utilityPaidInMonth + $maintenancePaidInMonth;
            if ($availableToSettle + 1e-6 >= $total) {
                $newStatus = 'paid';
            } elseif ($availableToSettle > 0.01) {
                $newStatus = 'partial';
            } else {
                $newStatus = ($inv['status'] === 'draft') ? 'draft' : 'sent';
            }
        }
        if ($inv['status'] !== $newStatus) {
            $upd = $this->db->prepare("UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?");
            $upd->execute([$newStatus, (int)$inv['id']]);
        }
        return $newStatus;
    }

    /**
     * Batch: Update statuses for all invoices in the month of the given date.
     */
    public function updateStatusesForMonth(string $anyDateInMonth)
    {
        $first = date('Y-m-01', strtotime($anyDateInMonth));
        $last = date('Y-m-t', strtotime($anyDateInMonth));
        $stmt = $this->db->prepare("SELECT id, tenant_id, total, status, issue_date FROM {$this->table} WHERE issue_date BETWEEN ? AND ?");
        $stmt->execute([$first, $last]);
        $invoices = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($invoices as $inv) {
            $this->updateStatusForTenantMonth((int)$inv['tenant_id'], $inv['issue_date']);
        }
    }
}
