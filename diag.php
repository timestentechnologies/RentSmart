<?php
// Lightweight standalone diagnostic endpoint.
// This file is intentionally NOT loading index.php so it can work even when the app is down.

$key = (string)($_GET['key'] ?? '');
$expectedKey = (string)(getenv('DEBUG_KEY') ?: '');
$fallbackKey = 'd64d26855a79ea22b96cc7ef5ec97eccf6b6ed2fe1ce1ccc3ea95a053b1e7d5'; // optional: set only if DEBUG_KEY is not available on the server

if ($expectedKey === '') {
    $expectedKey = $fallbackKey;
}

function run_probe(string $root): array {
    $steps = [];
    $safe = function(string $name, callable $fn) use (&$steps) {
        try {
            $res = $fn();
            $steps[] = ['step' => $name, 'ok' => true, 'result' => $res];
        } catch (\Throwable $e) {
            $steps[] = [
                'step' => $name,
                'ok' => false,
                'error' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile() . ':' . $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ],
            ];
        }
    };

    $safe('require vendor/autoload.php', function() use ($root) {
        require_once $root . '/vendor/autoload.php';
        return 'loaded';
    });

    $safe('require config/database.php', function() use ($root) {
        require_once $root . '/config/database.php';
        return 'loaded';
    });

    $safe('require app/helpers.php', function() use ($root) {
        require_once $root . '/app/helpers.php';
        return 'loaded';
    });

    $safe('session_start', function() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        return 'status=' . session_status();
    });

    $safe('dotenv load .env', function() use ($root) {
        if (!class_exists('Dotenv\\Dotenv')) {
            throw new \RuntimeException('Dotenv class not found');
        }
        if (!file_exists($root . '/.env')) {
            throw new \RuntimeException('.env not found');
        }
        $dotenv = \Dotenv\Dotenv::createImmutable($root . '/');
        $dotenv->load();
        return ['APP_ENV' => getenv('APP_ENV') ?: null];
    });

    $safe('db connect (Connection::getInstance)', function() {
        if (!class_exists('App\\Database\\Connection')) {
            throw new \RuntimeException('App\\Database\\Connection class not found');
        }
        $db = \App\Database\Connection::getInstance()->getConnection();
        $stmt = $db->query('SELECT 1');
        $row = $stmt ? $stmt->fetch() : null;
        return $row;
    });

    return $steps;
}

if ($expectedKey === '' || $key === '' || !hash_equals($expectedKey, $key)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden";
    exit;
}

function tail_file(string $path, int $maxBytes = 65536): string {
    if (!is_file($path) || !is_readable($path)) return "(missing or not readable)";
    $size = filesize($path);
    if ($size === false) return "(unable to stat file)";
    $fh = fopen($path, 'rb');
    if (!$fh) return "(unable to open file)";
    $seek = max(0, $size - $maxBytes);
    fseek($fh, $seek);
    $data = stream_get_contents($fh);
    fclose($fh);
    return $data === false ? "(unable to read file)" : $data;
}

function extract_recent_errors(string $logChunk, int $maxLines = 80): array {
    $lines = preg_split('/\r\n|\r|\n/', $logChunk);
    if (!is_array($lines)) return [];
    $matched = [];
    $patterns = [
        '/fatal error/i',
        '/uncaught/i',
        '/parse error/i',
        '/exception/i',
        '/allowed memory size/i',
    ];
    foreach ($lines as $line) {
        $line = (string)$line;
        foreach ($patterns as $p) {
            if (preg_match($p, $line)) {
                $matched[] = $line;
                break;
            }
        }
    }
    if (count($matched) <= $maxLines) return $matched;
    return array_slice($matched, -$maxLines);
}

$root = __DIR__;
$checks = [
    'time_utc' => gmdate('c'),
    'php_version' => PHP_VERSION,
    'sapi' => PHP_SAPI,
    'cwd' => getcwd(),
    'document_root' => (string)($_SERVER['DOCUMENT_ROOT'] ?? ''),
    'script_filename' => (string)($_SERVER['SCRIPT_FILENAME'] ?? ''),
    'index_exists' => is_file($root . '/index.php'),
    'vendor_autoload_exists' => is_file($root . '/vendor/autoload.php'),
    'env_exists' => is_file($root . '/.env'),
    'logs_dir_exists' => is_dir($root . '/logs'),
    'php_errors_log_exists' => is_file($root . '/logs/php_errors.log'),
];

header('Content-Type: application/json; charset=utf-8');

$phpLogTail = tail_file($root . '/logs/php_errors.log', 400000);
$tmpErrorFile = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'rentsmart_last_error.json';
$tmpErrorRaw = @file_get_contents($tmpErrorFile);
$tmpErrorPayload = null;
if ($tmpErrorRaw !== false) {
    $tmpErrorPayload = json_decode($tmpErrorRaw, true);
}

$probe = null;
if (!empty($_GET['probe'])) {
    $probe = run_probe($root);
}

echo json_encode([
    'success' => true,
    'checks' => $checks,
    'php_errors_log_path' => $root . '/logs/php_errors.log',
    'last_php_errors_log_tail' => $phpLogTail,
    'recent_error_lines' => extract_recent_errors($phpLogTail, 120),
    'tmp_last_error_file' => $tmpErrorFile,
    'tmp_last_error_payload' => $tmpErrorPayload,
    'probe' => $probe,
], JSON_PRETTY_PRINT);
