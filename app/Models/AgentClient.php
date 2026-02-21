<?php

namespace App\Models;

class AgentClient extends Model
{
    protected $table = 'agent_clients';
    protected $linkTable = 'agent_client_properties';

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
            property_id INT NULL,
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

        try {
            $this->db->exec("ALTER TABLE {$this->table} MODIFY property_id INT NULL");
        } catch (\Exception $e) {
        }

        $linkSql = "CREATE TABLE IF NOT EXISTS {$this->linkTable} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            agent_client_id INT NOT NULL,
            property_id INT NOT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_client_property (agent_client_id, property_id),
            UNIQUE KEY uniq_property (property_id),
            INDEX idx_user_id (user_id),
            INDEX idx_client (agent_client_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        try {
            $this->db->exec($linkSql);
        } catch (\Exception $e) {
            // ignore
        }
    }

    public function getAllForUser($userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*,\n"
            . "       GROUP_CONCAT(DISTINCT p.name ORDER BY p.name SEPARATOR ', ') AS property_names,\n"
            . "       GROUP_CONCAT(DISTINCT acp.property_id ORDER BY acp.property_id SEPARATOR ',') AS property_ids\n"
            . "FROM {$this->table} c\n"
            . "LEFT JOIN {$this->linkTable} acp ON acp.agent_client_id = c.id\n"
            . "LEFT JOIN properties p ON p.id = acp.property_id\n"
            . "WHERE c.user_id = ?\n"
            . "GROUP BY c.id\n"
            . "ORDER BY c.id DESC"
        );
        $stmt->execute([(int)$userId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$r) {
            $r['property_ids'] = $this->csvToIntArray($r['property_ids'] ?? '');
        }
        unset($r);
        return $rows;
    }

    public function getByIdWithAccess($id, $userId)
    {
        $stmt = $this->db->prepare(
            "SELECT c.*,\n"
            . "       GROUP_CONCAT(DISTINCT p.name ORDER BY p.name SEPARATOR ', ') AS property_names,\n"
            . "       GROUP_CONCAT(DISTINCT acp.property_id ORDER BY acp.property_id SEPARATOR ',') AS property_ids\n"
            . "FROM {$this->table} c\n"
            . "LEFT JOIN {$this->linkTable} acp ON acp.agent_client_id = c.id\n"
            . "LEFT JOIN properties p ON p.id = acp.property_id\n"
            . "WHERE c.id = ? AND c.user_id = ?\n"
            . "GROUP BY c.id\n"
            . "LIMIT 1"
        );
        $stmt->execute([(int)$id, (int)$userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            return $row;
        }
        $row['property_ids'] = $this->csvToIntArray($row['property_ids'] ?? '');
        return $row;
    }

    public function getClientPropertyIds($clientId, $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT property_id FROM {$this->linkTable} WHERE agent_client_id = ? AND user_id = ? ORDER BY property_id"
        );
        $stmt->execute([(int)$clientId, (int)$userId]);
        $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        return array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));
    }

    public function isPropertyLinkedToClient($clientId, $propertyId, $userId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM {$this->linkTable} WHERE agent_client_id = ? AND property_id = ? AND user_id = ? LIMIT 1"
        );
        $stmt->execute([(int)$clientId, (int)$propertyId, (int)$userId]);
        return (bool)$stmt->fetchColumn();
    }

    public function isPropertyAvailableForClient($propertyId, $userId, $excludeClientId = null): bool
    {
        $sql = "SELECT agent_client_id FROM {$this->linkTable} WHERE property_id = ? AND user_id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int)$propertyId, (int)$userId]);
        $linkedClientId = $stmt->fetchColumn();
        if (!$linkedClientId) {
            return true;
        }
        if ($excludeClientId !== null && (int)$excludeClientId > 0) {
            return (int)$linkedClientId === (int)$excludeClientId;
        }
        return false;
    }

    public function syncClientProperties($clientId, $userId, array $propertyIds): void
    {
        $propertyIds = array_values(array_unique(array_filter(array_map('intval', $propertyIds), fn($v) => $v > 0)));
        $this->db->prepare("DELETE FROM {$this->linkTable} WHERE agent_client_id = ? AND user_id = ?")
            ->execute([(int)$clientId, (int)$userId]);
        if (empty($propertyIds)) {
            return;
        }
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO {$this->linkTable} (user_id, agent_client_id, property_id) VALUES (?, ?, ?)"
        );
        foreach ($propertyIds as $pid) {
            $stmt->execute([(int)$userId, (int)$clientId, (int)$pid]);
        }
    }

    public function getAvailablePropertiesForUser($userId, $forClientId = null): array
    {
        $params = [(int)$userId];
        $sql =
            "SELECT p.*\n"
            . "FROM properties p\n"
            . "LEFT JOIN {$this->linkTable} acp ON acp.property_id = p.id\n"
            . "WHERE (p.owner_id = ? OR p.manager_id = ? OR p.agent_id = ? OR p.caretaker_user_id = ?)\n";
        $params = [(int)$userId, (int)$userId, (int)$userId, (int)$userId];

        if ($forClientId !== null && (int)$forClientId > 0) {
            $sql .= " AND (acp.id IS NULL OR acp.agent_client_id = ?)";
            $params[] = (int)$forClientId;
        } else {
            $sql .= " AND acp.id IS NULL";
        }

        $sql .= " GROUP BY p.id ORDER BY p.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    private function csvToIntArray($csv): array
    {
        $csv = trim((string)$csv);
        if ($csv === '') return [];
        $parts = array_map('trim', explode(',', $csv));
        $ids = [];
        foreach ($parts as $p) {
            $v = (int)$p;
            if ($v > 0) $ids[] = $v;
        }
        return array_values(array_unique($ids));
    }
}
