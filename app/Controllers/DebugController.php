<?php

namespace App\Controllers;

use App\Database\Connection;

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

        if (!function_exists('xmlrpc_encode_request') || !function_exists('xmlrpc_decode')) {
            header('Content-Type: application/json');
            http_response_code(200);
            echo json_encode([
                'success' => false,
                'message' => 'XML-RPC functions not available on this server (install/enable php-xmlrpc).',
            ]);
            exit;
        }

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
            $req = xmlrpc_encode_request('authenticate', [$database, $username, $password, []]);
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: text/xml\r\n",
                    'content' => $req,
                    'timeout' => 15,
                ]
            ]);

            $response = @file_get_contents($commonUrl, false, $ctx);
            if ($response === false) {
                $err = error_get_last();
                header('Content-Type: application/json');
                http_response_code(200);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to connect to Odoo common endpoint.',
                    'url' => $commonUrl,
                    'php_error' => $err,
                ]);
                exit;
            }

            $decoded = xmlrpc_decode($response);
            if (is_array($decoded) && xmlrpc_is_fault($decoded)) {
                header('Content-Type: application/json');
                http_response_code(200);
                echo json_encode([
                    'success' => false,
                    'message' => 'Odoo XML-RPC fault.',
                    'url' => $commonUrl,
                    'faultCode' => $decoded['faultCode'] ?? null,
                    'faultString' => $decoded['faultString'] ?? null,
                ]);
                exit;
            }

            $uid = (int)$decoded;
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
}
