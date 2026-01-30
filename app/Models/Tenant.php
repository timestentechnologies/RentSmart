<?php

namespace App\Models;

class Tenant extends Model
{
    protected $table = 'tenants';

    public function getAll($userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        $sql = "SELECT t.*, 
                COALESCE(p.name, p2.name) as property_name,
                COALESCE(u.unit_number, u2.unit_number) as unit_number,
                l.rent_amount,
                l.start_date,
                l.end_date,
                (SELECT SUM(amount) FROM payments WHERE lease_id = l.id) as total_payments,
                (SELECT SUM(amount) FROM payments 
                 WHERE lease_id = l.id 
                 AND payment_date >= DATE_FORMAT(NOW() ,'%Y-%m-01')) as current_month_payment,
                (SELECT GROUP_CONCAT(
                    CONCAT(ut.utility_type, ': ', 
                        COALESCE(
                            (SELECT reading_value 
                             FROM utility_readings ur 
                             WHERE ur.utility_id = ut.id 
                             ORDER BY ur.reading_date DESC 
                             LIMIT 1),
                            'No reading'
                        )
                    )
                    SEPARATOR ', '
                )
                FROM utilities ut 
                WHERE ut.unit_id = COALESCE(u.id, u2.id)) as utility_readings
                FROM tenants t
                LEFT JOIN leases l ON t.id = l.tenant_id AND l.status = 'active'
                LEFT JOIN units u ON l.unit_id = u.id
                LEFT JOIN units u2 ON t.unit_id = u2.id
                LEFT JOIN properties p ON u.property_id = p.id
                LEFT JOIN properties p2 ON COALESCE(u.property_id, u2.property_id, t.property_id) = p2.id";

        $params = [];
        
        // Add role-based conditions
        if ($userId && !$user->isAdmin()) {
            $sql .= " WHERE (1=0";
            if ($user->isLandlord()) {
                $sql .= " OR p.owner_id = ? OR p2.owner_id = ?";
                $params[] = $userId;
                $params[] = $userId;
            }
            if ($user->isManager()) {
                $sql .= " OR p.manager_id = ? OR p2.manager_id = ?";
                $params[] = $userId;
                $params[] = $userId;
            }
            if ($user->isAgent()) {
                $sql .= " OR p.agent_id = ? OR p2.agent_id = ?";
                $params[] = $userId;
                $params[] = $userId;
            }
            if ($user->isCaretaker()) {
                $sql .= " OR p.caretaker_user_id = ? OR p2.caretaker_user_id = ?";
                $params[] = $userId;
                $params[] = $userId;
            }
            $sql .= ")";
        }
        
        $sql .= " ORDER BY t.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getById($id, $userId = null)
    {
        $user = new User();
        $user->find($userId);
        
        $sql = "SELECT t.*, 
                COALESCE(p.name, p2.name) as property_name,
                COALESCE(u.unit_number, u2.unit_number) as unit_number,
                l.rent_amount,
                l.start_date,
                l.end_date
                FROM tenants t
                LEFT JOIN leases l ON t.id = l.tenant_id AND l.status = 'active'
                LEFT JOIN units u ON l.unit_id = u.id
                LEFT JOIN units u2 ON t.unit_id = u2.id
                LEFT JOIN properties p ON u.property_id = p.id
                LEFT JOIN properties p2 ON COALESCE(u.property_id, u2.property_id, t.property_id) = p2.id
                WHERE t.id = ?";

        $params = [$id];
        
        // Add role-based conditions
        if (!$user->isAdmin()) {
            $sql .= " AND (1=0";
            if ($user->isLandlord()) {
                $sql .= " OR p.owner_id = ? OR p2.owner_id = ?";
                $params[] = $userId;
                $params[] = $userId;
            }
            if ($user->isManager()) {
                $sql .= " OR p.manager_id = ? OR p2.manager_id = ?";
                $params[] = $userId;
                $params[] = $userId;
            }
            if ($user->isAgent()) {
                $sql .= " OR p.agent_id = ? OR p2.agent_id = ?";
                $params[] = $userId;
                $params[] = $userId;
            }
            if ($user->isCaretaker()) {
                $sql .= " OR p.caretaker_user_id = ? OR p2.caretaker_user_id = ?";
                $params[] = $userId;
                $params[] = $userId;
            }
            $sql .= ")";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

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

    public function getActiveLeases($userId = null)
    {
        $user = new User();
        $user->find($userId);
        
        $sql = "SELECT t.*, 
                l.id as lease_id,
                l.rent_amount,
                l.start_date,
                l.end_date,
                u.unit_number,
                p.name as property_name
                FROM tenants t
                JOIN leases l ON t.id = l.tenant_id
                JOIN units u ON l.unit_id = u.id
                JOIN properties p ON u.property_id = p.id
                WHERE l.status = 'active'";

        $params = [];
        
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
            if ($user->isCaretaker()) {
                $sql .= " OR p.caretaker_user_id = ?";
                $params[] = $userId;
            }
            $sql .= ")";
        }
        
        $sql .= " ORDER BY t.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get all active tenants with their lease and unit information
     * @return array Array of active tenants
     */
    public function getActiveTenants()
    {
        $query = "SELECT 
                    t.*,
                    l.id as lease_id,
                    l.start_date,
                    l.end_date,
                    l.rent_amount,
                    l.status as lease_status,
                    u.unit_number,
                    p.name as property_name,
                    p.address as property_address,
                    (SELECT SUM(amount) 
                     FROM payments 
                     WHERE lease_id = l.id) as total_payments,
                    (SELECT COUNT(*) 
                     FROM payments 
                     WHERE lease_id = l.id 
                     AND payment_date > DATE_SUB(NOW(), INTERVAL 30 DAY)) as recent_payments_count
                FROM tenants t
                LEFT JOIN leases l ON t.id = l.tenant_id AND l.status = 'active'
                LEFT JOIN units u ON l.unit_id = u.id
                LEFT JOIN properties p ON u.property_id = p.id
                WHERE l.id IS NOT NULL
                ORDER BY t.last_name, t.first_name";

        try {
            $tenants = $this->query($query);
            
            // Process each tenant's data
            foreach ($tenants as &$tenant) {
                $tenant['full_name'] = $tenant['first_name'] . ' ' . $tenant['last_name'];
                $tenant['lease_duration'] = $this->calculateLeaseDuration($tenant['start_date'], $tenant['end_date']);
                $tenant['total_payments'] = floatval($tenant['total_payments'] ?? 0);
                $tenant['recent_payments_count'] = intval($tenant['recent_payments_count']);
                $tenant['payment_status'] = $this->determinePaymentStatus($tenant['total_payments'], $tenant['rent_amount']);
            }
            
            return $tenants;
        } catch (\PDOException $e) {
            error_log("Error in getActiveTenants: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get tenant statistics
     * @return array Tenant statistics
     */
    public function getTenantStatistics()
    {
        $query = "SELECT
                    COUNT(DISTINCT t.id) as total_tenants,
                    COUNT(DISTINCT CASE WHEN l.status = 'active' THEN t.id END) as active_tenants,
                    COUNT(DISTINCT CASE WHEN l.status = 'expired' THEN t.id END) as expired_leases,
                    COUNT(DISTINCT CASE WHEN l.end_date < NOW() THEN t.id END) as overdue_leases,
                    COUNT(DISTINCT CASE 
                        WHEN l.end_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY) 
                        THEN t.id 
                    END) as expiring_soon,
                    ROUND(AVG(l.rent_amount), 2) as average_rent
                FROM tenants t
                LEFT JOIN leases l ON t.id = l.tenant_id";

        try {
            $results = $this->query($query);
            $stats = $results[0] ?? [
                'total_tenants' => 0,
                'active_tenants' => 0,
                'expired_leases' => 0,
                'overdue_leases' => 0,
                'expiring_soon' => 0,
                'average_rent' => 0
            ];

            // Convert to appropriate types
            $stats['total_tenants'] = intval($stats['total_tenants']);
            $stats['active_tenants'] = intval($stats['active_tenants']);
            $stats['expired_leases'] = intval($stats['expired_leases']);
            $stats['overdue_leases'] = intval($stats['overdue_leases']);
            $stats['expiring_soon'] = intval($stats['expiring_soon']);
            $stats['average_rent'] = floatval($stats['average_rent']);

            return $stats;
        } catch (\PDOException $e) {
            error_log("Error in getTenantStatistics: " . $e->getMessage());
            return [
                'total_tenants' => 0,
                'active_tenants' => 0,
                'expired_leases' => 0,
                'overdue_leases' => 0,
                'expiring_soon' => 0,
                'average_rent' => 0
            ];
        }
    }

    /**
     * Calculate lease duration in months
     * @param string $start_date Start date
     * @param string $end_date End date
     * @return int Duration in months
     */
    private function calculateLeaseDuration($start_date, $end_date)
    {
        $start = new \DateTime($start_date);
        $end = new \DateTime($end_date);
        $interval = $start->diff($end);
        return ($interval->y * 12) + $interval->m;
    }

    /**
     * Determine payment status based on payments and rent
     * @param float $total_payments Total payments made
     * @param float $rent_amount Monthly rent amount
     * @return string Payment status
     */
    private function determinePaymentStatus($total_payments, $rent_amount)
    {
        if ($total_payments >= $rent_amount) {
            return 'current';
        } elseif ($total_payments > 0) {
            return 'partial';
        } else {
            return 'pending';
        }
    }

    /**
     * Find a tenant by email
     */
    public function findByEmail($email)
    {
        $sql = "SELECT * FROM tenants WHERE email = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Verify tenant password
     */
    public function verifyPassword($tenant, $password)
    {
        if (!isset($tenant['password'])) return false;
        return password_verify($password, $tenant['password']);
    }

    /**
     * Set (hash) password for a tenant
     */
    public function setPassword($id, $password)
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE tenants SET password = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$hash, $id]);
    }

    /**
     * Get all tenants for reports
     */
    public function getAllTenants($userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        if (!$userData) {
            return [];
        }

        // For admin users
        if ($userData['role'] === 'admin') {
            $sql = "SELECT 
                        t.*,
                        l.id as lease_id,
                        l.start_date,
                        l.end_date,
                        l.rent_amount,
                        l.status as lease_status,
                        u.unit_number,
                        p.name as property_name
                    FROM tenants t
                    LEFT JOIN leases l ON t.id = l.tenant_id AND l.status = 'active'
                    LEFT JOIN units u ON l.unit_id = u.id
                    LEFT JOIN properties p ON u.property_id = p.id
                    ORDER BY t.name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } else {
            // For regular users
            $sql = "SELECT 
                        t.*,
                        l.id as lease_id,
                        l.start_date,
                        l.end_date,
                        l.rent_amount,
                        l.status as lease_status,
                        u.unit_number,
                        p.name as property_name
                    FROM tenants t
                    LEFT JOIN leases l ON t.id = l.tenant_id AND l.status = 'active'
                    LEFT JOIN units u ON l.unit_id = u.id
                    LEFT JOIN properties p ON u.property_id = p.id
                    WHERE (p.owner_id = ? OR p.manager_id = ? OR p.agent_id = ? OR p.caretaker_user_id = ?)
                    ORDER BY t.name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $userId, $userId, $userId]);
        }
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get delinquent tenants for reports
     */
    public function getDelinquentTenants($userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        if (!$userData) {
            return [];
        }

        // For admin users
        if ($userData['role'] === 'admin') {
            $sql = "SELECT 
                        t.*,
                        l.rent_amount,
                        u.unit_number,
                        p.name as property_name,
                        COALESCE(SUM(pay.amount), 0) as total_paid,
                        (l.rent_amount - COALESCE(SUM(pay.amount), 0)) as balance_due,
                        MAX(pay.payment_date) as last_payment_date
                    FROM tenants t
                    INNER JOIN leases l ON t.id = l.tenant_id AND l.status = 'active'
                    INNER JOIN units u ON l.unit_id = u.id
                    INNER JOIN properties p ON u.property_id = p.id
                    LEFT JOIN payments pay ON l.id = pay.lease_id 
                        AND pay.status IN ('completed', 'verified')
                        AND MONTH(pay.payment_date) = MONTH(CURRENT_DATE())
                        AND YEAR(pay.payment_date) = YEAR(CURRENT_DATE())
                    GROUP BY t.id, l.rent_amount, u.unit_number, p.name
                    HAVING balance_due > 0
                    ORDER BY balance_due DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } else {
            // For regular users
            $sql = "SELECT 
                        t.*,
                        l.rent_amount,
                        u.unit_number,
                        p.name as property_name,
                        COALESCE(SUM(pay.amount), 0) as total_paid,
                        (l.rent_amount - COALESCE(SUM(pay.amount), 0)) as balance_due,
                        MAX(pay.payment_date) as last_payment_date
                    FROM tenants t
                    INNER JOIN leases l ON t.id = l.tenant_id AND l.status = 'active'
                    INNER JOIN units u ON l.unit_id = u.id
                    INNER JOIN properties p ON u.property_id = p.id
                    LEFT JOIN payments pay ON l.id = pay.lease_id 
                        AND pay.status IN ('completed', 'verified')
                        AND MONTH(pay.payment_date) = MONTH(CURRENT_DATE())
                        AND YEAR(pay.payment_date) = YEAR(CURRENT_DATE())
                    WHERE (p.owner_id = ? OR p.manager_id = ? OR p.agent_id = ? OR p.caretaker_user_id = ?)
                    GROUP BY t.id, l.rent_amount, u.unit_number, p.name
                    HAVING balance_due > 0
                    ORDER BY balance_due DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $userId, $userId, $userId]);
        }
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
} 