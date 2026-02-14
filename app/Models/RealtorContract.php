<?php

namespace App\Models;

class RealtorContract extends Model
{
    protected $table = 'realtor_contracts';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS realtor_contracts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            realtor_client_id INT NOT NULL,
            realtor_listing_id INT NOT NULL,
            terms_type ENUM('one_time','monthly') NOT NULL DEFAULT 'one_time',
            total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            monthly_amount DECIMAL(12,2) NULL,
            duration_months INT NULL,
            start_month DATE NULL,
            status ENUM('active','completed','cancelled') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_client_listing (realtor_client_id, realtor_listing_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        try {
            $this->db->exec($sql);
        } catch (\Exception $e) {
            // ignore (e.g., missing CREATE privilege on some hosting)
        }
    }

    public function getAllWithDetails($userId): array
    {
        $sql = "SELECT c.*, 
                       rc.name AS client_name, rc.phone AS client_phone,
                       rl.title AS listing_title, rl.location AS listing_location
                FROM {$this->table} c
                LEFT JOIN realtor_clients rc ON rc.id = c.realtor_client_id
                LEFT JOIN realtor_listings rl ON rl.id = c.realtor_listing_id
                WHERE c.user_id = ?
                ORDER BY c.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int)$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function getByIdWithAccess($id, $userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([(int)$id, (int)$userId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}
