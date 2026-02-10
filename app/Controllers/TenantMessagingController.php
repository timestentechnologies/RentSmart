<?php

namespace App\Controllers;

use App\Models\Message;
use App\Models\Lease;
use App\Models\Unit;
use App\Models\Property;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Setting;
use App\Models\Notification;

class TenantMessagingController
{
    private $tenantId;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->tenantId = $_SESSION['tenant_id'] ?? null;
    }

    private function requireTenant()
    {
        if (!$this->tenantId) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }
    }

    private function getOwnerUserIdForActiveLease(): ?int
    {
        try {
            $leaseModel = new Lease();
            $lease = $leaseModel->getActiveLeaseByTenant((int)$this->tenantId);
            if (!$lease) return null;
            $unitModel = new Unit();
            $unit = $unitModel->find($lease['unit_id']);
            if (!$unit || empty($unit['property_id'])) return null;
            $propertyModel = new Property();
            $prop = $propertyModel->find($unit['property_id']);
            if (!$prop) return null;
            if (!empty($prop['owner_id'])) return (int)$prop['owner_id'];
            if (!empty($prop['manager_id'])) return (int)$prop['manager_id'];
            if (!empty($prop['agent_id'])) return (int)$prop['agent_id'];
            if (!empty($prop['caretaker_user_id'])) return (int)$prop['caretaker_user_id'];
        } catch (\Exception $e) {
            error_log('TenantMessagingController getOwnerUserId error: ' . $e->getMessage());
        }
        return null;
    }

    public function index()
    {
        $this->requireTenant();
        $ownerUserId = $this->getOwnerUserIdForActiveLease();
        $owner = null;
        if ($ownerUserId) {
            $owner = (new User())->find($ownerUserId);
        }

        $settingModel = new Setting();
        $settings = $settingModel->getAllAsAssoc();
        $siteLogo = isset($settings['site_logo']) && $settings['site_logo']
            ? BASE_URL . '/public/assets/images/' . $settings['site_logo']
            : BASE_URL . '/public/assets/images/logo.svg';
        $siteFavicon = isset($settings['site_favicon']) && $settings['site_favicon']
            ? BASE_URL . '/public/assets/images/' . $settings['site_favicon']
            : BASE_URL . '/public/assets/images/site_favicon_1750832003.png';

        require __DIR__ . '/../../views/tenant/messaging.php';
    }

    public function thread()
    {
        header('Content-Type: application/json');
        $this->requireTenant();
        try {
            $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
            if ($userId <= 0) {
                $userId = $this->getOwnerUserIdForActiveLease() ?: 0;
            }
            if ($userId <= 0) throw new \Exception('No recipient');
            $msg = new Message();
            $conv = $msg->getConversation('tenant', (int)$this->tenantId, 'user', (int)$userId, 500);
            echo json_encode(['success' => true, 'messages' => $conv, 'user_id' => $userId]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function send()
    {
        header('Content-Type: application/json');
        $this->requireTenant();
        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') throw new \Exception('Invalid request');
            if (!function_exists('verify_csrf_token') || !verify_csrf_token()) throw new \Exception('Invalid CSRF token');
            $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
            if ($userId <= 0) {
                $userId = $this->getOwnerUserIdForActiveLease() ?: 0;
            }
            $body = trim($_POST['body'] ?? '');
            if ($userId <= 0 || $body === '') throw new \Exception('Message and recipient are required');

            $msg = new Message();
            $id = $msg->insertMessage([
                'sender_type' => 'tenant',
                'sender_id' => (int)$this->tenantId,
                'receiver_type' => 'user',
                'receiver_id' => (int)$userId,
                'body' => $body,
            ]);

            // In-app notification to recipient
            try {
                $tenantModel = new Tenant();
                $t = $tenantModel->find((int)$this->tenantId);
                $tenantName = trim((string)(($t['first_name'] ?? '') . ' ' . ($t['last_name'] ?? '')));
                if ($tenantName === '') {
                    $tenantName = (string)($t['name'] ?? 'Tenant');
                }
                $n = new Notification();
                $n->createNotification([
                    'recipient_type' => 'user',
                    'recipient_id' => (int)$userId,
                    'actor_type' => 'tenant',
                    'actor_id' => (int)$this->tenantId,
                    'title' => 'New Message',
                    'body' => $tenantName . ': ' . mb_strimwidth((string)$body, 0, 160, '…'),
                    'link' => BASE_URL . '/messaging',
                    'entity_type' => 'message',
                    'entity_id' => (int)$id,
                    'payload' => [
                        'tenant_id' => (int)$this->tenantId,
                        'user_id' => (int)$userId,
                    ],
                ]);
            } catch (\Throwable $notifyErr) {
                error_log('TenantMessagingController::send notify failed: ' . $notifyErr->getMessage());
            }

            // Optional email notification to recipient
            try {
                $userModel = new User();
                $recipient = $userModel->find($userId);
                $recipientEmail = $recipient['email'] ?? '';
                if ($recipientEmail !== '') {
                    $settingModel = new Setting();
                    $settings = $settingModel->getAllAsAssoc();
                    $siteUrl = rtrim($settings['site_url'] ?? '', '/');
                    $logoUrl = isset($settings['site_logo']) && $settings['site_logo'] ? ($siteUrl . '/public/assets/images/' . $settings['site_logo']) : '';
                    $footer = '<div style="margin-top:30px;font-size:12px;color:#888;text-align:center;">Powered by <a href="https://timestentechnologies.co.ke" target="_blank" style="color:#888;text-decoration:none;">Timesten Technologies</a></div>';

                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = $settings['smtp_host'] ?? '';
                    $mail->Port = (int)($settings['smtp_port'] ?? 587);
                    $mail->SMTPAuth = true;
                    $mail->Username = $settings['smtp_user'] ?? '';
                    $mail->Password = $settings['smtp_pass'] ?? '';
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->setFrom($settings['smtp_user'] ?? '', $settings['site_name'] ?? 'RentSmart');
                    $mail->isHTML(true);
                    $bodyHtml =
                        '<div style="max-width:520px;margin:auto;border:1px solid #eee;padding:24px;font-family:sans-serif;">'
                        . ($logoUrl ? '<div style="text-align:center;margin-bottom:24px;"><img src="' . $logoUrl . '" alt="Logo" style="max-width:180px;max-height:80px;"></div>' : '') .
                        '<p style="font-size:16px;">You have a new message from a tenant.</p>' .
                        '<blockquote style="margin:16px 0;padding:12px 16px;background:#f9f9f9;border-left:3px solid #ccc;white-space:pre-wrap;">' . nl2br(htmlspecialchars($body)) . '</blockquote>' .
                        '<p><a href="' . $siteUrl . '/messaging" style="background:#0061f2;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;display:inline-block;">View and reply</a></p>' .
                        $footer .
                        '</div>';
                    $mail->clearAddresses();
                    $mail->addAddress($recipientEmail, $recipient['name'] ?? 'User');
                    $mail->Subject = 'New message from tenant';
                    $mail->Body = $bodyHtml;
                    try { $mail->send(); } catch (\PHPMailer\PHPMailer\Exception $e) { error_log('Tenant messaging mail send error: ' . $e->getMessage()); }
                }
            } catch (\Exception $e) {
                error_log('Tenant messaging email error: ' . $e->getMessage());
            }

            echo json_encode(['success' => true, 'id' => $id]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}
