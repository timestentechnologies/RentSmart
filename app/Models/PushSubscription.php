<?php

namespace App\Models;

class PushSubscription extends Model
{
    protected $table = 'push_subscriptions';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    private function ensureTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recipient_type ENUM('user','tenant') NOT NULL,
            recipient_id INT NOT NULL,
            endpoint VARCHAR(1024) NOT NULL,
            p256dh VARCHAR(255) NULL,
            auth VARCHAR(255) NULL,
            content_encoding VARCHAR(50) NULL,
            user_agent VARCHAR(255) NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_recipient_endpoint (recipient_type, recipient_id, endpoint),
            INDEX idx_recipient (recipient_type, recipient_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->db->exec($sql);
    }

    public function upsert(string $recipientType, int $recipientId, array $sub, ?string $userAgent = null)
    {
        $endpoint = (string)($sub['endpoint'] ?? '');
        $keys = is_array($sub['keys'] ?? null) ? $sub['keys'] : [];
        $p256dh = isset($keys['p256dh']) ? (string)$keys['p256dh'] : null;
        $auth = isset($keys['auth']) ? (string)$keys['auth'] : null;
        $contentEncoding = isset($sub['contentEncoding']) ? (string)$sub['contentEncoding'] : null;

        if ($endpoint === '') {
            throw new \Exception('Missing endpoint');
        }

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} (recipient_type, recipient_id, endpoint, p256dh, auth, content_encoding, user_agent)
             VALUES (?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE p256dh=VALUES(p256dh), auth=VALUES(auth), content_encoding=VALUES(content_encoding), user_agent=VALUES(user_agent)"
        );
        $stmt->execute([
            $recipientType,
            (int)$recipientId,
            $endpoint,
            $p256dh,
            $auth,
            $contentEncoding,
            $userAgent,
        ]);

        return true;
    }

    public function deleteByEndpoint(string $recipientType, int $recipientId, string $endpoint)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE recipient_type = ? AND recipient_id = ? AND endpoint = ?");
        $stmt->execute([$recipientType, (int)$recipientId, $endpoint]);
        return $stmt->rowCount() > 0;
    }

    public function listForRecipient(string $recipientType, int $recipientId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE recipient_type = ? AND recipient_id = ? ORDER BY id DESC");
        $stmt->execute([$recipientType, (int)$recipientId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function deleteById(int $id)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->execute([(int)$id]);
        return $stmt->rowCount() > 0;
    }
}
