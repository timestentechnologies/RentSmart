<?php

namespace App\Models;

class AirbnbUnitRate extends Model
{
    protected $table = 'airbnb_unit_rates';

    public function __construct()
    {
        parent::__construct();
        $this->table = 'airbnb_unit_rates';
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
                    unit_id INT NOT NULL,
                    base_price_per_night DECIMAL(10,2) NOT NULL,
                    weekend_price DECIMAL(10,2) DEFAULT NULL,
                    weekly_discount_percent DECIMAL(5,2) DEFAULT 0.00,
                    monthly_discount_percent DECIMAL(5,2) DEFAULT 0.00,
                    seasonal_rates_json JSON DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_unit (unit_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                $this->db->exec($sql);
            }
        } catch (\Exception $e) {
            error_log("Error in AirbnbUnitRate::ensureTable: " . $e->getMessage());
        }
    }

    public function getByUnitId($unitId)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE unit_id = ?");
        $stmt->execute([$unitId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function createOrUpdate($unitId, $data)
    {
        $existing = $this->getByUnitId($unitId);
        
        if ($existing) {
            $fields = [];
            $params = ['unit_id' => $unitId];
            
            $allowedFields = [
                'base_price_per_night', 'weekend_price', 
                'weekly_discount_percent', 'monthly_discount_percent', 'seasonal_rates_json'
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
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE unit_id = :unit_id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } else {
            $sql = "INSERT INTO {$this->table} (
                unit_id, base_price_per_night, weekend_price,
                weekly_discount_percent, monthly_discount_percent, seasonal_rates_json
            ) VALUES (
                :unit_id, :base_price_per_night, :weekend_price,
                :weekly_discount_percent, :monthly_discount_percent, :seasonal_rates_json
            )";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'unit_id' => $unitId,
                'base_price_per_night' => $data['base_price_per_night'] ?? 0.00,
                'weekend_price' => $data['weekend_price'] ?? null,
                'weekly_discount_percent' => $data['weekly_discount_percent'] ?? 0.00,
                'monthly_discount_percent' => $data['monthly_discount_percent'] ?? 0.00,
                'seasonal_rates_json' => isset($data['seasonal_rates_json']) ? json_encode($data['seasonal_rates_json']) : null
            ]);
        }
    }

    public function calculatePrice($unitId, $checkIn, $checkOut, $guestCount = 1)
    {
        $rates = $this->getByUnitId($unitId);
        if (!$rates) {
            return null;
        }

        $checkInDate = new \DateTime($checkIn);
        $checkOutDate = new \DateTime($checkOut);
        $nights = $checkInDate->diff($checkOutDate)->days;

        $basePrice = (float)$rates['base_price_per_night'];
        $weekendPrice = $rates['weekend_price'] ? (float)$rates['weekend_price'] : $basePrice;
        
        $total = 0;
        $currentDate = clone $checkInDate;
        
        for ($i = 0; $i < $nights; $i++) {
            $dayOfWeek = (int)$currentDate->format('N');
            // Friday = 5, Saturday = 6, Sunday = 7
            if ($dayOfWeek >= 5 && $dayOfWeek <= 7) {
                $total += $weekendPrice;
            } else {
                $total += $basePrice;
            }
            $currentDate->modify('+1 day');
        }

        // Apply discounts
        $discount = 0;
        if ($nights >= 30 && $rates['monthly_discount_percent'] > 0) {
            $discount = $total * ($rates['monthly_discount_percent'] / 100);
        } elseif ($nights >= 7 && $rates['weekly_discount_percent'] > 0) {
            $discount = $total * ($rates['weekly_discount_percent'] / 100);
        }

        return [
            'nights' => $nights,
            'base_total' => $total,
            'discount' => $discount,
            'subtotal' => $total - $discount,
            'total' => $total - $discount
        ];
    }
}
