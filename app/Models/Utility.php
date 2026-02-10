<?php

namespace App\Models;

class Utility extends Model
{
    protected $table = 'utilities';
    protected $fillable = [
        'unit_id', 'utility_type', 'meter_number', 'is_metered', 'flat_rate'
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function readings()
    {
        return $this->hasMany(UtilityReading::class, 'utility_id');
    }

    public function getLatestReading($utilityId = null)
    {
        if ($utilityId) {
            $sql = "SELECT * FROM utility_readings WHERE utility_id = ? ORDER BY reading_date DESC, id DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$utilityId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        }
        
        return $this->readings()
            ->orderBy('reading_date', 'desc')
            ->first();
    }

    public function getPreviousReading()
    {
        return $this->readings()
            ->orderBy('reading_date', 'desc')
            ->skip(1)
            ->first();
    }

    public function calculateUsage($currentReading = null, $previousReading = null)
    {
        if (!$this->is_metered) {
            return null;
        }

        if (!$currentReading) {
            $currentReading = $this->getLatestReading();
        }

        if (!$previousReading) {
            $previousReading = $this->getPreviousReading();
        }

        if (!$currentReading || !$previousReading) {
            return null;
        }

        return $currentReading->reading_value - $previousReading->reading_value;
    }

    public function getUtilityTypeLabel()
    {
        $types = [
            'water' => 'Water',
            'electricity' => 'Electricity',
            'gas' => 'Gas',
            'internet' => 'Internet',
            'other' => 'Other'
        ];

        return $types[$this->utility_type] ?? ucfirst($this->utility_type);
    }

    public function getStatusBadge()
    {
        if ($this->is_metered) {
            return '<span class="badge bg-primary">Metered</span>';
        } else {
            return '<span class="badge bg-secondary">Flat Rate</span>';
        }
    }

    public static function getUtilityTypes()
    {
        return [
            'water' => 'Water',
            'electricity' => 'Electricity',
            'gas' => 'Gas',
            'internet' => 'Internet',
            'other' => 'Other'
        ];
    }

    public function getAll($userId = null)
    {
        try {
            $sql = "SELECT u.*, un.unit_number, p.id as property_id, p.name as property_name, t.name as tenant_name
                    FROM utilities u
                    JOIN units un ON u.unit_id = un.id
                    JOIN properties p ON un.property_id = p.id
                    LEFT JOIN leases l ON l.unit_id = un.id AND l.status = 'active'
                    LEFT JOIN tenants t ON t.id = l.tenant_id
                    ORDER BY p.name, un.unit_number, u.utility_type";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log("Error in Utility::getAll: " . $e->getMessage());
            return [];
        }
    }

    public function getUnitsByProperty($propertyId)
    {
        $sql = "SELECT u.*, p.name as property_name 
            FROM units u 
            JOIN properties p ON u.property_id = p.id 
            WHERE u.property_id = ? 
            ORDER BY u.unit_number";
            
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$propertyId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getUtilitiesByUnit($unitId)
    {
        $sql = "SELECT ut.*, un.unit_number, p.id as property_id, p.name as property_name, t.name as tenant_name,
                   lr.reading_value as latest_reading, lr.reading_date as latest_reading_date,
                   lr.cost as latest_cost,
                   pr.reading_value as previous_reading,
                   pr.reading_date as previous_reading_date
            FROM utilities ut
            JOIN units un ON ut.unit_id = un.id
            JOIN properties p ON un.property_id = p.id
            LEFT JOIN leases l ON l.unit_id = un.id AND l.status = 'active'
            LEFT JOIN tenants t ON t.id = l.tenant_id
            LEFT JOIN (
                SELECT ur1.*
                FROM utility_readings ur1
                LEFT JOIN utility_readings ur2
                ON ur1.utility_id = ur2.utility_id AND ur1.reading_date < ur2.reading_date
                WHERE ur2.id IS NULL AND ur1.utility_id IN (SELECT id FROM utilities WHERE unit_id = ?)
            ) lr ON ut.id = lr.utility_id
            LEFT JOIN (
                SELECT ur1.*
                FROM utility_readings ur1
                LEFT JOIN utility_readings ur2
                ON ur1.utility_id = ur2.utility_id AND ur1.reading_date < ur2.reading_date
                WHERE ur2.reading_date = (
                    SELECT MAX(reading_date) 
                    FROM utility_readings 
                    WHERE utility_id = ur1.utility_id
                )
                AND ur1.utility_id IN (SELECT id FROM utilities WHERE unit_id = ?)
            ) pr ON ut.id = pr.utility_id
            WHERE ut.unit_id = ?
            ORDER BY ut.utility_type";
            
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$unitId, $unitId, $unitId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getUtilityStats()
    {
        $sql = "SELECT 
                COUNT(*) as total_utilities,
                SUM(CASE WHEN is_metered = 1 THEN 1 ELSE 0 END) as metered_utilities,
                SUM(CASE WHEN is_metered = 0 THEN 1 ELSE 0 END) as flat_rate_utilities,
                COUNT(DISTINCT unit_id) as units_with_utilities
            FROM utilities";
            
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getUtilitiesByType()
    {
        $sql = "SELECT 
                utility_type,
                COUNT(*) as count
            FROM utilities
            GROUP BY utility_type
            ORDER BY count DESC";
            
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getTenantUtilities($tenantId)
    {
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');

        // Tenant portal should only show utilities for the tenant's active lease unit.
        $sql = "SELECT u.*,
                       mr.reading_value,
                       mr.reading_date,
                       mr.cost as current_cost,
                       CASE
                           WHEN u.is_metered = 1 THEN IFNULL(mr.cost, 0)
                           ELSE IFNULL(u.flat_rate, 0)
                       END as amount,
                       (
                           CASE
                               WHEN u.is_metered = 1 THEN IFNULL(mr.cost, 0)
                               ELSE IFNULL(u.flat_rate, 0)
                           END
                           - IFNULL((
                               SELECT SUM(p.amount)
                               FROM payments p
                               JOIN leases l ON p.lease_id = l.id
                               WHERE l.tenant_id = ?
                                 AND l.status = 'active'
                                 AND p.payment_type = 'utility'
                                 AND p.status IN ('completed','verified')
                                 AND p.utility_id = u.id
                                 AND (
                                       (p.applies_to_month IS NOT NULL AND p.applies_to_month BETWEEN ? AND ?)
                                    OR (p.applies_to_month IS NULL AND p.payment_date BETWEEN ? AND ?)
                                 )
                           ), 0)
                       ) AS net_amount
                FROM utilities u
                JOIN leases l0 ON l0.unit_id = u.unit_id AND l0.tenant_id = ? AND l0.status = 'active'
                LEFT JOIN (
                    SELECT ur.*
                    FROM utility_readings ur
                    INNER JOIN (
                        SELECT utility_id, MAX(id) AS max_id
                        FROM utility_readings
                        WHERE reading_date BETWEEN ? AND ?
                        GROUP BY utility_id
                    ) x ON ur.utility_id = x.utility_id AND ur.id = x.max_id
                ) mr ON u.id = mr.utility_id
                ORDER BY u.utility_type";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tenantId, $monthStart, $monthEnd, $monthStart, $monthEnd, $tenantId, $monthStart, $monthEnd]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
} 