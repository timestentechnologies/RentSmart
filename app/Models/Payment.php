<?php

namespace App\Models;

use App\Database\Connection;
use PDO;

class Payment extends Model
{
    protected $table = 'payments';

    public function getAll($userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        $sql = "SELECT p.*,
                t.name as tenant_name,
                u.unit_number,
                pr.name as property_name
                FROM payments p
                JOIN leases l ON p.lease_id = l.id
                JOIN tenants t ON l.tenant_id = t.id
                JOIN units u ON l.unit_id = u.id
                JOIN properties pr ON u.property_id = pr.id";

        $params = [];
        
        // Add role-based conditions
        if ($userId && !$user->isAdmin()) {
            $sql .= " WHERE (1=0";
            if ($user->isLandlord()) {
                $sql .= " OR pr.owner_id = ?";
                $params[] = $userId;
            }
            if ($user->isManager()) {
                $sql .= " OR pr.manager_id = ?";
                $params[] = $userId;
            }
            if ($user->isAgent()) {
                $sql .= " OR pr.agent_id = ?";
                $params[] = $userId;
            }
            // Caretaker assigned to property
            $sql .= " OR pr.caretaker_user_id = ?";
            $params[] = $userId;
            $sql .= ")";
        }
        
        $sql .= " ORDER BY p.payment_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getById($id, $userId = null)
    {
        $user = new User();
        $user->find($userId);
        
        $sql = "SELECT p.*,\n                t.name as tenant_name,\n                t.email as tenant_email,\n                t.phone as tenant_phone,\n                u.unit_number,\n                pr.name as property_name,\n                pr.address as property_address,\n                pr.city as property_city,\n                pr.state as property_state,\n                pr.zip_code as property_zip,\n                pr.owner_id as property_owner_id,\n                pr.manager_id as property_manager_id,\n                ut.utility_type,\n                mmp.phone_number, mmp.transaction_code, mmp.verification_status\n                FROM payments p\n                JOIN leases l ON p.lease_id = l.id\n                JOIN tenants t ON l.tenant_id = t.id\n                JOIN units u ON l.unit_id = u.id\n                JOIN properties pr ON u.property_id = pr.id\n                LEFT JOIN utilities ut ON p.utility_id = ut.id\n                LEFT JOIN manual_mpesa_payments mmp ON p.id = mmp.payment_id\n                WHERE p.id = ?";
        $params = [$id];
        
        // Add role-based conditions
        if (!$user->isAdmin()) {
            $sql .= " AND (1=0";
            if ($user->isLandlord()) {
                $sql .= " OR pr.owner_id = ?";
                $params[] = $userId;
            }
            if ($user->isManager()) {
                $sql .= " OR pr.manager_id = ?";
                $params[] = $userId;
            }
            if ($user->isAgent()) {
                $sql .= " OR pr.agent_id = ?";
                $params[] = $userId;
            }
            // Caretaker assigned to property
            $sql .= " OR pr.caretaker_user_id = ?";
            $params[] = $userId;
            $sql .= ")";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $payment = $stmt->fetch(\PDO::FETCH_ASSOC);
        // Fetch manager/owner name
        if ($payment) {
            $ownerName = null;
            $managerName = null;
            $userModel = new User();
            if (!empty($payment['property_owner_id'])) {
                $owner = $userModel->find($payment['property_owner_id']);
                $ownerName = $owner['name'] ?? null;
            }
            if (!empty($payment['property_manager_id'])) {
                $manager = $userModel->find($payment['property_manager_id']);
                $managerName = $manager['name'] ?? null;
            }
            if (!empty($payment['property_agent_id'])) {
                $agent = $userModel->find($payment['property_agent_id']);
                $agentName = $agent['name'] ?? null;
            }
            $payment['property_owner_name'] = $ownerName;
            $payment['property_manager_name'] = $managerName;
            $payment['property_agent_name'] = $agentName;
        }
        return $payment;
    }

    public function getMonthlyRevenue($userId = null)
    {
        $user = new User();
        $user->find($userId);
        
        $sql = "SELECT 
                DATE_FORMAT(p.payment_date, '%Y-%m') as month,
                SUM(p.amount) as total_amount,
                COUNT(*) as payment_count
                FROM payments p
                JOIN leases l ON p.lease_id = l.id
                JOIN units u ON l.unit_id = u.id
                JOIN properties pr ON u.property_id = pr.id
                WHERE p.payment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";

        $params = [];
        
        // Add role-based conditions
        if (!$user->isAdmin()) {
            $sql .= " AND (1=0";
            if ($user->isLandlord()) {
                $sql .= " OR pr.owner_id = ?";
                $params[] = $userId;
            }
            if ($user->isManager()) {
                $sql .= " OR pr.manager_id = ?";
                $params[] = $userId;
            }
            if ($user->isAgent()) {
                $sql .= " OR pr.agent_id = ?";
                $params[] = $userId;
            }
            // Caretaker assigned to property
            $sql .= " OR pr.caretaker_user_id = ?";
            $params[] = $userId;
            $sql .= ")";
        }
        
        $sql .= " GROUP BY DATE_FORMAT(p.payment_date, '%Y-%m')
                  ORDER BY month DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getPaymentsByProperty($userId = null)
    {
        $user = new User();
        $user->find($userId);
        
        $sql = "SELECT 
                pr.id as property_id,
                pr.name as property_name,
                SUM(p.amount) as total_amount,
                COUNT(*) as payment_count
                FROM payments p
                JOIN leases l ON p.lease_id = l.id
                JOIN units u ON l.unit_id = u.id
                JOIN properties pr ON u.property_id = pr.id
                WHERE p.payment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";

        $params = [];
        
        // Add role-based conditions
        if (!$user->isAdmin()) {
            $sql .= " AND (1=0";
            if ($user->isLandlord()) {
                $sql .= " OR pr.owner_id = ?";
                $params[] = $userId;
            }
            if ($user->isManager()) {
                $sql .= " OR pr.manager_id = ?";
                $params[] = $userId;
            }
            if ($user->isAgent()) {
                $sql .= " OR pr.agent_id = ?";
                $params[] = $userId;
            }
            // Caretaker assigned to property
            $sql .= " OR pr.caretaker_user_id = ?";
            $params[] = $userId;
            $sql .= ")";
        }
        
        $sql .= " GROUP BY pr.id, pr.name
                  ORDER BY total_amount DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getPaymentsWithTenantInfo($userId = null)
    {
        $user = new User();
        $user->find($userId);
        
        $sql = "SELECT p.*, 
                t.name as tenant_name, 
                t.id as tenant_id,
                u.unit_number,
                pr.name as property_name,
                mmp.phone_number,
                mmp.transaction_code,
                mmp.verification_status as mpesa_status,
                ut.utility_type
                FROM payments p 
                JOIN leases l ON p.lease_id = l.id 
                JOIN tenants t ON l.tenant_id = t.id 
                JOIN units u ON l.unit_id = u.id
                JOIN properties pr ON u.property_id = pr.id
                LEFT JOIN manual_mpesa_payments mmp ON p.id = mmp.payment_id
                LEFT JOIN utilities ut ON p.utility_id = ut.id";

        $params = [];
        
        // Add role-based conditions
        if (!$user->isAdmin()) {
            $sql .= " WHERE (1=0";
            if ($user->isLandlord()) {
                $sql .= " OR pr.owner_id = ?";
                $params[] = $userId;
            }
            if ($user->isManager()) {
                $sql .= " OR pr.manager_id = ?";
                $params[] = $userId;
            }
            if ($user->isAgent()) {
                $sql .= " OR pr.agent_id = ?";
                $params[] = $userId;
            }
            // Caretaker assigned to property
            $sql .= " OR pr.caretaker_user_id = ?";
            $params[] = $userId;
            $sql .= ")";
        }
        
        $sql .= " ORDER BY p.payment_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getActiveLease($tenantId, $userId = null)
    {
        $user = new User();
        $user->find($userId);
        
        $sql = "SELECT l.* 
                FROM leases l
                JOIN units u ON l.unit_id = u.id
                JOIN properties pr ON u.property_id = pr.id
                WHERE l.tenant_id = ? 
                AND l.status = 'active'";

        $params = [$tenantId];
        
        // Add role-based conditions
        if (!$user->isAdmin()) {
            $sql .= " AND (1=0";
            if ($user->isLandlord()) {
                $sql .= " OR pr.owner_id = ?";
                $params[] = $userId;
            }
            if ($user->isManager()) {
                $sql .= " OR pr.manager_id = ?";
                $params[] = $userId;
            }
            if ($user->isAgent()) {
                $sql .= " OR pr.agent_id = ?";
                $params[] = $userId;
            }
            // Caretaker assigned to property
            $sql .= " OR pr.caretaker_user_id = ?";
            $params[] = $userId;
            $sql .= ")";
        }
        
        $sql .= " LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getRecent($limit = 5, $userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        $sql = "SELECT p.*, t.name as tenant_name 
                FROM payments p 
                JOIN leases l ON p.lease_id = l.id 
                JOIN tenants t ON l.tenant_id = t.id ";
        $params = [];
        // Role-based filtering
        if ($userId && !$user->isAdmin()) {
            $sql .= " WHERE (1=0";
            if ($user->isLandlord()) {
                $sql .= " OR l.unit_id IN (SELECT id FROM units WHERE property_id IN (SELECT id FROM properties WHERE owner_id = ?))";
                $params[] = $userId;
            }
            if ($user->isManager()) {
                $sql .= " OR l.unit_id IN (SELECT id FROM units WHERE property_id IN (SELECT id FROM properties WHERE manager_id = ?))";
                $params[] = $userId;
            }
            if ($user->isAgent()) {
                $sql .= " OR l.unit_id IN (SELECT id FROM units WHERE property_id IN (SELECT id FROM properties WHERE agent_id = ?))";
                $params[] = $userId;
            }
            // Caretaker assigned to property
            $sql .= " OR l.unit_id IN (SELECT id FROM units WHERE property_id IN (SELECT id FROM properties WHERE caretaker_user_id = ?))";
            $params[] = $userId;
            // Tenant: only their own payments
            if (isset($userData['role']) && $userData['role'] === 'tenant') {
                // Find tenant_id for this user
                $tenantModel = new \App\Models\Tenant();
                $tenant = $tenantModel->findByEmail($userData['email']);
                if ($tenant && isset($tenant['id'])) {
                    $sql .= " OR l.tenant_id = ?";
                    $params[] = $tenant['id'];
                }
            }
            $sql .= ")";
        }
        $sql .= " ORDER BY p.payment_date DESC LIMIT ?";
        $params[] = $limit;
        return $this->query($sql, $params);
    }

    public function getTotalRevenue()
    {
        $sql = "SELECT COALESCE(SUM(amount), 0) as total 
                FROM payments 
                WHERE YEAR(payment_date) = YEAR(CURRENT_DATE()) AND MONTH(payment_date) = MONTH(CURRENT_DATE())";
        $result = $this->query($sql);
        return $result[0]['total'] ?? 0;
    }

    public function getLastMonthRevenue()
    {
        $sql = "SELECT COALESCE(SUM(amount), 0) as total 
                FROM payments 
                WHERE YEAR(payment_date) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH) AND MONTH(payment_date) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH)";
        $result = $this->query($sql);
        return $result[0]['total'] ?? 0;
    }

    public function getOutstandingBalance($userId = null)
    {
        try {
            $user = new User();
            
            // Only proceed with role check if userId is provided
            $isAdmin = false;
            $userData = null;
            if ($userId) {
                $userData = $user->find($userId);
                if (!$userData) {
                    return 0; // Return 0 if user not found
                }
                $isAdmin = ($userData['role'] === 'admin' || $userData['role'] === 'administrator');
            }
            
            // Calculate outstanding balance for rent and utilities separately
            $sql = "SELECT 
                    l.id as lease_id,
                    l.rent_amount,
                    l.start_date,
                    l.end_date,
                    u.id as unit_id,
                    t.id as tenant_id,
                    t.first_name,
                    t.last_name
                FROM leases l
                JOIN units u ON l.unit_id = u.id
                JOIN properties pr ON u.property_id = pr.id
                JOIN tenants t ON l.tenant_id = t.id
                WHERE l.status = 'active'";

            $params = [];
            
            // Add role-based conditions only if user is not admin
            if ($userId && !$isAdmin) {
                $sql .= " AND (";
                $conditions = [];
                
                if ($userData['role'] === 'landlord') {
                    $conditions[] = "pr.owner_id = ?";
                    $params[] = $userId;
                }
                if ($userData['role'] === 'manager') {
                    $conditions[] = "pr.manager_id = ?";
                    $params[] = $userId;
                }
                if ($userData['role'] === 'agent') {
                    $conditions[] = "pr.agent_id = ?";
                    $params[] = $userId;
                }
                if ($userData['role'] === 'caretaker') {
                    $conditions[] = "pr.caretaker_user_id = ?";
                    $params[] = $userId;
                }
                
                if (empty($conditions)) {
                    $conditions[] = "1=0"; // No access if role doesn't match
                }
                
                $sql .= implode(" OR ", $conditions) . ")";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $leases = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $totalOutstanding = 0;
            
            foreach ($leases as $lease) {
                // Calculate rent outstanding (simplified - just current month)
                $monthlyRent = $lease['rent_amount'];
                
                // Get rent payments for this lease
                $rentStmt = $this->db->prepare("
                    SELECT SUM(amount) as total_paid
                    FROM payments 
                    WHERE lease_id = ? AND payment_type = 'rent' AND status = 'completed'
                ");
                $rentStmt->execute([$lease['lease_id']]);
                $rentPaid = $rentStmt->fetch(\PDO::FETCH_ASSOC);
                $rentPaidAmount = $rentPaid['total_paid'] ?? 0;
                
                $rentOutstanding = max(0, $monthlyRent - $rentPaidAmount);
                $totalOutstanding += $rentOutstanding;
                
                // Calculate utility outstanding
                $utilityStmt = $this->db->prepare("
                    SELECT u.*, 
                           CASE 
                               WHEN u.is_metered = 1 THEN IFNULL(ur.cost, 0)
                               ELSE IFNULL(u.flat_rate, 0)
                           END as amount
                    FROM utilities u
                    LEFT JOIN (
                        SELECT ur1.*
                        FROM utility_readings ur1
                        INNER JOIN (
                            SELECT utility_id, MAX(id) as max_id
                            FROM utility_readings
                            GROUP BY utility_id
                        ) ur2 ON ur1.utility_id = ur2.utility_id AND ur1.id = ur2.max_id
                    ) ur ON u.id = ur.utility_id
                    WHERE u.unit_id = ?
                ");
                $utilityStmt->execute([$lease['unit_id']]);
                $utilities = $utilityStmt->fetchAll(\PDO::FETCH_ASSOC);
                
                foreach ($utilities as $utility) {
                    $utilityAmount = $utility['amount'];
                    
                    // Get utility payments for this specific utility
                    $utilityPaymentStmt = $this->db->prepare("
                        SELECT SUM(amount) as total_paid
                        FROM payments 
                        WHERE lease_id = ? AND utility_id = ? AND payment_type = 'utility' AND status = 'completed'
                    ");
                    $utilityPaymentStmt->execute([$lease['lease_id'], $utility['id']]);
                    $utilityPaid = $utilityPaymentStmt->fetch(\PDO::FETCH_ASSOC);
                    $utilityPaidAmount = $utilityPaid['total_paid'] ?? 0;
                    
                    $utilityOutstanding = max(0, $utilityAmount - $utilityPaidAmount);
                    $totalOutstanding += $utilityOutstanding;
                }
            }
            
            return $totalOutstanding;
            
        } catch (\Exception $e) {
            error_log("Error in getOutstandingBalance: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return 0; // Return 0 on error to prevent breaking the dashboard
        }
    }

    public function getOutstandingTenantsCount($userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        $sql = "SELECT COUNT(DISTINCT l.tenant_id) as count
                FROM leases l
                JOIN units u ON l.unit_id = u.id
                JOIN properties pr ON u.property_id = pr.id
                LEFT JOIN payments p ON l.id = p.lease_id 
                    AND YEAR(p.payment_date) = YEAR(CURRENT_DATE()) 
                    AND MONTH(p.payment_date) = MONTH(CURRENT_DATE())
                WHERE l.status = 'active'
                AND (p.id IS NULL OR p.amount < l.rent_amount)";
        $params = [];
        // Role-based filtering
        if ($userId && !$user->isAdmin()) {
            $roleFilter = [];
            if ($user->isLandlord()) {
                $roleFilter[] = "pr.owner_id = ?";
                $params[] = $userId;
            }
            if ($user->isManager()) {
                $roleFilter[] = "pr.manager_id = ?";
                $params[] = $userId;
            }
            if ($user->isAgent()) {
                $roleFilter[] = "pr.agent_id = ?";
                $params[] = $userId;
            }
            if (isset($userData['role']) && $userData['role'] === 'caretaker') {
                $roleFilter[] = "pr.caretaker_user_id = ?";
                $params[] = $userId;
            }
            if (isset($userData['role']) && $userData['role'] === 'tenant') {
                $tenantModel = new \App\Models\Tenant();
                $tenant = $tenantModel->findByEmail($userData['email']);
                if ($tenant && isset($tenant['id'])) {
                    $roleFilter[] = "l.tenant_id = ?";
                    $params[] = $tenant['id'];
                }
            }
            if (!empty($roleFilter)) {
                $sql .= ' AND (' . implode(' OR ', $roleFilter) . ')';
            }
        }
        $result = $this->query($sql, $params);
        return $result[0]['count'] ?? 0;
    }

    public function getRevenueForPeriod($startDate, $endDate, $userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        $sql = "SELECT COALESCE(SUM(p.amount), 0) as total 
                FROM payments p 
                JOIN leases l ON p.lease_id = l.id 
                JOIN tenants t ON l.tenant_id = t.id ";
        $params = [];
        $where = [];
        $where[] = "p.payment_date BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
        // Role-based filtering
        if ($userId && !$user->isAdmin()) {
            $roleFilter = [];
            if ($user->isLandlord()) {
                $roleFilter[] = "l.unit_id IN (SELECT id FROM units WHERE property_id IN (SELECT id FROM properties WHERE owner_id = ?))";
                $params[] = $userId;
            }
            if ($user->isManager()) {
                $roleFilter[] = "l.unit_id IN (SELECT id FROM units WHERE property_id IN (SELECT id FROM properties WHERE manager_id = ?))";
                $params[] = $userId;
            }
            if ($user->isAgent()) {
                $roleFilter[] = "l.unit_id IN (SELECT id FROM units WHERE property_id IN (SELECT id FROM properties WHERE agent_id = ?))";
                $params[] = $userId;
            }
            // Caretaker assigned to property
            if ($user->isCaretaker()) {
                $roleFilter[] = "l.unit_id IN (SELECT id FROM units WHERE property_id IN (SELECT id FROM properties WHERE caretaker_user_id = ?))";
                $params[] = $userId;
            }
            if (isset($userData['role']) && $userData['role'] === 'tenant') {
                $tenantModel = new \App\Models\Tenant();
                $tenant = $tenantModel->findByEmail($userData['email']);
                if ($tenant && isset($tenant['id'])) {
                    $roleFilter[] = "l.tenant_id = ?";
                    $params[] = $tenant['id'];
                }
            }
            if (!empty($roleFilter)) {
                $where[] = '(' . implode(' OR ', $roleFilter) . ')';
            }
        }
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $result = $this->query($sql, $params);
        return $result[0]['total'] ?? 0;
    }

    public function getCollectionRate($startDate, $endDate)
    {
        $sql = "SELECT 
                    (COALESCE(SUM(p.amount), 0) / COALESCE(SUM(l.rent_amount), 0) * 100) as collection_rate
                FROM leases l
                LEFT JOIN payments p ON l.id = p.lease_id 
                    AND p.payment_date BETWEEN ? AND ?
                WHERE l.status = 'active'
                AND l.start_date <= ?";
        
        $result = $this->query($sql, [$startDate, $endDate, $endDate]);
        return round($result[0]['collection_rate'] ?? 0, 2);
    }

    public function getPaymentsForPeriod($startDate, $endDate)
    {
        $sql = "SELECT p.*,
                       t.name as tenant_name,
                       pr.name as property_name,
                       u.unit_number
                FROM payments p
                JOIN leases l ON p.lease_id = l.id
                JOIN tenants t ON l.tenant_id = t.id
                JOIN units u ON l.unit_id = u.id
                JOIN properties pr ON u.property_id = pr.id
                WHERE p.payment_date BETWEEN ? AND ?
                ORDER BY p.payment_date DESC";
        
        return $this->query($sql, [$startDate, $endDate]);
    }

    public function getRevenueByProperty($startDate, $endDate)
    {
        $sql = "SELECT 
                    pr.name as property_name,
                    COALESCE(SUM(p.amount), 0) as total_revenue,
                    COUNT(DISTINCT l.id) as total_leases,
                    COUNT(DISTINCT p.id) as total_payments
                FROM properties pr
                LEFT JOIN units u ON pr.id = u.property_id
                LEFT JOIN leases l ON u.id = l.unit_id
                LEFT JOIN payments p ON l.id = p.lease_id 
                    AND p.payment_date BETWEEN ? AND ?
                GROUP BY pr.id, pr.name
                ORDER BY total_revenue DESC";
        
        return $this->query($sql, [$startDate, $endDate]);
    }

    public function getRevenueByType($startDate, $endDate)
    {
        $sql = "SELECT 
                    payment_type,
                    COALESCE(SUM(amount), 0) as total_amount,
                    COUNT(*) as count
                FROM payments
                WHERE payment_date BETWEEN ? AND ?
                GROUP BY payment_type
                ORDER BY total_amount DESC";
        
        return $this->query($sql, [$startDate, $endDate]);
    }

    public function getTotalDelinquent()
    {
        $sql = "SELECT 
                    COALESCE(SUM(l.rent_amount - COALESCE(p.paid_amount, 0)), 0) as total_delinquent
                FROM leases l
                LEFT JOIN (
                    SELECT lease_id, SUM(amount) as paid_amount
                    FROM payments
                    WHERE YEAR(payment_date) = YEAR(CURRENT_DATE())
                    AND MONTH(payment_date) = MONTH(CURRENT_DATE())
                    GROUP BY lease_id
                ) p ON l.id = p.lease_id
                WHERE l.status = 'active'
                AND (p.paid_amount IS NULL OR p.paid_amount < l.rent_amount)";
        
        $result = $this->query($sql);
        return $result[0]['total_delinquent'] ?? 0;
    }

    public function getDelinquencyRate()
    {
        $sql = "SELECT 
                    (COUNT(CASE WHEN p.paid_amount IS NULL OR p.paid_amount < l.rent_amount THEN 1 END) * 100.0 / COUNT(*)) as delinquency_rate
                FROM leases l
                LEFT JOIN (
                    SELECT lease_id, SUM(amount) as paid_amount
                    FROM payments
                    WHERE YEAR(payment_date) = YEAR(CURRENT_DATE())
                    AND MONTH(payment_date) = MONTH(CURRENT_DATE())
                    GROUP BY lease_id
                ) p ON l.id = p.lease_id
                WHERE l.status = 'active'";
        
        $result = $this->query($sql);
        return round($result[0]['delinquency_rate'] ?? 0, 2);
    }

    public function getDelinquentTenants()
    {
        $sql = "SELECT 
                    t.id,
                    t.name,
                    t.phone,
                    t.email,
                    l.rent_amount,
                    COALESCE(p.paid_amount, 0) as paid_amount,
                    (l.rent_amount - COALESCE(p.paid_amount, 0)) as outstanding_amount,
                    pr.name as property_name,
                    u.unit_number
                FROM leases l
                JOIN tenants t ON l.tenant_id = t.id
                JOIN units u ON l.unit_id = u.id
                JOIN properties pr ON u.property_id = pr.id
                LEFT JOIN (
                    SELECT lease_id, SUM(amount) as paid_amount
                    FROM payments
                    WHERE YEAR(payment_date) = YEAR(CURRENT_DATE())
                    AND MONTH(payment_date) = MONTH(CURRENT_DATE())
                    GROUP BY lease_id
                ) p ON l.id = p.lease_id
                WHERE l.status = 'active'
                AND (p.paid_amount IS NULL OR p.paid_amount < l.rent_amount)
                ORDER BY outstanding_amount DESC";
        
        return $this->query($sql);
    }

    public function getAgingReport()
    {
        $sql = "SELECT 
                    CASE 
                        WHEN DATEDIFF(CURRENT_DATE(), payment_date) <= 30 THEN '0-30 days'
                        WHEN DATEDIFF(CURRENT_DATE(), payment_date) <= 60 THEN '31-60 days'
                        WHEN DATEDIFF(CURRENT_DATE(), payment_date) <= 90 THEN '61-90 days'
                        ELSE 'Over 90 days'
                    END as aging_period,
                    COUNT(*) as count,
                    SUM(amount) as total_amount
                FROM payments
                WHERE payment_date <= CURRENT_DATE()
                GROUP BY aging_period
                ORDER BY 
                    CASE aging_period
                        WHEN '0-30 days' THEN 1
                        WHEN '31-60 days' THEN 2
                        WHEN '61-90 days' THEN 3
                        ELSE 4
                    END";
        
        return $this->query($sql);
    }

    public function getPaymentTrends($months = 12)
    {
        $sql = "SELECT 
                    DATE_FORMAT(payment_date, '%Y-%m') as month,
                    COUNT(*) as payment_count,
                    SUM(amount) as total_amount,
                    COUNT(DISTINCT lease_id) as unique_tenants,
                    AVG(amount) as average_payment
                FROM payments
                WHERE payment_date >= DATE_SUB(CURRENT_DATE(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
                ORDER BY month ASC";
        
        return $this->query($sql, [$months]);
    }

    public function getTenantPaymentHistory()
    {
        $sql = "SELECT 
                    t.name as tenant_name,
                    COUNT(p.id) as total_payments,
                    AVG(p.amount) as average_payment,
                    MAX(p.payment_date) as last_payment_date,
                    SUM(CASE WHEN DATEDIFF(p.payment_date, l.payment_day) > 0 THEN 1 ELSE 0 END) as late_payments
                FROM tenants t
                JOIN leases l ON t.id = l.tenant_id
                LEFT JOIN payments p ON l.id = p.lease_id
                GROUP BY t.id, t.name
                ORDER BY total_payments DESC";
        
        return $this->query($sql);
    }

    public function userHasAccessToLease($leaseId, $userId)
    {
        $user = new User();
        $user->find($userId);
        
        // Admin has access to all leases
        if ($user->isAdmin()) {
            return true;
        }
        
        $sql = "SELECT 1
                FROM leases l
                JOIN units u ON l.unit_id = u.id
                JOIN properties pr ON u.property_id = pr.id
                WHERE l.id = ?
                AND (1=0";
        
        $params = [$leaseId];
        
        if ($user->isLandlord()) {
            $sql .= " OR pr.owner_id = ?";
            $params[] = $userId;
        }
        if ($user->isManager()) {
            $sql .= " OR pr.manager_id = ?";
            $params[] = $userId;
        }
        if ($user->isAgent()) {
            $sql .= " OR pr.agent_id = ?";
            $params[] = $userId;
        }
        // Caretaker
        $sql .= " OR pr.caretaker_user_id = ?";
        $params[] = $userId;
        
        $sql .= ")";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $sql = "INSERT INTO subscription_payments (user_id, subscription_id, amount, payment_method, status) 
                VALUES (:user_id, :subscription_id, :amount, :payment_method, :status)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $data['user_id'],
            'subscription_id' => $data['subscription_id'],
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'status' => $data['status']
        ]);

        return $this->db->lastInsertId();
    }

    public function updateStatus($paymentId, $status)
    {
        $sql = "UPDATE subscription_payments SET status = :status WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'status' => $status,
            'id' => $paymentId
        ]);
    }

    public function findById($id)
    {
        $sql = "SELECT * FROM subscription_payments WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function logPayment($paymentId, $logType, $logData)
    {
        $sql = "INSERT INTO subscription_payment_logs (payment_id, log_type, log_data) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$paymentId, $logType, $logData]);
    }

    public function getUserPayments($userId)
    {
        $sql = "SELECT p.*, s.plan_type, m.mpesa_receipt_number, m.transaction_date 
                FROM subscription_payments p 
                LEFT JOIN subscriptions s ON p.subscription_id = s.id 
                LEFT JOIN mpesa_transactions m ON m.payment_id = p.id 
                WHERE p.user_id = ? 
                ORDER BY p.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getAllPayments()
    {
        $sql = "SELECT sp.*, 
                u.name as user_name,
                u.email as user_email,
                s.plan_type,
                s.status as subscription_status,
                mt.mpesa_receipt_number,
                mt.phone_number,
                mt.transaction_date as mpesa_transaction_date,
                mt.result_code as mpesa_result_code,
                mt.result_description as mpesa_result_description,
                mmp.id as manual_mpesa_id,
                mmp.phone_number as manual_phone_number,
                mmp.transaction_code as manual_transaction_code,
                mmp.verification_status as manual_verification_status
                FROM subscription_payments sp
                LEFT JOIN users u ON sp.user_id = u.id
                LEFT JOIN subscriptions s ON sp.subscription_id = s.id
                LEFT JOIN mpesa_transactions mt ON sp.id = mt.payment_id
                LEFT JOIN manual_mpesa_payments mmp ON sp.id = mmp.payment_id
                ORDER BY sp.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPaymentDetails($id)
    {
        $sql = "SELECT sp.*, 
                u.name as user_name,
                u.email as user_email,
                s.plan_type,
                s.status as subscription_status,
                mt.mpesa_receipt_number,
                mt.phone_number,
                mt.transaction_date as mpesa_transaction_date,
                mt.result_code as mpesa_result_code,
                mt.result_description as mpesa_result_description,
                spl.log_type,
                spl.log_data,
                spl.created_at as log_created_at
                FROM subscription_payments sp
                LEFT JOIN users u ON sp.user_id = u.id
                LEFT JOIN subscriptions s ON sp.subscription_id = s.id
                LEFT JOIN mpesa_transactions mt ON sp.id = mt.payment_id
                LEFT JOIN subscription_payment_logs spl ON sp.id = spl.payment_id
                WHERE sp.id = ?
                ORDER BY spl.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$payment) {
            return null;
        }
        
        // Get all logs for this payment
        $sql = "SELECT * FROM subscription_payment_logs 
                WHERE payment_id = ? 
                ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $payment['logs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $payment;
    }

    public function deleteByUserId($userId)
    {
        try {
            // Delete mpesa transactions first (foreign key constraint)
            $sql = "DELETE FROM mpesa_transactions WHERE payment_id IN (
                SELECT id FROM {$this->table} WHERE user_id = ?
            )";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            
            // Now delete the payments
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE user_id = ?");
            return $stmt->execute([$userId]);
        } catch (\Exception $e) {
            error_log("Error deleting payments for user {$userId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a rent payment (for property/tenant rent, not subscription)
     */
    public function createRentPayment($data)
    {
        $sql = "INSERT INTO payments (lease_id, amount, payment_date, payment_type, payment_method, notes, status) 
                VALUES (:lease_id, :amount, :payment_date, :payment_type, :payment_method, :notes, :status)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'lease_id' => $data['lease_id'],
            'amount' => $data['amount'],
            'payment_date' => $data['payment_date'],
            'payment_type' => $data['payment_type'],
            'payment_method' => $data['payment_method'],
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? 'completed'
        ]);
        return $this->db->lastInsertId();
    }

    public function createUtilityPayment($data)
    {
        $sql = "INSERT INTO payments (lease_id, utility_id, amount, payment_date, payment_type, payment_method, notes, status) 
                VALUES (:lease_id, :utility_id, :amount, :payment_date, :payment_type, :payment_method, :notes, :status)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'lease_id' => $data['lease_id'],
            'utility_id' => $data['utility_id'],
            'amount' => $data['amount'],
            'payment_date' => $data['payment_date'],
            'payment_type' => $data['payment_type'],
            'payment_method' => $data['payment_method'],
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? 'completed'
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * Get the due amount for a lease for the current month
     */
    public function getDueAmountForLease($leaseId) {
        $sql = "SELECT l.rent_amount - COALESCE(SUM(p.amount), 0) as due
                FROM leases l
                LEFT JOIN payments p ON l.id = p.lease_id
                    AND YEAR(p.payment_date) = YEAR(CURRENT_DATE())
                    AND MONTH(p.payment_date) = MONTH(CURRENT_DATE())
                WHERE l.id = ?
                GROUP BY l.rent_amount";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$leaseId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return max(0, $row['due'] ?? 0);
    }

    /**
     * Update a rent payment in the payments table
     */
    public function updatePayment($id, $data)
    {
        $sql = "UPDATE payments SET amount = :amount, payment_date = :payment_date, payment_method = :payment_method, reference_number = :reference_number, status = :status, notes = :notes WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'amount' => $data['amount'],
            'payment_date' => $data['payment_date'],
            'payment_method' => $data['payment_method'],
            'reference_number' => $data['reference_number'],
            'status' => $data['status'],
            'notes' => $data['notes'],
            'id' => $id
        ]);
    }

    public function getTenantRentPayments($tenantId)
    {
        $sql = "SELECT p.*, 
                       mmp.phone_number, 
                       mmp.transaction_code,
                       mmp.verification_status as mpesa_status
                FROM payments p 
                JOIN leases l ON p.lease_id = l.id 
                LEFT JOIN manual_mpesa_payments mmp ON p.id = mmp.payment_id
                WHERE l.tenant_id = ? AND p.payment_type = 'rent' 
                ORDER BY p.payment_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getTenantAllPayments($tenantId)
    {
        $sql = "SELECT p.*, 
                       mmp.phone_number, 
                       mmp.transaction_code,
                       mmp.verification_status as mpesa_status,
                       u.utility_type
                FROM payments p 
                JOIN leases l ON p.lease_id = l.id 
                LEFT JOIN manual_mpesa_payments mmp ON p.id = mmp.payment_id
                LEFT JOIN utilities u ON p.utility_id = u.id
                WHERE l.tenant_id = ? 
                ORDER BY p.payment_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getTenantOverdueRent($tenantId)
    {
        $sql = "SELECT l.*, (
                    l.rent_amount - IFNULL((SELECT SUM(amount) FROM payments WHERE lease_id = l.id AND payment_type = 'rent' AND status IN ('completed', 'verified')), 0)
                ) AS overdue_amount
                FROM leases l
                WHERE l.tenant_id = ? AND l.status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getTenantMissedRentMonths($tenantId)
    {
        $leaseStmt = $this->db->prepare("SELECT * FROM leases WHERE tenant_id = ? AND status = 'active' LIMIT 1");
        $leaseStmt->execute([$tenantId]);
        $lease = $leaseStmt->fetch(\PDO::FETCH_ASSOC);
        if (!$lease) {
            return [];
        }

        $rentAmount = isset($lease['rent_amount']) ? (float)$lease['rent_amount'] : 0.0;
        if ($rentAmount <= 0) {
            return [];
        }

        try {
            $leaseStart = new \DateTime($lease['start_date']);
        } catch (\Exception $e) {
            return [];
        }

        $startMonth = new \DateTime($leaseStart->format('Y-m-01'));
        $today = new \DateTime();
        $currentMonth = new \DateTime($today->format('Y-m-01'));
        if (!empty($lease['end_date'])) {
            try {
                $leaseEnd = new \DateTime($lease['end_date']);
                $leaseEndMonth = new \DateTime($leaseEnd->format('Y-m-01'));
                if ($leaseEndMonth < $currentMonth) {
                    $currentMonth = $leaseEndMonth;
                }
            } catch (\Exception $e) {
            }
        }

        $months = [];
        $cursor = clone $startMonth;
        while ($cursor <= $currentMonth) {
            $months[] = [
                'year' => (int)$cursor->format('Y'),
                'month' => (int)$cursor->format('n'),
                'label' => $cursor->format('F Y'),
                'outstanding' => $rentAmount,
            ];
            $cursor->modify('+1 month');
        }

        if (empty($months)) {
            return [];
        }

        $payStmt = $this->db->prepare("SELECT amount FROM payments WHERE lease_id = ? AND payment_type = 'rent' AND status IN ('completed','verified') ORDER BY payment_date ASC, id ASC");
        $payStmt->execute([$lease['id']]);
        $paidRows = $payStmt->fetchAll(\PDO::FETCH_ASSOC);

        $remainingPaid = 0.0;
        foreach ($paidRows as $row) {
            $remainingPaid += isset($row['amount']) ? (float)$row['amount'] : 0.0;
        }

        for ($i = 0; $i < count($months); $i++) {
            if ($remainingPaid <= 0) {
                break;
            }
            $apply = $remainingPaid >= $rentAmount ? $rentAmount : $remainingPaid;
            $months[$i]['outstanding'] = max(0.0, $rentAmount - $apply);
            $remainingPaid -= $apply;
        }

        $missed = [];
        foreach ($months as $m) {
            if ($m['outstanding'] > 0.0001) {
                $missed[] = [
                    'year' => $m['year'],
                    'month' => $m['month'],
                    'label' => $m['label'],
                    'amount' => $m['outstanding'],
                ];
            }
        }

        return $missed;
    }

    public function getTenantRentCoverage($tenantId)
    {
        $leaseStmt = $this->db->prepare("SELECT * FROM leases WHERE tenant_id = ? AND status = 'active' LIMIT 1");
        $leaseStmt->execute([$tenantId]);
        $lease = $leaseStmt->fetch(\PDO::FETCH_ASSOC);
        if (!$lease) {
            return [];
        }

        $rentAmount = isset($lease['rent_amount']) ? (float)$lease['rent_amount'] : 0.0;
        if ($rentAmount <= 0.0) {
            return [];
        }

        try {
            $leaseStart = new \DateTime($lease['start_date']);
        } catch (\Exception $e) {
            return [];
        }

        $startMonth = new \DateTime($leaseStart->format('Y-m-01'));
        $today = new \DateTime();
        $currentMonth = new \DateTime($today->format('Y-m-01'));

        $stmt = $this->db->prepare("SELECT IFNULL(SUM(amount),0) AS total_paid FROM payments WHERE lease_id = ? AND payment_type = 'rent' AND status IN ('completed','verified')");
        $stmt->execute([$lease['id']]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: ['total_paid' => 0];
        $totalPaid = (float)$row['total_paid'];

        if ($totalPaid < 0.01) {
            $nextDue = clone $startMonth;
            return [
                'months_paid' => 0,
                'prepaid_months' => 0,
                'next_due_year' => (int)$nextDue->format('Y'),
                'next_due_month' => (int)$nextDue->format('n'),
                'next_due_label' => $nextDue->format('F Y'),
                'due_now' => $nextDue <= $currentMonth,
            ];
        }

        $monthsPaid = (int)floor($totalPaid / $rentAmount + 1e-6);
        $nextDue = clone $startMonth;
        $nextDue->modify("+{$monthsPaid} month");

        $dueNow = ($nextDue <= $currentMonth);
        $prepaidMonths = 0;
        if (!$dueNow) {
            $yDiff = (int)$nextDue->format('Y') - (int)$currentMonth->format('Y');
            $mDiff = (int)$nextDue->format('n') - (int)$currentMonth->format('n');
            $prepaidMonths = $yDiff * 12 + $mDiff;
        }

        return [
            'months_paid' => $monthsPaid,
            'prepaid_months' => max(0, $prepaidMonths),
            'next_due_year' => (int)$nextDue->format('Y'),
            'next_due_month' => (int)$nextDue->format('n'),
            'next_due_label' => $nextDue->format('F Y'),
            'due_now' => $dueNow,
        ];
    }

    /**
     * Delete a payment by ID
     */
    public function delete($id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (\Exception $e) {
            error_log("Error deleting payment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get database connection for external use
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Get attachments for this payment
     */
    public function getAttachments($paymentId = null)
    {
        $id = $paymentId ?: $this->id;
        if (!$id) return [];
        
        $stmt = $this->db->prepare("
            SELECT * FROM file_uploads 
            WHERE entity_type = 'payment' 
            AND entity_id = ? 
            AND file_type = 'attachment'
            ORDER BY created_at DESC
        ");
        $stmt->execute([$id]);
        $files = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Add full URL to each file
        foreach ($files as &$file) {
            $file['url'] = BASE_URL . '/' . $file['upload_path'];
        }
        
        return $files;
    }

    /**
     * Get all files for this payment
     */
    public function getAllFiles($paymentId = null)
    {
        $id = $paymentId ?: $this->id;
        if (!$id) return [];
        
        $stmt = $this->db->prepare("
            SELECT * FROM file_uploads 
            WHERE entity_type = 'payment' 
            AND entity_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$id]);
        $files = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Add full URL to each file
        foreach ($files as &$file) {
            $file['url'] = BASE_URL . '/' . $file['upload_path'];
        }
        
        return $files;
    }

    /**
     * Get revenue by date range for reports
     */
    public function getRevenueByDateRange($startDate, $endDate, $userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        if (!$userData) {
            return [];
        }

        // For admin users
        if ($userData['role'] === 'admin') {
            $sql = "SELECT 
                        DATE(payment_date) as date,
                        SUM(amount) as total_amount,
                        COUNT(*) as payment_count
                    FROM payments
                    WHERE payment_date BETWEEN ? AND ?
                    AND status IN ('completed', 'verified')
                    GROUP BY DATE(payment_date)
                    ORDER BY date ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$startDate, $endDate]);
        } else {
            // For regular users - filter by their properties
            $sql = "SELECT 
                        DATE(p.payment_date) as date,
                        SUM(p.amount) as total_amount,
                        COUNT(*) as payment_count
                    FROM payments p
                    INNER JOIN leases l ON p.lease_id = l.id
                    INNER JOIN units u ON l.unit_id = u.id
                    INNER JOIN properties pr ON u.property_id = pr.id
                    WHERE p.payment_date BETWEEN ? AND ?
                    AND p.status IN ('completed', 'verified')
                    AND (pr.user_id = ? OR pr.caretaker_user_id = ?)
                    GROUP BY DATE(p.payment_date)
                    ORDER BY date ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$startDate, $endDate, $userId, $userId]);
        }
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get recent payments for reports
     */
    public function getRecentPayments($userId = null, $limit = 10)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        if (!$userData) {
            return [];
        }

        // For admin users
        if ($userData['role'] === 'admin') {
            $sql = "SELECT 
                        p.*,
                        t.name as tenant_name,
                        pr.name as property_name,
                        u.unit_number
                    FROM payments p
                    LEFT JOIN leases l ON p.lease_id = l.id
                    LEFT JOIN tenants t ON l.tenant_id = t.id
                    LEFT JOIN units u ON l.unit_id = u.id
                    LEFT JOIN properties pr ON u.property_id = pr.id
                    WHERE p.status IN ('completed', 'verified')
                    ORDER BY p.payment_date DESC
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
        } else {
            // For regular users
            $sql = "SELECT 
                        p.*,
                        t.name as tenant_name,
                        pr.name as property_name,
                        u.unit_number
                    FROM payments p
                    LEFT JOIN leases l ON p.lease_id = l.id
                    LEFT JOIN tenants t ON l.tenant_id = t.id
                    LEFT JOIN units u ON l.unit_id = u.id
                    LEFT JOIN properties pr ON u.property_id = pr.id
                    WHERE p.status IN ('completed', 'verified')
                    AND (pr.user_id = ? OR pr.caretaker_user_id = ?)
                    ORDER BY p.payment_date DESC
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $userId, $limit]);
        }
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get tenant payment statistics for reports
     */
    public function getTenantPaymentStats($userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        if (!$userData) {
            return [];
        }

        // For admin users
        if ($userData['role'] === 'admin') {
            $sql = "SELECT 
                        t.id,
                        t.name as tenant_name,
                        COUNT(p.id) as total_payments,
                        SUM(p.amount) as total_paid,
                        MAX(p.payment_date) as last_payment_date
                    FROM tenants t
                    LEFT JOIN leases l ON t.id = l.tenant_id
                    LEFT JOIN payments p ON l.id = p.lease_id AND p.status IN ('completed', 'verified')
                    GROUP BY t.id, t.name
                    ORDER BY total_paid DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } else {
            // For regular users
            $sql = "SELECT 
                        t.id,
                        t.name as tenant_name,
                        COUNT(p.id) as total_payments,
                        SUM(p.amount) as total_paid,
                        MAX(p.payment_date) as last_payment_date
                    FROM tenants t
                    LEFT JOIN leases l ON t.id = l.tenant_id
                    LEFT JOIN payments p ON l.id = p.lease_id AND p.status IN ('completed', 'verified')
                    INNER JOIN units u ON l.unit_id = u.id
                    INNER JOIN properties pr ON u.property_id = pr.id
                    WHERE (pr.user_id = ? OR pr.caretaker_user_id = ?)
                    GROUP BY t.id, t.name
                    ORDER BY total_paid DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $userId]);
        }
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get outstanding payments for reports
     */
    public function getOutstandingPayments($userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        if (!$userData) {
            return [];
        }

        // For admin users
        if ($userData['role'] === 'admin') {
            $sql = "SELECT 
                        t.name as tenant_name,
                        t.email as tenant_email,
                        t.phone as tenant_phone,
                        p.name as property_name,
                        u.unit_number,
                        l.rent_amount,
                        COALESCE(SUM(pay.amount), 0) as total_paid,
                        (l.rent_amount - COALESCE(SUM(pay.amount), 0)) as balance_due,
                        l.start_date,
                        l.end_date
                    FROM leases l
                    INNER JOIN tenants t ON l.tenant_id = t.id
                    INNER JOIN units u ON l.unit_id = u.id
                    INNER JOIN properties p ON u.property_id = p.id
                    LEFT JOIN payments pay ON l.id = pay.lease_id 
                        AND pay.status IN ('completed', 'verified')
                        AND MONTH(pay.payment_date) = MONTH(CURRENT_DATE())
                        AND YEAR(pay.payment_date) = YEAR(CURRENT_DATE())
                    WHERE l.status = 'active'
                    GROUP BY l.id, t.name, t.email, t.phone, p.name, u.unit_number, l.rent_amount, l.start_date, l.end_date
                    HAVING balance_due > 0
                    ORDER BY balance_due DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } else {
            // For regular users
            $sql = "SELECT 
                        t.name as tenant_name,
                        t.email as tenant_email,
                        t.phone as tenant_phone,
                        p.name as property_name,
                        u.unit_number,
                        l.rent_amount,
                        COALESCE(SUM(pay.amount), 0) as total_paid,
                        (l.rent_amount - COALESCE(SUM(pay.amount), 0)) as balance_due,
                        l.start_date,
                        l.end_date
                    FROM leases l
                    INNER JOIN tenants t ON l.tenant_id = t.id
                    INNER JOIN units u ON l.unit_id = u.id
                    INNER JOIN properties p ON u.property_id = p.id
                    LEFT JOIN payments pay ON l.id = pay.lease_id 
                        AND pay.status IN ('completed', 'verified')
                        AND MONTH(pay.payment_date) = MONTH(CURRENT_DATE())
                        AND YEAR(pay.payment_date) = YEAR(CURRENT_DATE())
                    WHERE l.status = 'active'
                    AND (p.user_id = ? OR p.caretaker_user_id = ?)
                    GROUP BY l.id, t.name, t.email, t.phone, p.name, u.unit_number, l.rent_amount, l.start_date, l.end_date
                    HAVING balance_due > 0
                    ORDER BY balance_due DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $userId]);
        }
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get payment history for reports
     */
    public function getPaymentHistory($userId = null, $limit = 100)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        if (!$userData) {
            return [];
        }

        // For admin users
        if ($userData['role'] === 'admin') {
            $sql = "SELECT 
                        p.*,
                        t.name as tenant_name,
                        pr.name as property_name,
                        u.unit_number,
                        l.rent_amount
                    FROM payments p
                    LEFT JOIN leases l ON p.lease_id = l.id
                    LEFT JOIN tenants t ON l.tenant_id = t.id
                    LEFT JOIN units u ON l.unit_id = u.id
                    LEFT JOIN properties pr ON u.property_id = pr.id
                    ORDER BY p.payment_date DESC
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
        } else {
            // For regular users
            $sql = "SELECT 
                        p.*,
                        t.name as tenant_name,
                        pr.name as property_name,
                        u.unit_number,
                        l.rent_amount
                    FROM payments p
                    LEFT JOIN leases l ON p.lease_id = l.id
                    LEFT JOIN tenants t ON l.tenant_id = t.id
                    LEFT JOIN units u ON l.unit_id = u.id
                    LEFT JOIN properties pr ON u.property_id = pr.id
                    WHERE (pr.user_id = ? OR pr.caretaker_user_id = ?)
                    ORDER BY p.payment_date DESC
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $userId, $limit]);
        }
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get property revenue with outstanding balance for financial reports
     */
    public function getPropertyRevenueForReport($startDate, $endDate, $userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        if (!$userData) {
            return [];
        }

        // For admin users
        if ($userData['role'] === 'admin') {
            $sql = "SELECT 
                        pr.id,
                        pr.name,
                        COALESCE(SUM(CASE 
                            WHEN p.status IN ('completed', 'verified') 
                            AND p.payment_date BETWEEN ? AND ?
                            THEN p.amount 
                            ELSE 0 
                        END), 0) as revenue,
                        COALESCE(SUM(CASE 
                            WHEN l.status = 'active'
                            THEN l.rent_amount - COALESCE((
                                SELECT SUM(amount) 
                                FROM payments 
                                WHERE lease_id = l.id 
                                AND status IN ('completed', 'verified')
                                AND MONTH(payment_date) = MONTH(CURRENT_DATE())
                                AND YEAR(payment_date) = YEAR(CURRENT_DATE())
                            ), 0)
                            ELSE 0 
                        END), 0) as outstanding,
                        CASE 
                            WHEN SUM(CASE WHEN l.status = 'active' THEN l.rent_amount ELSE 0 END) > 0
                            THEN (SUM(CASE 
                                WHEN p.status IN ('completed', 'verified') 
                                AND MONTH(p.payment_date) = MONTH(CURRENT_DATE())
                                AND YEAR(p.payment_date) = YEAR(CURRENT_DATE())
                                THEN p.amount 
                                ELSE 0 
                            END) / SUM(CASE WHEN l.status = 'active' THEN l.rent_amount ELSE 0 END)) * 100
                            ELSE 0
                        END as collection_rate
                    FROM properties pr
                    LEFT JOIN units u ON pr.id = u.property_id
                    LEFT JOIN leases l ON u.id = l.unit_id
                    LEFT JOIN payments p ON l.id = p.lease_id
                    GROUP BY pr.id, pr.name
                    HAVING revenue > 0 OR outstanding > 0
                    ORDER BY revenue DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$startDate, $endDate]);
        } else {
            // For regular users
            $sql = "SELECT 
                        pr.id,
                        pr.name,
                        COALESCE(SUM(CASE 
                            WHEN p.status IN ('completed', 'verified') 
                            AND p.payment_date BETWEEN ? AND ?
                            THEN p.amount 
                            ELSE 0 
                        END), 0) as revenue,
                        COALESCE(SUM(CASE 
                            WHEN l.status = 'active'
                            THEN l.rent_amount - COALESCE((
                                SELECT SUM(amount) 
                                FROM payments 
                                WHERE lease_id = l.id 
                                AND status IN ('completed', 'verified')
                                AND MONTH(payment_date) = MONTH(CURRENT_DATE())
                                AND YEAR(payment_date) = YEAR(CURRENT_DATE())
                            ), 0)
                            ELSE 0 
                        END), 0) as outstanding,
                        CASE 
                            WHEN SUM(CASE WHEN l.status = 'active' THEN l.rent_amount ELSE 0 END) > 0
                            THEN (SUM(CASE 
                                WHEN p.status IN ('completed', 'verified') 
                                AND MONTH(p.payment_date) = MONTH(CURRENT_DATE())
                                AND YEAR(p.payment_date) = YEAR(CURRENT_DATE())
                                THEN p.amount 
                                ELSE 0 
                            END) / SUM(CASE WHEN l.status = 'active' THEN l.rent_amount ELSE 0 END)) * 100
                            ELSE 0
                        END as collection_rate
                    FROM properties pr
                    LEFT JOIN units u ON pr.id = u.property_id
                    LEFT JOIN leases l ON u.id = l.unit_id
                    LEFT JOIN payments p ON l.id = p.lease_id
                    WHERE (pr.user_id = ? OR pr.caretaker_user_id = ?)
                    GROUP BY pr.id, pr.name
                    HAVING revenue > 0 OR outstanding > 0
                    ORDER BY revenue DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$startDate, $endDate, $userId, $userId]);
        }
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
} 