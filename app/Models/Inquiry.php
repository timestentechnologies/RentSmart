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
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO {$this->table} (unit_id, property_id, name, contact, preferred_date, message, source)
                VALUES (:unit_id, :property_id, :name, :contact, :preferred_date, :message, :source)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'unit_id' => $data['unit_id'],
            'property_id' => $data['property_id'],
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
        $sql = "SELECT i.*, u.unit_number, p.name as property_name
                FROM {$this->table} i
                JOIN units u ON u.id = i.unit_id
                JOIN properties p ON p.id = i.property_id";
        $role = strtolower((string)$role);
        if (!in_array($role, ['admin', 'administrator'], true)) {
            $sql .= " WHERE (p.owner_id = ? OR p.manager_id = ? OR p.agent_id = ? OR p.caretaker_user_id = ?)";
            $params[] = $userId;
            $params[] = $userId;
            $params[] = $userId;
            $params[] = $userId;

            if ($role === 'realtor') {
                $sql .= " AND (i.source = 'vacant_units' OR i.source IS NULL OR i.source = '')";
            }
        }
        $sql .= " ORDER BY i.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
?>

