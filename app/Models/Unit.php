<?php

namespace App\Models;

class Unit extends Model
{
    protected $table = 'units';

    public function __construct()
    {
        parent::__construct();
        $this->table = 'units';
        $this->checkTable();
    }

    private function checkTable()
    {
        try {
            error_log("Unit::checkTable - Checking if units table exists");
            
            // Check if table exists
            $sql = "SHOW TABLES LIKE '{$this->table}'";
            $stmt = $this->db->query($sql);
            $tableExists = $stmt->rowCount() > 0;
            error_log("Unit::checkTable - Table exists: " . ($tableExists ? 'yes' : 'no'));

            if (!$tableExists) {
                error_log("Unit::checkTable - Creating units table");
                $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    property_id INT NOT NULL,
                    unit_number VARCHAR(50) NOT NULL,
                    type ENUM('studio', '1bhk', '2bhk', '3bhk', 'other') NOT NULL,
                    size DECIMAL(10,2),
                    rent_amount DECIMAL(10,2) NOT NULL,
                    status ENUM('vacant', 'occupied', 'maintenance') NOT NULL DEFAULT 'vacant',
                    tenant_id INT,
                    lease_start DATE,
                    lease_end DATE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
                    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL,
                    UNIQUE KEY unique_unit_number (property_id, unit_number)
                )";
                $this->db->exec($sql);
                error_log("Unit::checkTable - Created units table");
            }

            // Check if tenant_id column exists
            $sql = "SHOW COLUMNS FROM {$this->table} LIKE 'tenant_id'";
            $stmt = $this->db->query($sql);
            if ($stmt->rowCount() === 0) {
                $sql = "ALTER TABLE {$this->table} 
                        ADD COLUMN tenant_id INT,
                        ADD COLUMN lease_start DATE,
                        ADD COLUMN lease_end DATE,
                        ADD FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL";
                $this->db->exec($sql);
            }

            // Check table structure
            error_log("Unit::checkTable - Checking table structure");
            $sql = "DESCRIBE {$this->table}";
            $stmt = $this->db->query($sql);
            $columns = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            error_log("Unit::checkTable - Table structure: " . print_r($columns, true));

            // Check if any columns are missing
            $requiredColumns = [
                'id' => 'int',
                'property_id' => 'int',
                'unit_number' => 'varchar',
                'type' => 'enum',
                'size' => 'decimal',
                'rent_amount' => 'decimal',
                'status' => 'enum',
                'created_at' => 'timestamp',
                'updated_at' => 'timestamp'
            ];

            $existingColumns = array_column($columns, 'Type', 'Field');
            $missingColumns = array_diff_key($requiredColumns, $existingColumns);

            if (!empty($missingColumns)) {
                error_log("Unit::checkTable - Missing columns: " . print_r($missingColumns, true));
                throw new Exception("Units table is missing required columns: " . implode(', ', array_keys($missingColumns)));
            }

            // Check if foreign key exists
            error_log("Unit::checkTable - Checking foreign key");
            $sql = "SELECT * FROM information_schema.KEY_COLUMN_USAGE 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = '{$this->table}' 
                   AND REFERENCED_TABLE_NAME = 'properties'";
            $stmt = $this->db->query($sql);
            $foreignKey = $stmt->fetch(\PDO::FETCH_ASSOC);
            error_log("Unit::checkTable - Foreign key: " . print_r($foreignKey, true));

            if (!$foreignKey) {
                error_log("Unit::checkTable - Adding foreign key");
                $sql = "ALTER TABLE {$this->table} 
                        ADD CONSTRAINT fk_{$this->table}_property_id 
                        FOREIGN KEY (property_id) 
                        REFERENCES properties(id) 
                        ON DELETE CASCADE";
                $this->db->exec($sql);
                error_log("Unit::checkTable - Added foreign key");
            }

            // Check if unique key exists
            error_log("Unit::checkTable - Checking unique key");
            $sql = "SHOW INDEX FROM {$this->table} WHERE Key_name = 'unique_unit_number'";
            $stmt = $this->db->query($sql);
            $uniqueKey = $stmt->fetch(\PDO::FETCH_ASSOC);
            error_log("Unit::checkTable - Unique key: " . print_r($uniqueKey, true));

            if (!$uniqueKey) {
                error_log("Unit::checkTable - Adding unique key");
                $sql = "ALTER TABLE {$this->table} 
                        ADD UNIQUE KEY unique_unit_number (property_id, unit_number)";
                $this->db->exec($sql);
                error_log("Unit::checkTable - Added unique key");
            }

        } catch (Exception $e) {
            error_log("Error in Unit::checkTable: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    public function getAll($userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        $sql = "SELECT u.*, p.name as property_name 
                FROM units u
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
        
        $sql .= " ORDER BY p.name, u.unit_number";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getById($id, $userId = null)
    {
        $user = new User();
        $user->find($userId);
        
        $sql = "SELECT u.*, p.name as property_name 
                FROM units u
                JOIN properties p ON u.property_id = p.id
                WHERE u.id = ?";

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

    public function where($column, $value, $userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        $sql = "SELECT u.*, 
                p.name as property_name,
                COALESCE(t.id, 0) as has_tenant,
                CASE 
                    WHEN l.id IS NOT NULL AND l.status = 'active' AND CURRENT_DATE BETWEEN l.start_date AND l.end_date 
                    THEN 'occupied'
                    ELSE u.status 
                END as actual_status
                FROM units u
                JOIN properties p ON u.property_id = p.id
                LEFT JOIN tenants t ON u.tenant_id = t.id
                LEFT JOIN leases l ON u.id = l.unit_id AND l.status = 'active'
                WHERE u.{$column} = ?";

        $params = [$value];
        
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
        
        $sql .= " ORDER BY u.unit_number";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $units = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Update the status based on actual occupancy
        foreach ($units as &$unit) {
            $unit['status'] = $unit['actual_status'];
            unset($unit['actual_status']);
        }
        
        return $units;
    }

    public function getVacantUnits($userId = null)
    {
        $user = new User();
        $user->find($userId);
        
        $sql = "SELECT u.*, 
                       p.name as property_name,
                       p.address,
                       p.city,
                       p.state,
                       p.description as property_description
                FROM units u
                JOIN properties p ON u.property_id = p.id
                LEFT JOIN leases l ON u.id = l.unit_id AND l.status = 'active'
                WHERE (
                    CASE 
                        WHEN l.id IS NOT NULL AND l.status = 'active' AND CURRENT_DATE BETWEEN l.start_date AND l.end_date 
                        THEN 'occupied'
                        ELSE u.status
                    END
                ) = 'vacant'";

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
            // Caretaker assigned to property
            $sql .= " OR p.caretaker_user_id = ?";
            $params[] = $userId;
            $sql .= ")";
        }
        
        $sql .= " ORDER BY p.name, u.unit_number";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Public-facing vacant units (no role filters)
     */
    public function getVacantUnitsPublic()
    {
        $sql = "SELECT u.*, 
                       p.name as property_name,
                       p.address,
                       p.city,
                       p.state,
                       p.description as property_description
                FROM units u
                JOIN properties p ON u.property_id = p.id
                LEFT JOIN leases l ON u.id = l.unit_id AND l.status = 'active'
                WHERE (
                    CASE 
                        WHEN l.id IS NOT NULL AND l.status = 'active' AND CURRENT_DATE BETWEEN l.start_date AND l.end_date 
                        THEN 'occupied'
                        ELSE u.status
                    END
                ) = 'vacant'
                ORDER BY p.name, u.unit_number";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function checkExistingUnit($propertyId, $unitNumber, $excludeUnitId = null)
    {
        try {
            error_log("Unit::checkExistingUnit - Checking for property_id: {$propertyId}, unit_number: {$unitNumber}, exclude_id: {$excludeUnitId}");
            $sql = "SELECT * FROM {$this->table} WHERE property_id = ? AND unit_number = ?";
            $params = [$propertyId, $unitNumber];

            if ($excludeUnitId !== null) {
                $sql .= " AND id != ?";
                $params[] = $excludeUnitId;
            }

            error_log("Unit::checkExistingUnit - SQL: " . $sql);
            error_log("Unit::checkExistingUnit - Params: " . print_r($params, true));

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            error_log("Unit::checkExistingUnit - Result: " . print_r($result, true));
            return $result;
        } catch (Exception $e) {
            error_log("Error in Unit::checkExistingUnit: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            return false;
        }
    }

    public function create($data)
    {
        try {
            error_log("Unit::create - Creating unit with data: " . print_r($data, true));
            $sql = "INSERT INTO {$this->table} (
                property_id, 
                unit_number, 
                type, 
                size, 
                rent_amount, 
                status
            ) VALUES (
                :property_id,
                :unit_number,
                :type,
                :size,
                :rent_amount,
                :status
            )";

            error_log("Unit::create - SQL: " . $sql);

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'property_id' => $data['property_id'],
                'unit_number' => $data['unit_number'],
                'type' => $data['type'],
                'size' => $data['size'],
                'rent_amount' => $data['rent_amount'],
                'status' => $data['status'] ?? 'vacant'
            ]);

            if ($result) {
                $id = $this->db->lastInsertId();
                error_log("Unit::create - Created unit with ID: " . $id);
                return $id;
            } else {
                error_log("Unit::create - Failed to create unit");
                return false;
            }
        } catch (Exception $e) {
            error_log("Error in Unit::create: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            return false;
        }
    }

    public function update($id, $data)
    {
        try {
            error_log("Unit::update - Updating unit {$id} with data: " . print_r($data, true));
            $sql = "UPDATE {$this->table} SET 
                unit_number = :unit_number,
                type = :type,
                size = :size,
                rent_amount = :rent_amount,
                status = :status
                WHERE id = :id";

            error_log("Unit::update - SQL: " . $sql);

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'id' => $id,
                'unit_number' => $data['unit_number'],
                'type' => $data['type'],
                'size' => $data['size'],
                'rent_amount' => $data['rent_amount'],
                'status' => $data['status']
            ]);

            error_log("Unit::update - Result: " . ($result ? 'success' : 'failure'));
            return $result;
        } catch (Exception $e) {
            error_log("Error in Unit::update: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            return false;
        }
    }

    public function delete($id)
    {
        try {
            error_log("Unit::delete - Deleting unit {$id}");
            $sql = "DELETE FROM {$this->table} WHERE id = ?";
            error_log("Unit::delete - SQL: " . $sql);

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$id]);

            error_log("Unit::delete - Result: " . ($result ? 'success' : 'failure'));
            return $result;
        } catch (Exception $e) {
            error_log("Error in Unit::delete: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            return false;
        }
    }

    public function find($id)
    {
        try {
            error_log("Unit::find - Finding unit {$id}");
            $sql = "SELECT * FROM {$this->table} WHERE id = ?";
            error_log("Unit::find - SQL: " . $sql);

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            error_log("Unit::find - Result: " . print_r($result, true));
            return $result;
        } catch (Exception $e) {
            error_log("Error in Unit::find: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Get images for this unit
     */
    public function getImages()
    {
        if (!$this->id) return [];
        
        $stmt = $this->db->prepare("
            SELECT * FROM file_uploads 
            WHERE entity_type = 'unit' 
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
     * Get documents for this unit
     */
    public function getDocuments()
    {
        if (!$this->id) return [];
        
        $stmt = $this->db->prepare("
            SELECT * FROM file_uploads 
            WHERE entity_type = 'unit' 
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
     * Get all files for this unit
     */
    public function getAllFiles()
    {
        if (!$this->id) return [];
        
        $stmt = $this->db->prepare("
            SELECT * FROM file_uploads 
            WHERE entity_type = 'unit' 
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
} 
