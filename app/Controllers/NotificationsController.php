<?php

namespace App\Controllers;

use App\Models\ActivityLog;
use App\Models\Notice;
use App\Models\Message;
use App\Models\Notification;

class NotificationsController
{
    private function json($data, int $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    private function ensureSession()
    {
        if (!isset($_SESSION)) {
            @session_start();
        }
    }

    private function getUserRecipient()
    {
        $this->ensureSession();
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            return null;
        }
        return ['type' => 'user', 'id' => $userId];
    }

    private function getTenantRecipient()
    {
        $this->ensureSession();
        $tenantId = (int)($_SESSION['tenant_id'] ?? 0);
        if ($tenantId <= 0) {
            return null;
        }
        return ['type' => 'tenant', 'id' => $tenantId];
    }

    public function unreadCount()
    {
        try {
            $rec = $this->getUserRecipient();
            if (!$rec) {
                $this->json(['success' => true, 'count' => 0]);
            }
            $model = new Notification();
            $count = $model->getUnreadCount($rec['type'], (int)$rec['id']);
            $this->json(['success' => true, 'count' => $count]);
        } catch (\Throwable $t) {
            $this->json(['success' => false, 'message' => 'Unable to load notifications'], 500);
        }
    }

    public function list()
    {
        try {
            $rec = $this->getUserRecipient();
            if (!$rec) {
                $this->json(['success' => true, 'items' => []]);
            }
            $status = isset($_GET['status']) ? strtolower(trim((string)$_GET['status'])) : null;
            $limit = isset($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 20;
            $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;

            $model = new Notification();
            $items = $model->listForRecipient($rec['type'], (int)$rec['id'], $status, $limit, $offset);
            $this->json(['success' => true, 'items' => $items]);
        } catch (\Throwable $t) {
            $this->json(['success' => false, 'message' => 'Unable to load notifications'], 500);
        }
    }

    public function markRead()
    {
        try {
            $rec = $this->getUserRecipient();
            if (!$rec) {
                $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id <= 0) {
                $this->json(['success' => false, 'message' => 'Missing notification id'], 422);
            }
            $model = new Notification();
            $ok = $model->markRead($id, $rec['type'], (int)$rec['id']);
            $this->json(['success' => true, 'updated' => $ok]);
        } catch (\Throwable $t) {
            $this->json(['success' => false, 'message' => 'Unable to update notification'], 500);
        }
    }

    public function markAllRead()
    {
        try {
            $rec = $this->getUserRecipient();
            if (!$rec) {
                $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            $model = new Notification();
            $n = $model->markAllRead($rec['type'], (int)$rec['id']);
            $this->json(['success' => true, 'updated' => $n]);
        } catch (\Throwable $t) {
            $this->json(['success' => false, 'message' => 'Unable to update notifications'], 500);
        }
    }

    public function tenantUnreadCount()
    {
        try {
            $rec = $this->getTenantRecipient();
            if (!$rec) {
                $this->json(['success' => true, 'count' => 0]);
            }
            $model = new Notification();
            $count = $model->getUnreadCount($rec['type'], (int)$rec['id']);
            $this->json(['success' => true, 'count' => $count]);
        } catch (\Throwable $t) {
            $this->json(['success' => false, 'message' => 'Unable to load notifications'], 500);
        }
    }

    public function tenantList()
    {
        try {
            $rec = $this->getTenantRecipient();
            if (!$rec) {
                $this->json(['success' => true, 'items' => []]);
            }
            $status = isset($_GET['status']) ? strtolower(trim((string)$_GET['status'])) : null;
            $limit = isset($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 20;
            $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;

            $model = new Notification();
            $items = $model->listForRecipient($rec['type'], (int)$rec['id'], $status, $limit, $offset);
            $this->json(['success' => true, 'items' => $items]);
        } catch (\Throwable $t) {
            $this->json(['success' => false, 'message' => 'Unable to load notifications'], 500);
        }
    }

    public function tenantMarkRead()
    {
        try {
            $rec = $this->getTenantRecipient();
            if (!$rec) {
                $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id <= 0) {
                $this->json(['success' => false, 'message' => 'Missing notification id'], 422);
            }
            $model = new Notification();
            $ok = $model->markRead($id, $rec['type'], (int)$rec['id']);
            $this->json(['success' => true, 'updated' => $ok]);
        } catch (\Throwable $t) {
            $this->json(['success' => false, 'message' => 'Unable to update notification'], 500);
        }
    }

    public function tenantMarkAllRead()
    {
        try {
            $rec = $this->getTenantRecipient();
            if (!$rec) {
                $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            $model = new Notification();
            $n = $model->markAllRead($rec['type'], (int)$rec['id']);
            $this->json(['success' => true, 'updated' => $n]);
        } catch (\Throwable $t) {
            $this->json(['success' => false, 'message' => 'Unable to update notifications'], 500);
        }
    }

    public function feed()
    {
        header('Content-Type: application/json');
        try {
            $this->ensureSession();
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) { echo json_encode(['success' => true, 'items' => []]); return; }

            $items = [];

            // Stored notifications
            try {
                $nModel = new Notification();
                $rows = $nModel->listForRecipient('user', (int)$userId, null, 20, 0);
                foreach ($rows as $r) {
                    $items[] = [
                        'type' => 'notification',
                        'title' => (string)($r['title'] ?? ''),
                        'body' => (string)($r['body'] ?? ''),
                        'ts' => strtotime($r['created_at'] ?? 'now'),
                        'icon' => 'bi-bell',
                        'link' => $r['link'] ?? null,
                    ];
                }
            } catch (\Throwable $e) {
            }

            // Activity logs (scoped to user's properties)
            try {
                $logModel = new ActivityLog();
                $logs = $logModel->getLogsForUserScope((int)$userId, []);
                foreach (array_slice($logs, 0, 20) as $l) {
                    $title = 'Activity: ' . ($l['action'] ?? '');
                    $desc = '';
                    if (!empty($l['entity_type'])) {
                        $desc .= ucfirst($l['entity_type']);
                        if (!empty($l['entity_id'])) { $desc .= ' #' . (int)$l['entity_id']; }
                    }
                    if (!empty($l['details'])) { $desc .= ($desc ? ' • ' : '') . (string)$l['details']; }
                    $items[] = [
                        'type' => 'activity',
                        'title' => $title,
                        'body' => $desc,
                        'ts' => strtotime($l['created_at'] ?? 'now'),
                        'icon' => 'bi-activity',
                        'link' => null,
                    ];
                }
            } catch (\Exception $e) { /* ignore */ }

            // Notices visible to this user
            try {
                $noticeModel = new Notice();
                $notices = $noticeModel->getVisibleForUser((int)$userId);
                foreach (array_slice($notices, 0, 10) as $n) {
                    $title = 'Notice: ' . ($n['title'] ?? '');
                    $body = mb_strimwidth((string)($n['body'] ?? ''), 0, 200, '…');
                    $items[] = [
                        'type' => 'notice',
                        'title' => $title,
                        'body' => $body,
                        'ts' => strtotime($n['created_at'] ?? 'now'),
                        'icon' => 'bi-megaphone',
                        'link' => BASE_URL . '/notices',
                    ];
                }
            } catch (\Exception $e) { /* ignore */ }

            // Messages to this user
            try {
                $msgModel = new Message();
                $db = $msgModel->getDb();
                $stmt = $db->prepare("SELECT id, sender_type, sender_id, body, created_at FROM messages WHERE receiver_type = 'user' AND receiver_id = ? ORDER BY id DESC LIMIT 10");
                $stmt->execute([(int)$userId]);
                $msgs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($msgs as $m) {
                    $body = mb_strimwidth((string)($m['body'] ?? ''), 0, 160, '…');
                    $items[] = [
                        'type' => 'message',
                        'title' => 'New Message',
                        'body' => $body,
                        'ts' => strtotime($m['created_at'] ?? 'now'),
                        'icon' => 'bi-chat-dots',
                        'link' => BASE_URL . '/messaging',
                    ];
                }
            } catch (\Exception $e) { /* ignore */ }

            usort($items, function($a,$b){ return ($b['ts'] ?? 0) <=> ($a['ts'] ?? 0); });
            echo json_encode(['success' => true, 'items' => array_slice($items, 0, 20)]);
        } catch (\Throwable $t) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to load notifications']);
        }
    }
}
