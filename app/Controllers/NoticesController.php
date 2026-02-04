<?php

namespace App\Controllers;

use App\Models\Notice;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;

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
            $notice->create([
                'user_id' => $userId,
                'title' => $title,
                'body' => $body,
                'property_id' => $propertyId,
                'unit_id' => $unitId,
                'tenant_id' => $tenantId,
                'pinned' => $pinned,
            ]);

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
        // For tenant portal
        $tenantId = $_SESSION['tenant_id'] ?? null;
        if (!$tenantId) { redirect('/tenant/login'); return; }
        $noticeModel = new Notice();
        $notices = $noticeModel->getVisibleForTenant((int)$tenantId);
        require 'views/tenant/notices.php';
    }
}
