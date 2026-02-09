<?php

namespace App\Models;

use PDO;
use DateTime;

class Lease extends Model
{
    protected $table = 'leases';

    public function getAll($userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        $sql = "SELECT l.*,
                t.name as tenant_name,
                t.phone as tenant_phone,
                u.unit_number,
                p.name as property_name
                FROM leases l
                JOIN tenants t ON l.tenant_id = t.id
                JOIN units u ON l.unit_id = u.id
                JOIN properties p ON u.property_id = p.id";

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
            // Caretaker assigned to property
            $sql .= " OR p.caretaker_user_id = ?";
            $params[] = $userId;
            $sql .= ")";
        }
        
        $sql .= " ORDER BY l.start_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getById($id, $userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        $sql = "SELECT l.*,
                t.name as tenant_name,
                t.phone as tenant_phone,
                u.unit_number,
                p.name as property_name,
                p.id as property_id
                FROM leases l
                JOIN tenants t ON l.tenant_id = t.id
                JOIN units u ON l.unit_id = u.id
                JOIN properties p ON u.property_id = p.id
                WHERE l.id = ?";

        $params = [$id];
        
        // Add role-based conditions
        if ($userId && !$user->isAdmin()) {
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

    public function getActiveLeases($userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        $sql = "SELECT l.*,
                t.name as tenant_name,
                t.phone as tenant_phone,
                u.unit_number,
                p.name as property_name
                FROM leases l
                JOIN tenants t ON l.tenant_id = t.id
                JOIN units u ON l.unit_id = u.id
                JOIN properties p ON u.property_id = p.id
                WHERE l.status = 'active'";

        $params = [];
        
        // Add role-based conditions
        if ($userId && !$user->isAdmin()) {
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
        
        $sql .= " ORDER BY l.end_date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getExpiringLeases($days = 30, $userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        $sql = "SELECT l.*,
                t.name as tenant_name,
                t.phone as tenant_phone,
                u.unit_number,
                p.name as property_name
                FROM leases l
                JOIN tenants t ON l.tenant_id = t.id
                JOIN units u ON l.unit_id = u.id
                JOIN properties p ON u.property_id = p.id
                WHERE l.status = 'active'
                AND l.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)";

        $params = [$days];
        
        // Add role-based conditions
        if ($userId && !$user->isAdmin()) {
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
                $sql .= " OR p.manager_id = (SELECT manager_id FROM users WHERE id = ?)";
                $params[] = $userId;
            }
            $sql .= ")";
        }
        
        $sql .= " ORDER BY l.end_date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getExpectedRevenue()
    {
        $sql = "SELECT SUM(rent_amount) as total FROM {$this->table} WHERE status = 'active'";
        $result = $this->query($sql);
        return $result[0]['total'] ?? 0;
    }

    public function getUpcomingVacancies($daysThreshold = 30, $userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        $sql = "SELECT l.*, 
                       p.name as property_name, 
                       u.unit_number,
                       CONCAT(t.first_name, ' ', t.last_name) as tenant_name
                FROM {$this->table} l
                JOIN units u ON l.unit_id = u.id
                JOIN properties p ON u.property_id = p.id
                JOIN tenants t ON l.tenant_id = t.id
                WHERE l.status = 'active' 
                AND l.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)";
                
        $params = [$daysThreshold];
        
        // Add role-based conditions
        if ($userId && !$user->isAdmin()) {
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
                $sql .= " OR p.manager_id = (SELECT manager_id FROM users WHERE id = ?)";
                $params[] = $userId;
            }
            $sql .= ")";
        }
        
        $sql .= " ORDER BY l.end_date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getLeaseRenewals($timeframe = '-30 days', $userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        $sql = "SELECT l.*, 
                       p.name as property_name, 
                       u.unit_number,
                       CONCAT(t.first_name, ' ', t.last_name) as tenant_name,
                       l.rent_amount as new_rent,
                       (SELECT rent_amount 
                        FROM {$this->table} 
                        WHERE unit_id = l.unit_id 
                        AND end_date < l.start_date 
                        ORDER BY end_date DESC 
                        LIMIT 1) as old_rent
                FROM {$this->table} l
                JOIN units u ON l.unit_id = u.id
                JOIN properties p ON u.property_id = p.id
                JOIN tenants t ON l.tenant_id = t.id
                WHERE l.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                AND EXISTS (
                    SELECT 1 
                    FROM {$this->table} prev 
                    WHERE prev.unit_id = l.unit_id 
                    AND prev.end_date < l.start_date
                )";
                
        $params = [];
        
        // Add role-based conditions
        if ($userId && !$user->isAdmin()) {
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
                $sql .= " OR p.manager_id = (SELECT manager_id FROM users WHERE id = ?)";
                $params[] = $userId;
            }
            $sql .= ")";
        }
        
        $sql .= " ORDER BY l.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getRentIncreases($userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        $sql = "SELECT l.*,
                       p.name as property_name,
                       u.unit_number,
                       CONCAT(t.first_name, ' ', t.last_name) as tenant_name,
                       l.rent_amount as new_rent,
                       prev.rent_amount as old_rent,
                       ((l.rent_amount - prev.rent_amount) / prev.rent_amount * 100) as increase_percentage
                FROM {$this->table} l
                JOIN units u ON l.unit_id = u.id
                JOIN properties p ON u.property_id = p.id
                JOIN tenants t ON l.tenant_id = t.id
                JOIN {$this->table} prev ON prev.unit_id = l.unit_id 
                    AND prev.end_date < l.start_date
                    AND NOT EXISTS (
                        SELECT 1 
                        FROM {$this->table} mid 
                        WHERE mid.unit_id = l.unit_id 
                        AND mid.end_date > prev.end_date 
                        AND mid.end_date < l.start_date
                    )
                WHERE l.rent_amount > prev.rent_amount";
                
        $params = [];
        
        // Add role-based conditions
        if ($userId && !$user->isAdmin()) {
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
                $sql .= " OR p.manager_id = (SELECT manager_id FROM users WHERE id = ?)";
                $params[] = $userId;
            }
            $sql .= ")";
        }
        
        $sql .= " ORDER BY l.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getActiveLeaseByTenant($tenantId)
    {
        $sql = "SELECT * FROM leases WHERE tenant_id = ? AND status = 'active' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tenantId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getLatestInactiveLeaseByTenant($tenantId)
    {
        $sql = "SELECT * FROM leases WHERE tenant_id = ? AND status = 'inactive' ORDER BY updated_at DESC, id DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tenantId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get tenant lease statistics for reports
     */
    public function getTenantLeaseStats($userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        if (!$userData) {
            return [];
        }

        // For admin users
        if ($userData['role'] === 'admin') {
            $sql = "SELECT 
                        COUNT(*) as total_leases,
                        SUM(CASE WHEN l.status = 'active' THEN 1 ELSE 0 END) as active_leases,
                        SUM(CASE WHEN l.status = 'expired' THEN 1 ELSE 0 END) as expired_leases,
                        SUM(CASE WHEN l.status = 'terminated' THEN 1 ELSE 0 END) as terminated_leases,
                        AVG(l.rent_amount) as average_rent,
                        SUM(l.rent_amount) as total_monthly_rent
                    FROM leases l
                    INNER JOIN units u ON l.unit_id = u.id
                    INNER JOIN properties p ON u.property_id = p.id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } else {
            // For regular users
            $sql = "SELECT 
                        COUNT(*) as total_leases,
                        SUM(CASE WHEN l.status = 'active' THEN 1 ELSE 0 END) as active_leases,
                        SUM(CASE WHEN l.status = 'expired' THEN 1 ELSE 0 END) as expired_leases,
                        SUM(CASE WHEN l.status = 'terminated' THEN 1 ELSE 0 END) as terminated_leases,
                        AVG(l.rent_amount) as average_rent,
                        SUM(l.rent_amount) as total_monthly_rent
                    FROM leases l
                    INNER JOIN units u ON l.unit_id = u.id
                    INNER JOIN properties p ON u.property_id = p.id
                    WHERE (p.owner_id = ? OR p.manager_id = ? OR p.agent_id = ? OR p.caretaker_user_id = ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $userId, $userId, $userId]);
        }
        
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get lease history for reports
     */
    public function getLeaseHistory($userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        if (!$userData) {
            return [];
        }

        // For admin users
        if ($userData['role'] === 'admin') {
            $sql = "SELECT 
                        l.*,
                        t.name as tenant_name,
                        t.email as tenant_email,
                        t.phone as tenant_phone,
                        u.unit_number,
                        p.name as property_name
                    FROM leases l
                    INNER JOIN tenants t ON l.tenant_id = t.id
                    INNER JOIN units u ON l.unit_id = u.id
                    INNER JOIN properties p ON u.property_id = p.id
                    ORDER BY l.start_date DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } else {
            // For regular users
            $sql = "SELECT 
                        l.*,
                        t.name as tenant_name,
                        t.email as tenant_email,
                        t.phone as tenant_phone,
                        u.unit_number,
                        p.name as property_name
                    FROM leases l
                    INNER JOIN tenants t ON l.tenant_id = t.id
                    INNER JOIN units u ON l.unit_id = u.id
                    INNER JOIN properties p ON u.property_id = p.id
                    WHERE (p.owner_id = ? OR p.manager_id = ? OR p.agent_id = ? OR p.caretaker_user_id = ?)
                    ORDER BY l.start_date DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $userId, $userId, $userId]);
        }
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
} 