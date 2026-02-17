<?php
// Lightweight standalone diagnostic endpoint.
// This file is intentionally NOT loading index.php so it can work even when the app is down.

$expectedKey = ''; // TODO: set a strong random key before uploading, e.g. '9f3c2b7c...'
$key = (string)($_GET['key'] ?? '');
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

echo json_encode([
    'success' => true,
    'checks' => $checks,
    'last_php_errors_log_tail' => tail_file($root . '/logs/php_errors.log', 120000),
], JSON_PRETTY_PRINT);
