<?php

// Emergency bootstrap diagnostic (bypasses routing).
// Purpose: identify where the production 500 occurs when index.php might not even compile.

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

$stepFile = __DIR__ . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'bootstrap_diag_step.txt';
$diagFile = __DIR__ . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'bootstrap_diag_last.json';

function set_step($s) {
    global $stepFile;
    @file_put_contents($stepFile, (string)$s);
}

register_shutdown_function(function () {
    global $diagFile, $stepFile;
    $err = error_get_last();
    $step = is_file($stepFile) ? trim((string)@file_get_contents($stepFile)) : '';

    if (is_array($err) && !empty($err)) {
        $payload = [
            'success' => false,
            'kind' => 'fatal',
            'step' => $step,
            'error' => $err,
        ];
        @file_put_contents($diagFile, json_encode($payload));
        echo json_encode($payload);
        return;
    }

    // If no fatal, still return last known step.
    $payload = [
        'success' => true,
        'kind' => 'ok',
        'step' => $step,
        'message' => 'No fatal error captured in shutdown handler.',
    ];
    @file_put_contents($diagFile, json_encode($payload));
    echo json_encode($payload);
});

try {
    $expected = readEnvValue('DEBUG_TOKEN', '');
    $token = (string)($_GET['token'] ?? '');

    if ($expected === '' || !hash_equals($expected, $token)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Forbidden',
        ]);
        exit;
    }

    @ini_set('display_errors', '0');
    @ini_set('log_errors', '1');
    @error_reporting(E_ALL);

    set_step('start');

    set_step('require vendor/autoload.php');
    require_once __DIR__ . '/vendor/autoload.php';

    set_step('require config/database.php');
    require_once __DIR__ . '/config/database.php';

    set_step('require app/helpers.php');
    require_once __DIR__ . '/app/helpers.php';

    set_step('require HomeController.php');
    require_once __DIR__ . '/app/Controllers/HomeController.php';

    set_step('done');

    // If we reached here, shutdown handler will output success=true.
    exit;
} catch (\Throwable $e) {
    // Non-fatal exceptions
    $payload = [
        'success' => false,
        'kind' => 'exception',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ];
    @file_put_contents($diagFile, json_encode($payload));
    http_response_code(500);
    echo json_encode($payload);
    exit;
}
