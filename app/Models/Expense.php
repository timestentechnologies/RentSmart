<?php

namespace App\Models;

class Expense extends Model
{
    protected $table = 'expenses';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    private function ensureTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS expenses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            property_id INT NULL,
            unit_id INT NULL,
            category VARCHAR(100) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            expense_date DATE NOT NULL,
            payment_method ENUM('cash','check','bank_transfer','card','mpesa','other') NOT NULL DEFAULT 'cash',
            source_of_funds ENUM('rent_balance','cash','bank','mpesa','owner_funds','other') NOT NULL DEFAULT 'cash',
            notes TEXT NULL,
            attachments LONGTEXT NULL,
            reference_type VARCHAR(50) NULL,
            reference_id INT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->db->exec($sql);
    }

    public function getAll($userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);

        $sql = "SELECT e.*, p.name AS property_name
                FROM expenses e
                LEFT JOIN properties p ON e.property_id = p.id";
        $params = [];
        if ($userId && !$user->isAdmin()) {
            $propertyIds = $user->getAccessiblePropertyIds();
            $placeholders = '';
            if (!empty($propertyIds)) {
                $placeholders = implode(',', array_fill(0, count($propertyIds), '?'));
            }
            $sql .= " WHERE (e.user_id = ?";
            $params[] = $userId;
            if (!empty($propertyIds)) {
                $sql .= " OR e.property_id IN ($placeholders)";
                $params = array_merge($params, $propertyIds);
            }
            $sql .= ")";
        }
        $sql .= " ORDER BY e.expense_date DESC, e.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getByIdWithAccess($id, $userId = null)
    {
        $user = new User();
        $user->find($userId);
        $sql = "SELECT e.*, p.name AS property_name
                FROM expenses e
                LEFT JOIN properties p ON e.property_id = p.id
                WHERE e.id = ?";
        $params = [$id];
        if ($userId && !$user->isAdmin()) {
            $propertyIds = $user->getAccessiblePropertyIds();
            $placeholders = '';
            if (!empty($propertyIds)) {
                $placeholders = implode(',', array_fill(0, count($propertyIds), '?'));
            }
            $sql .= " AND (e.user_id = ?";
            $params[] = $userId;
            if (!empty($propertyIds)) {
                $sql .= " OR e.property_id IN ($placeholders)";
                $params = array_merge($params, $propertyIds);
            }
            $sql .= ")";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function insertExpense(array $data)
    {
        return $this->insert($data);
    }

    public function updateExpense($id, array $data)
    {
        return $this->updateById($id, $data);
    }

    /**
     * Get total expenses for a date period with role-based filtering
     */
    public function getTotalForPeriod($userId, $startDate, $endDate, $sourceOfFunds = null)
    {
        $user = new User();
        $userData = $user->find($userId);

        $sql = "SELECT COALESCE(SUM(e.amount), 0) AS total
                FROM expenses e";
        $params = [];

        $conditions = ["e.expense_date BETWEEN ? AND ?"];
        $params[] = $startDate;
        $params[] = $endDate;

        if ($sourceOfFunds !== null) {
            $conditions[] = "e.source_of_funds = ?";
            $params[] = $sourceOfFunds;
        }

        if ($userId && !$user->isAdmin()) {
            $propertyIds = $user->getAccessiblePropertyIds();
            $propertyFilter = '';
            if (!empty($propertyIds)) {
                $placeholders = implode(',', array_fill(0, count($propertyIds), '?'));
                $propertyFilter = " OR e.property_id IN ($placeholders)";
                $params = array_merge($params, $propertyIds);
            }
            $conditions[] = "(e.user_id = ?$propertyFilter)";
            array_splice($params, 2 + ($sourceOfFunds !== null ? 1 : 0), 0, [$userId]);
        }

        $sql .= ' WHERE ' . implode(' AND ', $conditions);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (float)($row['total'] ?? 0);
    }
}
