<?php

namespace App\Models;

class Setting extends Model
{
    protected $table = 'settings';

    public function getAllAsAssoc()
    {
        try {
            error_log("Starting getAllAsAssoc method");
            
            // Debug database connection
            error_log("Database connection status: " . ($this->db ? "Connected" : "Not connected"));
            
            // Direct query to get all settings
            $sql = "SELECT setting_key, setting_value FROM {$this->table}";
            error_log("Executing SQL: " . $sql);
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Debug raw results
            error_log("Raw results from database:");
            error_log(print_r($results, true));
            
            // Convert to associative array
            $settings = [];
            foreach ($results as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
                error_log("Added setting: {$row['setting_key']} = {$row['setting_value']}");
            }
            
            // Debug final settings array
            error_log("Final settings array:");
            error_log(print_r($settings, true));
            
            return $settings;
        } catch (\Exception $e) {
            error_log("Error in getAllAsAssoc: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }

    public function get($key)
    {
        $sql = "SELECT setting_value FROM {$this->table} WHERE setting_key = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$key]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? $result['setting_value'] : null;
    }

    public function updateByKey($key, $value)
    {
        $sql = "INSERT INTO {$this->table} (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$key, $value, $value]);
    }

    public function ensureDefaultSettings()
    {
        $defaults = [
            'site_name' => 'RentSmart',
            'site_description' => '',
            'site_keywords' => '',
            'site_email' => '',
            'site_logo' => '',
            'site_favicon' => '',
            'site_footer_text' => '',
            'site_analytics_code' => '',
            'maintenance_mode' => '0',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'smtp_host' => '',
            'smtp_port' => '',
            'smtp_user' => '',
            'smtp_pass' => '',
            'sms_provider' => '',
            'sms_api_key' => '',
            'sms_api_secret' => ''
        ];

        foreach ($defaults as $key => $value) {
            $sql = "INSERT IGNORE INTO {$this->table} (setting_key, setting_value) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$key, $value]);
        }
    }
} 