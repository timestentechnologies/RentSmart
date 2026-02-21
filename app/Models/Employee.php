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
            salary DECIMAL(10,2) DEFAULT 0,
            property_id INT NULL,
            realtor_listing_id INT NULL,
            role VARCHAR(50) NOT NULL DEFAULT 'general',
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->db->exec($sql);

        // Ensure realtor_listing_id exists for linking employees to realtor listings
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM employees LIKE 'realtor_listing_id'");
            if ($stmt && $stmt->rowCount() === 0) {
                $this->db->exec("ALTER TABLE employees ADD COLUMN realtor_listing_id INT NULL AFTER property_id");
            }
        } catch (\Exception $e) {
            // ignore
        }

        // Ensure role column supports arbitrary property-related roles
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM employees LIKE 'role'");
            $col = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$col) {
                $this->db->exec("ALTER TABLE employees ADD COLUMN role VARCHAR(50) NOT NULL DEFAULT 'general' AFTER property_id");
            } else {
                if (isset($col['Type']) && stripos($col['Type'], 'enum') !== false) {
                    $this->db->exec("ALTER TABLE employees MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'general'");
                }
            }
        } catch (\Exception $e) {
            // ignore
        }
        
        // Drop legacy title column if it exists
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM employees LIKE 'title'");
            if ($stmt && $stmt->rowCount() > 0) {
                $this->db->exec("ALTER TABLE employees DROP COLUMN title");
            }
        } catch (\Exception $e) {
            // ignore
        }
    }

    public function getAll($userId = null)
    {
        $user = new User();
        $user->find($userId);
        $sql = "SELECT e.*, p.name AS property_name, rl.title AS realtor_listing_title
                FROM employees e
                LEFT JOIN properties p ON e.property_id = p.id
                LEFT JOIN realtor_listings rl ON e.realtor_listing_id = rl.id";
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
        $sql = "SELECT e.*, p.name AS property_name, rl.title AS realtor_listing_title
                FROM employees e
                LEFT JOIN properties p ON e.property_id = p.id
                LEFT JOIN realtor_listings rl ON e.realtor_listing_id = rl.id
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

    public function getCaretakers($userId = null)
    {
        $user = new User();
        $user->find($userId);
        $sql = "SELECT e.* FROM employees e";
        $params = [];
        $conditions = ["e.role = 'caretaker'"];
        if ($userId && !$user->isAdmin()) {
            $propertyIds = $user->getAccessiblePropertyIds();
            $sub = [];
            if (!empty($propertyIds)) {
                $placeholders = implode(',', array_fill(0, count($propertyIds), '?'));
                $sub[] = "e.property_id IN ($placeholders)";
                $params = array_merge($params, $propertyIds);
            }
            $sub[] = "e.user_id = ?";
            $params[] = $userId;
            $conditions[] = '(' . implode(' OR ', $sub) . ')';
        }
        $sql .= ' WHERE ' . implode(' AND ', $conditions) . ' ORDER BY e.name';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
