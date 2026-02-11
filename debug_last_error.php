<?php

// Standalone emergency debug endpoint (bypasses index.php routing)
// Reads last captured error payload from sys temp dir.

header('Content-Type: application/json');

function readEnvValue($key, $default = '') {
    $envFile = __DIR__ . DIRECTORY_SEPARATOR . '.env';
    if (!is_file($envFile)) {
        return $default;
    }

    $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return $default;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || (isset($line[0]) && $line[0] === '#')) {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $k = trim(substr($line, 0, $pos));
        if ($k !== $key) {
            continue;
        }
        $v = trim(substr($line, $pos + 1));
        if (strlen($v) >= 2 && (($v[0] === '"' && $v[strlen($v) - 1] === '"') || ($v[0] === '\'' && $v[strlen($v) - 1] === '\''))) {
            $v = substr($v, 1, -1);
        }
        return $v;
    }

    return $default;
}

try {
    $expected = readEnvValue('DEBUG_TOKEN', '');
    $token = (string)($_GET['token'] ?? '');

    if ($expected === '' || !hash_equals($expected, $token)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Forbidden',
            'hint' => 'Set DEBUG_TOKEN in .env and call ?token=...',
        ]);
        exit;
    }

    $debugFile = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'rentsmart_last_error.json';
    $raw = is_file($debugFile) ? (string)@file_get_contents($debugFile) : '';

    echo json_encode([
        'success' => true,
        'file' => $debugFile,
        'exists' => is_file($debugFile),
        'data' => ($raw !== '' ? (json_decode($raw, true) ?: null) : null),
        'raw' => $raw,
    ]);
    exit;
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);
    exit;
}
