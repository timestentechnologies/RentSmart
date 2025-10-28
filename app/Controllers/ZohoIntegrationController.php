<?php

namespace App\Controllers;

use App\Database\Connection;

class ZohoIntegrationController
{
    private $db;
    private $clientId;
    private $clientSecret;
    private $refreshToken;
    private $accessToken;
    private $organizationId;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
        
        // Get Zoho credentials from settings
        $stmt = $this->db->prepare("
            SELECT setting_key, setting_value 
            FROM settings 
            WHERE setting_key IN ('zoho_client_id', 'zoho_client_secret', 'zoho_refresh_token', 'zoho_access_token', 'zoho_organization_id')
        ");
        $stmt->execute();
        $settings = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        
        $this->clientId = $settings['zoho_client_id'] ?? null;
        $this->clientSecret = $settings['zoho_client_secret'] ?? null;
        $this->refreshToken = $settings['zoho_refresh_token'] ?? null;
        $this->accessToken = $settings['zoho_access_token'] ?? null;
        $this->organizationId = $settings['zoho_organization_id'] ?? null;
    }

    /**
     * Show Zoho integration management page
     */
    public function manage()
    {
        try {
            requireAuth();

            $isConfigured = !empty($this->clientId) && !empty($this->refreshToken) && !empty($this->organizationId);

            // Get sync status
            $stmt = $this->db->prepare("SELECT * FROM zoho_sync_log ORDER BY synced_at DESC LIMIT 10");
            $stmt->execute();
            $syncLog = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            require 'views/integrations/zoho.php';

        } catch (\Exception $e) {
            error_log("Zoho manage error: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading Zoho integration';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }

    /**
     * Sync payments to Zoho Books
     */
    public function syncPayments()
    {
        try {
            requireAuth();

            if (!$this->isConfigured()) {
                throw new \Exception('Zoho Books not configured');
            }

            // Get payments from last sync
            $stmt = $this->db->prepare("
                SELECT p.*, t.name as tenant_name, t.email as tenant_email, 
                       u.unit_number, pr.name as property_name
                FROM payments p
                LEFT JOIN tenants t ON p.tenant_id = t.id
                LEFT JOIN units u ON p.unit_id = u.id
                LEFT JOIN properties pr ON u.property_id = pr.id
                WHERE p.created_at > (
                    SELECT COALESCE(MAX(synced_at), '2000-01-01') 
                    FROM zoho_sync_log 
                    WHERE entity_type = 'payment' AND status = 'success'
                )
                ORDER BY p.created_at ASC
                LIMIT 100
            ");
            $stmt->execute();
            $payments = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($payments)) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'No new payments to sync',
                    'synced' => 0
                ]);
                exit;
            }

            $synced = 0;
            $errors = [];

            foreach ($payments as $payment) {
                try {
                    // Create customer if not exists
                    $customerId = $this->getOrCreateCustomer($payment);
                    
                    // Create invoice
                    $invoiceData = [
                        'customer_id' => $customerId,
                        'date' => date('Y-m-d', strtotime($payment['payment_date'])),
                        'due_date' => date('Y-m-d', strtotime($payment['payment_date'])),
                        'line_items' => [
                            [
                                'name' => 'Rent - ' . $payment['property_name'] . ' Unit ' . $payment['unit_number'],
                                'description' => 'Monthly rent payment',
                                'rate' => $payment['amount'],
                                'quantity' => 1
                            ]
                        ]
                    ];

                    $invoice = $this->callZohoAPI('/invoices', 'POST', $invoiceData);

                    if (isset($invoice['invoice']['invoice_id'])) {
                        // Record payment
                        $paymentData = [
                            'customer_id' => $customerId,
                            'payment_mode' => $payment['payment_method'] === 'mpesa' ? 'cash' : $payment['payment_method'],
                            'amount' => $payment['amount'],
                            'date' => date('Y-m-d', strtotime($payment['payment_date'])),
                            'invoices' => [
                                [
                                    'invoice_id' => $invoice['invoice']['invoice_id'],
                                    'amount_applied' => $payment['amount']
                                ]
                            ]
                        ];

                        $this->callZohoAPI('/customerpayments', 'POST', $paymentData);

                        // Log success
                        $this->logSync('payment', $payment['id'], 'success', $invoice['invoice']['invoice_id']);
                        $synced++;
                    }

                } catch (\Exception $e) {
                    $errors[] = "Payment ID {$payment['id']}: " . $e->getMessage();
                    $this->logSync('payment', $payment['id'], 'error', null, $e->getMessage());
                }
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => "Synced $synced payments to Zoho Books",
                'synced' => $synced,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            error_log("Zoho sync error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Sync expenses to Zoho Books
     */
    public function syncExpenses()
    {
        try {
            requireAuth();

            if (!$this->isConfigured()) {
                throw new \Exception('Zoho Books not configured');
            }

            // Get expenses from last sync
            $stmt = $this->db->prepare("
                SELECT e.*, p.name as property_name
                FROM expenses e
                LEFT JOIN properties p ON e.property_id = p.id
                WHERE e.created_at > (
                    SELECT COALESCE(MAX(synced_at), '2000-01-01') 
                    FROM zoho_sync_log 
                    WHERE entity_type = 'expense' AND status = 'success'
                )
                ORDER BY e.created_at ASC
                LIMIT 100
            ");
            $stmt->execute();
            $expenses = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($expenses)) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'No new expenses to sync',
                    'synced' => 0
                ]);
                exit;
            }

            $synced = 0;
            $errors = [];

            foreach ($expenses as $expense) {
                try {
                    $expenseData = [
                        'account_name' => 'Property Expenses',
                        'paid_through_account_name' => 'Cash',
                        'date' => date('Y-m-d', strtotime($expense['expense_date'])),
                        'amount' => $expense['amount'],
                        'description' => $expense['description'] . ' - ' . $expense['property_name'],
                        'category_name' => $expense['category'] ?? 'Maintenance'
                    ];

                    $result = $this->callZohoAPI('/expenses', 'POST', $expenseData);

                    if (isset($result['expense']['expense_id'])) {
                        $this->logSync('expense', $expense['id'], 'success', $result['expense']['expense_id']);
                        $synced++;
                    }

                } catch (\Exception $e) {
                    $errors[] = "Expense ID {$expense['id']}: " . $e->getMessage();
                    $this->logSync('expense', $expense['id'], 'error', null, $e->getMessage());
                }
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => "Synced $synced expenses to Zoho Books",
                'synced' => $synced,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            error_log("Zoho expense sync error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Get or create customer in Zoho
     */
    private function getOrCreateCustomer($payment)
    {
        // Search for existing customer
        $customers = $this->callZohoAPI('/contacts?email=' . urlencode($payment['tenant_email']), 'GET');

        if (!empty($customers['contacts'])) {
            return $customers['contacts'][0]['contact_id'];
        }

        // Create new customer
        $customerData = [
            'contact_name' => $payment['tenant_name'],
            'contact_type' => 'customer',
            'email' => $payment['tenant_email']
        ];

        $result = $this->callZohoAPI('/contacts', 'POST', $customerData);

        return $result['contact']['contact_id'];
    }

    /**
     * Save Zoho configuration
     */
    public function saveConfig()
    {
        try {
            requireAuth();

            $clientId = $_POST['client_id'] ?? '';
            $clientSecret = $_POST['client_secret'] ?? '';
            $refreshToken = $_POST['refresh_token'] ?? '';
            $organizationId = $_POST['organization_id'] ?? '';

            if (empty($clientId) || empty($clientSecret) || empty($refreshToken) || empty($organizationId)) {
                throw new \Exception('All fields are required');
            }

            // Save to database
            $settings = [
                'zoho_client_id' => $clientId,
                'zoho_client_secret' => $clientSecret,
                'zoho_refresh_token' => $refreshToken,
                'zoho_organization_id' => $organizationId
            ];

            foreach ($settings as $key => $value) {
                $stmt = $this->db->prepare("
                    INSERT INTO settings (setting_key, setting_value, updated_at)
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                        setting_value = VALUES(setting_value),
                        updated_at = NOW()
                ");
                $stmt->execute([$key, $value]);
            }

            $_SESSION['flash_message'] = 'Zoho Books configured successfully';
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . BASE_URL . '/integrations/zoho');
            exit;

        } catch (\Exception $e) {
            error_log("Zoho config error: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/integrations/zoho');
            exit;
        }
    }

    /**
     * Call Zoho Books API
     */
    private function callZohoAPI($endpoint, $method = 'GET', $data = [])
    {
        // Refresh access token if needed
        $this->refreshAccessToken();

        $url = "https://books.zoho.com/api/v3" . $endpoint;
        
        if (strpos($endpoint, '?') !== false) {
            $url .= '&organization_id=' . $this->organizationId;
        } else {
            $url .= '?organization_id=' . $this->organizationId;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Zoho-oauthtoken ' . $this->accessToken,
            'Content-Type: application/json'
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode >= 400 || (isset($result['code']) && $result['code'] != 0)) {
            $errorMsg = $result['message'] ?? 'Unknown error';
            throw new \Exception("Zoho API Error: " . $errorMsg);
        }

        return $result;
    }

    /**
     * Refresh Zoho access token
     */
    private function refreshAccessToken()
    {
        if (empty($this->refreshToken)) {
            throw new \Exception('Refresh token not configured');
        }

        $url = "https://accounts.zoho.com/oauth/v2/token";
        $params = [
            'refresh_token' => $this->refreshToken,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'refresh_token'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if (isset($data['access_token'])) {
            $this->accessToken = $data['access_token'];
            
            // Save to database
            $stmt = $this->db->prepare("
                INSERT INTO settings (setting_key, setting_value, updated_at)
                VALUES ('zoho_access_token', ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value),
                    updated_at = NOW()
            ");
            $stmt->execute([$this->accessToken]);
        } else {
            throw new \Exception('Failed to refresh access token');
        }
    }

    /**
     * Log sync activity
     */
    private function logSync($entityType, $entityId, $status, $externalId = null, $errorMessage = null)
    {
        $stmt = $this->db->prepare("
            INSERT INTO zoho_sync_log (entity_type, entity_id, status, external_id, error_message, synced_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$entityType, $entityId, $status, $externalId, $errorMessage]);
    }

    /**
     * Check if Zoho is configured
     */
    private function isConfigured()
    {
        return !empty($this->clientId) && !empty($this->refreshToken) && !empty($this->organizationId);
    }
}
