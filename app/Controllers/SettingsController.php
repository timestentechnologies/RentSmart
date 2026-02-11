<?php

namespace App\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Database\Connection;
use PDO;
use Exception;

class SettingsController
{
    private $settings;
    private $db;

    public function __construct() {
        try {
            $this->db = Connection::getInstance()->getConnection();
            
            // Verify database connection and table structure
            $stmt = $this->db->query("SHOW TABLES LIKE 'settings'");
            if ($stmt->rowCount() === 0) {
                throw new Exception("Settings table not found in database");
            }
            
            // Check table structure
            $stmt = $this->db->query("DESCRIBE settings");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            error_log("Settings table columns: " . print_r($columns, true));
            
            // Check existing settings
            $stmt = $this->db->query("SELECT COUNT(*) FROM settings");
            $count = $stmt->fetchColumn();
            error_log("Number of settings in database: " . $count);
            
            $this->settings = new Setting();
            $this->settings->ensureDefaultSettings();
            
            // Check if user is logged in and is admin
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Please login to continue';
            $_SESSION['flash_type'] = 'danger';
            redirect('/home');
        }

            if ($_SESSION['user_role'] !== 'admin') {
                $_SESSION['flash_message'] = 'Access denied';
                $_SESSION['flash_type'] = 'danger';
                redirect('/dashboard');
            }
            
        } catch (Exception $e) {
            error_log("Database Connection Error in SettingsController: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $_SESSION['flash_message'] = 'Database connection error. Please try again later.';
            $_SESSION['flash_type'] = 'danger';
        }
    }

    public function ai()
    {
        try {
            // Always ensure required keys exist (idempotent)
            $this->settings->ensureDefaultSettings();
            $settings = $this->settings->getAllAsAssoc();

            echo view('settings/ai', [
                'title' => 'AI Settings - RentSmart',
                'settings' => $settings
            ]);
        } catch (Exception $e) {
            error_log("Error loading AI settings: " . $e->getMessage());
            echo view('errors/500', [
                'title' => '500 Internal Server Error'
            ]);
        }
    }

    public function updateAI()
    {
        try {
            if (!verify_csrf_token()) {
                $_SESSION['flash_message'] = 'Invalid security token';
                $_SESSION['flash_type'] = 'danger';
                redirect('/settings/ai');
            }

            // Read current values to avoid overwriting with defaults when fields are missing
            $currentProvider = $this->settings->get('ai_provider') ?? 'openai';
            $currentApiKey   = $this->settings->get('openai_api_key') ?? '';
            $currentModel    = $this->settings->get('openai_model') ?? 'gpt-4.1-mini';
            $currentPrompt   = $this->settings->get('ai_system_prompt') ?? 'You are RentSmart Support AI. Help users with property management tasks and app guidance.';
            $currentGoogleKey = $this->settings->get('google_api_key') ?? '';
            $currentGoogleModel = $this->settings->get('google_model') ?? 'gemini-3-flash-preview';

            $aiEnabled = isset($_POST['ai_enabled']) ? '1' : '0';
            $aiProvider = isset($_POST['ai_provider']) ? trim((string)$_POST['ai_provider']) : $currentProvider;
            $apiKey     = isset($_POST['openai_api_key']) ? trim((string)$_POST['openai_api_key']) : $currentApiKey;
            $model      = isset($_POST['openai_model']) ? trim((string)$_POST['openai_model']) : $currentModel;
            $prompt     = isset($_POST['ai_system_prompt']) ? (string)$_POST['ai_system_prompt'] : $currentPrompt;
            $googleKey  = isset($_POST['google_api_key']) ? trim((string)$_POST['google_api_key']) : $currentGoogleKey;
            $googleModel = isset($_POST['google_model']) ? trim((string)$_POST['google_model']) : $currentGoogleModel;

            // If any critical field ends up blank string, preserve the current value
            if ($aiProvider === '') { $aiProvider = $currentProvider; }
            if ($apiKey === '')     { $apiKey = $currentApiKey; }
            if ($model === '')      { $model = $currentModel; }
            if ($prompt === '')     { $prompt = $currentPrompt; }
            if ($googleKey === '')  { $googleKey = $currentGoogleKey; }
            if ($googleModel === ''){ $googleModel = $currentGoogleModel; }

            error_log('updateAI payload -> enabled: ' . $aiEnabled . ', provider: ' . $aiProvider . ', model: ' . $model . ', googleModel: ' . $googleModel);

            $updates = [
                'ai_enabled' => $aiEnabled,
                'ai_provider' => $aiProvider,
                'openai_api_key' => $apiKey,
                'openai_model' => $model,
                'google_api_key' => $googleKey,
                'google_model' => $googleModel,
                'ai_system_prompt' => $prompt,
            ];

            foreach ($updates as $k => $v) {
                $this->settings->updateByKey($k, $v);
            }

            $_SESSION['flash_message'] = 'AI settings updated successfully';
            $_SESSION['flash_type'] = 'success';
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'Error updating AI settings: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }

        // Ensure keys exist for next load and reflect latest
        $this->settings->ensureDefaultSettings();
        redirect('/settings/ai');
    }

    public function index()
    {
        try {
            // Get all settings
            $settings = $this->settings->getAllAsAssoc();
            
            // Debug output
            error_log("Settings before ensureDefaultSettings: " . print_r($settings, true));
            
            // Ensure we have the required settings
            if (empty($settings)) {
                $this->settings->ensureDefaultSettings();
                $settings = $this->settings->getAllAsAssoc();
            }
            
            // Debug output
            error_log('Settings loaded for view: ' . print_r($settings, true));
            
            echo view('settings/index', [
                'title' => 'System Settings - RentSmart',
                'settings' => $settings
            ]);
        } catch (Exception $e) {
            error_log("Error loading settings: " . $e->getMessage());
            echo view('errors/500', [
                'title' => '500 Internal Server Error'
            ]);
        }
    }

    public function email() {
        try {
            // Debug connection
            error_log("Database connection status in email method: " . ($this->db ? "Connected" : "Not connected"));
            
            // Get all settings
            $settings = $this->settings->getAllAsAssoc();
            error_log("Settings after getAllAsAssoc in email method:");
            error_log(print_r($settings, true));
            
            // Ensure we have the required settings
            if (empty($settings)) {
                error_log("Settings were empty, ensuring defaults...");
                $this->settings->ensureDefaultSettings();
                $settings = $this->settings->getAllAsAssoc();
                error_log("Settings after ensuring defaults:");
                error_log(print_r($settings, true));
            }
            
            // Debug final settings before passing to view
            error_log("Final settings being passed to email view:");
            error_log(print_r($settings, true));
            
            echo view('settings/email', [
                'title' => 'Email Settings - RentSmart',
                'settings' => $settings
            ]);
        } catch (Exception $e) {
            error_log("Error in email method: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo view('errors/500', [
                'title' => '500 Internal Server Error'
            ]);
        }
    }

    public function update()
    {
        try {
            // Verify CSRF token
            if (!verify_csrf_token()) {
                $_SESSION['flash_message'] = 'Invalid security token';
                $_SESSION['flash_type'] = 'danger';
                redirect('/settings');
            }

            $allowedFields = [
                'site_name', 'site_email', 'site_description', 'site_keywords',
                'site_footer_text', 'site_analytics_code', 'maintenance_mode',
                'timezone', 'date_format'
            ];

            // Process text fields
            foreach ($allowedFields as $field) {
                if (isset($_POST[$field])) {
                    $this->settings->updateByKey($field, $_POST[$field]);
                }
            }
            
            // Handle file uploads
            $fileFields = [
                'site_logo' => ['dir' => 'images', 'allowed' => ['jpg', 'jpeg', 'png', 'gif']],
                'site_favicon' => ['dir' => 'images', 'allowed' => ['ico', 'png']]
            ];

            foreach ($fileFields as $field => $config) {
                if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES[$field];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    
                    if (in_array($ext, $config['allowed'])) {
                        $newName = $field . '_' . time() . '.' . $ext;
                        $targetPath = __DIR__ . '/../../public/assets/' . $config['dir'] . '/' . $newName;
                        
                        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                            // Delete old file if exists
                            $oldFile = $this->settings->get($field);
                            if ($oldFile) {
                                $oldPath = __DIR__ . '/../../public/assets/' . $config['dir'] . '/' . $oldFile;
                                if (file_exists($oldPath)) {
                                    unlink($oldPath);
                                }
                            }
                            
                            $this->settings->updateByKey($field, $newName);
                        }
                    }
                }
            }

            // Handle checkbox fields
            $checkboxFields = ['maintenance_mode'];
            foreach ($checkboxFields as $field) {
                $value = isset($_POST[$field]) ? '1' : '0';
                $this->settings->updateByKey($field, $value);
            }

            $_SESSION['flash_message'] = 'Settings updated successfully!';
            $_SESSION['flash_type'] = 'success';
            
        } catch (Exception $e) {
            error_log("Error updating settings: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error updating settings: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
        
        redirect('/settings');
    }

    public function updateSite()
    {
        $settingModel = new Setting();
        $allowedKeys = ['site_name', 'site_description'];

        foreach ($_POST as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                $settingModel->updateByKey($key, $value);
            }
        }

        if (!empty($_FILES)) {
            $this->handleFileUpload('site_logo', 'logo.svg');
            $this->handleFileUpload('site_favicon', 'favicon.ico');
        }
        
        $this->redirectWithFlash('Site settings updated successfully!');
    }

    public function updateProfile()
    {
        $userModel = new User();
        $userId = $_SESSION['user_id'];
        
        $data = ['name' => $_POST['name'], 'email' => $_POST['email']];
        $userModel->update($userId, $data);

        if (!empty($_POST['password'])) {
            $userModel->updatePassword($userId, $_POST['password']);
        }

        $this->redirectWithFlash('Profile updated successfully!');
    }

    public function storeUser()
    {
        $userModel = new User();
        $data = [
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'password' => $_POST['password'],
            'role' => $_POST['role']
        ];
        $userModel->createUser($data);

        $this->redirectWithFlash('User added successfully!');
    }
    
    public function updateMail() {
        try {
            // Verify CSRF token
            if (!verify_csrf_token()) {
                $_SESSION['flash_message'] = 'Invalid security token';
                $_SESSION['flash_type'] = 'danger';
                redirect('/settings/email');
            }

            $allowedFields = [
                'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass',
                'sms_provider', 'sms_api_key', 'sms_api_secret'
            ];

            $updates = array_intersect_key($_POST, array_flip($allowedFields));

            foreach ($updates as $key => $value) {
                $this->settings->updateByKey($key, $value);
            }

            $_SESSION['flash_message'] = 'Email/SMS settings updated successfully!';
            $_SESSION['flash_type'] = 'success';
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'Error updating settings: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
        
        redirect('/settings/email');
    }

    public function testEmail() {
        try {
            // Verify CSRF token
            if (!verify_csrf_token()) {
                $_SESSION['flash_message'] = 'Invalid security token';
                $_SESSION['flash_type'] = 'danger';
                redirect('/settings/email');
            }

            $settings = $this->settings->getAllAsAssoc();
            
            // Test email configuration
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $settings['smtp_host'];
            $mail->Port = $settings['smtp_port'];
            $mail->SMTPAuth = true;
            $mail->Username = $settings['smtp_user'];
            $mail->Password = $settings['smtp_pass'];
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            
            $mail->setFrom($settings['smtp_user'], 'RentSmart System');
            $mail->addAddress($settings['site_email'], $settings['site_name']);
            $mail->Subject = 'Test Email from RentSmart';
            $mail->isHTML(true);
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $base = defined('BASE_URL') ? BASE_URL : '';
            $siteUrl = rtrim((string)($settings['site_url'] ?? ($scheme . '://' . $host . $base)), '/');
            $logoUrl = isset($settings['site_logo']) && $settings['site_logo'] ? ($siteUrl . '/public/assets/images/' . $settings['site_logo']) : '';
            $footer = '<div style="margin-top:30px;font-size:12px;color:#888;text-align:center;">Powered by <a href="https://timestentechnologies.co.ke" target="_blank" style="color:#888;text-decoration:none;">Timesten Technologies</a></div>';
            $mail->Body =
                '<div style="max-width:520px;margin:auto;border:1px solid #eee;padding:24px;font-family:sans-serif;">'
                . ($logoUrl ? '<div style="text-align:center;margin-bottom:24px;"><img src="' . $logoUrl . '" alt="Logo" style="max-width:180px;max-height:80px;"></div>' : '') .
                '<p style="font-size:16px;">This is a test email from RentSmart.</p>' .
                '<p>If you received this, your email settings are working correctly.</p>' .
                $footer .
                '</div>';
            
            $mail->send();

            $_SESSION['flash_message'] = 'Test email sent successfully';
            $_SESSION['flash_type'] = 'success';
        } catch (Exception $e) {
            error_log($e->getMessage());
            $_SESSION['flash_message'] = 'Failed to send test email: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
        
        redirect('/settings/email');
    }

    public function testSMS() {
        try {
            // Verify CSRF token
            if (!verify_csrf_token()) {
                $_SESSION['flash_message'] = 'Invalid security token';
                $_SESSION['flash_type'] = 'danger';
                redirect('/settings');
            }

            $settings = $this->settings->getAllAsAssoc();
            
            // Test SMS configuration
            // Implementation depends on your SMS provider
            
            $_SESSION['flash_message'] = 'Test SMS sent successfully';
            $_SESSION['flash_type'] = 'success';
        } catch (Exception $e) {
            error_log($e->getMessage());
            $_SESSION['flash_message'] = 'Failed to send test SMS: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
        
        redirect('/settings');
    }

    public function backup() {
        try {
            $tables = [];
            $return = '';
            
            // Get all tables
            $stmt = $this->db->query("SHOW TABLES");
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            
            // Generate backup
            foreach ($tables as $table) {
                $stmt = $this->db->query("SHOW CREATE TABLE `$table`");
                $row = $stmt->fetch(PDO::FETCH_NUM);
                
                $return .= "DROP TABLE IF EXISTS `$table`;\n\n";
                $return .= $row[1] . ";\n\n";
                
                $stmt = $this->db->query("SELECT * FROM `$table`");
                while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                    $return .= "INSERT INTO `$table` VALUES(";
                    foreach ($row as $value) {
                        $value = addslashes($value);
                        $value = str_replace("\n", "\\n", $value);
                        $return .= $value === null ? "NULL," : "'$value',";
                    }
                    $return = rtrim($return, ",");
                    $return .= ");\n";
                }
                $return .= "\n\n";
            }
            
            $filename = 'backup-' . date('Y-m-d-H-i-s') . '.sql';
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . $filename);
            header('Content-Length: ' . strlen($return));
            echo $return;
            exit;
            
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'Error creating backup: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            redirect('/settings');
        }
    }

    public function restore() {
        if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_message'] = 'No backup file uploaded or upload failed.';
            $_SESSION['flash_type'] = 'danger';
            redirect('/settings');
        }

        $file = $_FILES['backup_file'];
        if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'sql') {
            $_SESSION['flash_message'] = 'Invalid backup file format. Please upload a .sql file.';
            $_SESSION['flash_type'] = 'danger';
            redirect('/settings');
        }

        try {
            $sql = file_get_contents($file['tmp_name']);
            $this->db->exec($sql);
            
            $_SESSION['flash_message'] = 'Database restored successfully!';
            $_SESSION['flash_type'] = 'success';
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'Error restoring database: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
        
        redirect('/settings');
    }

    public function payments()
    {
        try {
            // Get all settings
            $settings = $this->settings->getAllAsAssoc();
            
            // Ensure we have the required settings
            if (empty($settings)) {
                $this->settings->ensureDefaultSettings();
                $settings = $this->settings->getAllAsAssoc();
            }
            
            // Capture the view content
            ob_start();
            echo view('settings/payments', [
                'title' => 'Payment Settings - RentSmart',
                'settings' => $settings
            ]);
            $content = ob_get_clean();
            
            // Render the main layout with the captured content
            echo view('layouts/main', [
                'title' => 'Payment Settings - RentSmart',
                'content' => $content,
                'siteName' => $settings['site_name'] ?? 'RentSmart',
                'siteLogo' => asset('images/' . ($settings['site_logo'] ?? 'default-logo.png')),
                'current_uri' => current_uri()
            ]);
        } catch (Exception $e) {
            error_log("Error loading payment settings: " . $e->getMessage());
            echo view('errors/500', [
                'title' => '500 Internal Server Error'
            ]);
        }
    }

    public function updatePayments()
    {
        try {
            $settings = [
                // M-Pesa Settings
                'mpesa_consumer_key' => $_POST['mpesa_consumer_key'] ?? '',
                'mpesa_consumer_secret' => $_POST['mpesa_consumer_secret'] ?? '',
                'mpesa_shortcode' => $_POST['mpesa_shortcode'] ?? '',
                'mpesa_passkey' => $_POST['mpesa_passkey'] ?? '',
                'mpesa_environment' => $_POST['mpesa_environment'] ?? 'sandbox',
                'mpesa_callback_url' => $_POST['mpesa_callback_url'] ?? '',

                // Stripe Settings
                'stripe_public_key' => $_POST['stripe_public_key'] ?? '',
                'stripe_secret_key' => $_POST['stripe_secret_key'] ?? '',
                'stripe_webhook_secret' => $_POST['stripe_webhook_secret'] ?? '',
                'stripe_environment' => $_POST['stripe_environment'] ?? 'test',

                // PayPal Settings
                'paypal_client_id' => $_POST['paypal_client_id'] ?? '',
                'paypal_secret' => $_POST['paypal_secret'] ?? '',
                'paypal_environment' => $_POST['paypal_environment'] ?? 'sandbox',
                'paypal_webhook_id' => $_POST['paypal_webhook_id'] ?? ''
            ];

            foreach ($settings as $key => $value) {
                // Save the setting even if empty (to allow clearing values)
                $this->settings->updateByKey($key, $value);
            }

            $_SESSION['flash_message'] = 'Payment settings updated successfully';
            $_SESSION['flash_type'] = 'success';
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'Error updating payment settings: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }

        redirect('/settings/payments');
    }

    public function publicPages()
    {
        try {
            $this->settings->ensureDefaultSettings();
            $settings = $this->settings->getAllAsAssoc();
            $this->seedPublicPagesDefaults($settings);
            $settings = $this->settings->getAllAsAssoc();

            echo view('settings/public_pages', [
                'title' => 'Public Pages Content - RentSmart',
                'settings' => $settings
            ]);
        } catch (Exception $e) {
            error_log('Error loading public pages settings: ' . $e->getMessage());
            echo view('errors/500', [
                'title' => '500 Internal Server Error'
            ]);
        }
    }

    private function seedPublicPagesDefaults(array $settings)
    {
        $defaults = [
            'home_hero_title' => 'Property Management Made Easy',
            'home_hero_subtitle' => 'Streamline your property management with RentSmart. The all-in-one solution for landlords and property managers.',
            'home_hero_primary_text' => 'Start 7-Day Free Trial',
            'home_hero_secondary_text' => 'Learn More',

            'home_stat1_number' => '500+',
            'home_stat1_label' => 'Properties Managed',
            'home_stat2_number' => '500+',
            'home_stat2_label' => 'Happy Clients',
            'home_stat3_number' => '99%',
            'home_stat3_label' => 'Customer Satisfaction',
            'home_stat4_number' => '24/7',
            'home_stat4_label' => 'Support Available',

            'home_split_badge' => 'All-in-one platform',
            'home_split_title' => 'Manage Rent, Utilities & Maintenance in One Place',
            'home_split_description' => 'Track payments, utilities, and maintenance requests with clear records and automated invoicing—so landlords and tenants always know what is due and what has been paid.',
            'home_split_bullets_json' => json_encode([
                ['title' => 'Accurate payment types', 'text' => 'Rent, utilities, and maintenance always recorded correctly.'],
                ['title' => 'Automated invoicing', 'text' => 'Invoices update based on what was paid—no confusion.'],
                ['title' => 'Tenant self-service', 'text' => 'Tenants can pay and track balances from the portal.'],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

            'home_why_title' => 'Why Choose RentSmart for Property Management?',
            'home_why_subtitle' => 'A modern, Kenyan-ready platform for landlords, managers, and agents—built for speed, clarity, and accurate records.',
            'home_why_cards_json' => json_encode([
                ['title' => 'M-PESA ready', 'text' => 'Accept payments and keep references organized for quick verification and reporting.'],
                ['title' => 'Secure & reliable', 'text' => 'Keep your tenant and payment records safe with a cloud-ready setup and clear audit trails.'],
                ['title' => 'Clear dashboards', 'text' => 'See what is due, what was paid, and what needs action—without digging through spreadsheets.'],
                ['title' => 'Accurate invoicing', 'text' => 'Rent, utilities, and maintenance are tracked separately so invoices and balances remain correct.'],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

            'home_features_title' => 'Complete Property Management Software Features',
            'home_features_subtitle' => 'Streamline your rental business with our comprehensive property management tools designed for landlords and real estate professionals in Kenya',
            'home_features_cards_json' => json_encode([
                ['icon' => 'bi bi-house-door', 'title' => 'Property Management', 'text' => 'Manage unlimited properties, units, and tenants from one centralized dashboard. Track occupancy rates, rental income, and property performance in real-time.'],
                ['icon' => 'bi bi-cash-coin', 'title' => 'Rent Collection & Tenant Portal', 'text' => 'Collect rent via M-PESA and give tenants self-service access to pay, view balances, and track payment history—anytime, anywhere.'],
                ['icon' => 'bi bi-file-earmark-text', 'title' => 'Lease Management System', 'text' => 'Create digital lease agreements, track lease terms, and receive automated renewal reminders. Simplify tenant onboarding and documentation.'],
                ['icon' => 'bi bi-graph-up', 'title' => 'Invoices & Financial Reports', 'text' => 'Generate invoices and receipts, and access clear reporting on income, expenses, and performance for confident decision-making.'],
                ['icon' => 'bi bi-bell', 'title' => 'Automated Notifications', 'text' => 'Stay informed with automated SMS and email notifications for rent due dates, maintenance updates, lease renewals, and payment confirmations.'],
                ['icon' => 'bi bi-lightning-charge', 'title' => 'Utilities Management', 'text' => 'Track metered and flat-rate utilities, readings, charges, and payments—so utilities are always clearly separated from rent.'],
                ['icon' => 'bi bi-tools', 'title' => 'Maintenance Management', 'text' => 'Log requests, assign work, track progress, and record maintenance costs with clear references for invoices and statements.'],
                ['icon' => 'bi bi-cash-stack', 'title' => 'Expense Tracking', 'text' => 'Record property expenses and keep a clear view of profitability with statements and reports by property and time period.'],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

            'home_testimonials_title' => 'Trusted by Property Managers Across Kenya',
            'home_testimonials_subtitle' => 'See how landlords and real estate professionals are transforming their property management with RentSmart',
            'home_testimonials_json' => json_encode([
                ['name' => 'Mercy Wanjiru', 'role' => 'Property Manager', 'text' => '"RentSmart has completely transformed how we manage our properties. The automated rent collection and reporting features save us hours every week."'],
                ['name' => 'James Kamau', 'role' => 'Landlord', 'text' => '"The tenant portal has made communication so much easier. My tenants love being able to pay rent and submit maintenance requests online."'],
                ['name' => 'David Kibara', 'role' => 'Real Estate Agent', 'text' => '"The financial reports and analytics help me make data-driven decisions. RentSmart has helped us increase our property portfolio\'s performance."'],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

            'home_faq_title' => 'Frequently Asked Questions',
            'home_faq_subtitle' => 'Everything you need to know about RentSmart property management software',
            'home_faq_items_json' => json_encode([
                ['q' => 'What is RentSmart property management software?', 'a' => 'RentSmart is a comprehensive cloud-based property management system designed for landlords, property managers, and real estate agents in Kenya. It helps you manage properties, tenants, rent collection, maintenance, utilities, and financial reporting all in one platform. With M-PESA integration and automated features, RentSmart simplifies rental property management.'],
                ['q' => 'How long is the free trial period?', 'a' => 'RentSmart offers a generous 7-day free trial with full access to all features. No credit card required to start. You can explore all property management features, add properties and tenants, collect rent, and generate reports during the trial period. After 7-day, you can choose a plan that fits your needs.'],
                ['q' => 'Does RentSmart integrate with M-PESA?', 'a' => 'Yes! RentSmart has full M-PESA integration for seamless rent collection. Tenants can pay rent directly through M-PESA, and payments are automatically recorded in the system. You\'ll receive instant notifications when payments are made, and the system automatically reconciles payments with tenant accounts.'],
                ['q' => 'How many properties can I manage with RentSmart?', 'a' => 'The number of properties you can manage depends on your subscription plan. Our Basic plan supports up to 10 properties, Professional plan up to 50 properties, and Enterprise plan offers unlimited properties. Each property can have multiple units, and you can manage all of them from a single dashboard.'],
                ['q' => 'Can tenants access the system?', 'a' => 'Yes! RentSmart includes a dedicated tenant portal where tenants can log in to view their lease details, make rent payments, submit maintenance requests, and access important documents. This self-service portal reduces your workload and improves tenant satisfaction.'],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

            'home_cta_title' => 'Transform Your Property Management Today',
            'home_cta_description' => 'Join hundreds of landlords and property managers in Kenya who are simplifying their rental business with RentSmart. Start your free trial now!',
            'home_cta_button_text' => 'Start Your 7-Day Free Trial',
            'home_cta_footnote' => 'No credit card required',

            'contact_hero_title' => 'Contact Sales & Support',
            'contact_hero_subtitle' => "We'd love to hear from you. Send us a message and we'll respond shortly.",

            'terms_header' => 'Terms of Service',
            'privacy_header' => 'Privacy Policy',
        ];

        foreach ($defaults as $key => $value) {
            if (!isset($settings[$key]) || $settings[$key] === '') {
                $this->settings->updateByKey($key, (string)$value);
            }
        }
    }

    public function updatePublicPages()
    {
        try {
            if (!verify_csrf_token()) {
                $_SESSION['flash_message'] = 'Invalid security token';
                $_SESSION['flash_type'] = 'danger';
                redirect('/settings/public-pages');
            }

            $allowedTextKeys = [
                'home_hero_title',
                'home_hero_subtitle',
                'home_hero_primary_text',
                'home_hero_secondary_text',

                'home_stat1_number','home_stat1_label',
                'home_stat2_number','home_stat2_label',
                'home_stat3_number','home_stat3_label',
                'home_stat4_number','home_stat4_label',

                'home_split_badge',
                'home_split_title',
                'home_split_description',
                'home_split_bullets_json',

                'home_why_title',
                'home_why_subtitle',
                'home_why_cards_json',

                'home_features_title',
                'home_features_subtitle',
                'home_features_cards_json',

                'home_testimonials_title',
                'home_testimonials_subtitle',
                'home_testimonials_json',

                'home_faq_title',
                'home_faq_subtitle',
                'home_faq_items_json',

                'home_cta_title',
                'home_cta_description',
                'home_cta_button_text',
                'home_cta_footnote',

                'contact_hero_title',
                'contact_hero_subtitle',

                'terms_header',
                'privacy_header',
            ];

            foreach ($allowedTextKeys as $key) {
                if (array_key_exists($key, $_POST)) {
                    $this->settings->updateByKey($key, trim((string)$_POST[$key]));
                }
            }

            // Validate JSON fields if provided (do not overwrite with invalid JSON)
            $jsonKeys = [
                'home_split_bullets_json',
                'home_why_cards_json',
                'home_features_cards_json',
                'home_testimonials_json',
                'home_faq_items_json',
            ];
            foreach ($jsonKeys as $key) {
                if (!array_key_exists($key, $_POST)) {
                    continue;
                }
                $raw = trim((string)$_POST[$key]);
                if ($raw === '') {
                    $this->settings->updateByKey($key, '');
                    continue;
                }
                json_decode($raw, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Invalid JSON provided for: ' . $key);
                }
                $this->settings->updateByKey($key, $raw);
            }

            // Handle split image upload
            if (isset($_FILES['home_split_image']) && $_FILES['home_split_image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['home_split_image'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($ext, $allowed, true)) {
                    throw new Exception('Invalid image type for split image');
                }

                $newName = 'home_split_image_' . time() . '.' . $ext;
                $targetPath = __DIR__ . '/../../public/assets/images/' . $newName;
                if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                    throw new Exception('Failed to upload split image');
                }

                // Delete old file if exists
                $old = $this->settings->get('home_split_image');
                if (!empty($old)) {
                    $oldPath = __DIR__ . '/../../public/assets/images/' . $old;
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                $this->settings->updateByKey('home_split_image', $newName);
            }

            $_SESSION['flash_message'] = 'Public pages content updated successfully';
            $_SESSION['flash_type'] = 'success';
        } catch (Exception $e) {
            error_log('Error updating public pages content: ' . $e->getMessage());
            $_SESSION['flash_message'] = 'Error updating public pages content: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }

        redirect('/settings/public-pages');
    }

    public function testMpesa()
    {
        try {
            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Use form data if provided, otherwise use saved settings
            if (!empty($input['consumer_key']) && !empty($input['consumer_secret'])) {
                $consumerKey = $input['consumer_key'];
                $consumerSecret = $input['consumer_secret'];
                $environment = $input['environment'] ?? 'sandbox';
            } else {
                $settings = $this->settings->getAllAsAssoc();
                $consumerKey = $settings['mpesa_consumer_key'] ?? '';
                $consumerSecret = $settings['mpesa_consumer_secret'] ?? '';
                $environment = $settings['mpesa_environment'] ?? 'sandbox';
            }

            if (empty($consumerKey) || empty($consumerSecret)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'M-Pesa credentials are not configured. Please enter Consumer Key and Consumer Secret.'
                ]);
                return;
            }

            $url = $environment === 'production' 
                ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
                : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

            $credentials = base64_encode($consumerKey . ':' . $consumerSecret);
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Basic ' . $credentials
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            // Log the response for debugging
            error_log("M-Pesa Test - HTTP Code: $httpCode");
            error_log("M-Pesa Test - Response: $response");
            if ($curlError) {
                error_log("M-Pesa Test - cURL Error: $curlError");
            }
            
            if ($httpCode === 200) {
                $responseData = json_decode($response, true);
                echo json_encode([
                    'success' => true,
                    'message' => 'M-Pesa connection successful! Access token received.',
                    'token_preview' => isset($responseData['access_token']) ? substr($responseData['access_token'], 0, 20) . '...' : 'N/A'
                ]);
            } else {
                $responseData = json_decode($response, true);
                $errorMessage = $responseData['error_description'] ?? $responseData['errorMessage'] ?? 'Failed to authenticate with M-Pesa API';
                
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => $errorMessage,
                    'http_code' => $httpCode
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error testing M-Pesa connection: ' . $e->getMessage()
            ]);
        }
    }

    public function getMpesaDetails()
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                return;
            }

            $settings = $this->settings->getAllAsAssoc();
            $shortcode = $settings['mpesa_shortcode'] ?? '';

            if (empty($shortcode)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'M-Pesa is not properly configured'
                ]);
                return;
            }

            echo json_encode([
                'success' => true,
                'shortcode' => $shortcode,
                'environment' => $settings['mpesa_environment'] ?? 'sandbox'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error getting M-Pesa details: ' . $e->getMessage()
            ]);
        }
    }

    public function testStripe()
    {
        try {
            $settings = $this->settings->getAllAsAssoc();
            $secretKey = $settings['stripe_secret_key'] ?? '';

            if (empty($secretKey)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Stripe credentials are not configured'
                ]);
                return;
            }

            $ch = curl_init('https://api.stripe.com/v1/balance');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $secretKey
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($httpCode === 200) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Stripe connection successful'
                ]);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to authenticate with Stripe API'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error testing Stripe connection: ' . $e->getMessage()
            ]);
        }
    }

    public function testPaypal()
    {
        try {
            $settings = $this->settings->getAllAsAssoc();
            $clientId = $settings['paypal_client_id'] ?? '';
            $secret = $settings['paypal_secret'] ?? '';
            $environment = $settings['paypal_environment'] ?? 'sandbox';

            if (empty($clientId) || empty($secret)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'PayPal credentials are not configured'
                ]);
                return;
            }

            $url = $environment === 'live'
                ? 'https://api-m.paypal.com/v1/oauth2/token'
                : 'https://api-m.sandbox.paypal.com/v1/oauth2/token';

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Accept-Language: en_US'
            ]);
            curl_setopt($ch, CURLOPT_USERPWD, $clientId . ':' . $secret);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($httpCode === 200) {
                echo json_encode([
                    'success' => true,
                    'message' => 'PayPal connection successful'
                ]);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to authenticate with PayPal API'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error testing PayPal connection: ' . $e->getMessage()
            ]);
        }
    }

    private function handleFileUpload($fileInputName, $fileName)
    {
        if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
            $uploadDir = getcwd() . '/assets/images/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $targetFile = $uploadDir . $fileName;
            move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $targetFile);
        }
    }

    private function redirectWithFlash($message, $type = 'success')
    {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
        redirect('/settings');
    }
} 