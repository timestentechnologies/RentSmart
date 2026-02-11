<?php

namespace App\Controllers;

class DebugController
{
    public function lastError()
    {
        $isAdmin = false;
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $role = strtolower((string)($_SESSION['user_role'] ?? ''));
            $isAdmin = !empty($_SESSION['user_id']) && in_array($role, ['admin', 'administrator'], true);
        } catch (\Throwable $ignore) {
            $isAdmin = false;
        }

        // If not an admin session, fall back to token-based access.
        if (!$isAdmin) {
            $token = (string)($_GET['token'] ?? '');
            $expected = (string)(getenv('DEBUG_TOKEN') ?: '');

            if ($expected === '' || !hash_equals($expected, $token)) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Forbidden',
                ]);
                exit;
            }
        }

        $debugFile = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'rentsmart_last_error.json';
        $raw = is_file($debugFile) ? (string)@file_get_contents($debugFile) : '';
        $data = $raw !== '' ? (json_decode($raw, true) ?: null) : null;

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'file' => $debugFile,
            'data' => $data,
            'raw' => $raw,
        ]);
        exit;
    }
}
