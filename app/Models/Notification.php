<?php

namespace App\Models;

use App\Services\WebPushService;

class Notification extends Model
{
    protected $table = 'notifications';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    private function ensureTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recipient_type ENUM('user','tenant') NOT NULL,
            recipient_id INT NOT NULL,
            actor_type ENUM('user','tenant') NULL,
            actor_id INT NULL,
            title VARCHAR(255) NOT NULL,
            body TEXT NULL,
            link VARCHAR(500) NULL,
            entity_type VARCHAR(60) NULL,
            entity_id INT NULL,
            payload JSON NULL,
            read_at DATETIME NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_recipient (recipient_type, recipient_id, read_at),
            INDEX idx_created (created_at),
            INDEX idx_entity (entity_type, entity_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->db->exec($sql);
    }

    public function createNotification(array $data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table}
             (recipient_type, recipient_id, actor_type, actor_id, title, body, link, entity_type, entity_id, payload)
             VALUES (?,?,?,?,?,?,?,?,?,?)"
        );

        $payload = null;
        if (array_key_exists('payload', $data)) {
            $payload = is_string($data['payload']) ? $data['payload'] : json_encode($data['payload']);
        }

        $stmt->execute([
            $data['recipient_type'],
            (int)$data['recipient_id'],
            $data['actor_type'] ?? null,
            isset($data['actor_id']) ? (int)$data['actor_id'] : null,
            $data['title'],
            $data['body'] ?? null,
            $data['link'] ?? null,
            $data['entity_type'] ?? null,
            isset($data['entity_id']) ? (int)$data['entity_id'] : null,
            $payload,
        ]);

        $id = (int)$this->db->lastInsertId();

        // Best-effort Web Push (never block core flow)
        try {
            $push = new WebPushService();
            $push->sendToRecipient(
                (string)$data['recipient_type'],
                (int)$data['recipient_id'],
                [
                    'id' => $id,
                    'title' => (string)($data['title'] ?? ''),
                    'body' => (string)($data['body'] ?? ''),
                    'link' => (string)($data['link'] ?? ''),
                    'entity_type' => $data['entity_type'] ?? null,
                    'entity_id' => isset($data['entity_id']) ? (int)$data['entity_id'] : null,
                ]
            );
        } catch (\Throwable $t) {
        }

        return $id;
    }

    public function getUnreadCount(string $recipientType, int $recipientId)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS c FROM {$this->table} WHERE recipient_type = ? AND recipient_id = ? AND read_at IS NULL"
        );
        $stmt->execute([$recipientType, $recipientId]);
        return (int)($stmt->fetch(\PDO::FETCH_ASSOC)['c'] ?? 0);
    }

    public function listForRecipient(string $recipientType, int $recipientId, ?string $status = null, int $limit = 20, int $offset = 0)
    {
        $where = "recipient_type = ? AND recipient_id = ?";
        $params = [$recipientType, $recipientId];

        if ($status === 'unread') {
            $where .= " AND read_at IS NULL";
        } elseif ($status === 'read') {
            $where .= " AND read_at IS NOT NULL";
        }

        $sql = "SELECT id, title, body, link, entity_type, entity_id, payload, read_at, created_at
                FROM {$this->table}
                WHERE {$where}
                ORDER BY id DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $params[0]);
        $stmt->bindValue(2, $params[1], \PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, \PDO::PARAM_INT);
        $stmt->bindValue(4, $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$r) {
            if (!empty($r['payload']) && is_string($r['payload'])) {
                $decoded = json_decode($r['payload'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $r['payload'] = $decoded;
                }
            }
        }

        return $rows;
    }

    public function markRead(int $id, string $recipientType, int $recipientId)
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET read_at = COALESCE(read_at, NOW()) WHERE id = ? AND recipient_type = ? AND recipient_id = ?"
        );
        $stmt->execute([$id, $recipientType, $recipientId]);
        return $stmt->rowCount() > 0;
    }

    public function markAllRead(string $recipientType, int $recipientId)
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET read_at = NOW() WHERE recipient_type = ? AND recipient_id = ? AND read_at IS NULL"
        );
        $stmt->execute([$recipientType, $recipientId]);
        return (int)$stmt->rowCount();
    }
}
