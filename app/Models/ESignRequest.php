<?php

namespace App\Models;

class ESignRequest extends Model
{
    protected $table = 'esign_requests';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    private function ensureTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS esign_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            message TEXT NULL,
            requester_user_id INT NOT NULL,
            recipient_type ENUM('user','tenant') NOT NULL,
            recipient_id INT NOT NULL,
            entity_type VARCHAR(50) NULL,
            entity_id INT NULL,
            token VARCHAR(64) NOT NULL UNIQUE,
            status ENUM('pending','signed','declined','expired') NOT NULL DEFAULT 'pending',
            expires_at DATETIME NULL,
            signed_at DATETIME NULL,
            declined_at DATETIME NULL,
            signer_name VARCHAR(150) NULL,
            signature_image LONGTEXT NULL,
            signature_type ENUM('draw','upload','initials') NULL,
            initials VARCHAR(50) NULL,
            document_path VARCHAR(255) NULL,
            signed_document_path VARCHAR(255) NULL,
            signature_ip VARCHAR(64) NULL,
            signature_user_agent VARCHAR(255) NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_requester (requester_user_id),
            INDEX idx_recipient (recipient_type, recipient_id),
            INDEX idx_entity (entity_type, entity_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->db->exec($sql);
    }

    public function createRequest(array $data)
    {
        $token = bin2hex(random_bytes(16));
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (title, message, requester_user_id, recipient_type, recipient_id, entity_type, entity_id, token, status, expires_at, document_path) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $data['title'],
            $data['message'] ?? null,
            (int)$data['requester_user_id'],
            $data['recipient_type'],
            (int)$data['recipient_id'],
            $data['entity_type'] ?? null,
            $data['entity_id'] ?? null,
            $token,
            'pending',
            $data['expires_at'] ?? null,
            $data['document_path'] ?? null,
        ]);
        return ['id' => (int)$this->db->lastInsertId(), 'token' => $token];
    }

    public function getByToken($token)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE token = ? LIMIT 1");
        $stmt->execute([$token]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        return $this->find($id);
    }

    public function listForUser($userId)
    {
        $sql = "SELECT er.*,
                       CASE WHEN er.recipient_type='tenant' THEN t.name ELSE u2.name END recipient_name,
                       u.name AS requester_name
                FROM {$this->table} er
                LEFT JOIN users u ON er.requester_user_id = u.id
                LEFT JOIN users u2 ON (er.recipient_type='user' AND er.recipient_id = u2.id)
                LEFT JOIN tenants t ON (er.recipient_type='tenant' AND er.recipient_id = t.id)
                WHERE er.requester_user_id = ? OR (er.recipient_type='user' AND er.recipient_id = ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int)$userId, (int)$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function markSigned($token, $name, $image, $ip = null, $ua = null, $sigType = null, $initials = null)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} 
            SET status='signed', signer_name=?, signature_image=?, signature_type=?, initials=?, signed_at=NOW(), signature_ip=?, signature_user_agent=? 
            WHERE token=?");
        return $stmt->execute([$name, $image, $sigType, $initials, $ip, $ua, $token]);
    }

    public function markDeclined($token)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status='declined', declined_at=NOW() WHERE token=?");
        return $stmt->execute([$token]);
    }

    public function setSignedDocumentPath($token, $path)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET signed_document_path = ? WHERE token = ?");
        return $stmt->execute([$path, $token]);
    }
}
