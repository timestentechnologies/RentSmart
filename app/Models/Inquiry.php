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
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO {$this->table} (unit_id, realtor_listing_id, realtor_user_id, property_id, name, contact, preferred_date, message, source)
                VALUES (:unit_id, :realtor_listing_id, :realtor_user_id, :property_id, :name, :contact, :preferred_date, :message, :source)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'unit_id' => $data['unit_id'] ?? null,
            'realtor_listing_id' => $data['realtor_listing_id'] ?? null,
            'realtor_user_id' => $data['realtor_user_id'] ?? null,
            'property_id' => $data['property_id'] ?? null,
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
                       p.name as property_name,
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
                $sql .= " WHERE (p.owner_id = ? OR p.manager_id = ? OR p.agent_id = ? OR p.caretaker_user_id = ?)";
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
}
?>

