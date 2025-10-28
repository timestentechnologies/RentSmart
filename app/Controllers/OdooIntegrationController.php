<?php

namespace App\Controllers;

use App\Database\Connection;

class OdooIntegrationController
{
    private $db;
    private $url;
    private $database;
    private $username;
    private $password;
    private $uid;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
        
        // Get Odoo credentials from settings
        $stmt = $this->db->prepare("
            SELECT setting_key, setting_value 
            FROM settings 
            WHERE setting_key IN ('odoo_url', 'odoo_database', 'odoo_username', 'odoo_password', 'odoo_uid')
        ");
        $stmt->execute();
        $settings = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        
        $this->url = $settings['odoo_url'] ?? null;
        $this->database = $settings['odoo_database'] ?? null;
        $this->username = $settings['odoo_username'] ?? null;
        $this->password = $settings['odoo_password'] ?? null;
        $this->uid = $settings['odoo_uid'] ?? null;
    }

    /**
     * Show Odoo integration management page
     */
    public function manage()
    {
        try {
            requireAuth();

            $isConfigured = !empty($this->url) && !empty($this->database) && !empty($this->password);

            // Get sync status
            $stmt = $this->db->prepare("SELECT * FROM odoo_sync_log ORDER BY synced_at DESC LIMIT 10");
            $stmt->execute();
            $syncLog = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            require 'views/integrations/odoo.php';

        } catch (\Exception $e) {
            error_log("Odoo manage error: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading Odoo integration';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }

    /**
     * Sync payments to Odoo
     */
    public function syncPayments()
    {
        try {
            requireAuth();

            if (!$this->isConfigured()) {
                throw new \Exception('Odoo not configured');
            }

            // Authenticate if needed
            if (empty($this->uid)) {
                $this->authenticate();
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
                    FROM odoo_sync_log 
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
                    $partnerId = $this->getOrCreatePartner($payment);
                    
                    // Create invoice
                    $invoiceData = [
                        'partner_id' => $partnerId,
                        'invoice_date' => date('Y-m-d', strtotime($payment['payment_date'])),
                        'invoice_date_due' => date('Y-m-d', strtotime($payment['payment_date'])),
                        'move_type' => 'out_invoice',
                        'invoice_line_ids' => [
                            [0, 0, [
                                'name' => 'Rent - ' . $payment['property_name'] . ' Unit ' . $payment['unit_number'],
                                'quantity' => 1,
                                'price_unit' => $payment['amount']
                            ]]
                        ]
                    ];

                    $invoiceId = $this->callOdooAPI('account.move', 'create', [$invoiceData]);

                    if ($invoiceId) {
                        // Post the invoice
                        $this->callOdooAPI('account.move', 'action_post', [[$invoiceId]]);

                        // Register payment
                        $paymentData = [
                            'amount' => $payment['amount'],
                            'payment_date' => date('Y-m-d', strtotime($payment['payment_date'])),
                            'payment_type' => 'inbound',
                            'partner_type' => 'customer',
                            'partner_id' => $partnerId,
                            'journal_id' => 1, // Cash journal
                            'payment_method_id' => 1
                        ];

                        $paymentId = $this->callOdooAPI('account.payment', 'create', [$paymentData]);

                        // Log success
                        $this->logSync('payment', $payment['id'], 'success', $invoiceId);
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
                'message' => "Synced $synced payments to Odoo",
                'synced' => $synced,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            error_log("Odoo sync error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Sync expenses to Odoo
     */
    public function syncExpenses()
    {
        try {
            requireAuth();

            if (!$this->isConfigured()) {
                throw new \Exception('Odoo not configured');
            }

            if (empty($this->uid)) {
                $this->authenticate();
            }

            // Get expenses from last sync
            $stmt = $this->db->prepare("
                SELECT e.*, p.name as property_name
                FROM expenses e
                LEFT JOIN properties p ON e.property_id = p.id
                WHERE e.created_at > (
                    SELECT COALESCE(MAX(synced_at), '2000-01-01') 
                    FROM odoo_sync_log 
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
                        'name' => $expense['description'] . ' - ' . $expense['property_name'],
                        'date' => date('Y-m-d', strtotime($expense['expense_date'])),
                        'unit_amount' => $expense['amount'],
                        'quantity' => 1,
                        'employee_id' => $this->uid
                    ];

                    $expenseId = $this->callOdooAPI('hr.expense', 'create', [$expenseData]);

                    if ($expenseId) {
                        $this->logSync('expense', $expense['id'], 'success', $expenseId);
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
                'message' => "Synced $synced expenses to Odoo",
                'synced' => $synced,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            error_log("Odoo expense sync error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Get or create partner in Odoo
     */
    private function getOrCreatePartner($payment)
    {
        // Search for existing partner
        $partnerIds = $this->callOdooAPI('res.partner', 'search', [[['email', '=', $payment['tenant_email']]]]);

        if (!empty($partnerIds)) {
            return $partnerIds[0];
        }

        // Create new partner
        $partnerData = [
            'name' => $payment['tenant_name'],
            'email' => $payment['tenant_email'],
            'customer_rank' => 1
        ];

        return $this->callOdooAPI('res.partner', 'create', [$partnerData]);
    }

    /**
     * Authenticate with Odoo
     */
    private function authenticate()
    {
        $url = rtrim($this->url, '/') . '/xmlrpc/2/common';
        
        $client = new \SimpleXMLRPCClient($url);
        $uid = $client->call('authenticate', [
            $this->database,
            $this->username,
            $this->password,
            []
        ]);

        if ($uid) {
            $this->uid = $uid;
            
            // Save to database
            $stmt = $this->db->prepare("
                INSERT INTO settings (setting_key, setting_value, updated_at)
                VALUES ('odoo_uid', ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value),
                    updated_at = NOW()
            ");
            $stmt->execute([$uid]);
        } else {
            throw new \Exception('Odoo authentication failed');
        }
    }

    /**
     * Save Odoo configuration
     */
    public function saveConfig()
    {
        try {
            requireAuth();

            $url = $_POST['url'] ?? '';
            $database = $_POST['database'] ?? '';
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            if (empty($url) || empty($database) || empty($username) || empty($password)) {
                throw new \Exception('All fields are required');
            }

            // Save to database
            $settings = [
                'odoo_url' => $url,
                'odoo_database' => $database,
                'odoo_username' => $username,
                'odoo_password' => $password
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

            $_SESSION['flash_message'] = 'Odoo configured successfully';
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . BASE_URL . '/integrations/odoo');
            exit;

        } catch (\Exception $e) {
            error_log("Odoo config error: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/integrations/odoo');
            exit;
        }
    }

    /**
     * Call Odoo API using XML-RPC
     */
    private function callOdooAPI($model, $method, $params = [])
    {
        $url = rtrim($this->url, '/') . '/xmlrpc/2/object';
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: text/xml',
                'content' => xmlrpc_encode_request('execute_kw', [
                    $this->database,
                    $this->uid,
                    $this->password,
                    $model,
                    $method,
                    $params
                ])
            ]
        ]);

        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new \Exception('Failed to connect to Odoo');
        }

        $result = xmlrpc_decode($response);

        if (is_array($result) && xmlrpc_is_fault($result)) {
            throw new \Exception("Odoo API Error: " . $result['faultString']);
        }

        return $result;
    }

    /**
     * Log sync activity
     */
    private function logSync($entityType, $entityId, $status, $externalId = null, $errorMessage = null)
    {
        $stmt = $this->db->prepare("
            INSERT INTO odoo_sync_log (entity_type, entity_id, status, external_id, error_message, synced_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$entityType, $entityId, $status, $externalId, $errorMessage]);
    }

    /**
     * Check if Odoo is configured
     */
    private function isConfigured()
    {
        return !empty($this->url) && !empty($this->database) && !empty($this->password);
    }
}
