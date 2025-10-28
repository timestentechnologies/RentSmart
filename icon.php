<?php
// Dynamic icon generator: resizes current favicon to requested square size
// Usage: /icon.php?size=192

// Derive BASE
if (defined('BASE_URL')) {
    $base = rtrim(BASE_URL, '/');
} else {
    $base_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    $base = $base_dir !== '/' ? $base_dir : '';
}

$size = isset($_GET['size']) ? (int)$_GET['size'] : 192;
if ($size < 48) { $size = 48; }
if ($size > 1024) { $size = 1024; }

// Default favicon path
$faviconRel = '/public/assets/images/site_favicon_1750832003.png';

// Try to get favicon from settings
try {
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/app/helpers.php';
    $settingModel = new \App\Models\Setting();
    $settings = $settingModel->getAllAsAssoc();
    if (!empty($settings['site_favicon'])) {
        $faviconRel = '/public/assets/images/' . $settings['site_favicon'];
    }
} catch (\Throwable $e) {
    // ignore
}

$faviconFs = __DIR__ . str_replace($base, '', $base . $faviconRel);
// Fallback: if computed path missing, try direct path
if (!is_file($faviconFs)) {
    $fallback = __DIR__ . $faviconRel;
    if (is_file($fallback)) {
        $faviconFs = $fallback;
    }
}
if (!is_file($faviconFs)) {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo 'Icon source not found';
    exit;
}

// Load source image
$src = @imagecreatefrompng($faviconFs);
if (!$src) {
    $src = @imagecreatefromjpeg($faviconFs);
}
if (!$src) {
    $src = @imagecreatefromgif($faviconFs);
}
if (!$src) {
    http_response_code(415);
    header('Content-Type: text/plain');
    echo 'Unsupported icon format';
    exit;
}

$width = imagesx($src);
$height = imagesy($src);

// Create square canvas with transparency
$dst = imagecreatetruecolor($size, $size);
imagesavealpha($dst, true);
$trans = imagecolorallocatealpha($dst, 0, 0, 0, 127);
imagefill($dst, 0, 0, $trans);

// Fit source inside square (contain)
$scale = min($size / $width, $size / $height);
$newW = (int) round($width * $scale);
$newH = (int) round($height * $scale);
$dstX = (int) floor(($size - $newW) / 2);
$dstY = (int) floor(($size - $newH) / 2);

imagealphablending($dst, true);
imagecopyresampled($dst, $src, $dstX, $dstY, 0, 0, $newW, $newH, $width, $height);

header('Content-Type: image/png');
header('Cache-Control: public, max-age=31536000, immutable');
imagepng($dst);
imagedestroy($dst);
imagedestroy($src);
exit;


