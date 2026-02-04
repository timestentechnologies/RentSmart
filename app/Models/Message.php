<?php

namespace App\Models;

class Message extends Model
{
    protected $table = 'messages';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    private function ensureTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_type ENUM('user','tenant') NOT NULL,
            sender_id INT NOT NULL,
            receiver_type ENUM('user','tenant') NOT NULL,
            receiver_id INT NOT NULL,
            body TEXT NOT NULL,
            read_at DATETIME NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_sender (sender_type, sender_id),
            INDEX idx_receiver (receiver_type, receiver_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->db->exec($sql);
    }

    public function insertMessage(array $data)
    {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (sender_type, sender_id, receiver_type, receiver_id, body) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['sender_type'],
            (int)$data['sender_id'],
            $data['receiver_type'],
            (int)$data['receiver_id'],
            $data['body']
        ]);
        return $this->db->lastInsertId();
    }

    public function getConversation(string $aType, int $aId, string $bType, int $bId, int $limit = 200)
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE ((sender_type = ? AND sender_id = ? AND receiver_type = ? AND receiver_id = ?) 
                    OR (sender_type = ? AND sender_id = ? AND receiver_type = ? AND receiver_id = ?))
                ORDER BY created_at ASC, id ASC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $aType);
        $stmt->bindValue(2, $aId, \PDO::PARAM_INT);
        $stmt->bindValue(3, $bType);
        $stmt->bindValue(4, $bId, \PDO::PARAM_INT);
        $stmt->bindValue(5, $bType);
        $stmt->bindValue(6, $bId, \PDO::PARAM_INT);
        $stmt->bindValue(7, $aType);
        $stmt->bindValue(8, $aId, \PDO::PARAM_INT);
        $stmt->bindValue(9, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
