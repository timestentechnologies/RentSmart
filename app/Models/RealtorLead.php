<?php

namespace App\Models;

class RealtorLead extends Model
{
    protected $table = 'realtor_leads';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    private function ensureTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS realtor_leads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            realtor_listing_id INT NULL,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(50) NOT NULL,
            email VARCHAR(150) NULL,
            source VARCHAR(100) NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'new',
            notes TEXT NULL,
            converted_client_id INT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        try {
            $this->db->exec($sql);
        } catch (\Exception $e) {
        }

        try {
            $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN realtor_listing_id INT NULL AFTER user_id");
        } catch (\Exception $e) {
        }

        // If the column exists as ENUM (older installs), widen it to VARCHAR for custom stages
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'status'");
            $col = $stmt ? $stmt->fetch(\PDO::FETCH_ASSOC) : null;
            $type = strtolower((string)($col['Type'] ?? ''));
            if ($type !== '' && strpos($type, 'enum(') === 0) {
                $this->db->exec("ALTER TABLE {$this->table} MODIFY status VARCHAR(50) NOT NULL DEFAULT 'new'");
            }
        } catch (\Exception $e) {
        }
    }

    public function getAll($userId)
    {
        $stmt = $this->db->prepare(
            "SELECT l.*, rl.title AS listing_title, rl.location AS listing_location\n"
            . "FROM {$this->table} l\n"
            . "LEFT JOIN realtor_listings rl ON rl.id = l.realtor_listing_id\n"
            . "WHERE l.user_id = ?\n"
            . "ORDER BY l.id DESC"
        );
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
        $stmt = $this->db->prepare(
            "SELECT l.*, rl.title AS listing_title, rl.location AS listing_location\n"
            . "FROM {$this->table} l\n"
            . "LEFT JOIN realtor_listings rl ON rl.id = l.realtor_listing_id\n"
            . "WHERE l.id = ? AND l.user_id = ?\n"
            . "LIMIT 1"
        );
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
