<?php

namespace App\Models;

class RealtorClient extends Model
{
    protected $table = 'realtor_clients';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    private function ensureTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS realtor_clients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            realtor_listing_id INT NULL,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(50) NOT NULL,
            email VARCHAR(150) NULL,
            notes TEXT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        try {
            $this->db->exec($sql);
        } catch (\Exception $e) {
            // ignore (e.g., missing CREATE privilege on some hosting)
        }

        try {
            $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN realtor_listing_id INT NULL AFTER user_id");
        } catch (\Exception $e) {
        }
    }

    public function getAll($userId)
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, rl.title AS listing_title, rl.location AS listing_location\n"
            . "FROM {$this->table} c\n"
            . "LEFT JOIN realtor_listings rl ON rl.id = c.realtor_listing_id\n"
            . "WHERE c.user_id = ?\n"
            . "ORDER BY c.id DESC"
        );
        $stmt->execute([(int)$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getByIdWithAccess($id, $userId)
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, rl.title AS listing_title, rl.location AS listing_location\n"
            . "FROM {$this->table} c\n"
            . "LEFT JOIN realtor_listings rl ON rl.id = c.realtor_listing_id\n"
            . "WHERE c.id = ? AND c.user_id = ?\n"
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
}
