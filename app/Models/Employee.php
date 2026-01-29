<?php

namespace App\Models;

class Employee extends Model
{
    protected $table = 'employees';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    private function ensureTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS employees (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(150) NULL,
            phone VARCHAR(50) NULL,
            title VARCHAR(150) NULL,
            salary DECIMAL(10,2) DEFAULT 0,
            property_id INT NULL,
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->db->exec($sql);
    }

    public function getAll($userId = null)
    {
        $user = new User();
        $user->find($userId);
        $sql = "SELECT e.*, p.name AS property_name
                FROM employees e
                LEFT JOIN properties p ON e.property_id = p.id";
        $params = [];
        if ($userId && !$user->isAdmin()) {
            $propertyIds = $user->getAccessiblePropertyIds();
            $sql .= " WHERE e.user_id = ?";
            $params[] = $userId;
            if (!empty($propertyIds)) {
                $placeholders = implode(',', array_fill(0, count($propertyIds), '?'));
                $sql .= " OR e.property_id IN ($placeholders)";
                $params = array_merge($params, $propertyIds);
            }
        }
        $sql .= " ORDER BY e.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getByIdWithAccess($id, $userId = null)
    {
        $user = new User();
        $user->find($userId);
        $sql = "SELECT e.*, p.name AS property_name
                FROM employees e
                LEFT JOIN properties p ON e.property_id = p.id
                WHERE e.id = ?";
        $params = [$id];
        if ($userId && !$user->isAdmin()) {
            $propertyIds = $user->getAccessiblePropertyIds();
            $sql .= " AND (e.user_id = ?";
            $params[] = $userId;
            if (!empty($propertyIds)) {
                $placeholders = implode(',', array_fill(0, count($propertyIds), '?'));
                $sql .= " OR e.property_id IN ($placeholders)";
                $params = array_merge($params, $propertyIds);
            }
            $sql .= ")";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}
