<?php

namespace App\Models;

class AgentContract extends Model
{
    protected $table = 'agent_contracts';
    protected $unitLinkTable = 'agent_contract_units';

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
            commission_percent DECIMAL(5,2) NULL,
            rent_total DECIMAL(12,2) NULL,
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

        try {
            $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN commission_percent DECIMAL(5,2) NULL AFTER instructions");
        } catch (\Exception $e) {
        }
        try {
            $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN rent_total DECIMAL(12,2) NULL AFTER commission_percent");
        } catch (\Exception $e) {
        }

        $linkSql = "CREATE TABLE IF NOT EXISTS {$this->unitLinkTable} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            agent_contract_id INT NOT NULL,
            unit_id INT NOT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_contract_unit (agent_contract_id, unit_id),
            INDEX idx_user_id (user_id),
            INDEX idx_contract (agent_contract_id),
            INDEX idx_unit (unit_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        try {
            $this->db->exec($linkSql);
        } catch (\Exception $e) {
            // ignore
        }
    }

    public function getAllWithDetails($userId): array
    {
        $sql = "SELECT c.*, 
                       ac.name AS client_name, ac.phone AS client_phone,
                       p.name AS property_name,
                       GROUP_CONCAT(DISTINCT acu.unit_id ORDER BY acu.unit_id SEPARATOR ',') AS unit_ids
                FROM {$this->table} c
                LEFT JOIN agent_clients ac ON ac.id = c.agent_client_id
                LEFT JOIN properties p ON p.id = c.property_id
                LEFT JOIN {$this->unitLinkTable} acu ON acu.agent_contract_id = c.id
                WHERE c.user_id = ?
                GROUP BY c.id
                ORDER BY c.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int)$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function getByIdWithAccess($id, $userId)
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, GROUP_CONCAT(DISTINCT acu.unit_id ORDER BY acu.unit_id SEPARATOR ',') AS unit_ids\n"
            . "FROM {$this->table} c\n"
            . "LEFT JOIN {$this->unitLinkTable} acu ON acu.agent_contract_id = c.id\n"
            . "WHERE c.id = ? AND c.user_id = ?\n"
            . "GROUP BY c.id\n"
            . "LIMIT 1"
        );
        $stmt->execute([(int)$id, (int)$userId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getIdByClientProperty($userId, $clientId, $propertyId)
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM {$this->table} WHERE user_id = ? AND agent_client_id = ? AND property_id = ? ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([(int)$userId, (int)$clientId, (int)$propertyId]);
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    }

    public function syncContractUnits($contractId, $userId, array $unitIds): void
    {
        $unitIds = array_values(array_unique(array_filter(array_map('intval', $unitIds), fn($v) => $v > 0)));
        $this->db->prepare("DELETE FROM {$this->unitLinkTable} WHERE agent_contract_id = ? AND user_id = ?")
            ->execute([(int)$contractId, (int)$userId]);
        if (empty($unitIds)) {
            return;
        }
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO {$this->unitLinkTable} (user_id, agent_contract_id, unit_id) VALUES (?, ?, ?)"
        );
        foreach ($unitIds as $uid) {
            $stmt->execute([(int)$userId, (int)$contractId, (int)$uid]);
        }
    }
}
