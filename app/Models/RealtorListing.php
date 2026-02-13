<?php

namespace App\Models;

class RealtorListing extends Model
{
    protected $table = 'realtor_listings';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    private function ensureTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS realtor_listings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            listing_type ENUM('plot','commercial_apartment','residential_apartment') NOT NULL DEFAULT 'plot',
            location VARCHAR(255) NOT NULL,
            price DECIMAL(12,2) NOT NULL DEFAULT 0,
            status ENUM('active','inactive','sold','rented') NOT NULL DEFAULT 'active',
            description TEXT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->db->exec($sql);
    }

    public function getAll($userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([(int)$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getRecent($userId, $limit = 5)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY id DESC LIMIT " . (int)$limit);
        $stmt->execute([(int)$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getByIdWithAccess($id, $userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([(int)$id, (int)$userId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function countAll($userId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS c FROM {$this->table} WHERE user_id = ?");
        $stmt->execute([(int)$userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    public function countByStatus($userId, $status)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS c FROM {$this->table} WHERE user_id = ? AND status = ?");
        $stmt->execute([(int)$userId, (string)$status]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }
}
