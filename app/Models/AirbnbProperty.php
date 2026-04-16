<?php

namespace App\Models;

class AirbnbProperty extends Model
{
    protected $table = 'airbnb_properties';

    public function __construct()
    {
        parent::__construct();
        $this->table = 'airbnb_properties';
        $this->ensureTable();
    }

    private function ensureTable()
    {
        try {
            $sql = "SHOW TABLES LIKE '{$this->table}'";
            $stmt = $this->db->query($sql);
            $tableExists = $stmt->rowCount() > 0;

            if (!$tableExists) {
                $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    property_id INT NOT NULL,
                    is_airbnb_enabled TINYINT(1) NOT NULL DEFAULT 1,
                    min_stay_nights INT NOT NULL DEFAULT 1,
                    max_stay_nights INT NOT NULL DEFAULT 30,
                    check_in_time TIME NOT NULL DEFAULT '14:00:00',
                    check_out_time TIME NOT NULL DEFAULT '11:00:00',
                    cleaning_fee DECIMAL(10,2) DEFAULT 0.00,
                    security_deposit DECIMAL(10,2) DEFAULT 0.00,
                    booking_lead_time_hours INT DEFAULT 24,
                    instant_booking TINYINT(1) DEFAULT 0,
                    house_rules TEXT,
                    cancellation_policy ENUM('flexible','moderate','strict') DEFAULT 'moderate',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_property (property_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                $this->db->exec($sql);
            }

            // Ensure allow_office_payments column exists
            $this->ensureOfficePaymentColumn();
        } catch (\Exception $e) {
            error_log("Error in AirbnbProperty::ensureTable: " . $e->getMessage());
        }
    }

    private function ensureOfficePaymentColumn()
    {
        try {
            $col = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'allow_office_payments'")->fetch(\PDO::FETCH_ASSOC);
            if (!$col) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN allow_office_payments TINYINT(1) NOT NULL DEFAULT 1");
            }
        } catch (\Exception $e) {
            error_log("Error adding allow_office_payments column: " . $e->getMessage());
        }
    }

    public function getByPropertyId($propertyId)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE property_id = ?");
        $stmt->execute([$propertyId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function createOrUpdate($propertyId, $data)
    {
        $existing = $this->getByPropertyId($propertyId);
        
        if ($existing) {
            // Update
            $fields = [];
            $params = ['property_id' => $propertyId];
            
                'is_airbnb_enabled', 'min_stay_nights', 'max_stay_nights',
                'check_in_time', 'check_out_time', 'cleaning_fee', 'security_deposit',
                'booking_lead_time_hours', 'instant_booking', 'house_rules', 'cancellation_policy',
                'allow_office_payments'
            ];
            
            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $fields[] = "$key = :$key";
                    $params[$key] = $value;
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE property_id = :property_id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } else {
            // Create
            $sql = "INSERT INTO {$this->table} (
                property_id, is_airbnb_enabled, min_stay_nights, max_stay_nights,
                check_in_time, check_out_time, cleaning_fee, security_deposit,
                booking_lead_time_hours, instant_booking, house_rules, cancellation_policy
            ) VALUES (
                :property_id, :is_airbnb_enabled, :min_stay_nights, :max_stay_nights,
                :check_in_time, :check_out_time, :cleaning_fee, :security_deposit,
                :booking_lead_time_hours, :instant_booking, :house_rules, :cancellation_policy
            )";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'property_id' => $propertyId,
                'is_airbnb_enabled' => $data['is_airbnb_enabled'] ?? 1,
                'min_stay_nights' => $data['min_stay_nights'] ?? 1,
                'max_stay_nights' => $data['max_stay_nights'] ?? 30,
                'check_in_time' => $data['check_in_time'] ?? '14:00:00',
                'check_out_time' => $data['check_out_time'] ?? '11:00:00',
                'cleaning_fee' => $data['cleaning_fee'] ?? 0.00,
                'security_deposit' => $data['security_deposit'] ?? 0.00,
                'booking_lead_time_hours' => $data['booking_lead_time_hours'] ?? 24,
                'instant_booking' => $data['instant_booking'] ?? 0,
                'house_rules' => $data['house_rules'] ?? null,
                'cancellation_policy' => $data['cancellation_policy'] ?? 'moderate'
            ]);
        }
    }

    public function getAirbnbEnabledProperties($userId = null, $role = null)
    {
        $sql = "SELECT p.*, ap.* 
            FROM properties p 
            JOIN {$this->table} ap ON p.id = ap.property_id 
            WHERE ap.is_airbnb_enabled = 1";
        
        $params = [];
        
        if ($userId && $role) {
            $role = strtolower((string)$role);
            if (!in_array($role, ['admin', 'administrator'], true)) {
                if ($role === 'airbnb_manager') {
                    $sql .= " AND p.airbnb_manager_id = ?";
                    $params[] = $userId;
                } else {
                    $sql .= " AND (p.owner_id = ? OR p.manager_id = ? OR p.caretaker_user_id = ? OR p.airbnb_manager_id = ?)";
                    $params[] = $userId;
                    $params[] = $userId;
                    $params[] = $userId;
                    $params[] = $userId;
                }
            }
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
