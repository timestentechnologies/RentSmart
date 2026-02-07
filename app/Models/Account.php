<?php

namespace App\Models;

class Account extends Model
{
    protected $table = 'accounts';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
        $this->ensureDefaults();
    }

    private function ensureTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS accounts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            type ENUM('asset','liability','equity','revenue','expense') NOT NULL,
            parent_id INT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_type (type),
            INDEX idx_parent (parent_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->db->exec($sql);
    }

    private function ensureDefaults()
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM accounts");
        $count = (int)$stmt->fetchColumn();
        if ($count === 0) {
            $defaults = [
                ['1000','Cash','asset'],
                ['1100','Accounts Receivable','asset'],
                ['2000','Accounts Payable','liability'],
                ['3000','Owner\'s Equity','equity'],
                ['4000','Rental Income','revenue'],
                ['5000','Maintenance Expense','expense'],
            ];
            $ins = $this->db->prepare("INSERT INTO accounts (code,name,type) VALUES (?,?,?)");
            foreach ($defaults as $d) { $ins->execute($d); }
        }
    }

    public function getAll()
    {
        $stmt = $this->db->query("SELECT * FROM accounts ORDER BY type, code");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getByType($type)
    {
        $stmt = $this->db->prepare("SELECT * FROM accounts WHERE type = ? ORDER BY code");
        $stmt->execute([$type]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findByCode($code)
    {
        $stmt = $this->db->prepare("SELECT * FROM accounts WHERE code = ? LIMIT 1");
        $stmt->execute([$code]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function findFirstByType(string $type)
    {
        $stmt = $this->db->prepare("SELECT * FROM accounts WHERE type = ? AND is_active = 1 ORDER BY code LIMIT 1");
        $stmt->execute([$type]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function ensureByCode(string $code, string $name, string $type)
    {
        $existing = $this->findByCode($code);
        if ($existing) return $existing;

        $ins = $this->db->prepare("INSERT INTO accounts (code,name,type) VALUES (?,?,?)");
        $ins->execute([$code, $name, $type]);
        return $this->findByCode($code);
    }
}
