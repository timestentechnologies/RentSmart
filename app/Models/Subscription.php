<?php

namespace App\Models;

use PDO;
use DateTime;

class Subscription extends Model
{
    protected $table = 'subscriptions';

    /**
     * Create a new subscription for a user
     */
    public function createSubscription($userId, $planId)
    {
        $plan = $this->getPlan($planId);
        if (!$plan) {
            throw new \Exception('Invalid subscription plan');
        }

        $now = new DateTime();
        $trialEndsAt = (new DateTime())->modify('+7 days');

        $data = [
            'user_id' => $userId,
            'plan_id' => $planId,
            'plan_type' => $plan['name'],
            'status' => 'trialing',
            'trial_ends_at' => $trialEndsAt->format('Y-m-d H:i:s'),
            'current_period_starts_at' => $now->format('Y-m-d H:i:s'),
            'current_period_ends_at' => $trialEndsAt->format('Y-m-d H:i:s')
        ];

        $subscriptionId = $this->create($data);

        // Update user's trial end date
        $userModel = new User();
        $userModel->update($userId, [
            'is_subscribed' => true,
            'trial_ends_at' => $trialEndsAt->format('Y-m-d H:i:s')
        ]);

        return $subscriptionId;
    }

    /**
     * Get subscription plan details
     */
    public function getPlan($planId)
    {
        $stmt = $this->db->prepare("SELECT * FROM subscription_plans WHERE id = ?");
        $stmt->execute([$planId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all subscription plans
     */
    public function getAllPlans()
    {
        $stmt = $this->db->query("SELECT * FROM subscription_plans ORDER BY price ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user's active subscription
     */
    public function getUserSubscription($userId)
    {
        $stmt = $this->db->prepare("
            SELECT s.*, sp.* 
            FROM {$this->table} s
            JOIN subscription_plans sp ON s.plan_type = sp.name
            WHERE s.user_id = ? 
            ORDER BY s.created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check if user's subscription is active
     */
    public function isSubscriptionActive($userId)
    {
        $user = (new User())->find($userId);
        
        // If user is an administrator, always return true
        if ($user && ($user['role'] === 'admin' || $user['role'] === 'administrator')) {
            return true;
        }

        $subscription = $this->getUserSubscription($userId);
        if (!$subscription) {
            return false;
        }

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $endDate = new \DateTime($subscription['current_period_ends_at'], new \DateTimeZone('UTC'));

        // If in trial period
        if ($subscription['status'] === 'trialing') {
            $trialEnd = new \DateTime($subscription['trial_ends_at'], new \DateTimeZone('UTC'));
            return $now <= $trialEnd;
        }

        // If subscription is active
        return $subscription['status'] === 'active' && $now <= $endDate;
    }

    public function isEnterprisePlan($userId): bool
    {
        try {
            $sub = $this->getUserSubscription($userId);
            $name = strtolower((string)($sub['name'] ?? ($sub['plan_type'] ?? '')));
            return $name === 'enterprise';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Update subscription status
     */
    public function updateSubscriptionStatus($userId, $status, $endDate = null)
    {
        $data = ['status' => $status];
        if ($endDate) {
            $data['current_period_ends_at'] = $endDate;
        }

        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET status = :status" . ($endDate ? ", current_period_ends_at = :end_date" : "") . "
            WHERE user_id = :user_id 
            ORDER BY created_at DESC 
            LIMIT 1
        ");

        $params = ['status' => $status, 'user_id' => $userId];
        if ($endDate) {
            $params['end_date'] = $endDate;
        }

        return $stmt->execute($params);
    }

    /**
     * Renew subscription
     */
    public function renewSubscription($userId)
    {
        $subscription = $this->getUserSubscription($userId);
        if (!$subscription) {
            throw new \Exception('No subscription found');
        }

        $now = new DateTime();
        $endDate = (new DateTime())->modify('+1 month');

        return $this->updateSubscriptionStatus($userId, 'active', $endDate->format('Y-m-d H:i:s'));
    }

    public function getPlanById($planId)
    {
        $sql = "SELECT * FROM subscription_plans WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$planId]);
        return $stmt->fetch();
    }

    public function updateOrCreateSubscription($userId, $data)
    {
        // Get plan details to ensure we have the correct plan name
        $plan = $this->getPlanById($data['plan_id']);
        if (!$plan) {
            throw new \Exception('Invalid subscription plan');
        }

        // Check if user has an existing subscription
        $existingSub = $this->getUserSubscription($userId);
        
        if ($existingSub) {
            // Update existing subscription (only the latest one)
            $sql = "UPDATE subscriptions SET 
                    plan_id = ?,
                    plan_type = ?,
                    status = ?,
                    current_period_starts_at = ?,
                    current_period_ends_at = ?,
                    updated_at = NOW()
                    WHERE user_id = ? 
                    ORDER BY id DESC
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['plan_id'],
                $plan['name'],
                $data['status'],
                $data['current_period_starts_at'],
                $data['current_period_ends_at'],
                $userId
            ]);
        } else {
            // Create new subscription
            $sql = "INSERT INTO subscriptions 
                    (user_id, plan_id, plan_type, status, current_period_starts_at, current_period_ends_at, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $userId,
                $data['plan_id'],
                $plan['name'],
                $data['status'],
                $data['current_period_starts_at'],
                $data['current_period_ends_at']
            ]);
        }
    }

    public function getAllSubscriptions()
    {
        $sql = "SELECT s.*, 
                u.name as user_name,
                u.email as user_email,
                sp.name as plan_name,
                sp.price as plan_price,
                (SELECT COUNT(*) FROM subscription_payments WHERE subscription_id = s.id) as payment_count,
                (SELECT COUNT(*) FROM properties p 
                   WHERE p.owner_id = s.user_id 
                      OR p.manager_id = s.user_id 
                      OR p.agent_id = s.user_id) as property_count
                FROM subscriptions s
                LEFT JOIN users u ON s.user_id = u.id
                LEFT JOIN subscription_plans sp ON s.plan_type = sp.name
                ORDER BY s.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updatePlan($planId, $data)
    {
        // Ensure optional limit columns exist
        try {
            $this->db->query("SHOW COLUMNS FROM subscription_plans LIKE 'property_limit'")->fetch(PDO::FETCH_ASSOC) ?: $this->db->exec("ALTER TABLE subscription_plans ADD COLUMN property_limit INT NULL DEFAULT NULL");
        } catch (\Exception $e) {}
        try {
            $this->db->query("SHOW COLUMNS FROM subscription_plans LIKE 'unit_limit'")->fetch(PDO::FETCH_ASSOC) ?: $this->db->exec("ALTER TABLE subscription_plans ADD COLUMN unit_limit INT NULL DEFAULT NULL");
        } catch (\Exception $e) {}
        try {
            $this->db->query("SHOW COLUMNS FROM subscription_plans LIKE 'listing_limit'")->fetch(PDO::FETCH_ASSOC) ?: $this->db->exec("ALTER TABLE subscription_plans ADD COLUMN listing_limit INT NULL DEFAULT NULL");
        } catch (\Exception $e) {}

        $fields = [];
        $params = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[$key] = $value;
        }
        $params['id'] = $planId;
        $sql = "UPDATE subscription_plans SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteByUserId($userId)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }

    /**
     * Create a new subscription payment record
     */
    public function createPayment($data)
    {
        try {
            // Create the subscription payment record
            $sql = "INSERT INTO subscription_payments 
                    (user_id, subscription_id, amount, payment_method, status, transaction_reference, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            error_log('Creating subscription payment with data: ' . json_encode([
                'user_id' => $data['user_id'],
                'subscription_id' => $data['subscription_id'],
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'status' => $data['status'] ?? 'pending',
                'transaction_reference' => $data['transaction_reference'] ?? null
            ]));
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['user_id'],
                $data['subscription_id'],
                $data['amount'],
                $data['payment_method'],
                $data['status'] ?? 'pending',
                $data['transaction_reference'] ?? null
            ]);
            
            $paymentId = $this->db->lastInsertId();
            error_log('Created subscription payment with ID: ' . $paymentId);

            // If it's a manual M-Pesa payment, create the record
            if ($data['payment_method'] === 'mpesa' && isset($data['mpesa_code'])) {
                $mpesaSql = "INSERT INTO manual_mpesa_payments 
                            (payment_id, phone_number, transaction_code, amount, verification_status, created_at, updated_at)
                            VALUES (?, ?, ?, ?, 'pending', NOW(), NOW())";
                
                error_log('Creating manual M-Pesa payment with data: ' . json_encode([
                    'payment_id' => $paymentId,
                    'phone_number' => $data['mpesa_phone'],
                    'transaction_code' => $data['mpesa_code'],
                    'amount' => $data['amount']
                ]));
                
                $mpesaStmt = $this->db->prepare($mpesaSql);
                $mpesaStmt->execute([
                    $paymentId,
                    $data['mpesa_phone'],
                    $data['mpesa_code'],
                    $data['amount']
                ]);
                
                error_log('Created manual M-Pesa payment record');
            }

            return $paymentId;
        } catch (\Exception $e) {
            error_log('Payment creation error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    public function findByUserId($userId)
    {
        $sql = "SELECT * FROM subscriptions WHERE user_id = ? ORDER BY id DESC LIMIT 1";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getActiveByUserAndPlan($userId, $planId)
    {
        $sql = "SELECT * FROM subscriptions WHERE user_id = ? AND plan_id = ? ORDER BY id DESC LIMIT 1";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([$userId, $planId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get subscriptions expiring in N days (status active or trialing)
     */
    public function getExpiringInDays($days)
    {
        $sql = "SELECT * FROM subscriptions WHERE status IN ('active', 'trialing') AND DATE(current_period_ends_at) = DATE(DATE_ADD(UTC_TIMESTAMP(), INTERVAL ? DAY))";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get subscriptions that expired on a specific date (status not renewed)
     */
    public function getExpiredOnDate($date)
    {
        $sql = "SELECT * FROM subscriptions WHERE status IN ('expired', 'inactive') AND DATE(current_period_ends_at) = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$date]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get subscriptions that expired before a specific date and not renewed
     */
    public function getExpiredBeforeDate($date)
    {
        $sql = "SELECT * FROM subscriptions WHERE status IN ('expired', 'inactive') AND DATE(current_period_ends_at) < ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$date]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
} 