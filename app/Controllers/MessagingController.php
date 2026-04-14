<?php

namespace App\Controllers;

use App\Models\Message;
use App\Models\Tenant;
use App\Models\Property;
use App\Models\User;
use App\Models\Setting;
use App\Models\Notification;

class MessagingController
{
    private $userId;
    private $userRole;

    public function __construct()
    {
        $this->userId = $_SESSION['user_id'] ?? null;
        $this->userRole = $_SESSION['user_role'] ?? null;
    }

    public function index()
    {
        // Build recipients list based on role and accessible properties
        $recipients = [
            'tenants' => [],
            'caretakers' => [],
            'admins' => [],
            'users' => [], // only populated for admin
            'bookings' => [], // for airbnb_manager
            'walkins' => [], // for airbnb_manager
        ];

        try {
            $userRole = strtolower((string)($this->userRole ?? ''));

            // For airbnb_manager, load bookings and walk-in guests instead of tenants
            if ($userRole === 'airbnb_manager') {
                // Load bookings (Airbnb guests)
                $bookingModel = new \App\Models\AirbnbBooking();
                $bookings = $bookingModel->getBookingsForUser($this->userId, $this->userRole);
                foreach ($bookings as $b) {
                    $recipients['bookings'][] = [
                        'id' => (int)$b['id'],
                        'name' => $b['guest_name'] ?? 'Guest #'.$b['id'],
                        'email' => $b['guest_email'] ?? null,
                        'phone' => $b['guest_phone'] ?? null,
                        'property' => $b['property_name'] ?? null,
                        'unit' => $b['unit_number'] ?? null,
                        'check_in' => $b['check_in_date'] ?? null,
                        'check_out' => $b['check_out_date'] ?? null,
                        'status' => $b['status'] ?? null,
                    ];
                }

                // Load walk-in guests
                $walkinModel = new \App\Models\AirbnbWalkinGuest();
                $walkins = $walkinModel->getWalkinGuestsForUser($this->userId, $this->userRole);
                foreach ($walkins as $w) {
                    $recipients['walkins'][] = [
                        'id' => (int)$w['id'],
                        'name' => $w['guest_name'] ?? 'Walk-in #'.$w['id'],
                        'email' => $w['guest_email'] ?? null,
                        'phone' => $w['guest_phone'] ?? null,
                        'property' => $w['property_name'] ?? null,
                        'unit' => $w['unit_number'] ?? null,
                        'status' => $w['status'] ?? null,
                    ];
                }
            } else {
                // Tenants (role-filtered) - for regular property managers
                $tenantModel = new Tenant();
                $tenants = $tenantModel->getAll($this->userId);
                foreach ($tenants as $t) {
                    $recipients['tenants'][] = [
                        'id' => (int)$t['id'],
                        'name' => $t['name'] ?? 'Tenant #'.$t['id'],
                        'property' => $t['property_name'] ?? null,
                        'unit' => $t['unit_number'] ?? null,
                    ];
                }

                // Caretakers for accessible properties
                $propertyModel = new Property();
                $properties = $propertyModel->getAll($this->userId);
                $caretakerIds = [];
                foreach ($properties as $p) {
                    if (!empty($p['caretaker_user_id'])) {
                        $caretakerIds[(int)$p['caretaker_user_id']] = true;
                    }
                }
                if (!empty($caretakerIds)) {
                    $ids = array_keys($caretakerIds);
                    if (!empty($ids)) {
                        $in = implode(',', array_fill(0, count($ids), '?'));
                        $userModel = new User();
                        $stmt = $userModel->getDb()->prepare("SELECT id, name, role FROM users WHERE id IN ($in)");
                        $stmt->execute($ids);
                        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                        foreach ($rows as $u) {
                            $recipients['caretakers'][] = [
                                'id' => (int)$u['id'],
                                'name' => $u['name'] ?? 'User #'.$u['id'],
                                'role' => $u['role'] ?? 'caretaker'
                            ];
                        }
                    }
                }
            }

            // Admins (available to all roles)
            $userModel = new User();
            $stmt = $userModel->getDb()->prepare("SELECT id, name FROM users WHERE role IN ('admin','administrator') ORDER BY name ASC");
            $stmt->execute();
            $admins = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($admins as $a) {
                $recipients['admins'][] = [
                    'id' => (int)$a['id'],
                    'name' => $a['name'] ?? 'Admin #'.$a['id']
                ];
            }

            // If current user is admin, expose all users as recipients
            $me = new User();
            $me->find($this->userId);
            if ($me->isAdmin()) {
                $stmtAll = $userModel->getDb()->prepare("SELECT id, name, role FROM users WHERE id <> ? ORDER BY name ASC");
                $stmtAll->execute([(int)$this->userId]);
                $allUsers = $stmtAll->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($allUsers as $u) {
                    $recipients['users'][] = [
                        'id' => (int)$u['id'],
                        'name' => $u['name'] ?? ('User #'.$u['id']),
                        'role' => $u['role'] ?? null
                    ];
                }
            }
        } catch (\Exception $e) {
            error_log('MessagingController::index error: ' . $e->getMessage());
            $recipients = ['tenants'=>[],'caretakers'=>[],'admins'=>[],'users'=>[],'bookings'=>[],'walkins'=>[]];
        }

        require 'views/messaging/index.php';
    }

    public function thread()
    {
        header('Content-Type: application/json');
        try {
            $rawType = $_GET['type'] ?? 'tenant';
            $allowedTypes = ['user', 'tenant', 'booking', 'walkin'];
            $type = in_array($rawType, $allowedTypes, true) ? $rawType : 'tenant';
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) throw new \Exception('Invalid recipient');

            // Authorization: ensure current user can contact this recipient (or is admin)
            if (!$this->canContact($type, $id)) {
                http_response_code(403);
                echo json_encode(['success'=>false,'message'=>'Not allowed']);
                exit;
            }

            $msg = new Message();
            $conv = $msg->getConversation('user', (int)$this->userId, $type, $id, 500);
            echo json_encode(['success'=>true,'messages'=>$conv]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        exit;
    }

    public function broadcastMeta()
    {
        header('Content-Type: application/json');
        try {
            if (!$this->userId) {
                throw new \Exception('Unauthorized');
            }

            $key = strtolower(trim((string)($_GET['key'] ?? '')));
            if ($key === '') {
                throw new \Exception('Invalid broadcast recipient');
            }

            $userRole = strtolower((string)($this->userRole ?? ''));
            $recipients = [];
            $count = 0;

            // Airbnb manager broadcast options
            if ($userRole === 'airbnb_manager') {
                $bookingModel = new \App\Models\AirbnbBooking();
                $walkinModel = new \App\Models\AirbnbWalkinGuest();

                if ($key === 'all_bookings') {
                    $bookings = $bookingModel->getBookingsForUser($this->userId, $this->userRole);
                    $count = count($bookings);
                    foreach ($bookings as $b) {
                        $recipients[] = [
                            'id' => (int)$b['id'],
                            'name' => (string)($b['guest_name'] ?? ('Guest #' . $b['id'])),
                            'property' => (string)($b['property_name'] ?? ''),
                            'unit' => (string)($b['unit_number'] ?? ''),
                            'email' => (string)($b['guest_email'] ?? ''),
                        ];
                        if (count($recipients) >= 50) break;
                    }
                } else if ($key === 'upcoming_checkins') {
                    $bookings = $bookingModel->getUpcomingCheckIns($this->user->getAccessiblePropertyIds(), 30);
                    $count = count($bookings);
                    foreach ($bookings as $b) {
                        $recipients[] = [
                            'id' => (int)$b['id'],
                            'name' => (string)($b['guest_name'] ?? ('Guest #' . $b['id'])),
                            'property' => (string)($b['property_name'] ?? ''),
                            'unit' => (string)($b['unit_number'] ?? ''),
                            'email' => (string)($b['guest_email'] ?? ''),
                        ];
                        if (count($recipients) >= 50) break;
                    }
                } else if ($key === 'active_walkins') {
                    $walkins = $walkinModel->getWalkinGuestsForUser($this->userId, $this->userRole);
                    // Filter to only inquiry and offered status
                    $walkins = array_filter($walkins, function($w) {
                        return in_array($w['status'], ['inquiry', 'offered'], true);
                    });
                    $count = count($walkins);
                    foreach ($walkins as $w) {
                        $recipients[] = [
                            'id' => (int)$w['id'],
                            'name' => (string)($w['guest_name'] ?? ('Walk-in #' . $w['id'])),
                            'property' => (string)($w['property_name'] ?? ''),
                            'unit' => (string)($w['unit_number'] ?? ''),
                            'email' => (string)($w['guest_email'] ?? ''),
                        ];
                        if (count($recipients) >= 50) break;
                    }
                } else {
                    throw new \Exception('Invalid broadcast recipient');
                }
            } else {
                // Regular property manager broadcast options
                $tenantModel = new Tenant();
                $ids = [];
                if ($key === 'all') {
                    $ids = $tenantModel->getAccessibleTenantIds((int)$this->userId);
                } else if ($key === 'due_current_month') {
                    $ids = $tenantModel->getTenantIdsWithRentBalanceCurrentMonth((int)$this->userId);
                } else if ($key === 'due_previous_months') {
                    $ids = $tenantModel->getTenantIdsWithRentBalanceIncludingPreviousMonths((int)$this->userId);
                } else {
                    throw new \Exception('Invalid broadcast recipient');
                }

                $count = count($ids);
                if ($count > 0) {
                    $set = [];
                    foreach ($ids as $tid) {
                        $tid = (int)$tid;
                        if ($tid > 0) {
                            $set[$tid] = true;
                        }
                    }

                    $all = $tenantModel->getAll($this->userId);
                    foreach ($all as $t) {
                        $tid = (int)($t['id'] ?? 0);
                        if ($tid <= 0 || !isset($set[$tid])) {
                            continue;
                        }
                        $recipients[] = [
                            'id' => $tid,
                            'name' => (string)($t['name'] ?? ('Tenant #' . $tid)),
                            'property' => (string)($t['property_name'] ?? ''),
                            'unit' => (string)($t['unit_number'] ?? ''),
                        ];
                        if (count($recipients) >= 50) {
                            break;
                        }
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'count' => $count,
                'recipients' => $recipients,
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function send()
    {
        header('Content-Type: application/json');
        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                throw new \Exception('Invalid request');
            }
            if (!function_exists('verify_csrf_token') || !verify_csrf_token()) {
                throw new \Exception('Invalid CSRF token');
            }
            if (!$this->userId) { throw new \Exception('Unauthorized'); }

            $rawReceiverType = (string)($_POST['receiver_type'] ?? 'tenant');
            $allowedTypes = ['tenant', 'user', 'broadcast', 'booking', 'walkin'];
            $receiverType = in_array($rawReceiverType, $allowedTypes, true) ? $rawReceiverType : 'tenant';

            $receiverIdRaw = (string)($_POST['receiver_id'] ?? '0');
            $receiverId = (int)$receiverIdRaw;
            $body = trim($_POST['body'] ?? '');

            if ($body === '') {
                throw new \Exception('Message and recipient are required');
            }

            if ($receiverType !== 'broadcast' && $receiverId <= 0) {
                throw new \Exception('Message and recipient are required');
            }

            if ($receiverType === 'broadcast') {
                $broadcastKey = strtolower(trim($receiverIdRaw));
                if ($broadcastKey === '') {
                    throw new \Exception('Invalid broadcast recipient');
                }

                $userRole = strtolower((string)($this->userRole ?? ''));

                // Airbnb manager broadcasts
                if ($userRole === 'airbnb_manager') {
                    return $this->sendAirbnbBroadcast($broadcastKey, $body);
                }

                // Regular property manager broadcasts
                $tenantModel = new Tenant();
                $targetTenantIds = [];
                if ($broadcastKey === 'all') {
                    $targetTenantIds = $tenantModel->getAccessibleTenantIds((int)$this->userId);
                } else if ($broadcastKey === 'due_current_month') {
                    $targetTenantIds = $tenantModel->getTenantIdsWithRentBalanceCurrentMonth((int)$this->userId);
                } else if ($broadcastKey === 'due_previous_months') {
                    $targetTenantIds = $tenantModel->getTenantIdsWithRentBalanceIncludingPreviousMonths((int)$this->userId);
                } else {
                    throw new \Exception('Invalid broadcast recipient');
                }

                if (empty($targetTenantIds)) {
                    echo json_encode(['success' => true, 'sent' => 0]);
                    exit;
                }

                $msg = new Message();
                $sent = 0;
                foreach ($targetTenantIds as $tid) {
                    $tid = (int)$tid;
                    if ($tid <= 0) {
                        continue;
                    }

                    if (!$this->canContact('tenant', $tid)) {
                        continue;
                    }

                    $id = $msg->insertMessage([
                        'sender_type' => 'user',
                        'sender_id' => (int)$this->userId,
                        'receiver_type' => 'tenant',
                        'receiver_id' => (int)$tid,
                        'body' => $body
                    ]);
                    $sent++;

                    // In-app notification to recipient
                    try {
                        $userModel = new User();
                        $sender = $userModel->find((int)$this->userId);
                        $senderName = (string)($sender['name'] ?? 'Staff');
                        $preview = mb_strimwidth((string)$body, 0, 160, '…');

                        $n = new Notification();
                        $n->createNotification([
                            'recipient_type' => 'tenant',
                            'recipient_id' => (int)$tid,
                            'actor_type' => 'user',
                            'actor_id' => (int)$this->userId,
                            'title' => 'New Message',
                            'body' => $senderName . ': ' . $preview,
                            'link' => BASE_URL . '/tenant/messaging',
                            'entity_type' => 'message',
                            'entity_id' => (int)$id,
                            'payload' => [
                                'user_id' => (int)$this->userId,
                                'tenant_id' => (int)$tid,
                            ],
                        ]);
                    } catch (\Throwable $notifyErr) {
                        error_log('MessagingController::send (broadcast) notify failed: ' . $notifyErr->getMessage());
                    }

                    // Optional email notification to recipient
                    try {
                        $userModel = new User();
                        $sender = $userModel->find((int)$this->userId);
                        $t = $tenantModel->getById((int)$tid, (int)$this->userId);
                        $recipientEmail = (!empty($t) && !empty($t['email'])) ? (string)$t['email'] : '';
                        $recipientName = (!empty($t) && !empty($t['name'])) ? (string)$t['name'] : 'Tenant';

                        if ($recipientEmail !== '') {
                            $settingModel = new Setting();
                            $settings = $settingModel->getAllAsAssoc();
                            $siteUrl = rtrim($settings['site_url'] ?? 'https://rentsmart.co.ke', '/');
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
                                '<p style="font-size:16px;">Dear ' . htmlspecialchars($recipientName) . ',</p>' .
                                '<p>You have a new message from <strong>' . htmlspecialchars((string)($sender['name'] ?? 'User')) . '</strong> on RentSmart.</p>' .
                                '<blockquote style="margin:16px 0;padding:12px 16px;background:#f9f9f9;border-left:3px solid #ccc;white-space:pre-wrap;">' . nl2br(htmlspecialchars($body)) . '</blockquote>' .
                                '<p><a href="' . $siteUrl . '/tenant/messaging" style="background:#0061f2;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;display:inline-block;">View and reply</a></p>' .
                                '<p>Thank you,<br>RentSmart Team</p>' .
                                $footer .
                                '</div>';

                            $mail->clearAddresses();
                            $mail->addAddress($recipientEmail, $recipientName);
                            $mail->Subject = 'New message from ' . ((string)($sender['name'] ?? 'RentSmart User'));
                            $mail->Body = $bodyHtml;
                            try { $mail->send(); } catch (\PHPMailer\PHPMailer\Exception $e) { error_log('Messaging mail send error: ' . $e->getMessage()); }
                        }
                    } catch (\Exception $e) {
                        error_log('Messaging mail error: ' . $e->getMessage());
                    }
                }

                echo json_encode(['success' => true, 'sent' => $sent]);
                exit;
            }

            // Authorization
            if (!$this->canContact($receiverType, $receiverId)) {
                throw new \Exception('You do not have permission to message this recipient');
            }

            $msg = new Message();
            $id = $msg->insertMessage([
                'sender_type' => 'user',
                'sender_id' => (int)$this->userId,
                'receiver_type' => $receiverType,
                'receiver_id' => $receiverId,
                'body' => $body
            ]);

            // In-app notification to recipient
            try {
                $userModel = new User();
                $sender = $userModel->find((int)$this->userId);
                $senderName = (string)($sender['name'] ?? 'Staff');
                $preview = mb_strimwidth((string)$body, 0, 160, '…');

                $n = new Notification();
                if ($receiverType === 'tenant') {
                    $n->createNotification([
                        'recipient_type' => 'tenant',
                        'recipient_id' => (int)$receiverId,
                        'actor_type' => 'user',
                        'actor_id' => (int)$this->userId,
                        'title' => 'New Message',
                        'body' => $senderName . ': ' . $preview,
                        'link' => BASE_URL . '/tenant/messaging',
                        'entity_type' => 'message',
                        'entity_id' => (int)$id,
                        'payload' => [
                            'user_id' => (int)$this->userId,
                            'tenant_id' => (int)$receiverId,
                        ],
                    ]);
                } else {
                    $n->createNotification([
                        'recipient_type' => 'user',
                        'recipient_id' => (int)$receiverId,
                        'actor_type' => 'user',
                        'actor_id' => (int)$this->userId,
                        'title' => 'New Message',
                        'body' => $senderName . ': ' . $preview,
                        'link' => BASE_URL . '/messaging',
                        'entity_type' => 'message',
                        'entity_id' => (int)$id,
                        'payload' => [
                            'sender_user_id' => (int)$this->userId,
                            'user_id' => (int)$receiverId,
                        ],
                    ]);
                }
            } catch (\Throwable $notifyErr) {
                error_log('MessagingController::send notify failed: ' . $notifyErr->getMessage());
            }

            try {
                $userModel = new User();
                $sender = $userModel->find((int)$this->userId);
                $recipientEmail = '';
                $recipientName = '';

                if ($receiverType === 'tenant') {
                    $tenantModel = new Tenant();
                    $t = $tenantModel->getById($receiverId, (int)$this->userId);
                    if (!empty($t) && !empty($t['email'])) {
                        $recipientEmail = (string)$t['email'];
                        $recipientName = (string)($t['name'] ?? 'Tenant');
                    }
                } else if ($receiverType === 'booking') {
                    $bookingModel = new \App\Models\AirbnbBooking();
                    $b = $bookingModel->findById($receiverId);
                    if (!empty($b) && !empty($b['guest_email'])) {
                        $recipientEmail = (string)$b['guest_email'];
                        $recipientName = (string)($b['guest_name'] ?? 'Guest');
                    }
                } else if ($receiverType === 'walkin') {
                    $walkinModel = new \App\Models\AirbnbWalkinGuest();
                    $w = $walkinModel->findById($receiverId);
                    if (!empty($w) && !empty($w['guest_email'])) {
                        $recipientEmail = (string)$w['guest_email'];
                        $recipientName = (string)($w['guest_name'] ?? 'Walk-in Guest');
                    }
                } else {
                    $target = $userModel->find($receiverId);
                    if (!empty($target) && !empty($target['email'])) {
                        $recipientEmail = (string)$target['email'];
                        $recipientName = (string)($target['name'] ?? 'User');
                    }
                }

                if ($recipientEmail !== '') {
                    $settingModel = new Setting();
                    $settings = $settingModel->getAllAsAssoc();
                    $siteUrl = rtrim($settings['site_url'] ?? 'https://rentsmart.co.ke', '/');
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
                        '<p style="font-size:16px;">Dear ' . htmlspecialchars($recipientName) . ',</p>' .
                        '<p>You have a new message from <strong>' . htmlspecialchars((string)($sender['name'] ?? 'User')) . '</strong> on RentSmart.</p>' .
                        '<blockquote style="margin:16px 0;padding:12px 16px;background:#f9f9f9;border-left:3px solid #ccc;white-space:pre-wrap;">' . nl2br(htmlspecialchars($body)) . '</blockquote>' .
                        '<p><a href="' . $siteUrl . '/messaging" style="background:#0061f2;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;display:inline-block;">View and reply</a></p>' .
                        '<p>Thank you,<br>RentSmart Team</p>' .
                        $footer .
                        '</div>';

                    $mail->clearAddresses();
                    $mail->addAddress($recipientEmail, $recipientName);
                    $mail->Subject = 'New message from ' . ((string)($sender['name'] ?? 'RentSmart User'));
                    $mail->Body = $bodyHtml;
                    try { $mail->send(); } catch (\PHPMailer\PHPMailer\Exception $e) { error_log('Messaging mail send error: ' . $e->getMessage()); }
                }
            } catch (\Exception $e) {
                error_log('Messaging mail error: ' . $e->getMessage());
            }
            echo json_encode(['success'=>true,'id'=>$id]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        exit;
    }

    /**
     * Handle broadcast messaging for Airbnb Manager
     */
    private function sendAirbnbBroadcast(string $broadcastKey, string $body): void
    {
        $bookingModel = new \App\Models\AirbnbBooking();
        $walkinModel = new \App\Models\AirbnbWalkinGuest();
        $msg = new Message();
        $sent = 0;
        $recipients = [];

        // Get recipients based on broadcast key
        if ($broadcastKey === 'all_bookings') {
            $recipients = $bookingModel->getBookingsForUser($this->userId, $this->userRole);
        } else if ($broadcastKey === 'upcoming_checkins') {
            $userModel = new User();
            $propertyIds = $userModel->getAccessiblePropertyIds();
            $recipients = $bookingModel->getUpcomingCheckIns($propertyIds, 7);
        } else if ($broadcastKey === 'active_walkins') {
            $walkins = $walkinModel->getWalkinGuestsForUser($this->userId, $this->userRole);
            // Filter to only inquiry and offered status
            $recipients = array_filter($walkins, function($w) {
                return in_array($w['status'], ['inquiry', 'offered'], true);
            });
        } else {
            throw new \Exception('Invalid broadcast recipient');
        }

        if (empty($recipients)) {
            echo json_encode(['success' => true, 'sent' => 0]);
            exit;
        }

        // Determine receiver type based on broadcast key
        $receiverType = (strpos($broadcastKey, 'walkin') !== false) ? 'walkin' : 'booking';

        foreach ($recipients as $recipient) {
            $recipientId = (int)($recipient['id'] ?? 0);
            if ($recipientId <= 0) continue;

            if (!$this->canContact($receiverType, $recipientId)) {
                continue;
            }

            // Save message
            $msg->insertMessage([
                'sender_type' => 'user',
                'sender_id' => (int)$this->userId,
                'receiver_type' => $receiverType,
                'receiver_id' => $recipientId,
                'body' => $body
            ]);
            $sent++;

            // Send email directly to guest
            try {
                $recipientEmail = ($receiverType === 'booking') 
                    ? ($recipient['guest_email'] ?? '') 
                    : ($recipient['guest_email'] ?? '');
                $recipientName = ($receiverType === 'booking')
                    ? ($recipient['guest_name'] ?? 'Guest')
                    : ($recipient['guest_name'] ?? 'Walk-in Guest');

                if ($recipientEmail !== '') {
                    $userModel = new User();
                    $sender = $userModel->find((int)$this->userId);
                    $settingModel = new Setting();
                    $settings = $settingModel->getAllAsAssoc();
                    $siteUrl = rtrim($settings['site_url'] ?? 'https://rentsmart.co.ke', '/');
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
                        '<p style="font-size:16px;">Dear ' . htmlspecialchars($recipientName) . ',</p>' .
                        '<p>You have a new message from <strong>' . htmlspecialchars((string)($sender['name'] ?? 'RentSmart')) . '</strong>.</p>' .
                        '<blockquote style="margin:16px 0;padding:12px 16px;background:#f9f9f9;border-left:3px solid #ccc;white-space:pre-wrap;">' . nl2br(htmlspecialchars($body)) . '</blockquote>' .
                        '<p>Thank you,<br>RentSmart Team</p>' .
                        $footer .
                        '</div>';

                    $mail->clearAddresses();
                    $mail->addAddress($recipientEmail, $recipientName);
                    $mail->Subject = 'New message from ' . ((string)($sender['name'] ?? 'RentSmart'));
                    $mail->Body = $bodyHtml;
                    try { $mail->send(); } catch (\PHPMailer\PHPMailer\Exception $e) { error_log('Airbnb broadcast mail error: ' . $e->getMessage()); }
                }
            } catch (\Exception $e) {
                error_log('Airbnb broadcast email error: ' . $e->getMessage());
            }
        }

        echo json_encode(['success' => true, 'sent' => $sent]);
        exit;
    }

    private function canContact(string $receiverType, int $receiverId): bool
    {
        try {
            $userModel = new User();
            $user = $userModel->find($this->userId);
            if (!$user) return false;
            $userModel->find($this->userId); // hydrate for helpers

            // Admins can contact anyone
            if ($userModel->isAdmin()) return true;

            $userRole = strtolower((string)($this->userRole ?? ''));

            // Airbnb manager can contact their bookings and walkins
            if ($userRole === 'airbnb_manager') {
                if ($receiverType === 'booking') {
                    $bookingModel = new \App\Models\AirbnbBooking();
                    $booking = $bookingModel->findById($receiverId);
                    if (empty($booking)) return false;
                    // Check if booking's property is accessible to this user
                    $propertyIds = $userModel->getAccessiblePropertyIds();
                    return in_array((int)$booking['property_id'], $propertyIds, true);
                }
                if ($receiverType === 'walkin') {
                    $walkinModel = new \App\Models\AirbnbWalkinGuest();
                    $walkin = $walkinModel->findById($receiverId);
                    if (empty($walkin)) return false;
                    // Check if walkin's property is accessible to this user
                    $propertyIds = $userModel->getAccessiblePropertyIds();
                    return in_array((int)$walkin['property_id'], $propertyIds, true);
                }
                // airbnb_manager can also contact admins
                if ($receiverType === 'user') {
                    $target = (new User())->find($receiverId);
                    if (!$target) return false;
                    $targetRole = strtolower($target['role'] ?? '');
                    return in_array($targetRole, ['admin','administrator'], true);
                }
                return false;
            }

            if ($receiverType === 'tenant') {
                // Sender must have access to this tenant through accessible properties
                $tenantModel = new Tenant();
                $t = $tenantModel->getById($receiverId, (int)$this->userId);
                return !empty($t);
            }

            // receiverType === 'user'
            $target = (new User())->find($receiverId);
            if (!$target) return false;
            $targetRole = strtolower($target['role'] ?? '');

            // Always allow contacting admins
            if (in_array($targetRole, ['admin','administrator'], true)) return true;

            // Landlords/Managers/Agents/AirbnbManagers can contact caretakers assigned to their properties
            if (in_array(strtolower($user['role'] ?? ''), ['landlord','manager','agent','airbnb_manager'], true)) {
                if ($targetRole === 'caretaker') {
                    $ids = $userModel->getAccessiblePropertyIds();
                    if (empty($ids)) return false;
                    $in = implode(',', array_fill(0, count($ids), '?'));
                    $stmt = $userModel->getDb()->prepare("SELECT COUNT(*) AS c FROM properties WHERE caretaker_user_id = ? AND id IN ($in)");
                    $params = array_merge([(int)$receiverId], $ids);
                    $stmt->execute($params);
                    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                    return (int)($row['c'] ?? 0) > 0;
                }
                // Block messaging to other property owners/managers/agents (no lateral messaging)
                return false;
            }

            // Caretakers: can contact admins and L/M/A tied to their accessible properties
            if ($userModel->isCaretaker()) {
                if (in_array($targetRole, ['admin','administrator'], true)) return true;
                if (in_array($targetRole, ['landlord','manager','agent'], true)) {
                    $ids = $userModel->getAccessiblePropertyIds();
                    if (empty($ids)) return false;
                    $in = implode(',', array_fill(0, count($ids), '?'));
                    $stmt = $userModel->getDb()->prepare("SELECT COUNT(*) AS c FROM properties WHERE id IN ($in) AND (owner_id = ? OR manager_id = ? OR agent_id = ?)");
                    $params = array_merge($ids, [(int)$receiverId, (int)$receiverId, (int)$receiverId]);
                    $stmt->execute($params);
                    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                    return (int)($row['c'] ?? 0) > 0;
                }
                return false;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
