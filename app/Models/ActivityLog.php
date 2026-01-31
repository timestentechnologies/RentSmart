<?php

namespace App\Models;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    public function __construct()
    {
        parent::__construct();
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS activity_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                role VARCHAR(50) NULL,
                action VARCHAR(150) NOT NULL,
                entity_type VARCHAR(100) NULL,
                entity_id INT NULL,
                property_id INT NULL,
                details TEXT NULL,
                ip_address VARCHAR(64) NULL,
                user_agent VARCHAR(255) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_property_id (property_id),
                INDEX idx_entity (entity_type, entity_id),
                INDEX idx_action (action),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Exception $e) {
            error_log('ActivityLog ensure table error: ' . $e->getMessage());
        }
    }

    public function add($userId, $role, $action, $entityType = null, $entityId = null, $propertyId = null, $details = null, $ip = null, $agent = null)
    {
        $sql = "INSERT INTO {$this->table} (user_id, role, action, entity_type, entity_id, property_id, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId,
            $role,
            $action,
            $entityType,
            $entityId,
            $propertyId,
            $details,
            $ip,
            $agent
        ]);
        return $this->db->lastInsertId();
    }

    public function getLogs($filters = [])
    {
        $sql = "SELECT l.*, u.name as user_name, u.email as user_email FROM {$this->table} l LEFT JOIN users u ON l.user_id = u.id WHERE 1=1";
        $params = [];
        if (!empty($filters['user_id'])) { $sql .= " AND l.user_id = ?"; $params[] = (int)$filters['user_id']; }
        if (!empty($filters['role'])) { $sql .= " AND l.role = ?"; $params[] = $filters['role']; }
        if (!empty($filters['action'])) { $sql .= " AND l.action = ?"; $params[] = $filters['action']; }
        if (!empty($filters['entity_type'])) { $sql .= " AND l.entity_type = ?"; $params[] = $filters['entity_type']; }
        if (!empty($filters['property_id'])) { $sql .= " AND l.property_id = ?"; $params[] = (int)$filters['property_id']; }
        if (!empty($filters['start_date'])) { $sql .= " AND l.created_at >= ?"; $params[] = $filters['start_date']; }
        if (!empty($filters['end_date'])) { $sql .= " AND l.created_at <= ?"; $params[] = $filters['end_date']; }
        $sql .= " ORDER BY l.id DESC LIMIT 1000";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getLogsForUserScope($userId, $filters = [])
    {
        $properties = (new Property())->getAll($userId);
        $propertyIds = array_map(function ($p) { return (int)$p['id']; }, $properties);
        $sql = "SELECT l.*, u.name as user_name, u.email as user_email FROM {$this->table} l LEFT JOIN users u ON l.user_id = u.id WHERE 1=1";
        $params = [];
        if (!empty($propertyIds)) {
            $in = implode(',', array_fill(0, count($propertyIds), '?'));
            $sql .= " AND (l.property_id IN ($in) OR l.user_id = ?)";
            $params = array_merge($params, $propertyIds);
            $params[] = $userId;
        } else {
            $sql .= " AND l.user_id = ?";
            $params[] = $userId;
        }
        if (!empty($filters['action'])) { $sql .= " AND l.action = ?"; $params[] = $filters['action']; }
        if (!empty($filters['entity_type'])) { $sql .= " AND l.entity_type = ?"; $params[] = $filters['entity_type']; }
        if (!empty($filters['start_date'])) { $sql .= " AND l.created_at >= ?"; $params[] = $filters['start_date']; }
        if (!empty($filters['end_date'])) { $sql .= " AND l.created_at <= ?"; $params[] = $filters['end_date']; }
        $sql .= " ORDER BY l.id DESC LIMIT 1000";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
