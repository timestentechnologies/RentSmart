<?php

namespace App\Models;

class EmployeePayment extends Model
{
    protected $table = 'employee_payments';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    private function ensureTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS employee_payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            pay_date DATE NOT NULL,
            payment_method ENUM('cash','check','bank_transfer','card','mpesa','other') NOT NULL DEFAULT 'cash',
            source_of_funds ENUM('rent_balance','cash','bank','mpesa','owner_funds','other') NOT NULL DEFAULT 'cash',
            notes TEXT NULL,
            expense_id INT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->db->exec($sql);
    }

    public function insertPayment(array $data)
    {
        return $this->insert($data);
    }
}
