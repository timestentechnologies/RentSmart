<?php

namespace App\Models;

class ContactMessageReply extends Model
{
    protected $table = 'contact_message_replies';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    private function ensureTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS contact_message_replies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            contact_message_id INT NOT NULL,
            user_id INT NULL,
            reply_message TEXT NOT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_message (contact_message_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->db->exec($sql);
    }

    public function create(array $data)
    {
        return parent::create($data);
    }

    public function getByMessageId(int $messageId): array
    {
        $stmt = $this->db->prepare("SELECT r.*, u.name AS user_name
                                    FROM {$this->table} r
                                    LEFT JOIN users u ON r.user_id = u.id
                                    WHERE r.contact_message_id = ?
                                    ORDER BY r.id ASC");
        $stmt->execute([(int)$messageId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    }
}
