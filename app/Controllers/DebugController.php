<?php

namespace App\Controllers;

class DebugController
{
    public function __construct()
    {
        // no auth by default; protected by DEBUG_KEY
    }

    public function lastError()
    {
        $key = (string)($_GET['key'] ?? '');
        $expected = (string)(getenv('DEBUG_KEY') ?: '');

        if ($expected === '' || $key === '' || !hash_equals($expected, $key)) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            exit;
        }

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
}
