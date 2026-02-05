<?php

namespace App\Controllers;

use App\Models\ActivityLog;
use App\Models\Notice;
use App\Models\Message;

class NotificationsController
{
    public function feed()
    {
        header('Content-Type: application/json');
        try {
            if (!isset($_SESSION)) { @session_start(); }
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) { echo json_encode(['success' => true, 'items' => []]); return; }

            $items = [];

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
