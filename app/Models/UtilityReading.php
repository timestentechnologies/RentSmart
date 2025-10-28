<?php

namespace App\Models;

class UtilityReading extends Model
{
    protected $table = 'utility_readings';
    protected $fillable = [
        'utility_id', 'reading_date', 'reading_value'
    ];

    public function utility()
    {
        return $this->belongsTo(Utility::class, 'utility_id');
    }

    public function getFormattedDate()
    {
        return date('M d, Y', strtotime($this->reading_date));
    }

    public function getFormattedValue()
    {
        return number_format($this->reading_value, 2);
    }

    /**
     * Get the latest reading for a utility by utility_id
     */
    public function getLatestByUtilityId($utilityId)
    {
        $sql = "SELECT * FROM utility_readings WHERE utility_id = ? ORDER BY reading_date DESC, id DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$utilityId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get the previous reading for a utility by utility_id
     */
    public function getPreviousByUtilityId($utilityId)
    {
        $sql = "SELECT * FROM utility_readings WHERE utility_id = ? ORDER BY reading_date DESC, id DESC LIMIT 1 OFFSET 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$utilityId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
} 