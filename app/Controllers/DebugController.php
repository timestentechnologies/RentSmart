<?php

namespace App\Controllers;

use App\Database\Connection;
use App\Services\XmlRpcClient;

class DebugController
{
    public function __construct()
    {
        // no auth by default; protected by DEBUG_KEY
    }

    private function requireDebugAccess(): void
    {
        $role = strtolower((string)($_SESSION['user_role'] ?? ''));
        if (!empty($_SESSION['user_id']) && in_array($role, ['admin', 'administrator'], true)) {
            return;
        }

        $key = (string)($_GET['key'] ?? '');
        $expected = (string)(getenv('DEBUG_KEY') ?: '');

        if ($expected === '' || $key === '' || !hash_equals($expected, $key)) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            exit;
        }
    }

    public function lastError()
    {
        $this->requireDebugAccess();

        $debugFile = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'rentsmart_last_error.json';
        $raw = @file_get_contents($debugFile);
        $payload = null;
        if ($raw !== false) {
            $payload = json_decode($raw, true);
        }

        header('Content-Type: application/json');
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'file' => $debugFile,
            'last_error' => $payload,
        ]);
        exit;
    }

    public function odooTest()
    {
        $this->requireDebugAccess();

        try {
            $db = Connection::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('odoo_url','odoo_database','odoo_username','odoo_password')");
            $stmt->execute();
            $settings = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

            $url = rtrim((string)($settings['odoo_url'] ?? ''), '/');
            $database = (string)($settings['odoo_database'] ?? '');
            $username = (string)($settings['odoo_username'] ?? '');
            $password = (string)($settings['odoo_password'] ?? '');

            if ($url === '' || $database === '' || $username === '' || $password === '') {
                header('Content-Type: application/json');
                http_response_code(200);
                echo json_encode([
                    'success' => false,
                    'message' => 'Missing Odoo settings (odoo_url, odoo_database, odoo_username, odoo_password).',
                    'present' => [
                        'odoo_url' => $url !== '',
                        'odoo_database' => $database !== '',
                        'odoo_username' => $username !== '',
                        'odoo_password' => $password !== '',
                    ],
                ]);
                exit;
            }

            $commonUrl = $url . '/xmlrpc/2/common';

            $client = new XmlRpcClient($commonUrl, 15);
            $decoded = $client->call('authenticate', [$database, $username, $password, []]);
            $uid = (int)($decoded ?? 0);
            header('Content-Type: application/json');
            http_response_code(200);
            echo json_encode([
                'success' => $uid > 0,
                'message' => $uid > 0 ? 'Odoo authentication successful.' : 'Odoo authentication failed (uid=0).',
                'uid' => $uid,
                'url' => $commonUrl,
                'database' => $database,
                'username' => $username,
            ]);
            exit;
        } catch (\Throwable $e) {
            header('Content-Type: application/json');
            http_response_code(200);
            echo json_encode([
                'success' => false,
                'message' => 'Odoo test failed: ' . $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            exit;
        }
    }

    public function odooCreateLead()
    {
        $this->requireDebugAccess();

        try {
            $db = Connection::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('odoo_url','odoo_database','odoo_username','odoo_password')");
            $stmt->execute();
            $settings = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

            $url = rtrim((string)($settings['odoo_url'] ?? ''), '/');
            $database = (string)($settings['odoo_database'] ?? '');
            $username = (string)($settings['odoo_username'] ?? '');
            $password = (string)($settings['odoo_password'] ?? '');

            if ($url === '' || $database === '' || $username === '' || $password === '') {
                header('Content-Type: application/json');
                http_response_code(200);
                echo json_encode([
                    'success' => false,
                    'message' => 'Missing Odoo settings (odoo_url, odoo_database, odoo_username, odoo_password).',
                ]);
                exit;
            }

            $commonUrl = $url . '/xmlrpc/2/common';
            $common = new XmlRpcClient($commonUrl, 15);
            $uid = (int)($common->call('authenticate', [$database, $username, $password, []]) ?? 0);
            if ($uid <= 0) {
                header('Content-Type: application/json');
                http_response_code(200);
                echo json_encode([
                    'success' => false,
                    'message' => 'Odoo authentication failed (uid=0).',
                    'url' => $commonUrl,
                ]);
                exit;
            }

            $title = (string)($_GET['title'] ?? 'RentSmart Debug Lead');
            $email = (string)($_GET['email'] ?? '');
            $phone = (string)($_GET['phone'] ?? '');
            $contact = (string)($_GET['contact'] ?? '');
            $type = strtolower((string)($_GET['type'] ?? 'opportunity'));
            if (!in_array($type, ['lead', 'opportunity'], true)) {
                $type = 'opportunity';
            }

            $amount = 0.0;
            if (isset($_GET['amount']) && is_numeric($_GET['amount'])) {
                $amount = (float)$_GET['amount'];
            } else {
                try {
                    $planStmt = $db->prepare('SELECT price FROM subscription_plans WHERE id = ? LIMIT 1');
                    $planStmt->execute([1]);
                    $planRow = $planStmt->fetch(\PDO::FETCH_ASSOC);
                    $amount = isset($planRow['price']) ? (float)$planRow['price'] : 0.0;
                } catch (\Throwable $ignore) {
                    $amount = 0.0;
                }
            }

            $leadData = [
                'name' => $title,
                'type' => $type,
                'contact_name' => $contact,
                'email_from' => $email,
                'phone' => $phone,
                'description' => 'Source: RentSmart debug/odoo-create-lead',
            ];

            if ($amount > 0) {
                $leadData['expected_revenue'] = (float)$amount;
            }

            $objectUrl = $url . '/xmlrpc/2/object';
            $obj = new XmlRpcClient($objectUrl, 15);

            $tagId = null;
            try {
                $search = $obj->call('execute_kw', [
                    $database,
                    $uid,
                    $password,
                    'crm.tag',
                    'search_read',
                    [[['name', '=', 'Rentsmart']]],
                    ['fields' => ['id'], 'limit' => 1]
                ]);
                if (is_array($search) && !empty($search[0]['id'])) {
                    $tagId = (int)$search[0]['id'];
                } else {
                    $tagId = (int)($obj->call('execute_kw', [
                        $database,
                        $uid,
                        $password,
                        'crm.tag',
                        'create',
                        [['name' => 'Rentsmart']],
                        []
                    ]) ?? 0);
                }
            } catch (\Throwable $ignore) {
                $tagId = null;
            }

            if (!empty($tagId) && (int)$tagId > 0) {
                $leadData['tag_ids'] = [[6, 0, [(int)$tagId]]];
            }

            $createdId = $obj->call('execute_kw', [$database, $uid, $password, 'crm.lead', 'create', [$leadData], []]);

            header('Content-Type: application/json');
            http_response_code(200);
            echo json_encode([
                'success' => !empty($createdId),
                'message' => !empty($createdId) ? 'Lead created' : 'Lead not created (empty response)',
                'lead_id' => $createdId,
                'uid' => $uid,
                'object_url' => $objectUrl,
                'payload' => $leadData,
            ]);
            exit;
        } catch (\Throwable $e) {
            header('Content-Type: application/json');
            http_response_code(200);
            echo json_encode([
                'success' => false,
                'message' => 'Odoo lead create failed: ' . $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            exit;
        }
    }
}
