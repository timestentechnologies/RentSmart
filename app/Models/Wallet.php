<?php

namespace App\Models;

class Wallet extends Model
{
    protected $table = 'wallets';

    /**
     * Get wallet by user ID
     */
    public function getByUserId($userId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get or create wallet for user
     */
    public function getOrCreate($userId)
    {
        $wallet = $this->getByUserId($userId);
        if ($wallet) {
            return $wallet;
        }

        // Create new wallet with 0 balance
        $data = [
            'user_id' => $userId,
            'balance' => 0.00,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $this->insert($data);
        return $this->getByUserId($userId);
    }

    /**
     * Add funds to wallet
     */
    public function add($userId, $amount, $description = '', $referenceType = null, $referenceId = null)
    {
        $wallet = $this->getOrCreate($userId);
        $newBalance = (float)$wallet['balance'] + (float)$amount;

        // Update wallet balance
        $sql = "UPDATE {$this->table} SET balance = ?, updated_at = NOW() WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$newBalance, $userId]);

        // Log transaction
        $this->logTransaction($userId, $amount, 'credit', $description, $referenceType, $referenceId);

        return $newBalance;
    }

    /**
     * Deduct funds from wallet
     */
    public function deduct($userId, $amount, $description = '', $referenceType = null, $referenceId = null)
    {
        $wallet = $this->getByUserId($userId);
        if (!$wallet) {
            throw new \Exception('Wallet not found');
        }

        $currentBalance = (float)$wallet['balance'];
        $deductAmount = (float)$amount;

        if ($currentBalance < $deductAmount) {
            throw new \Exception('Insufficient wallet balance');
        }

        $newBalance = $currentBalance - $deductAmount;

        // Update wallet balance
        $sql = "UPDATE {$this->table} SET balance = ?, updated_at = NOW() WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$newBalance, $userId]);

        // Log transaction
        $this->logTransaction($userId, -$deductAmount, 'debit', $description, $referenceType, $referenceId);

        return $newBalance;
    }

    /**
     * Log wallet transaction
     */
    private function logTransaction($userId, $amount, $type, $description, $referenceType = null, $referenceId = null)
    {
        try {
            $sql = "INSERT INTO wallet_transactions (user_id, amount, type, description, reference_type, reference_id, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $amount, $type, $description, $referenceType, $referenceId]);
        } catch (\Exception $e) {
            error_log('Failed to log wallet transaction: ' . $e->getMessage());
        }
    }

    /**
     * Get transaction history
     */
    public function getTransactions($userId, $limit = 50)
    {
        $sql = "SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
