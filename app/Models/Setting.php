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
            
            // Deterministically return the latest record per key using a self-join
            $sql = "
                SELECT s.setting_key, s.setting_value
                FROM {$this->table} s
                INNER JOIN (
                    SELECT setting_key, MAX(id) AS max_id
                    FROM {$this->table}
                    GROUP BY setting_key
                ) latest
                ON latest.setting_key = s.setting_key AND latest.max_id = s.id
            ";
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
        $sql = "SELECT setting_value FROM {$this->table} WHERE setting_key = ? ORDER BY id DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$key]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? $result['setting_value'] : null;
    }

    public function updateByKey($key, $value)
    {
        // Try update first
        $sql = "UPDATE {$this->table} SET setting_value = ? WHERE setting_key = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$value, $key]);
        if ($stmt->rowCount() > 0) {
            return true;
        }

        // If no row updated, the key might not exist OR the value is identical.
        // Check if the key already exists; if so, no insert is needed.
        $check = $this->db->prepare("SELECT 1 FROM {$this->table} WHERE setting_key = ? LIMIT 1");
        $check->execute([$key]);
        if ($check->fetchColumn()) {
            // Key exists and value was likely identical; treat as success
            return true;
        }

        // Otherwise, insert a new row
        $sql = "INSERT INTO {$this->table} (setting_key, setting_value) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$key, $value]);
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
            'sms_api_secret' => '',
            'ai_enabled' => '0',
            'ai_provider' => 'openai',
            'openai_api_key' => '',
            'openai_model' => 'gpt-4.1-mini',
            // Google Gemini defaults
            'google_api_key' => '',
            'google_model' => 'gemini-3-flash-preview',
            'ai_system_prompt' => 'You are RentSmart Support AI. Help users with property management tasks and app guidance.',
            'demo_enabled' => '0',
            'demo_protected_user_ids_json' => '[]',
            'demo_protected_property_ids_json' => '[]',
            'demo_protected_unit_ids_json' => '[]',
            'demo_protected_tenant_ids_json' => '[]',
            'demo_protected_lease_ids_json' => '[]',
            'demo_protected_payment_ids_json' => '[]',
            'demo_protected_invoice_ids_json' => '[]',
            'demo_protected_realtor_listing_ids_json' => '[]',
            'demo_protected_realtor_client_ids_json' => '[]',
            'demo_protected_realtor_contract_ids_json' => '[]'
        ];

        foreach ($defaults as $key => $value) {
            // Insert only if key does not exist to prevent duplicates
            $checkSql = "SELECT 1 FROM {$this->table} WHERE setting_key = ? LIMIT 1";
            $check = $this->db->prepare($checkSql);
            $check->execute([$key]);
            if ($check->fetchColumn() === false) {
                $insertSql = "INSERT INTO {$this->table} (setting_key, setting_value) VALUES (?, ?)";
                $insert = $this->db->prepare($insertSql);
                $insert->execute([$key, $value]);
            }
        }
    }
} 