<?php

namespace App\Controllers;

use App\Models\Payment;
use App\Models\RealtorClient;
use App\Models\RealtorListing;

class AdminDebugController
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Please login to continue';
            $_SESSION['flash_type'] = 'danger';
            redirect('/home');
        }

        // Allow non-admin access to realtor payments debug only when a valid key is provided.
        // This helps diagnose role-specific 500s without opening the endpoint publicly.
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
        $isRealtorPaymentsDebug = (strpos($uri, '/admin/debug/realtor-payments') !== false);
        $isRenderPaymentsDebug = (strpos($uri, '/admin/debug/render-payments') !== false);
        if ($isRealtorPaymentsDebug || $isRenderPaymentsDebug) {
            $key = $_GET['key'] ?? null;
            if ($key && isset($_SESSION['csrf_token']) && hash_equals((string)$_SESSION['csrf_token'], (string)$key)) {
                return;
            }
        }

        $role = strtolower((string)($_SESSION['user_role'] ?? ''));
        if (!in_array($role, ['admin', 'administrator'], true)) {
            $_SESSION['flash_message'] = 'Access denied';
            $_SESSION['flash_type'] = 'danger';
            redirect('/dashboard');
        }
    }

    public function tenantPaymentError()
    {
        $acceptHeader = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
        $wantsJson = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (strpos($acceptHeader, 'application/json') !== false);

        $debugFile = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'rentsmart_tenant_payment_last_error.json';
        $raw = is_file($debugFile) ? (string)@file_get_contents($debugFile) : '';
        $data = $raw !== '' ? (json_decode($raw, true) ?: null) : null;

        if ($wantsJson) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'file' => $debugFile,
                'data' => $data,
            ]);
            exit;
        }

        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html><head><meta charset="utf-8"><title>Tenant Payment Debug</title>';
        echo '<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;padding:16px;max-width:980px;margin:0 auto}pre{background:#111827;color:#e5e7eb;padding:12px;border-radius:8px;overflow:auto}</style>';
        echo '</head><body>';
        echo '<h2>Tenant Payment Debug</h2>';
        echo '<div><strong>File:</strong> ' . htmlspecialchars($debugFile) . '</div>';
        echo '<div style="margin-top:12px"><strong>Last captured error:</strong></div>';
        echo '<pre>' . htmlspecialchars($raw !== '' ? $raw : 'No debug error captured yet. Trigger a tenant payment failure first.') . '</pre>';
        echo '</body></html>';
        exit;
    }

    public function realtorPayments()
    {
        $acceptHeader = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
        $wantsJson = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (strpos($acceptHeader, 'application/json') !== false)
            || (isset($_GET['format']) && strtolower((string)$_GET['format']) === 'json');

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $role = strtolower((string)($_SESSION['user_role'] ?? ''));

        $payload = [
            'success' => false,
            'user_id' => $userId,
            'user_role' => $role,
            'checks' => [],
            'counts' => [],
            'errors' => [],
        ];

        try {
            $paymentModel = new Payment();
            $db = $paymentModel->getDb();

            try {
                $stmt = $db->query("SELECT DATABASE() AS db");
                $payload['checks']['database'] = $stmt ? ($stmt->fetch(\PDO::FETCH_ASSOC)['db'] ?? null) : null;
            } catch (\Throwable $e) {
                $payload['checks']['database'] = null;
            }

            try {
                $stmt = $db->query("SHOW TABLES LIKE 'payments'");
                $payload['checks']['payments_table_exists'] = (bool)($stmt && $stmt->fetch(\PDO::FETCH_ASSOC));
            } catch (\Throwable $e) {
                $payload['checks']['payments_table_exists'] = null;
            }

            try {
                $stmt = $db->query("SHOW COLUMNS FROM payments LIKE 'realtor_user_id'");
                $payload['checks']['payments_has_realtor_user_id'] = (bool)($stmt && $stmt->fetch(\PDO::FETCH_ASSOC));
            } catch (\Throwable $e) {
                $payload['checks']['payments_has_realtor_user_id'] = null;
            }
            try {
                $stmt = $db->query("SHOW COLUMNS FROM payments LIKE 'realtor_client_id'");
                $payload['checks']['payments_has_realtor_client_id'] = (bool)($stmt && $stmt->fetch(\PDO::FETCH_ASSOC));
            } catch (\Throwable $e) {
                $payload['checks']['payments_has_realtor_client_id'] = null;
            }
            try {
                $stmt = $db->query("SHOW COLUMNS FROM payments LIKE 'realtor_listing_id'");
                $payload['checks']['payments_has_realtor_listing_id'] = (bool)($stmt && $stmt->fetch(\PDO::FETCH_ASSOC));
            } catch (\Throwable $e) {
                $payload['checks']['payments_has_realtor_listing_id'] = null;
            }

            try {
                $stmt = $db->query("SHOW TABLES LIKE 'realtor_clients'");
                $payload['checks']['realtor_clients_table_exists'] = (bool)($stmt && $stmt->fetch(\PDO::FETCH_ASSOC));
            } catch (\Throwable $e) {
                $payload['checks']['realtor_clients_table_exists'] = null;
            }
            try {
                $stmt = $db->query("SHOW TABLES LIKE 'realtor_listings'");
                $payload['checks']['realtor_listings_table_exists'] = (bool)($stmt && $stmt->fetch(\PDO::FETCH_ASSOC));
            } catch (\Throwable $e) {
                $payload['checks']['realtor_listings_table_exists'] = null;
            }

            $clients = [];
            $listings = [];
            try {
                $clientModel = new RealtorClient();
                $clients = $clientModel->getAll($userId);
            } catch (\Throwable $e) {
                $payload['errors'][] = ['where' => 'RealtorClient', 'message' => $e->getMessage()];
            }
            try {
                $listingModel = new RealtorListing();
                $listings = $listingModel->getAll($userId);
            } catch (\Throwable $e) {
                $payload['errors'][] = ['where' => 'RealtorListing', 'message' => $e->getMessage()];
            }

            $payments = [];
            try {
                $payments = $paymentModel->getPaymentsForRealtor($userId);
            } catch (\Throwable $e) {
                $payload['errors'][] = ['where' => 'Payment.getPaymentsForRealtor', 'message' => $e->getMessage()];
            }

            $payload['counts'] = [
                'clients' => is_array($clients) ? count($clients) : null,
                'listings' => is_array($listings) ? count($listings) : null,
                'payments' => is_array($payments) ? count($payments) : null,
            ];

            $payload['success'] = empty($payload['errors']);
        } catch (\Throwable $e) {
            $payload['errors'][] = ['where' => 'bootstrap', 'message' => $e->getMessage()];
        }

        if ($wantsJson) {
            header('Content-Type: application/json');
            echo json_encode($payload);
            exit;
        }

        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html><head><meta charset="utf-8"><title>Realtor Payments Debug</title>';
        echo '<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;padding:16px;max-width:980px;margin:0 auto}pre{background:#111827;color:#e5e7eb;padding:12px;border-radius:8px;overflow:auto}</style>';
        echo '</head><body>';
        echo '<h2>Realtor Payments Debug</h2>';
        echo '<div><strong>User ID:</strong> ' . (int)$userId . '</div>';
        echo '<div><strong>Role:</strong> ' . htmlspecialchars($role) . '</div>';
        echo '<div style="margin-top:12px"><a href="?format=json" target="_blank">Open JSON</a></div>';
        echo '<div style="margin-top:12px"><strong>Result:</strong></div>';
        echo '<pre>' . htmlspecialchars(json_encode($payload, JSON_PRETTY_PRINT)) . '</pre>';
        echo '</body></html>';
        exit;
    }

    public function renderPayments()
    {
        $acceptHeader = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
        $wantsJson = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (strpos($acceptHeader, 'application/json') !== false)
            || (isset($_GET['format']) && strtolower((string)$_GET['format']) === 'json');

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $role = strtolower((string)($_SESSION['user_role'] ?? ''));

        $payload = [
            'success' => false,
            'user_id' => $userId,
            'user_role' => $role,
            'step' => 'init',
            'exception' => null,
            'last_error' => null,
        ];

        $prevHandler = null;
        try {
            // Convert warnings/notices into exceptions so we can see what is crashing production.
            $prevHandler = set_error_handler(function ($severity, $message, $file, $line) {
                if (!(error_reporting() & $severity)) {
                    return false;
                }
                throw new \ErrorException($message, 0, $severity, $file, $line);
            });

            $payload['step'] = 'load_models';
            $paymentModel = new Payment();

            $payments = [];
            $tenants = [];
            $clients = [];
            $listings = [];
            $pendingPaymentsCount = 0;

            if ($role === 'realtor') {
                $payload['step'] = 'query_realtor';
                $clientModel = new RealtorClient();
                $listingModel = new RealtorListing();
                $payments = $paymentModel->getPaymentsForRealtor($userId);
                $clients = $clientModel->getAll($userId);
                $listings = $listingModel->getAll($userId);
            } else {
                $payload['step'] = 'query_tenant_payments';
                $payments = $paymentModel->getPaymentsWithTenantInfo($userId);
            }

            $payload['step'] = 'render_view';
            ob_start();
            require 'views/payments/index.php';
            $html = ob_get_clean();

            $payload['success'] = true;
            $payload['step'] = 'done';

            if ($wantsJson) {
                header('Content-Type: application/json');
                echo json_encode($payload);
                exit;
            }

            header('Content-Type: text/html; charset=utf-8');
            echo $html;
            exit;
        } catch (\Throwable $e) {
            $payload['exception'] = [
                'class' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ];
            $payload['last_error'] = error_get_last();

            if ($wantsJson) {
                header('Content-Type: application/json');
                echo json_encode($payload);
                exit;
            }

            header('Content-Type: text/html; charset=utf-8');
            echo '<!doctype html><html><head><meta charset="utf-8"><title>Render Payments Debug</title>';
            echo '<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;padding:16px;max-width:980px;margin:0 auto}pre{background:#111827;color:#e5e7eb;padding:12px;border-radius:8px;overflow:auto}</style>';
            echo '</head><body>';
            echo '<h2>Render Payments Debug</h2>';
            echo '<div><strong>User ID:</strong> ' . (int)$userId . '</div>';
            echo '<div><strong>Role:</strong> ' . htmlspecialchars($role) . '</div>';
            echo '<div style="margin-top:12px"><a href="?format=json" target="_blank">Open JSON</a></div>';
            echo '<div style="margin-top:12px"><strong>Payload:</strong></div>';
            echo '<pre>' . htmlspecialchars(json_encode($payload, JSON_PRETTY_PRINT)) . '</pre>';
            echo '</body></html>';
            exit;
        } finally {
            if ($prevHandler) {
                set_error_handler($prevHandler);
            } else {
                restore_error_handler();
            }
        }
    }
}
