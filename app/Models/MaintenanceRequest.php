<?php

namespace App\Models;

class MaintenanceRequest extends Model
{
    protected $table = 'maintenance_requests';

    public function create($data)
    {
        return $this->insert($data);
    }

    public function update($id, $data)
    {
        return $this->updateById($id, $data);
    }

    public function delete($id)
    {
        return $this->deleteById($id);
    }

    /**
     * Get all maintenance requests for a specific tenant
     */
    public function getByTenant($tenantId)
    {
        $sql = "SELECT mr.*, 
                t.name as tenant_name,
                t.email as tenant_email,
                t.phone as tenant_phone,
                u.unit_number,
                p.name as property_name
                FROM maintenance_requests mr
                LEFT JOIN tenants t ON mr.tenant_id = t.id
                LEFT JOIN units u ON mr.unit_id = u.id
                LEFT JOIN properties p ON mr.property_id = p.id
                WHERE mr.tenant_id = ?
                ORDER BY mr.requested_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get all maintenance requests for admin view
     */
    public function getAllForAdmin($userId = null)
    {
        return $this->getAllForAdminFiltered($userId, null, null, null);
    }

    public function getAllForAdminFiltered($userId = null, ?string $status = null, ?string $priority = null, ?string $category = null)
    {
        $user = new User();
        $user->find($userId);
        
        $sql = "SELECT mr.*, 
                COALESCE(t.name, t2.name) as tenant_name,
                COALESCE(t.email, t2.email) as tenant_email,
                COALESCE(t.phone, t2.phone) as tenant_phone,
                COALESCE(mr.tenant_id, l.tenant_id) as resolved_tenant_id,
                u.unit_number,
                p.name as property_name
                FROM maintenance_requests mr
                LEFT JOIN tenants t ON mr.tenant_id = t.id
                LEFT JOIN units u ON mr.unit_id = u.id
                LEFT JOIN properties p ON mr.property_id = p.id
                LEFT JOIN leases l ON mr.unit_id = l.unit_id AND l.status = 'active'
                LEFT JOIN tenants t2 ON l.tenant_id = t2.id";

        $params = [];

        $where = [];
        if (!empty($status)) {
            $where[] = 'mr.status = ?';
            $params[] = $status;
        }
        if (!empty($priority)) {
            $where[] = 'mr.priority = ?';
            $params[] = $priority;
        }
        if (!empty($category)) {
            $where[] = 'mr.category = ?';
            $params[] = $category;
        }
        
        // Add role-based conditions
        if ($userId && !$user->isAdmin()) {
            $roleSql = "(1=0";
            if ($user->isLandlord()) {
                $roleSql .= " OR p.owner_id = ?";
                $params[] = $userId;
            }
            if ($user->isManager()) {
                $roleSql .= " OR p.manager_id = ?";
                $params[] = $userId;
            }
            if ($user->isAgent()) {
                $roleSql .= " OR p.agent_id = ?";
                $params[] = $userId;
            }
            // Caretaker assigned to property
            $roleSql .= " OR p.caretaker_user_id = ?";
            $params[] = $userId;
            $roleSql .= ")";
            $where[] = $roleSql;
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        
        $sql .= " ORDER BY mr.requested_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get maintenance request by ID with tenant and property info
     */
    public function getById($id, $userId = null)
    {
        $user = new User();
        $user->find($userId);
        
        $sql = "SELECT mr.*, 
                COALESCE(t.name, t2.name) as tenant_name,
                COALESCE(t.email, t2.email) as tenant_email,
                COALESCE(t.phone, t2.phone) as tenant_phone,
                COALESCE(mr.tenant_id, l.tenant_id) as resolved_tenant_id,
                u.unit_number,
                p.name as property_name
                FROM maintenance_requests mr
                LEFT JOIN tenants t ON mr.tenant_id = t.id
                LEFT JOIN units u ON mr.unit_id = u.id
                LEFT JOIN properties p ON mr.property_id = p.id
                LEFT JOIN leases l ON mr.unit_id = l.unit_id AND l.status = 'active'
                LEFT JOIN tenants t2 ON l.tenant_id = t2.id
                WHERE mr.id = ?";

        $params = [$id];
        
        // Add role-based conditions
        if (!$user->isAdmin()) {
            $sql .= " AND (1=0";
            if ($user->isLandlord()) {
                $sql .= " OR p.owner_id = ?";
                $params[] = $userId;
            }
            if ($user->isManager()) {
                $sql .= " OR p.manager_id = ?";
                $params[] = $userId;
            }
            if ($user->isAgent()) {
                $sql .= " OR p.agent_id = ?";
                $params[] = $userId;
            }
            // Caretaker assigned to property
            $sql .= " OR p.caretaker_user_id = ?";
            $params[] = $userId;
            $sql .= ")";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get maintenance request statistics
     */
    public function getStatistics($userId = null)
    {
        $user = new User();
        $user->find($userId);
        
        $sql = "SELECT 
                COUNT(*) as total_requests,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_requests,
                COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_requests,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_requests,
                COUNT(CASE WHEN priority = 'urgent' THEN 1 END) as urgent_requests,
                AVG(CASE WHEN actual_cost IS NOT NULL THEN actual_cost END) as avg_cost
                FROM maintenance_requests mr
                LEFT JOIN properties p ON mr.property_id = p.id";

        $params = [];
        
        // Add role-based conditions
        if ($userId && !$user->isAdmin()) {
            $sql .= " WHERE (1=0";
            if ($user->isLandlord()) {
                $sql .= " OR p.owner_id = ?";
                $params[] = $userId;
            }
            if ($user->isManager()) {
                $sql .= " OR p.manager_id = ?";
                $params[] = $userId;
            }
            if ($user->isAgent()) {
                $sql .= " OR p.agent_id = ?";
                $params[] = $userId;
            }
            $sql .= ")";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get maintenance request by ID for tenant access
     */
    public function getByIdForTenant($id, $tenantId)
    {
        $sql = "SELECT mr.*, 
                t.name as tenant_name,
                t.email as tenant_email,
                t.phone as tenant_phone,
                u.unit_number,
                p.name as property_name
                FROM maintenance_requests mr
                LEFT JOIN tenants t ON mr.tenant_id = t.id
                LEFT JOIN units u ON mr.unit_id = u.id
                LEFT JOIN properties p ON mr.property_id = p.id
                WHERE mr.id = ? AND mr.tenant_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $tenantId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Update maintenance request status
     */
    public function updateStatus($id, $status, $notes = null, $assignedTo = null, $scheduledDate = null, $estimatedCost = null, $actualCost = null)
    {
        $data = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($notes !== null) {
            $data['notes'] = $notes;
        }

        if ($assignedTo !== null) {
            $data['assigned_to'] = $assignedTo;
        }

        if ($scheduledDate !== null) {
            $data['scheduled_date'] = $scheduledDate;
        }

        if ($estimatedCost !== null) {
            $data['estimated_cost'] = $estimatedCost;
        }

        if ($actualCost !== null) {
            $data['actual_cost'] = $actualCost;
        }

        if ($status === 'completed') {
            $data['completed_date'] = date('Y-m-d H:i:s');
        }

        return $this->updateById($id, $data);
    }

    /**
     * Get maintenance requests for reports
     */
    public function getMaintenanceRequests($startDate, $endDate, $userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        if (!$userData) {
            return [];
        }

        // For admin users
        if ($userData['role'] === 'admin') {
            $sql = "SELECT 
                        m.*,
                        p.name as property_name,
                        u.unit_number,
                        t.name as tenant_name
                    FROM maintenance_requests m
                    LEFT JOIN units u ON m.unit_id = u.id
                    LEFT JOIN properties p ON u.property_id = p.id
                    LEFT JOIN leases l ON u.id = l.unit_id AND l.status = 'active'
                    LEFT JOIN tenants t ON l.tenant_id = t.id
                    WHERE m.created_at BETWEEN ? AND ?
                    ORDER BY m.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$startDate, $endDate]);
        } else {
            // For regular users
            $sql = "SELECT 
                        m.*,
                        p.name as property_name,
                        u.unit_number,
                        t.name as tenant_name
                    FROM maintenance_requests m
                    LEFT JOIN units u ON m.unit_id = u.id
                    LEFT JOIN properties p ON u.property_id = p.id
                    LEFT JOIN leases l ON u.id = l.unit_id AND l.status = 'active'
                    LEFT JOIN tenants t ON l.tenant_id = t.id
                    WHERE m.created_at BETWEEN ? AND ?
                    AND (p.owner_id = ? OR p.manager_id = ? OR p.agent_id = ? OR p.caretaker_user_id = ?)
                    ORDER BY m.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$startDate, $endDate, $userId, $userId, $userId, $userId]);
        }
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get maintenance costs for reports
     */
    public function getMaintenanceCosts($startDate, $endDate, $userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        if (!$userData) {
            return [];
        }

        // For admin users
        if ($userData['role'] === 'admin') {
            $sql = "SELECT 
                        p.name as property_name,
                        COUNT(m.id) as total_requests,
                        SUM(CASE WHEN m.actual_cost IS NOT NULL THEN m.actual_cost ELSE 0 END) as total_cost,
                        AVG(CASE WHEN m.actual_cost IS NOT NULL THEN m.actual_cost ELSE 0 END) as average_cost,
                        SUM(CASE WHEN m.status = 'completed' THEN 1 ELSE 0 END) as completed_requests,
                        SUM(CASE WHEN m.status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
                        SUM(CASE WHEN m.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_requests
                    FROM properties p
                    LEFT JOIN units u ON p.id = u.property_id
                    LEFT JOIN maintenance_requests m ON u.id = m.unit_id 
                        AND m.created_at BETWEEN ? AND ?
                    GROUP BY p.id, p.name
                    ORDER BY total_cost DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$startDate, $endDate]);
        } else {
            // For regular users
            $sql = "SELECT 
                        p.name as property_name,
                        COUNT(m.id) as total_requests,
                        SUM(CASE WHEN m.actual_cost IS NOT NULL THEN m.actual_cost ELSE 0 END) as total_cost,
                        AVG(CASE WHEN m.actual_cost IS NOT NULL THEN m.actual_cost ELSE 0 END) as average_cost,
                        SUM(CASE WHEN m.status = 'completed' THEN 1 ELSE 0 END) as completed_requests,
                        SUM(CASE WHEN m.status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
                        SUM(CASE WHEN m.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_requests
                    FROM properties p
                    LEFT JOIN units u ON p.id = u.property_id
                    LEFT JOIN maintenance_requests m ON u.id = m.unit_id 
                        AND m.created_at BETWEEN ? AND ?
                    WHERE (p.owner_id = ? OR p.manager_id = ? OR p.agent_id = ? OR p.caretaker_user_id = ?)
                    GROUP BY p.id, p.name
                    ORDER BY total_cost DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$startDate, $endDate, $userId, $userId, $userId, $userId]);
        }
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
