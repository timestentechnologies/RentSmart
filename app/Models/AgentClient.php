<?php

namespace App\Models;

class AgentClient extends Model
{
    protected $table = 'agent_clients';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS agent_clients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            property_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(50) NOT NULL,
            email VARCHAR(150) NULL,
            notes TEXT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_property_id (property_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        try {
            $this->db->exec($sql);
        } catch (\Exception $e) {
            // ignore (e.g., missing CREATE privilege on some hosting)
        }
    }

    public function getAllForUser($userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, p.name AS property_name\n"
            . "FROM {$this->table} c\n"
            . "LEFT JOIN properties p ON p.id = c.property_id\n"
            . "WHERE c.user_id = ?\n"
            . "ORDER BY c.id DESC"
        );
        $stmt->execute([(int)$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function getByIdWithAccess($id, $userId)
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, p.name AS property_name\n"
            . "FROM {$this->table} c\n"
            . "LEFT JOIN properties p ON p.id = c.property_id\n"
            . "WHERE c.id = ? AND c.user_id = ?\n"
            . "LIMIT 1"
        );
        $stmt->execute([(int)$id, (int)$userId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}
