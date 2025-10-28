<?php

namespace App\Controllers;

use App\Database\Connection;

class QuickBooksIntegrationController
{
    private $db;
    private $clientId;
    private $clientSecret;
    private $refreshToken;
    private $accessToken;
    private $realmId;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
        
        // Get QuickBooks credentials from settings
        $stmt = $this->db->prepare("
            SELECT setting_key, setting_value 
            FROM settings 
            WHERE setting_key IN ('qb_client_id', 'qb_client_secret', 'qb_refresh_token', 'qb_access_token', 'qb_realm_id')
        ");
        $stmt->execute();
        $settings = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        
        $this->clientId = $settings['qb_client_id'] ?? null;
        $this->clientSecret = $settings['qb_client_secret'] ?? null;
        $this->refreshToken = $settings['qb_refresh_token'] ?? null;
        $this->accessToken = $settings['qb_access_token'] ?? null;
        $this->realmId = $settings['qb_realm_id'] ?? null;
    }

    /**
     * Show QuickBooks integration management page
     */
    public function manage()
    {
        try {
            requireAuth();

            $isConfigured = !empty($this->clientId) && !empty($this->refreshToken) && !empty($this->realmId);

            // Get sync status
            $stmt = $this->db->prepare("SELECT * FROM quickbooks_sync_log ORDER BY synced_at DESC LIMIT 10");
            $stmt->execute();
            $syncLog = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            require 'views/integrations/quickbooks.php';

        } catch (\Exception $e) {
            error_log("QuickBooks manage error: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading QuickBooks integration';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }

    /**
     * Sync payments to QuickBooks
     */
    public function syncPayments()
    {
        try {
            requireAuth();

            if (!$this->isConfigured()) {
                throw new \Exception('QuickBooks not configured');
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
                    FROM quickbooks_sync_log 
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
                        'CustomerRef' => ['value' => $customerId],
                        'TxnDate' => date('Y-m-d', strtotime($payment['payment_date'])),
                        'DueDate' => date('Y-m-d', strtotime($payment['payment_date'])),
                        'Line' => [
                            [
                                'Amount' => $payment['amount'],
                                'DetailType' => 'SalesItemLineDetail',
                                'SalesItemLineDetail' => [
                                    'ItemRef' => ['value' => $this->getRentIncomeItemId()],
                                    'Qty' => 1,
                                    'UnitPrice' => $payment['amount']
                                ],
                                'Description' => 'Rent - ' . $payment['property_name'] . ' Unit ' . $payment['unit_number']
                            ]
                        ]
                    ];

                    $invoice = $this->callQuickBooksAPI('/invoice', 'POST', $invoiceData);

                    if (isset($invoice['Invoice']['Id'])) {
                        // Record payment
                        $paymentData = [
                            'CustomerRef' => ['value' => $customerId],
                            'TotalAmt' => $payment['amount'],
                            'TxnDate' => date('Y-m-d', strtotime($payment['payment_date'])),
                            'Line' => [
                                [
                                    'Amount' => $payment['amount'],
                                    'LinkedTxn' => [
                                        [
                                            'TxnId' => $invoice['Invoice']['Id'],
                                            'TxnType' => 'Invoice'
                                        ]
                                    ]
                                ]
                            ]
                        ];

                        $this->callQuickBooksAPI('/payment', 'POST', $paymentData);

                        // Log success
                        $this->logSync('payment', $payment['id'], 'success', $invoice['Invoice']['Id']);
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
                'message' => "Synced $synced payments to QuickBooks",
                'synced' => $synced,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            error_log("QuickBooks sync error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Sync expenses to QuickBooks
     */
    public function syncExpenses()
    {
        try {
            requireAuth();

            if (!$this->isConfigured()) {
                throw new \Exception('QuickBooks not configured');
            }

            // Get expenses from last sync
            $stmt = $this->db->prepare("
                SELECT e.*, p.name as property_name
                FROM expenses e
                LEFT JOIN properties p ON e.property_id = p.id
                WHERE e.created_at > (
                    SELECT COALESCE(MAX(synced_at), '2000-01-01') 
                    FROM quickbooks_sync_log 
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
                        'AccountRef' => ['value' => $this->getExpenseAccountId()],
                        'TxnDate' => date('Y-m-d', strtotime($expense['expense_date'])),
                        'TotalAmt' => $expense['amount'],
                        'Line' => [
                            [
                                'Amount' => $expense['amount'],
                                'DetailType' => 'AccountBasedExpenseLineDetail',
                                'AccountBasedExpenseLineDetail' => [
                                    'AccountRef' => ['value' => $this->getExpenseAccountId()]
                                ],
                                'Description' => $expense['description'] . ' - ' . $expense['property_name']
                            ]
                        ]
                    ];

                    $result = $this->callQuickBooksAPI('/purchase', 'POST', $expenseData);

                    if (isset($result['Purchase']['Id'])) {
                        $this->logSync('expense', $expense['id'], 'success', $result['Purchase']['Id']);
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
                'message' => "Synced $synced expenses to QuickBooks",
                'synced' => $synced,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            error_log("QuickBooks expense sync error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Get or create customer in QuickBooks
     */
    private function getOrCreateCustomer($payment)
    {
        // Search for existing customer
        $query = "SELECT * FROM Customer WHERE PrimaryEmailAddr = '" . $payment['tenant_email'] . "'";
        $customers = $this->callQuickBooksAPI('/query?query=' . urlencode($query), 'GET');

        if (!empty($customers['QueryResponse']['Customer'])) {
            return $customers['QueryResponse']['Customer'][0]['Id'];
        }

        // Create new customer
        $customerData = [
            'DisplayName' => $payment['tenant_name'],
            'PrimaryEmailAddr' => ['Address' => $payment['tenant_email']]
        ];

        $result = $this->callQuickBooksAPI('/customer', 'POST', $customerData);

        return $result['Customer']['Id'];
    }

    /**
     * Get Rent Income Item ID (create if not exists)
     */
    private function getRentIncomeItemId()
    {
        // Check if stored in settings
        $stmt = $this->db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'qb_rent_item_id'");
        $stmt->execute();
        $itemId = $stmt->fetchColumn();

        if ($itemId) {
            return $itemId;
        }

        // Create rent income item
        $itemData = [
            'Name' => 'Rental Income',
            'Type' => 'Service',
            'IncomeAccountRef' => ['value' => $this->getIncomeAccountId()]
        ];

        $result = $this->callQuickBooksAPI('/item', 'POST', $itemData);
        $itemId = $result['Item']['Id'];

        // Save to settings
        $stmt = $this->db->prepare("
            INSERT INTO settings (setting_key, setting_value, updated_at)
            VALUES ('qb_rent_item_id', ?, NOW())
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        $stmt->execute([$itemId]);

        return $itemId;
    }

    /**
     * Get Income Account ID
     */
    private function getIncomeAccountId()
    {
        $query = "SELECT * FROM Account WHERE AccountType = 'Income' MAXRESULTS 1";
        $accounts = $this->callQuickBooksAPI('/query?query=' . urlencode($query), 'GET');
        
        if (!empty($accounts['QueryResponse']['Account'])) {
            return $accounts['QueryResponse']['Account'][0]['Id'];
        }

        return '1'; // Default income account
    }

    /**
     * Get Expense Account ID
     */
    private function getExpenseAccountId()
    {
        $query = "SELECT * FROM Account WHERE AccountType = 'Expense' MAXRESULTS 1";
        $accounts = $this->callQuickBooksAPI('/query?query=' . urlencode($query), 'GET');
        
        if (!empty($accounts['QueryResponse']['Account'])) {
            return $accounts['QueryResponse']['Account'][0]['Id'];
        }

        return '1'; // Default expense account
    }

    /**
     * Save QuickBooks configuration
     */
    public function saveConfig()
    {
        try {
            requireAuth();

            $clientId = $_POST['client_id'] ?? '';
            $clientSecret = $_POST['client_secret'] ?? '';
            $refreshToken = $_POST['refresh_token'] ?? '';
            $realmId = $_POST['realm_id'] ?? '';

            if (empty($clientId) || empty($clientSecret) || empty($refreshToken) || empty($realmId)) {
                throw new \Exception('All fields are required');
            }

            // Save to database
            $settings = [
                'qb_client_id' => $clientId,
                'qb_client_secret' => $clientSecret,
                'qb_refresh_token' => $refreshToken,
                'qb_realm_id' => $realmId
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

            $_SESSION['flash_message'] = 'QuickBooks configured successfully';
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . BASE_URL . '/integrations/quickbooks');
            exit;

        } catch (\Exception $e) {
            error_log("QuickBooks config error: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/integrations/quickbooks');
            exit;
        }
    }

    /**
     * Call QuickBooks API
     */
    private function callQuickBooksAPI($endpoint, $method = 'GET', $data = [])
    {
        // Refresh access token if needed
        $this->refreshAccessToken();

        $baseUrl = "https://quickbooks.api.intuit.com/v3/company/{$this->realmId}";
        $url = $baseUrl . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Accept: application/json',
            'Content-Type: application/json'
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMsg = $result['Fault']['Error'][0]['Message'] ?? 'Unknown error';
            throw new \Exception("QuickBooks API Error: " . $errorMsg);
        }

        return $result;
    }

    /**
     * Refresh QuickBooks access token
     */
    private function refreshAccessToken()
    {
        if (empty($this->refreshToken)) {
            throw new \Exception('Refresh token not configured');
        }

        $url = "https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer";
        
        $auth = base64_encode($this->clientId . ':' . $this->clientSecret);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refreshToken
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if (isset($data['access_token'])) {
            $this->accessToken = $data['access_token'];
            
            // Save to database
            $stmt = $this->db->prepare("
                INSERT INTO settings (setting_key, setting_value, updated_at)
                VALUES ('qb_access_token', ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value),
                    updated_at = NOW()
            ");
            $stmt->execute([$this->accessToken]);
            
            // Update refresh token if provided
            if (isset($data['refresh_token'])) {
                $this->refreshToken = $data['refresh_token'];
                $stmt = $this->db->prepare("
                    UPDATE settings SET setting_value = ?, updated_at = NOW()
                    WHERE setting_key = 'qb_refresh_token'
                ");
                $stmt->execute([$this->refreshToken]);
            }
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
            INSERT INTO quickbooks_sync_log (entity_type, entity_id, status, external_id, error_message, synced_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$entityType, $entityId, $status, $externalId, $errorMessage]);
    }

    /**
     * Check if QuickBooks is configured
     */
    private function isConfigured()
    {
        return !empty($this->clientId) && !empty($this->refreshToken) && !empty($this->realmId);
    }
}
