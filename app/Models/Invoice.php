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
        $start = new \DateTime(date('Y-m-01', strtotime($startDate)));
        $endBoundary = $endDate ? new \DateTime($endDate) : new \DateTime();
        // Use the month of end boundary
        $end = new \DateTime(date('Y-m-01', $endBoundary->getTimestamp()));
        $months = 0;
        while ($start <= $end && $months < 36) {
            $this->ensureMonthlyRentInvoice($tenantId, $start->format('Y-m-01'), $rentAmount, $userId, $noteTag);
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

    public function getAll($userId = null)
    {
        $sql = "SELECT i.*, t.name AS tenant_name
                FROM {$this->table} i
                LEFT JOIN tenants t ON i.tenant_id = t.id
                ORDER BY i.created_at DESC";
        return $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
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
            return (int)$existing['id'];
        }
        $monthLabel = date('F Y', strtotime($issueDate));
        $items = [[
            'description' => 'Monthly Rent - ' . $monthLabel,
            'quantity' => 1,
            'unit_price' => $rentAmount,
        ]];
        return (int)$this->createInvoice([
            'tenant_id' => $tenantId,
            'issue_date' => $issueDate,
            'due_date' => $issueDate,
            'status' => 'sent',
            'notes' => trim(($noteTag ? ($noteTag . ' ') : '') . 'Auto-created monthly rent invoice for ' . $monthLabel),
            'user_id' => $userId,
        ], $items);
    }

    /**
     * Update invoice status for a tenant's invoice in the given issue month based on rent payments in that month.
     * Marks as 'paid' if monthly payments >= invoice total, otherwise 'sent'. Returns the new status or null if none.
     */
    public function updateStatusForTenantMonth(int $tenantId, string $issueDate)
    {
        $inv = $this->findByTenantAndIssueMonth($tenantId, $issueDate);
        if (!$inv) return null;
        $first = date('Y-m-01', strtotime($inv['issue_date']));
        $last = date('Y-m-t', strtotime($inv['issue_date']));
        $sql = "SELECT COALESCE(SUM(p.amount),0) AS s
                FROM payments p
                JOIN leases l ON p.lease_id = l.id
                WHERE l.tenant_id = ?
                  AND p.payment_type = 'rent'
                  AND p.status IN ('completed','verified')
                  AND p.payment_date BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tenantId, $first, $last]);
        $paid = (float)($stmt->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);
        $total = (float)$inv['total'];
        if ($total > 0) {
            if ($paid + 1e-6 >= $total) {
                $newStatus = 'paid';
            } elseif ($paid > 0) {
                $newStatus = 'partial';
            } else {
                $newStatus = 'sent';
            }
        } else {
            $newStatus = 'sent';
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
