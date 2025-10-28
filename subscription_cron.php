<?php
// subscription_cron.php
// Run this script daily (via cron or browser) to send subscription expiry warnings, expiry notices, and overdue reminders.
// Usage: php subscription_cron.php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/Models/Subscription.php';
require_once __DIR__ . '/app/Models/User.php';
require_once __DIR__ . '/app/Models/Setting.php';

use App\Models\Subscription;
use App\Models\User;
use App\Models\Setting;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

date_default_timezone_set('UTC');

function sendStyledMail($to, $toName, $subject, $bodyContent, $settings) {
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $settings['smtp_host'] ?? '';
        $mail->Port = $settings['smtp_port'] ?? 587;
        $mail->SMTPAuth = true;
        $mail->Username = $settings['smtp_user'] ?? '';
        $mail->Password = $settings['smtp_pass'] ?? '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->setFrom($settings['smtp_user'] ?? '', $settings['site_name'] ?? 'RentSmart');
        $mail->isHTML(true);
        $logoUrl = isset($settings['site_logo']) && $settings['site_logo'] ? (defined('BASE_URL') ? (BASE_URL . '/public/assets/images/' . $settings['site_logo']) : '') : '';
        $footer = '<div style="margin-top:30px;font-size:12px;color:#888;text-align:center;">Powered by <a href="https://timestentechnologies.co.ke" target="_blank" style="color:#888;text-decoration:none;">Timesten Technologies</a></div>';
        $mail->addAddress($to, $toName);
        $mail->Subject = $subject;
        $mail->Body =
            '<div style="max-width:500px;margin:auto;border:1px solid #eee;padding:24px;font-family:sans-serif;">'
            . ($logoUrl ? '<div style="text-align:center;margin-bottom:24px;"><img src="' . $logoUrl . '" alt="Logo" style="max-width:180px;max-height:80px;"></div>' : '') .
            $bodyContent .
            $footer .
            '</div>';
        $mail->send();
    } catch (MailException $e) {
        error_log('Subscription cron mail error: ' . $e->getMessage());
    }
}

$subscriptionModel = new Subscription();
$userModel = new User();
$settingModel = new Setting();
$settings = $settingModel->getAllAsAssoc();

$today = new DateTime('now', new DateTimeZone('UTC'));
$oneDayAhead = (clone $today)->modify('+1 day');

// 1. Warning: Subscriptions expiring in 1 day
$expiring = $subscriptionModel->getExpiringInDays(1);
foreach ($expiring as $sub) {
    $user = $userModel->find($sub['user_id']);
    if (!$user || empty($user['email']) || empty($sub['current_period_ends_at'])) continue;
    $body = '<p style="font-size:16px;">Dear ' . htmlspecialchars($user['name']) . ',</p>' .
        '<p>Your RentSmart subscription will expire on <strong>' . date('F j, Y', strtotime($sub['current_period_ends_at'])) . '</strong>.</p>' .
        '<p>Please renew your subscription to avoid interruption of service.</p>' .
        '<p><a href="' . (defined('BASE_URL') ? BASE_URL : '') . '/subscription/renew" style="color:#fff;background:#007bff;padding:10px 18px;border-radius:4px;text-decoration:none;">Renew Now</a></p>' .
        '<p>Thank you,<br>RentSmart Team</p>';
    sendStyledMail($user['email'], $user['name'], 'Your RentSmart Subscription is About to Expire', $body, $settings);
}

// 2. Expired today: Subscriptions that expired today
$expiredToday = $subscriptionModel->getExpiredOnDate($today->format('Y-m-d'));
foreach ($expiredToday as $sub) {
    $user = $userModel->find($sub['user_id']);
    if (!$user || empty($user['email']) || empty($sub['current_period_ends_at'])) continue;
    $body = '<p style="font-size:16px;">Dear ' . htmlspecialchars($user['name']) . ',</p>' .
        '<p>Your RentSmart subscription expired today (<strong>' . date('F j, Y', strtotime($sub['current_period_ends_at'])) . '</strong>).</p>' .
        '<p>Please renew your subscription to regain access to all features.</p>' .
        '<p><a href="' . (defined('BASE_URL') ? BASE_URL : '') . '/subscription/renew" style="color:#fff;background:#007bff;padding:10px 18px;border-radius:4px;text-decoration:none;">Renew Now</a></p>' .
        '<p>Thank you,<br>RentSmart Team</p>';
    sendStyledMail($user['email'], $user['name'], 'Your RentSmart Subscription Has Expired', $body, $settings);
}

// 3. Overdue: Subscriptions expired before today and not renewed
$overdue = $subscriptionModel->getExpiredBeforeDate($today->format('Y-m-d'));
foreach ($overdue as $sub) {
    $user = $userModel->find($sub['user_id']);
    if (!$user || empty($user['email']) || empty($sub['current_period_ends_at'])) continue;
    $body = '<p style="font-size:16px;">Dear ' . htmlspecialchars($user['name']) . ',</p>' .
        '<p>Your RentSmart subscription expired on <strong>' . date('F j, Y', strtotime($sub['current_period_ends_at'])) . '</strong> and has not been renewed.</p>' .
        '<p>Please renew your subscription to regain access to all features.</p>' .
        '<p><a href="' . (defined('BASE_URL') ? BASE_URL : '') . '/subscription/renew" style="color:#fff;background:#007bff;padding:10px 18px;border-radius:4px;text-decoration:none;">Renew Now</a></p>' .
        '<p>Thank you,<br>RentSmart Team</p>';
    sendStyledMail($user['email'], $user['name'], 'Your RentSmart Subscription is Overdue', $body, $settings);
}

echo "Subscription notification emails sent.\n"; 