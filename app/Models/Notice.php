<?php

namespace App\Models;

class Notice extends Model
{
    protected $table = 'notices';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    private function ensureTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS notices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            property_id INT NULL,
            unit_id INT NULL,
            tenant_id INT NULL,
            pinned TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_prop (property_id),
            INDEX idx_unit (unit_id),
            INDEX idx_tenant (tenant_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->db->exec($sql);
    }

    public function create(array $data)
    {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (user_id, title, body, property_id, unit_id, tenant_id, pinned) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            (int)$data['user_id'],
            $data['title'],
            $data['body'],
            $data['property_id'] ?? null,
            $data['unit_id'] ?? null,
            $data['tenant_id'] ?? null,
            !empty($data['pinned']) ? 1 : 0,
        ]);
        return $this->db->lastInsertId();
    }

    public function getVisibleForUser($userId)
    {
        $user = new User();
        $info = $user->find($userId);
        if (!$info) return [];
        $role = strtolower($info['role'] ?? '');

        if ($user->isAdmin()) {
            $sql = "SELECT n.*, p.name AS property_name, u.unit_number, t.name as tenant_name
                    FROM {$this->table} n
                    LEFT JOIN properties p ON n.property_id = p.id
                    LEFT JOIN units u ON n.unit_id = u.id
                    LEFT JOIN tenants t ON n.tenant_id = t.id
                    ORDER BY n.pinned DESC, n.created_at DESC";
            return $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        }

        // Non-admin staff (landlord/manager/agent/caretaker): limit to accessible properties, plus global notices
        $propertyIds = $user->getAccessiblePropertyIds();
        $params = [];
        $conditions = ["(n.property_id IS NULL AND n.unit_id IS NULL AND n.tenant_id IS NULL)"]; // global
        if (!empty($propertyIds)) {
            $in = implode(',', array_fill(0, count($propertyIds), '?'));
            $conditions[] = "n.property_id IN ($in)";
            $params = array_merge($params, $propertyIds);
        }
        // Also include own authored notices
        $conditions[] = "n.user_id = ?";
        $params[] = $userId;

        $sql = "SELECT n.*, p.name AS property_name, u.unit_number, t.name as tenant_name
                FROM {$this->table} n
                LEFT JOIN properties p ON n.property_id = p.id
                LEFT JOIN units u ON n.unit_id = u.id
                LEFT JOIN tenants t ON n.tenant_id = t.id
                WHERE " . implode(' OR ', $conditions) . "
                ORDER BY n.pinned DESC, n.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getVisibleForTenant($tenantId)
    {
        // Find tenant's active lease for property/unit
        $stmt = $this->db->prepare("SELECT l.*, u.property_id FROM leases l JOIN units u ON l.unit_id = u.id WHERE l.tenant_id = ? AND l.status = 'active' LIMIT 1");
        $stmt->execute([(int)$tenantId]);
        $lease = $stmt->fetch(\PDO::FETCH_ASSOC);
        $propertyId = $lease['property_id'] ?? null;
        $unitId = $lease['unit_id'] ?? null;

        $params = [$tenantId];
        $conditions = ["n.tenant_id = ?"]; // direct-to-tenant
        if ($unitId) { $conditions[] = "n.unit_id = ?"; $params[] = (int)$unitId; }
        if ($propertyId) { $conditions[] = "n.property_id = ?"; $params[] = (int)$propertyId; }
        $conditions[] = "(n.property_id IS NULL AND n.unit_id IS NULL AND n.tenant_id IS NULL)"; // global

        $sql = "SELECT n.*, p.name AS property_name, u.unit_number
                FROM {$this->table} n
                LEFT JOIN properties p ON n.property_id = p.id
                LEFT JOIN units u ON n.unit_id = u.id
                WHERE " . implode(' OR ', $conditions) . "
                ORDER BY n.pinned DESC, n.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
