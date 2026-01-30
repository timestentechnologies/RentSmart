<?php

namespace App\Models;

class Property extends Model
{
    protected $table = 'properties';
    
    public function __construct()
    {
        parent::__construct();
        $this->table = 'properties';
        $this->ensureCaretakerColumns();
    }

    private function ensureCaretakerColumns()
    {
        try {
            // caretaker_name
            $stmt = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'caretaker_name'");
            if ($stmt->rowCount() === 0) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN caretaker_name VARCHAR(255) NULL AFTER description");
            }

            // caretaker_contact
            $stmt = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'caretaker_contact'");
            if ($stmt->rowCount() === 0) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN caretaker_contact VARCHAR(255) NULL AFTER caretaker_name");
            }

            // caretaker_user_id
            $stmt = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'caretaker_user_id'");
            if ($stmt->rowCount() === 0) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN caretaker_user_id INT NULL AFTER agent_id");
            }
        } catch (\Exception $e) {
            error_log("Property::ensureCaretakerColumns error: " . $e->getMessage());
        }
    }

    public function units()
    {
        $stmt = $this->db->prepare("
            SELECT * FROM units 
            WHERE property_id = ?
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getOccupancyRate()
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(CASE WHEN status = 'occupied' THEN 1 END) as occupied,
                COUNT(*) as total
            FROM units 
            WHERE property_id = ?
        ");
        $stmt->execute([$this->id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result['total'] > 0 
            ? ($result['occupied'] / $result['total']) * 100 
            : 0;
    }

    public function getMonthlyIncome($month = null, $year = null)
    {
        $month = $month ?: date('m');
        $year = $year ?: date('Y');

        $stmt = $this->db->prepare("
            SELECT SUM(u.rent_amount) as total
            FROM units u
            WHERE u.property_id = ?
            AND u.status = 'occupied'
        ");
        $stmt->execute([$this->id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result['total'] ?: 0;
    }

    /**
     * Get images for this property
     */
    public function getImages()
    {
        if (!$this->id) return [];
        
        $stmt = $this->db->prepare("
            SELECT * FROM file_uploads 
            WHERE entity_type = 'property' 
            AND entity_id = ? 
            AND file_type = 'image'
            ORDER BY created_at DESC
        ");
        $stmt->execute([$this->id]);
        $files = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Add full URL to each file
        foreach ($files as &$file) {
            $uploadPath = $file['upload_path'];
            // Ensure path starts with public/
            if (strpos($uploadPath, 'public/') === 0) {
                // Already has public/ prefix
                $file['url'] = BASE_URL . '/' . $uploadPath;
            } elseif (strpos($uploadPath, 'uploads/') === 0) {
                // Has uploads/ prefix, add public/
                $file['url'] = BASE_URL . '/public/' . $uploadPath;
            } else {
                // No prefix, add public/uploads/
                $file['url'] = BASE_URL . '/public/uploads/' . ltrim($uploadPath, '/');
            }
        }
        
        return $files;
    }

    /**
     * Get documents for this property
     */
    public function getDocuments()
    {
        if (!$this->id) return [];
        
        $stmt = $this->db->prepare("
            SELECT * FROM file_uploads 
            WHERE entity_type = 'property' 
            AND entity_id = ? 
            AND file_type = 'document'
            ORDER BY created_at DESC
        ");
        $stmt->execute([$this->id]);
        $files = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Add full URL to each file
        foreach ($files as &$file) {
            $uploadPath = $file['upload_path'];
            // Ensure path starts with public/
            if (strpos($uploadPath, 'public/') === 0) {
                // Already has public/ prefix
                $file['url'] = BASE_URL . '/' . $uploadPath;
            } elseif (strpos($uploadPath, 'uploads/') === 0) {
                // Has uploads/ prefix, add public/
                $file['url'] = BASE_URL . '/public/' . $uploadPath;
            } else {
                // No prefix, add public/uploads/
                $file['url'] = BASE_URL . '/public/uploads/' . ltrim($uploadPath, '/');
            }
        }
        
        return $files;
    }

    /**
     * Get all files for this property
     */
    public function getAllFiles()
    {
        if (!$this->id) return [];
        
        $stmt = $this->db->prepare("
            SELECT * FROM file_uploads 
            WHERE entity_type = 'property' 
            AND entity_id = ?
            ORDER BY file_type, created_at DESC
        ");
        $stmt->execute([$this->id]);
        $files = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Add full URL to each file
        foreach ($files as &$file) {
            $file['url'] = BASE_URL . '/' . $file['upload_path'];
        }
        
        return $files;
    }

    public function getRecent($limit = 2, $userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        $sql = "SELECT p.*, 
                COUNT(u.id) as total_units,
                SUM(CASE WHEN u.status = 'occupied' THEN 1 ELSE 0 END) as occupied_units,
                COALESCE(SUM(u.rent_amount), 0) as monthly_revenue
                FROM properties p
                LEFT JOIN units u ON p.id = u.property_id";

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
        
        $sql .= " GROUP BY p.id ORDER BY p.created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getOccupancyStats($userId = null)
    {
        $user = null;
        if ($userId) {
            $user = new User();
            $user->find($userId);
        }
        
        $sql = "SELECT 
                COUNT(DISTINCT u.id) as total_units,
                COUNT(DISTINCT CASE WHEN u.status = 'occupied' OR EXISTS (
                    SELECT 1 FROM leases l 
                    WHERE l.unit_id = u.id 
                    AND l.status = 'active'
                    AND CURRENT_DATE BETWEEN l.start_date AND l.end_date
                ) THEN u.id END) as occupied_units
                FROM units u
                LEFT JOIN properties p ON u.property_id = p.id";

        $params = [];
        
        // Add role-based conditions
        if ($user && !$user->isAdmin()) {
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

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$result) {
                return [
                    'total_units' => 0,
                    'occupied_units' => 0,
                    'occupancy_rate' => 0
                ];
            }
            
            $total_units = (int) ($result['total_units'] ?? 0);
            $occupied_units = (int) ($result['occupied_units'] ?? 0);

            return [
                'total_units' => $total_units,
                'occupied_units' => $occupied_units,
                'occupancy_rate' => $total_units > 0 ? round(($occupied_units / $total_units) * 100, 1) : 0
            ];
        } catch (\Exception $e) {
            error_log("Error in Property::getOccupancyStats: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            return [
                'total_units' => 0,
                'occupied_units' => 0,
                'occupancy_rate' => 0
            ];
        }
    }

    public function getAll($userId = null)
    {
        try {
            $user = new User();
            $userData = $user->find($userId);
            
            $sql = "SELECT 
                    p.*,
                    COUNT(DISTINCT u.id) as total_units,
                    SUM(CASE WHEN u.status = 'occupied' THEN 1 ELSE 0 END) as occupied_units,
                    COALESCE(SUM(u.rent_amount), 0) as monthly_revenue
                    FROM properties p
                    LEFT JOIN units u ON p.id = u.property_id";

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
            
            $sql .= " GROUP BY 
                    p.id, p.name, p.address, p.city, p.state, p.zip_code, 
                    p.property_type, p.description, p.year_built, p.total_area,
                    p.created_at, p.updated_at
                ORDER BY p.name";
                
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log("Error in Property::getAll: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    public function getById($id, $userId = null)
    {
        $user = new User();
        $user->find($userId);
        
        $sql = "SELECT p.*, 
                COUNT(u.id) as total_units,
                SUM(CASE WHEN u.status = 'occupied' THEN 1 ELSE 0 END) as occupied_units,
                COALESCE(SUM(u.rent_amount), 0) as monthly_revenue
                FROM properties p
                LEFT JOIN units u ON p.id = u.property_id
                WHERE p.id = ?";

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
        
        $sql .= " GROUP BY p.id";
        
        $result = $this->query($sql, $params);
        return $result[0] ?? null;
    }

    public function getAvailableUnits()
    {
        $sql = "SELECT u.id, u.unit_number, u.rent_amount, p.name as property_name
                FROM units u
                JOIN properties p ON u.property_id = p.id
                WHERE u.status = 'vacant'
                ORDER BY p.name, u.unit_number";
        return $this->query($sql);
    }

    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($result) {
            $this->id = $result['id'];
            return $result;
        }
        
        return null;
    }

    /**
     * Get occupancy rates for all properties
     * @return array Array of property occupancy data
     */
    public function getOccupancyByProperty($userId = null)
    {
        $user = null;
        if ($userId) {
            $user = new User();
            $user->find($userId);
        }
        
        $sql = "SELECT 
                p.id,
                p.name,
                COUNT(DISTINCT u.id) as total_units,
                COUNT(DISTINCT CASE WHEN u.status = 'occupied' OR EXISTS (
                    SELECT 1 FROM leases l 
                    WHERE l.unit_id = u.id 
                    AND l.status = 'active'
                    AND CURRENT_DATE BETWEEN l.start_date AND l.end_date
                ) THEN u.id END) as occupied_units
                FROM properties p
                LEFT JOIN units u ON p.id = u.property_id";

        $params = [];
        
        // Add role-based conditions
        if ($user && !$user->isAdmin()) {
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
        
        $sql .= " GROUP BY p.id, p.name";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Calculate occupancy rate for each property
            foreach ($results as &$result) {
                $total_units = (int) ($result['total_units'] ?? 0);
                $occupied_units = (int) ($result['occupied_units'] ?? 0);
                $result['occupancy_rate'] = $total_units > 0 ? round(($occupied_units / $total_units) * 100, 1) : 0;
            }
            
            return $results;
        } catch (\Exception $e) {
            error_log("Error in Property::getOccupancyByProperty: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * Get overall occupancy statistics
     * @return array Overall occupancy statistics
     */
    public function getOverallOccupancyStats()
    {
        $query = "SELECT 
                    COUNT(DISTINCT u.id) as total_units,
                    COUNT(DISTINCT CASE WHEN l.status = 'active' THEN u.id END) as occupied_units,
                    COALESCE(ROUND((COUNT(DISTINCT CASE WHEN l.status = 'active' THEN u.id END) * 100.0) / 
                        NULLIF(COUNT(DISTINCT u.id), 0), 1), 0) as overall_occupancy_rate
                FROM properties p
                LEFT JOIN units u ON u.property_id = p.id
                LEFT JOIN leases l ON l.unit_id = u.id AND l.status = 'active'";

        try {
            $results = $this->query($query);
            $result = $results[0] ?? [
                'total_units' => 0,
                'occupied_units' => 0,
                'overall_occupancy_rate' => 0
            ];
            
            // Add vacant units calculation
            $result['vacant_units'] = $result['total_units'] - $result['occupied_units'];
            
            // Convert to appropriate types
            $result['total_units'] = (int)$result['total_units'];
            $result['occupied_units'] = (int)$result['occupied_units'];
            $result['vacant_units'] = (int)$result['vacant_units'];
            $result['overall_occupancy_rate'] = (float)$result['overall_occupancy_rate'];
            
            return $result;
        } catch (\PDOException $e) {
            error_log("Error in getOverallOccupancyStats: " . $e->getMessage());
            return [
                'total_units' => 0,
                'occupied_units' => 0,
                'vacant_units' => 0,
                'overall_occupancy_rate' => 0
            ];
        }
    }

    public function deleteByOwnerId($ownerId)
    {
        try {
            // Get all properties for this owner
            $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE owner_id = ?");
            $stmt->execute([$ownerId]);
            $propertyIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            // Delete each property (which will cascade delete units, leases, payments, etc.)
            foreach ($propertyIds as $propertyId) {
                $this->delete($propertyId);
            }
            
            return true;
        } catch (\Exception $e) {
            error_log("Error deleting properties for owner {$ownerId}: " . $e->getMessage());
            throw $e;
        }
    }

    public function delete($id)
    {
        try {
            // Start transaction only if not already in one
            $inTransaction = $this->db->inTransaction();
            if (!$inTransaction) {
                $this->db->beginTransaction();
            }
            
            // Get all units for this property
            $sql = "SELECT id FROM units WHERE property_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $units = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            // Get all leases for these units
            if (!empty($units)) {
                $placeholders = str_repeat('?,', count($units) - 1) . '?';
                $sql = "SELECT id FROM leases WHERE unit_id IN ($placeholders)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($units);
                $leases = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                
                // Get all payments for these leases
                if (!empty($leases)) {
                    $placeholders = str_repeat('?,', count($leases) - 1) . '?';
                    
                    // Delete mpesa transactions first (due to foreign key)
                    $sql = "DELETE FROM mpesa_transactions WHERE payment_id IN (
                        SELECT id FROM payments WHERE lease_id IN ($placeholders)
                    )";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute($leases);
                    
                    // Delete payments
                    $sql = "DELETE FROM payments WHERE lease_id IN ($placeholders)";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute($leases);
                    
                    // Delete leases
                    $sql = "DELETE FROM leases WHERE id IN ($placeholders)";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute($leases);
                }
                
                // Delete units
                $placeholders = str_repeat('?,', count($units) - 1) . '?';
                $sql = "DELETE FROM units WHERE id IN ($placeholders)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($units);
            }
            
            // Finally delete the property
            $sql = "DELETE FROM properties WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$id]);
            
            // Commit transaction only if we started it
            if (!$inTransaction) {
                $this->db->commit();
            }
            
            return $result;
        } catch (\Exception $e) {
            // Rollback transaction only if we started it
            if (!$inTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error in Property::delete: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Get vacant units for reports
     */
    public function getVacantUnits($userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        if (!$userData) {
            return [];
        }

        // For admin users
        if ($userData['role'] === 'admin') {
            $sql = "SELECT 
                        u.*,
                        p.name as property_name,
                        p.address as property_address
                    FROM units u
                    INNER JOIN properties p ON u.property_id = p.id
                    WHERE u.status = 'vacant'
                    ORDER BY p.name, u.unit_number";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } else {
            // For regular users
            $sql = "SELECT 
                        u.*,
                        p.name as property_name,
                        p.address as property_address
                    FROM units u
                    INNER JOIN properties p ON u.property_id = p.id
                    WHERE u.status = 'vacant'
                    AND (p.owner_id = ? OR p.manager_id = ? OR p.agent_id = ? OR p.caretaker_user_id = ?)
                    ORDER BY p.name, u.unit_number";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $userId, $userId, $userId]);
        }
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get occupied units for reports
     */
    public function getOccupiedUnits($userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        if (!$userData) {
            return [];
        }

        // For admin users
        if ($userData['role'] === 'admin') {
            $sql = "SELECT 
                        u.*,
                        p.name as property_name,
                        p.address as property_address,
                        t.name as tenant_name,
                        l.start_date,
                        l.end_date,
                        l.rent_amount
                    FROM units u
                    INNER JOIN properties p ON u.property_id = p.id
                    LEFT JOIN leases l ON u.id = l.unit_id AND l.status = 'active'
                    LEFT JOIN tenants t ON l.tenant_id = t.id
                    WHERE u.status = 'occupied'
                    ORDER BY p.name, u.unit_number";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } else {
            // For regular users
            $sql = "SELECT 
                        u.*,
                        p.name as property_name,
                        p.address as property_address,
                        t.name as tenant_name,
                        l.start_date,
                        l.end_date,
                        l.rent_amount
                    FROM units u
                    INNER JOIN properties p ON u.property_id = p.id
                    LEFT JOIN leases l ON u.id = l.unit_id AND l.status = 'active'
                    LEFT JOIN tenants t ON l.tenant_id = t.id
                    WHERE u.status = 'occupied'
                    AND (p.owner_id = ? OR p.manager_id = ? OR p.agent_id = ? OR p.caretaker_user_id = ?)
                    ORDER BY p.name, u.unit_number";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $userId, $userId, $userId]);
        }
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
                        SUM(CASE WHEN m.cost IS NOT NULL THEN m.cost ELSE 0 END) as total_cost,
                        AVG(CASE WHEN m.cost IS NOT NULL THEN m.cost ELSE 0 END) as average_cost
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
                        SUM(CASE WHEN m.cost IS NOT NULL THEN m.cost ELSE 0 END) as total_cost,
                        AVG(CASE WHEN m.cost IS NOT NULL THEN m.cost ELSE 0 END) as average_cost
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