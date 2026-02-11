<?php

namespace App\Controllers;

use App\Models\Notice;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Setting;

class NoticesController
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
        $noticeModel = new Notice();
        $notices = [];
        $canPost = false;
        $properties = [];
        $tenants = [];

        if (!empty($this->userId)) {
            $notices = $noticeModel->getVisibleForUser($this->userId);
            $user = new User();
            $user->find($this->userId);
            $canPost = $user->isAdmin() || $user->isLandlord() || $user->isManager() || $user->isAgent() || $user->isCaretaker();
            if ($canPost) {
                $propertyModel = new Property();
                $properties = $propertyModel->getAll($this->userId);
                $tenantModel = new Tenant();
                $tenants = $tenantModel->getAll($this->userId);
            }
        }

        require 'views/notices/index.php';
    }

    public function store()
    {
        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { throw new \Exception('Invalid request'); }
            if (!function_exists('verify_csrf_token') || !verify_csrf_token()) {
                throw new \Exception('Invalid CSRF token');
            }
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) { throw new \Exception('Unauthorized'); }

            $title = trim($_POST['title'] ?? '');
            $body = trim($_POST['body'] ?? '');
            $propertyId = !empty($_POST['property_id']) ? (int)$_POST['property_id'] : null;
            $unitId = !empty($_POST['unit_id']) ? (int)$_POST['unit_id'] : null;
            $tenantId = !empty($_POST['tenant_id']) ? (int)$_POST['tenant_id'] : null;
            $pinned = !empty($_POST['pinned']) ? 1 : 0;

            if ($title === '' || $body === '') { throw new \Exception('Title and body are required'); }

            $user = new User();
            $user->find($userId);
            if (!($user->isAdmin() || $user->isLandlord() || $user->isManager() || $user->isAgent() || $user->isCaretaker())) {
                throw new \Exception('Permission denied');
            }

            // Enforce scope: only admins may post global notices
            if (!$user->isAdmin()) {
                if (empty($propertyId) && empty($unitId) && empty($tenantId)) {
                    throw new \Exception('Please target a property, unit, or tenant for your notice');
                }
                // Validate targets are within accessible properties/tenants
                $accessibleIds = $user->getAccessiblePropertyIds();
                if (!empty($propertyId) && !in_array((int)$propertyId, array_map('intval', $accessibleIds), true)) {
                    throw new \Exception('You cannot post a notice to this property');
                }
                if (!empty($unitId)) {
                    $stmt = (new \App\Models\Unit())->getDb()->prepare("SELECT u.property_id FROM units u WHERE u.id = ? LIMIT 1");
                    $stmt->execute([(int)$unitId]);
                    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $pid = (int)($row['property_id'] ?? 0);
                    if (!$pid || !in_array($pid, array_map('intval', $accessibleIds), true)) {
                        throw new \Exception('You cannot post a notice to this unit');
                    }
                }
                if (!empty($tenantId)) {
                    $t = (new Tenant())->getById((int)$tenantId, (int)$userId);
                    if (empty($t)) {
                        throw new \Exception('You cannot post a notice to this tenant');
                    }
                }
            }

            $notice = new Notice();
            $newId = $notice->create([
                'user_id' => $userId,
                'title' => $title,
                'body' => $body,
                'property_id' => $propertyId,
                'unit_id' => $unitId,
                'tenant_id' => $tenantId,
                'pinned' => $pinned,
            ]);

            try {
                $settingModel = new Setting();
                $settings = $settingModel->getAllAsAssoc();
                $siteUrl = rtrim($settings['site_url'] ?? 'https://rentsmart.co.ke', '/');
                $logoUrl = isset($settings['site_logo']) && $settings['site_logo'] ? ($siteUrl . '/public/assets/images/' . $settings['site_logo']) : '';
                $footer = '<div style="margin-top:30px;font-size:12px;color:#888;text-align:center;">Powered by <a href="https://timestentechnologies.co.ke" target="_blank" style="color:#888;text-decoration:none;">Timesten Technologies</a></div>';

                $db = $notice->getDb();
                $recipients = [];

                $addRecipient = function ($email, $name) use (&$recipients) {
                    $email = trim((string)$email);
                    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $key = strtolower($email);
                        if (!isset($recipients[$key])) {
                            $recipients[$key] = ['email' => $email, 'name' => (string)($name ?: 'User')];
                        }
                    }
                };

                if (empty($propertyId) && empty($unitId) && empty($tenantId)) {
                    $stmtU = $db->query("SELECT email, name FROM users WHERE email IS NOT NULL AND email <> ''");
                    foreach ($stmtU->fetchAll(\PDO::FETCH_ASSOC) as $u) { $addRecipient($u['email'] ?? '', $u['name'] ?? ''); }
                    $stmtT = $db->query("SELECT email, name FROM tenants WHERE email IS NOT NULL AND email <> ''");
                    foreach ($stmtT->fetchAll(\PDO::FETCH_ASSOC) as $t) { $addRecipient($t['email'] ?? '', $t['name'] ?? ''); }
                } else {
                    $effectivePropertyId = $propertyId;
                    if (!$effectivePropertyId && !empty($unitId)) {
                        $stmtP = $db->prepare("SELECT property_id FROM units WHERE id = ? LIMIT 1");
                        $stmtP->execute([(int)$unitId]);
                        $rowP = $stmtP->fetch(\PDO::FETCH_ASSOC);
                        $effectivePropertyId = (int)($rowP['property_id'] ?? 0) ?: null;
                    }

                    if (!empty($tenantId)) {
                        $stmt = $db->prepare("SELECT email, name FROM tenants WHERE id = ? LIMIT 1");
                        $stmt->execute([(int)$tenantId]);
                        if ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) { $addRecipient($r['email'] ?? '', $r['name'] ?? ''); }
                    }

                    if (!empty($unitId)) {
                        $stmt = $db->prepare("SELECT t.email, t.name FROM leases l JOIN tenants t ON l.tenant_id = t.id WHERE l.unit_id = ? AND l.status = 'active'");
                        $stmt->execute([(int)$unitId]);
                        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) { $addRecipient($r['email'] ?? '', $r['name'] ?? ''); }
                    }

                    if (!empty($effectivePropertyId)) {
                        $stmt = $db->prepare("SELECT t.email, t.name FROM leases l JOIN units u ON l.unit_id = u.id JOIN tenants t ON l.tenant_id = t.id WHERE u.property_id = ? AND l.status = 'active'");
                        $stmt->execute([(int)$effectivePropertyId]);
                        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) { $addRecipient($r['email'] ?? '', $r['name'] ?? ''); }

                        $prop = (new Property())->getById((int)$effectivePropertyId);
                        if ($prop) {
                            $userModel = new User();
                            foreach (['caretaker_user_id','owner_id','manager_id','agent_id'] as $key) {
                                if (!empty($prop[$key])) {
                                    $u = $userModel->find((int)$prop[$key]);
                                    if ($u && !empty($u['email'])) { $addRecipient($u['email'], $u['name'] ?? ''); }
                                }
                            }
                        }
                    }
                }

                if (!empty($recipients)) {
                    $author = (new User())->find((int)$userId);
                    $authorName = (string)($author['name'] ?? 'User');

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

                    foreach ($recipients as $rec) {
                        $mail->clearAddresses();
                        $mail->addAddress($rec['email'], $rec['name']);
                        $mail->Subject = 'New notice: ' . $title;
                        $mail->Body =
                            '<div style="max-width:520px;margin:auto;border:1px solid #eee;padding:24px;font-family:sans-serif;">'
                            . ($logoUrl ? '<div style="text-align:center;margin-bottom:24px;"><img src="' . $logoUrl . '" alt="Logo" style="max-width:180px;max-height:80px;"></div>' : '') .
                            '<p style="font-size:16px;">Dear ' . htmlspecialchars($rec['name']) . ',</p>' .
                            '<p>A new notice has been posted by <strong>' . htmlspecialchars($authorName) . '</strong> on RentSmart.</p>' .
                            '<h3 style="margin:12px 0;">' . htmlspecialchars($title) . '</h3>' .
                            '<div style="margin:10px 0 18px;white-space:pre-wrap;">' . nl2br(htmlspecialchars($body)) . '</div>' .
                            '<p><a href="' . $siteUrl . '/notices" style="background:#0061f2;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;display:inline-block;">View in dashboard</a></p>' .
                            '<p>Thank you,<br>RentSmart Team</p>' .
                            $footer .
                            '</div>';
                        try { $mail->send(); } catch (\PHPMailer\PHPMailer\Exception $e) { error_log('Notice mail send error: ' . $e->getMessage()); }
                    }
                }
            } catch (\Exception $e) {
                error_log('Notice mail error: ' . $e->getMessage());
            }

            $_SESSION['flash_message'] = 'Notice posted successfully';
            $_SESSION['flash_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
        redirect('/notices');
    }

    public function delete($id)
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) { throw new \Exception('Unauthorized'); }
            $user = new User();
            $user->find($userId);
            if (!($user->isAdmin())) { throw new \Exception('Only admin can delete'); }
            $stmt = (new Notice())->getDb()->prepare('DELETE FROM notices WHERE id = ?');
            $stmt->execute([(int)$id]);
            $_SESSION['flash_message'] = 'Notice deleted';
            $_SESSION['flash_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
        redirect('/notices');
    }

    public function tenant()
    {
        // If a staff user hits the tenant endpoint (e.g. due to cached links), redirect to staff notices
        if (!empty($_SESSION['user_id']) && empty($_SESSION['impersonating'])) {
            redirect('/notices');
            return;
        }

        // For tenant portal
        $tenantId = $_SESSION['tenant_id'] ?? null;
        if (!$tenantId) { redirect('/tenant/login'); return; }

        $tenantModel = new Tenant();
        $tenant = $tenantModel->find((int)$tenantId);

        $settingModel = new Setting();
        $settings = $settingModel->getAllAsAssoc();
        $siteLogo = isset($settings['site_logo']) && $settings['site_logo']
            ? BASE_URL . '/public/assets/images/' . $settings['site_logo']
            : BASE_URL . '/public/assets/images/logo.svg';
        $siteFavicon = isset($settings['site_favicon']) && $settings['site_favicon']
            ? BASE_URL . '/public/assets/images/' . $settings['site_favicon']
            : BASE_URL . '/public/assets/images/site_favicon_1750832003.png';

        $noticeModel = new Notice();
        $notices = $noticeModel->getVisibleForTenant((int)$tenantId);
        require 'views/tenant/notices.php';
    }
}
