<?php

namespace App\Models;

use App\Database\Connection;
use PDO;

class MpesaTransaction extends Model
{
    protected $table = 'mpesa_transactions';

    public function create($data)
    {
        if (isset($data['merchant_request_id'])) {
            // STK Push payment
        $sql = "INSERT INTO mpesa_transactions 
                (payment_id, merchant_request_id, checkout_request_id, phone_number, amount) 
                VALUES (:payment_id, :merchant_request_id, :checkout_request_id, :phone_number, :amount)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'payment_id' => $data['payment_id'],
            'merchant_request_id' => $data['merchant_request_id'],
            'checkout_request_id' => $data['checkout_request_id'],
            'phone_number' => $data['phone_number'],
            'amount' => $data['amount']
        ]);
        } else {
            // Manual M-Pesa payment
            $sql = "INSERT INTO manual_mpesa_payments 
                    (payment_id, phone_number, transaction_code, amount, verification_status) 
                    VALUES (:payment_id, :phone_number, :transaction_code, :amount, :verification_status)";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'payment_id' => $data['payment_id'],
                'phone_number' => $data['phone_number'],
                'transaction_code' => $data['mpesa_receipt_number'],
                'amount' => $data['amount'],
                'verification_status' => 'pending'
            ]);
        }
    }

    public function update($id, $data)
    {
        $sql = "UPDATE mpesa_transactions SET 
                status = :status,
                mpesa_receipt_number = :mpesa_receipt_number,
                transaction_date = :transaction_date,
                result_code = :result_code,
                result_description = :result_description
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'status' => $data['status'],
            'mpesa_receipt_number' => $data['mpesa_receipt_number'],
            'transaction_date' => $data['transaction_date'],
            'result_code' => $data['result_code'],
            'result_description' => $data['result_description'],
            'id' => $id
        ]);
    }

    public function findByCheckoutRequestId($checkoutRequestId)
    {
        $sql = "SELECT * FROM mpesa_transactions WHERE checkout_request_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$checkoutRequestId]);
        return $stmt->fetch();
    }

    public function findByPaymentId($paymentId)
    {
        $sql = "SELECT * FROM mpesa_transactions WHERE payment_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$paymentId]);
        return $stmt->fetch();
    }

    public function getAllTransactions()
    {
        $sql = "SELECT mt.*, 
                sp.amount as payment_amount,
                sp.status as payment_status,
                u.name as user_name,
                u.email as user_email,
                s.plan_type
                FROM mpesa_transactions mt
                LEFT JOIN subscription_payments sp ON mt.payment_id = sp.id
                LEFT JOIN users u ON sp.user_id = u.id
                LEFT JOIN subscriptions s ON sp.subscription_id = s.id
                ORDER BY mt.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTransactionDetails($id)
    {
        $sql = "SELECT mt.*, 
                sp.amount as payment_amount,
                sp.status as payment_status,
                u.name as user_name,
                u.email as user_email,
                s.plan_type,
                s.status as subscription_status
                FROM mpesa_transactions mt
                LEFT JOIN subscription_payments sp ON mt.payment_id = sp.id
                LEFT JOIN users u ON sp.user_id = u.id
                LEFT JOIN subscriptions s ON sp.subscription_id = s.id
                WHERE mt.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
} 