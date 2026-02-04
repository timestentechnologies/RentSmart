<?php

namespace App\Models;

class LedgerEntry extends Model
{
    protected $table = 'journal_entries';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    private function ensureTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS journal_entries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            entry_date DATE NOT NULL,
            account_id INT NOT NULL,
            description VARCHAR(255) NULL,
            debit DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            credit DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            user_id INT NULL,
            property_id INT NULL,
            reference_type VARCHAR(50) NULL,
            reference_id INT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_date (entry_date),
            INDEX idx_account (account_id),
            INDEX idx_user (user_id),
            INDEX idx_reference (reference_type, reference_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->db->exec($sql);
    }

    public function post(array $data)
    {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (entry_date, account_id, description, debit, credit, user_id, property_id, reference_type, reference_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['entry_date'] ?? date('Y-m-d'),
            (int)$data['account_id'],
            $data['description'] ?? null,
            (float)($data['debit'] ?? 0),
            (float)($data['credit'] ?? 0),
            $data['user_id'] ?? null,
            $data['property_id'] ?? null,
            $data['reference_type'] ?? null,
            $data['reference_id'] ?? null,
        ]);
        return $this->db->lastInsertId();
    }

    public function postDoubleEntry($date, $description, $debitAccountId, $creditAccountId, $amount, $userId = null, $referenceType = null, $referenceId = null)
    {
        $this->db->beginTransaction();
        try {
            $this->post([
                'entry_date' => $date,
                'account_id' => $debitAccountId,
                'description' => $description,
                'debit' => $amount,
                'credit' => 0,
                'user_id' => $userId,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);
            $this->post([
                'entry_date' => $date,
                'account_id' => $creditAccountId,
                'description' => $description,
                'debit' => 0,
                'credit' => $amount,
                'user_id' => $userId,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getGeneralLedger($userId = null, $startDate = null, $endDate = null)
    {
        $where = [];
        $params = [];
        if ($userId) { $where[] = 'je.user_id = ?'; $params[] = $userId; }
        if ($startDate) { $where[] = 'je.entry_date >= ?'; $params[] = $startDate; }
        if ($endDate) { $where[] = 'je.entry_date <= ?'; $params[] = $endDate; }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT je.*, a.code, a.name, a.type
                FROM {$this->table} je
                JOIN accounts a ON je.account_id = a.id
                $whereSql
                ORDER BY je.entry_date ASC, je.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getTrialBalance($userId = null, $startDate = null, $endDate = null)
    {
        $where = [];
        $params = [];
        if ($userId) { $where[] = 'je.user_id = ?'; $params[] = $userId; }
        if ($startDate) { $where[] = 'je.entry_date >= ?'; $params[] = $startDate; }
        if ($endDate) { $where[] = 'je.entry_date <= ?'; $params[] = $endDate; }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT a.id, a.code, a.name, a.type,
                       SUM(je.debit) AS total_debit,
                       SUM(je.credit) AS total_credit
                FROM {$this->table} je
                JOIN accounts a ON je.account_id = a.id
                $whereSql
                GROUP BY a.id, a.code, a.name, a.type
                ORDER BY a.type, a.code";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $debit = (float)($r['total_debit'] ?? 0);
            $credit = (float)($r['total_credit'] ?? 0);
            // Normal balances by account type
            switch ($r['type']) {
                case 'asset':
                case 'expense':
                    $r['balance_debit'] = max($debit - $credit, 0);
                    $r['balance_credit'] = max($credit - $debit, 0);
                    break;
                default:
                    $r['balance_debit'] = max($credit - $debit, 0) > 0 ? 0 : max($debit - $credit, 0);
                    $r['balance_credit'] = max($credit - $debit, 0);
            }
        }
        return $rows;
    }

    public function getBalancesByType($userId = null, $type = null, $startDate = null, $endDate = null)
    {
        $where = [];
        $params = [];
        if ($userId) { $where[] = 'je.user_id = ?'; $params[] = $userId; }
        if ($type) { $where[] = 'a.type = ?'; $params[] = $type; }
        if ($startDate) { $where[] = 'je.entry_date >= ?'; $params[] = $startDate; }
        if ($endDate) { $where[] = 'je.entry_date <= ?'; $params[] = $endDate; }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT a.id, a.code, a.name, a.type,
                       SUM(je.debit) AS total_debit,
                       SUM(je.credit) AS total_credit
                FROM {$this->table} je
                JOIN accounts a ON je.account_id = a.id
                $whereSql
                GROUP BY a.id, a.code, a.name, a.type
                ORDER BY a.code";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $total = 0.0;
        foreach ($rows as &$r) {
            $debit = (float)($r['total_debit'] ?? 0);
            $credit = (float)($r['total_credit'] ?? 0);
            if (in_array($r['type'], ['asset','expense'])) {
                $balance = $debit - $credit;
            } else {
                $balance = $credit - $debit;
            }
            $r['balance'] = $balance;
            $total += $balance;
        }
        return ['rows' => $rows, 'total' => $total];
    }
}
