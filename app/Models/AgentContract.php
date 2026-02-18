<?php

namespace App\Models;

class AgentContract extends Model
{
    protected $table = 'agent_contracts';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS agent_contracts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            property_id INT NULL,
            agent_client_id INT NOT NULL,
            terms_type ENUM('one_time','monthly') NOT NULL DEFAULT 'one_time',
            total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            monthly_amount DECIMAL(12,2) NULL,
            duration_months INT NULL,
            start_month DATE NULL,
            instructions TEXT NULL,
            status ENUM('active','completed','cancelled') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_property_id (property_id),
            INDEX idx_client_property (agent_client_id, property_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        try {
            $this->db->exec($sql);
        } catch (\Exception $e) {
            // ignore (e.g., missing CREATE privilege on some hosting)
        }

        try {
            $this->db->exec("ALTER TABLE {$this->table} MODIFY property_id INT NULL");
        } catch (\Exception $e) {
        }

        try {
            $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN instructions TEXT NULL AFTER start_month");
        } catch (\Exception $e) {
        }
    }

    public function getAllWithDetails($userId): array
    {
        $sql = "SELECT c.*, 
                       ac.name AS client_name, ac.phone AS client_phone,
                       p.name AS property_name
                FROM {$this->table} c
                LEFT JOIN agent_clients ac ON ac.id = c.agent_client_id
                LEFT JOIN properties p ON p.id = c.property_id
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
