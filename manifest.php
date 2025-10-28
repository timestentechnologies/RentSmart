<?php
header('Content-Type: application/manifest+json');
http_response_code(200);
// Derive base from script path if framework constant is unavailable
if (defined('BASE_URL')) {
    $base = rtrim(BASE_URL, '/');
} else {
    $base_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    $base = $base_dir !== '/' ? $base_dir : '';
}

// Use dynamic icon endpoint so sizes are guaranteed
$icon192 = $base . '/icon.php?size=192';
$icon512 = $base . '/icon.php?size=512';
try {
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/app/helpers.php';
    $settingModel = new \App\Models\Setting();
    $settings = $settingModel->getAllAsAssoc();
    // settings still used by icon.php; nothing else needed here
} catch (\Throwable $e) {
    // Fallback to default faviconPath if settings are unavailable
}
echo json_encode([
    'name' => 'RentSmart',
    'short_name' => 'RentSmart',
    'start_url' => $base . '/',
    'scope' => $base . '/',
    'display' => 'standalone',
    'background_color' => '#ffffff',
    'theme_color' => '#0d6efd',
    'icons' => [
        [ 'src' => $icon192, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable' ],
        [ 'src' => $icon512, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable' ]
    ]
], JSON_UNESCAPED_SLASHES);
exit;

