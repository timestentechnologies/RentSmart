<?php

namespace App\Controllers;

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
}
