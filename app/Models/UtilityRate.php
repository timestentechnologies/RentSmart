<?php

namespace App\Models;

class UtilityRate extends Model
{
    protected $table = 'utility_rates';
    protected $primaryKey = 'id';
    protected $fillable = [
        'utility_type', 'rate_per_unit', 'effective_from', 'effective_to', 'billing_method'
    ];

    public function getCurrentRate($utilityType, $date = null)
    {
        $date = $date ?: date('Y-m-d');
        $sql = "SELECT * FROM utility_rates WHERE utility_type = ? AND effective_from <= ? AND (effective_to IS NULL OR effective_to >= ?) ORDER BY effective_from DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$utilityType, $date, $date]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getAllTypesWithMethod()
    {
        $sql = "SELECT DISTINCT ur.utility_type, ur.billing_method, ur.rate_per_unit 
                FROM utility_rates ur 
                INNER JOIN (
                    SELECT utility_type, MAX(effective_from) as max_date 
                    FROM utility_rates 
                    WHERE (effective_to IS NULL OR effective_to >= CURDATE()) 
                    GROUP BY utility_type
                ) latest ON ur.utility_type = latest.utility_type AND ur.effective_from = latest.max_date
                ORDER BY ur.utility_type";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAllTypes()
    {
        $sql = "SELECT DISTINCT utility_type FROM utility_rates ORDER BY utility_type";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getRatesByType($utilityType)
    {
        $sql = "SELECT * FROM utility_rates WHERE utility_type = ? ORDER BY effective_from DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$utilityType]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
} 