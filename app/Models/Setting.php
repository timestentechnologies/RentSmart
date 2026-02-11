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

            // Homepage content
            'homepage_hero_title' => 'Property Management Made Easy',
            'homepage_hero_subtitle' => 'Streamline your property management with RentSmart. The all-in-one solution for landlords and property managers.',
            'homepage_split_badge' => 'All-in-one platform',
            'homepage_split_title' => 'Manage Rent, Utilities & Maintenance in One Place',
            'homepage_split_text' => 'Track payments, utilities, and maintenance requests with clear records and automated invoicing—so landlords and tenants always know what is due and what has been paid.',
            'homepage_split_list_json' => json_encode([
                [ 'title' => 'Accurate payment types', 'text' => 'Rent, utilities, and maintenance always recorded correctly.' ],
                [ 'title' => 'Automated invoicing', 'text' => 'Invoices update based on what was paid—no confusion.' ],
                [ 'title' => 'Tenant self-service', 'text' => 'Tenants can pay and track balances from the portal.' ],
            ]),
            'homepage_split_image' => 'new.png',

            'homepage_stats_json' => json_encode([
                [ 'number' => '500+', 'label' => 'Properties Managed' ],
                [ 'number' => '500+', 'label' => 'Happy Clients' ],
                [ 'number' => '99%', 'label' => 'Customer Satisfaction' ],
                [ 'number' => '24/7', 'label' => 'Support Available' ],
            ]),

            'homepage_why_title' => 'Why Choose RentSmart for Property Management?',
            'homepage_why_subtitle' => 'A modern, Kenyan-ready platform for landlords, managers, and agents—built for speed, clarity, and accurate records.',
            'homepage_why_cards_json' => json_encode([
                [ 'icon' => 'bi-phone', 'title' => 'M-PESA ready', 'text' => 'Accept payments and keep references organized for quick verification and reporting.' ],
                [ 'icon' => 'bi-shield-check', 'title' => 'Secure & reliable', 'text' => 'Keep your tenant and payment records safe with a cloud-ready setup and clear audit trails.' ],
                [ 'icon' => 'bi-graph-up', 'title' => 'Clear dashboards', 'text' => 'See what is due, what was paid, and what needs action—without digging through spreadsheets.' ],
                [ 'icon' => 'bi-receipt', 'title' => 'Accurate invoicing', 'text' => 'Rent, utilities, and maintenance are tracked separately so invoices and balances remain correct.' ],
            ]),

            'homepage_faqs_json' => json_encode([
                [ 'q' => 'What is RentSmart property management software?', 'a' => 'RentSmart is a comprehensive cloud-based property management system designed for landlords, property managers, and real estate agents in Kenya. It helps you manage properties, tenants, rent collection, maintenance, utilities, and financial reporting all in one platform. With M-PESA integration and automated features, RentSmart simplifies rental property management.' ],
                [ 'q' => 'How long is the free trial period?', 'a' => 'RentSmart offers a generous 7-day free trial with full access to all features. No credit card required to start. You can explore all property management features, add properties and tenants, collect rent, and generate reports during the trial period. After 7-day, you can choose a plan that fits your needs.' ],
                [ 'q' => 'Does RentSmart integrate with M-PESA?', 'a' => 'Yes! RentSmart has full M-PESA integration for seamless rent collection. Tenants can pay rent directly through M-PESA, and payments are automatically recorded in the system. You\'ll receive instant notifications when payments are made, and the system automatically reconciles payments with tenant accounts.' ],
                [ 'q' => 'How many properties can I manage with RentSmart?', 'a' => 'The number of properties you can manage depends on your subscription plan. Our Basic plan supports up to 10 properties, Professional plan up to 50 properties, and Enterprise plan offers unlimited properties. Each property can have multiple units, and you can manage all of them from a single dashboard.' ],
                [ 'q' => 'Can tenants access the system?', 'a' => 'Yes! RentSmart includes a dedicated tenant portal where tenants can log in to view their lease details, make rent payments, submit maintenance requests, and access important documents. This self-service portal reduces your workload and improves tenant satisfaction.' ],
                [ 'q' => 'Is my data secure with RentSmart?', 'a' => 'Absolutely! RentSmart uses bank-level security with SSL encryption to protect your data. All information is stored on secure cloud servers with automatic daily backups. We comply with data protection regulations and never share your information with third parties. Your property and tenant data is safe with us.' ],
                [ 'q' => 'What kind of reports can I generate?', 'a' => 'RentSmart provides comprehensive financial and operational reports including: income statements, rent collection reports, occupancy reports, expense tracking, tenant payment history, maintenance reports, utility billing reports, and property performance analytics. All reports can be exported to PDF or Excel for easy sharing.' ],
                [ 'q' => 'Do you offer customer support?', 'a' => 'Yes! We provide excellent customer support through email and phone. Our Kenyan support team is ready to help you with setup, training, and any questions you may have. We also offer video tutorials and documentation to help you get started.' ],
            ]),
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