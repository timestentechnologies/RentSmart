<?php

namespace App\Models;

use DateTime;
use PDO;

class PasswordReset extends Model
{
    protected $table = 'password_resets';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    private function ensureTable()
    {
        // Create table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `email` VARCHAR(150) NOT NULL,
            `token` VARCHAR(64) NOT NULL,
            `expires_at` DATETIME NOT NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP(),
            PRIMARY KEY (`id`),
            INDEX `idx_email` (`email`),
            INDEX `idx_token` (`token`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->db->exec($sql);
    }

    public function createToken(string $email, int $ttlMinutes = 60): string
    {
        // Invalidate previous tokens for this email
        $this->deleteByEmail($email);

        $token = bin2hex(random_bytes(32)); // 64-char hex
        $expiresAt = (new DateTime("+{$ttlMinutes} minutes"))->format('Y-m-d H:i:s');
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $token, $expiresAt]);
        return $token;
    }

    public function findByToken(string $token)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE token = ? LIMIT 1");
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        // Check expiry
        if (strtotime($row['expires_at']) < time()) {
            $this->deleteByToken($token);
            return null;
        }
        return $row;
    }

    public function deleteByToken(string $token): void
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE token = ?");
        $stmt->execute([$token]);
    }

    public function deleteByEmail(string $email): void
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
    }

    public function purgeExpired(): void
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE expires_at < NOW()");
        $stmt->execute();
    }
}
