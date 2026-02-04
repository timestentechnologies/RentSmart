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
    }

    public function generateNumber()
    {
        $prefix = 'INV-' . date('Ymd') . '-';
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE number LIKE ?");
        $stmt->execute([$prefix . '%']);
        $count = (int)$stmt->fetchColumn();
        return $prefix . str_pad((string)($count + 1), 3, '0', STR_PAD_LEFT);
    }

    public function createInvoice(array $data, array $items)
    {
        $this->db->beginTransaction();
        try {
            $number = $this->generateNumber();
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
}
