<?php
header('Content-Type: text/plain; charset=utf-8');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo "Diag started: ".date('c')."\n";
echo "PHP: ".PHP_VERSION.", SAPI: ".PHP_SAPI."\n";
echo "Dir: ".__DIR__."\n";
echo "php.ini: ".(php_ini_loaded_file() ?: 'unknown')."\n";

$op = isset($_GET['op']) ? $_GET['op'] : 'info';

function first3($path) {
    if (!file_exists($path)) return "missing";
    $h = @fopen($path, 'rb');
    if (!$h) return 'unreadable';
    $b = @fread($h, 3);
    @fclose($h);
    if ($b === false) return 'unreadable';
    return bin2hex($b);
}

function show_bytes($label, $path) {
    $hex = first3($path);
    $sz = file_exists($path) ? (string)filesize($path) : '0';
    echo $label.': '.$path.' => first3='.$hex.' size='.$sz."\n";
    if ($hex === 'efbbbf') echo "  -> BOM DETECTED\n";
    if ($hex === '3c3f70') echo "  -> clean PHP start\n";
}

echo "OPcache: ".(extension_loaded('Zend OPcache') ? 'loaded' : 'not loaded')."\n";
foreach (['opcache.enable','opcache.validate_timestamps','opcache.revalidate_freq'] as $k) {
    $v = ini_get($k);
    if ($v === false) { $v = 'n/a'; }
    echo $k.': '.$v."\n";
}

if ($op === 'reset_opcache') {
    if (function_exists('opcache_reset')) {
        $ok = @opcache_reset();
        echo 'opcache_reset(): '.($ok ? 'OK' : 'FAILED')."\n";
    } else {
        echo "opcache_reset(): not available\n";
    }
    exit;
}

$paths = [
    'InvoicesController' => __DIR__.'/app/Controllers/InvoicesController.php',
    'helpers'            => __DIR__.'/app/helpers.php',
    'index'              => __DIR__.'/index.php',
    'invoice_pdf'        => __DIR__.'/views/invoices/invoice_pdf.php',
    'show'               => __DIR__.'/views/invoices/show.php',
    'home'               => __DIR__.'/views/home.php',
];

echo "\nFile starts (first 3 bytes):\n";
foreach ($paths as $label => $p) {
    show_bytes($label, $p);
}

echo "\nComposer autoload check:\n";
try {
    require __DIR__.'/vendor/autoload.php';
    echo "vendor/autoload.php: OK\n";
    echo "Dompdf class available: ".(class_exists('\\Dompdf\\Dompdf') ? 'YES' : 'NO')."\n";
} catch (Throwable $e) {
    echo "Autoload error: ".$e->getMessage()."\n";
}

if ($op === 'class_exists') {
    echo "\nAutoload class_exists(App\\Controllers\\InvoicesController):\n";
    var_dump(class_exists('App\\Controllers\\InvoicesController'));
    exit;
}

if ($op === 'require_invoices_controller') {
    echo "\nRequiring InvoicesController.php now. If there is BOM/whitespace before namespace, a fatal error should appear below.\n\n";
    require __DIR__.'/app/Controllers/InvoicesController.php';
    echo "Included OK\n";
    exit;
}

$log = __DIR__.'/logs/php_errors.log';
$altLog = __DIR__.'/views/logs/php_errors.log';

// Memory-safe tail implementation (does not load entire file)
function tail_file($file, $lines = 300, $buffer = 4096) {
    $result = '';
    if (!is_readable($file)) {
        return $result;
    }
    $f = @fopen($file, 'rb');
    if (!$f) return $result;
    try {
        fseek($f, 0, SEEK_END);
        $pos = ftell($f);
        $data = '';
        $lineCount = 0;
        while ($pos > 0 && $lineCount <= $lines) {
            $seek = max($pos - $buffer, 0);
            $bytes = $pos - $seek;
            fseek($f, $seek);
            $chunk = fread($f, $bytes);
            if ($chunk === false) { break; }
            $data = $chunk . $data;
            $lineCount = substr_count($data, "\n");
            $pos = $seek;
        }
        $parts = explode("\n", $data);
        $result = implode("\n", array_slice($parts, -$lines));
    } finally {
        fclose($f);
    }
    return $result;
}

$tailLines = isset($_GET['tail']) ? max(50, min(5000, (int)$_GET['tail'])) : 300;
echo "\nRecent app log (logs/php_errors.log) — last {$tailLines} line(s):\n";
if (is_readable($log)) {
    $tail = tail_file($log, $tailLines);
    echo ($tail !== '' ? $tail : "(empty)") . "\n";
} else {
    echo "No readable logs/php_errors.log\n";
}

echo "\nAlternate view log (views/logs/php_errors.log) — last {$tailLines} line(s):\n";
if (is_readable($altLog)) {
    $tail2 = tail_file($altLog, $tailLines);
    echo ($tail2 !== '' ? $tail2 : "(empty)") . "\n";
} else {
    echo "No readable views/logs/php_errors.log\n";
}

echo "\nUsage:\n";
echo "  ?op=info (default) — show environment and bytes\n";
echo "  ?op=require_invoices_controller — trigger fatal if BOM/namespace issue\n";
echo "  ?op=class_exists — test autoloading (may fatal if parse error)\n";
echo "  ?op=reset_opcache — reset OPcache\n";
