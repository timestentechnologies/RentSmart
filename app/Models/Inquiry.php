<?php

namespace App\Models;

class Inquiry extends Model
{
    protected $table = 'inquiries';

    public function __construct()
    {
        parent::__construct();
        $this->table = 'inquiries';

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'crm_user_id'");
            if ($stmt && $stmt->rowCount() === 0) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN crm_user_id INT NULL AFTER realtor_user_id");
            }
        } catch (\Exception $e) {
        }

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'property_name'");
            if ($stmt && $stmt->rowCount() === 0) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN property_name VARCHAR(255) NULL AFTER property_id");
            }
        } catch (\Exception $e) {
        }

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'source'");
            if ($stmt && $stmt->rowCount() === 0) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN source VARCHAR(50) NULL AFTER message");
            }
        } catch (\Exception $e) {
        }

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'realtor_listing_id'");
            if ($stmt && $stmt->rowCount() === 0) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN realtor_listing_id INT NULL AFTER unit_id");
            }
        } catch (\Exception $e) {
        }

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'realtor_user_id'");
            if ($stmt && $stmt->rowCount() === 0) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN realtor_user_id INT NULL AFTER realtor_listing_id");
            }
        } catch (\Exception $e) {
        }

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'unit_id'");
            if ($stmt) {
                $col = $stmt->fetch(\PDO::FETCH_ASSOC);
                $nullable = isset($col['Null']) && strtoupper((string)$col['Null']) === 'YES';
                if (!$nullable) {
                    $this->db->exec("ALTER TABLE {$this->table} MODIFY unit_id INT NULL");
                }
            }
        } catch (\Exception $e) {
        }

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'property_id'");
            if ($stmt) {
                $col = $stmt->fetch(\PDO::FETCH_ASSOC);
                $nullable = isset($col['Null']) && strtoupper((string)$col['Null']) === 'YES';
                if (!$nullable) {
                    $this->db->exec("ALTER TABLE {$this->table} MODIFY property_id INT NULL");
                }
            }
        } catch (\Exception $e) {
        }

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'crm_stage'");
            if ($stmt && $stmt->rowCount() === 0) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN crm_stage VARCHAR(50) NULL AFTER source");
            }
        } catch (\Exception $e) {
        }
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO {$this->table} (unit_id, realtor_listing_id, realtor_user_id, crm_user_id, property_id, property_name, name, contact, preferred_date, message, source)
                VALUES (:unit_id, :realtor_listing_id, :realtor_user_id, :crm_user_id, :property_id, :property_name, :name, :contact, :preferred_date, :message, :source)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'unit_id' => $data['unit_id'] ?? null,
            'realtor_listing_id' => $data['realtor_listing_id'] ?? null,
            'realtor_user_id' => $data['realtor_user_id'] ?? null,
            'crm_user_id' => $data['crm_user_id'] ?? null,
            'property_id' => $data['property_id'] ?? null,
            'property_name' => $data['property_name'] ?? null,
            'name' => $data['name'],
            'contact' => $data['contact'],
            'preferred_date' => $data['preferred_date'] ?? null,
            'message' => $data['message'] ?? null,
            'source' => $data['source'] ?? 'vacant_units',
        ]);
        return $this->db->lastInsertId();
    }

    public function allVisibleForUser($userId, $role)
    {
        // Admin sees all, landlord/manager sees their properties' inquiries
        $params = [];
        $sql = "SELECT i.*, 
                       u.unit_number, 
                       COALESCE(NULLIF(i.property_name, ''), p.name) as property_name,
                       rl.title as listing_title
                FROM {$this->table} i
                LEFT JOIN units u ON u.id = i.unit_id
                LEFT JOIN properties p ON p.id = i.property_id
                LEFT JOIN realtor_listings rl ON rl.id = i.realtor_listing_id";
        $role = strtolower((string)$role);
        if (!in_array($role, ['admin', 'administrator'], true)) {
            if ($role === 'realtor') {
                $sql .= " WHERE i.realtor_user_id = ? AND (i.source = 'vacant_units' OR i.source IS NULL OR i.source = '')";
                $params[] = $userId;
            } else {
                $sql .= " WHERE ((p.owner_id = ? OR p.manager_id = ? OR p.agent_id = ? OR p.caretaker_user_id = ?) OR (i.crm_user_id = ? AND i.source = 'agent_crm'))";
                $params[] = $userId;
                $params[] = $userId;
                $params[] = $userId;
                $params[] = $userId;
                $params[] = $userId;
            }
        }
        $sql .= " ORDER BY i.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getByIdVisibleForUser($id, $userId, $role)
    {
        $role = strtolower((string)$role);
        $params = [(int)$id];
        $sql = "SELECT i.*, u.unit_number, u.rent_amount AS unit_rent_amount, u.status AS unit_status, COALESCE(NULLIF(i.property_name, ''), p.name) AS property_name\n"
            . "FROM {$this->table} i\n"
            . "LEFT JOIN units u ON u.id = i.unit_id\n"
            . "LEFT JOIN properties p ON p.id = i.property_id\n"
            . "WHERE i.id = ?";

        if (!in_array($role, ['admin', 'administrator'], true)) {
            if ($role === 'realtor') {
                $sql .= " AND i.realtor_user_id = ?";
                $params[] = (int)$userId;
            } else {
                $sql .= " AND ((p.owner_id = ? OR p.manager_id = ? OR p.agent_id = ? OR p.caretaker_user_id = ?) OR (i.crm_user_id = ? AND i.source = 'agent_crm'))";
                $params[] = (int)$userId;
                $params[] = (int)$userId;
                $params[] = (int)$userId;
                $params[] = (int)$userId;
                $params[] = (int)$userId;
            }
        }

        $sql .= " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function updateCrmStageWithAccess($id, $userId, $role, $stageKey): bool
    {
        $role = strtolower((string)$role);
        $stageKey = strtolower(trim((string)$stageKey));
        if ($stageKey === '') {
            $stageKey = 'new';
        }

        // Ensure the inquiry is visible to the user before updating.
        $params = [(int)$id];
        $sql = "SELECT i.id\n"
            . "FROM {$this->table} i\n"
            . "LEFT JOIN properties p ON p.id = i.property_id\n"
            . "WHERE i.id = ?";

        if (!in_array($role, ['admin', 'administrator'], true)) {
            if ($role === 'realtor') {
                $sql .= " AND i.realtor_user_id = ?";
                $params[] = (int)$userId;
            } else {
                $sql .= " AND ((p.owner_id = ? OR p.manager_id = ? OR p.agent_id = ? OR p.caretaker_user_id = ?) OR (i.crm_user_id = ? AND i.source = 'agent_crm'))";
                $params[] = (int)$userId;
                $params[] = (int)$userId;
                $params[] = (int)$userId;
                $params[] = (int)$userId;
                $params[] = (int)$userId;
            }
        }

        $check = $this->db->prepare($sql);
        $check->execute($params);
        $row = $check->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE {$this->table} SET crm_stage = ? WHERE id = ?");
        return (bool)$stmt->execute([(string)$stageKey, (int)$id]);
    }
}
?>

