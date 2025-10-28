<?php

namespace App\Controllers;

use App\Models\Subscription;
use App\Models\User;
use Exception;
use DateTime;

class SubscriptionController
{
    private $subscription;
    private $user;

    public function __construct()
    {
        $this->subscription = new Subscription();
        $this->user = new User();
    }

    public function showRenew()
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                redirect('/home');
            }

            $userId = $_SESSION['user_id'];
            $subscription = $this->subscription->getUserSubscription($userId);
            $plans = $this->subscription->getAllPlans();

            echo view('subscription/renew', [
                'title' => 'Renew Subscription - RentSmart',
                'subscription' => $subscription,
                'plans' => $plans
            ]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            if (getenv('APP_ENV') === 'development') {
                throw $e;
            }
            echo view('errors/500', [
                'title' => '500 Internal Server Error'
            ]);
        }
    }

    public function renew()
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->sendJsonResponse(401, ['error' => 'Please login to continue']);
                exit;
            }

            // Verify CSRF token
            if (!verify_csrf_token()) {
                $this->sendJsonResponse(400, ['error' => 'Invalid security token']);
                exit;
            }

            $userId = $_SESSION['user_id'];
            $planId = filter_input(INPUT_POST, 'plan_id', FILTER_SANITIZE_NUMBER_INT);
            $paymentMethod = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $mpesaPhone = filter_input(INPUT_POST, 'mpesa_phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $mpesaCode = filter_input(INPUT_POST, 'mpesa_code', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            // Validate required fields
            if (!$planId || !$paymentMethod) {
                $this->sendJsonResponse(400, ['error' => 'Please select a plan and payment method']);
                exit;
            }

            // Validate M-Pesa details if payment method is mpesa
            if ($paymentMethod === 'mpesa') {
                if (!$mpesaPhone || !$mpesaCode) {
                    $this->sendJsonResponse(400, ['error' => 'Please provide M-Pesa payment details']);
                    exit;
                }
                
                // Validate phone number format
                if (!preg_match('/^254\d{9}$/', $mpesaPhone)) {
                    $this->sendJsonResponse(400, ['error' => 'Invalid phone number format']);
                    exit;
                }
                
                // Validate M-Pesa code format
                if (!preg_match('/^[A-Z0-9]{10}$/', $mpesaCode)) {
                    $this->sendJsonResponse(400, ['error' => 'Invalid M-Pesa transaction code']);
                    exit;
                }
            }

            // Get plan details
            $plan = $this->subscription->getPlanById($planId);
            if (!$plan) {
                $this->sendJsonResponse(400, ['error' => 'Invalid subscription plan']);
                exit;
            }

            // Calculate correct period start and end
            $subscription = $this->subscription->getUserSubscription($userId);
            $now = new \DateTime();
            $duration = isset($plan['duration']) ? (int)$plan['duration'] : 30; // days
            $newStart = clone $now;

            if ($subscription && $subscription['status'] === 'trialing') {
                $trialEnd = new \DateTime($subscription['trial_ends_at']);
                if ($now < $trialEnd) {
                    $newStart = $trialEnd;
                }
            } elseif ($subscription && isset($subscription['current_period_ends_at'])) {
                $currentEnd = new \DateTime($subscription['current_period_ends_at']);
                if ($now < $currentEnd) {
                    $newStart = $currentEnd;
                }
            }
            $newEnd = (clone $newStart)->modify("+{$duration} days");

            // Get database connection from the model
            $db = $this->subscription->getDb();
            
            try {
                // Start database transaction
                $db->beginTransaction();

                // Update or create the subscription record with correct period
                $this->subscription->updateOrCreateSubscription($userId, [
                    'plan_id' => $planId,
                    'status' => 'pending_verification',
                    'current_period_starts_at' => $newStart->format('Y-m-d H:i:s'),
                    'current_period_ends_at' => $newEnd->format('Y-m-d H:i:s')
                ]);

                // Create payment record
                $paymentSql = "INSERT INTO subscription_payments 
                        (user_id, subscription_id, amount, payment_method, status, transaction_reference, created_at, updated_at)
                        VALUES (?, (SELECT id FROM subscriptions WHERE user_id = ? ORDER BY id DESC LIMIT 1), ?, ?, ?, ?, NOW(), NOW())";
                
                $paymentStmt = $db->prepare($paymentSql);
                $paymentStmt->execute([
                    $userId,
                    $userId,
                    $plan['price'],
                    $paymentMethod,
                    'pending',
                    $mpesaCode
                ]);
                
                $paymentId = $db->lastInsertId();
                
                if (!$paymentId) {
                    throw new \Exception('Failed to create payment record');
                }

                // Create manual M-Pesa payment record
                if ($paymentMethod === 'mpesa') {
                    $mpesaSql = "INSERT INTO manual_mpesa_payments 
                                (payment_id, phone_number, transaction_code, amount, verification_status, created_at, updated_at)
                                VALUES (?, ?, ?, ?, 'pending', NOW(), NOW())";
                    
                    $mpesaStmt = $db->prepare($mpesaSql);
                    $mpesaStmt->execute([
                        $paymentId,
                        $mpesaPhone,
                        $mpesaCode,
                        $plan['price']
                    ]);
                }

                $db->commit();

                // Send renewal confirmation email
                try {
                    $settingModel = new \App\Models\Setting();
                    $settings = $settingModel->getAllAsAssoc();
                    $user = $this->user->find($userId);
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = $settings['smtp_host'] ?? '';
                    $mail->Port = $settings['smtp_port'] ?? 587;
                    $mail->SMTPAuth = true;
                    $mail->Username = $settings['smtp_user'] ?? '';
                    $mail->Password = $settings['smtp_pass'] ?? '';
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->setFrom($settings['smtp_user'] ?? '', $settings['site_name'] ?? 'RentSmart');
                    $mail->isHTML(true);
                    $logoUrl = isset($settings['site_logo']) && $settings['site_logo'] ? (defined('BASE_URL') ? (BASE_URL . '/public/assets/images/' . $settings['site_logo']) : '') : '';
                    $footer = '<div style="margin-top:30px;font-size:12px;color:#888;text-align:center;">Powered by <a href="https://timestentechnologies.co.ke" target="_blank" style="color:#888;text-decoration:none;">Timesten Technologies</a></div>';
                    $mail->clearAddresses();
                    $mail->addAddress($user['email'], $user['name']);
                    $mail->Subject = 'Your RentSmart Subscription Has Been Renewed!';
                    $mail->Body =
                        '<div style="max-width:500px;margin:auto;border:1px solid #eee;padding:24px;font-family:sans-serif;">'
                        . ($logoUrl ? '<div style="text-align:center;margin-bottom:24px;"><img src="' . $logoUrl . '" alt="Logo" style="max-width:180px;max-height:80px;"></div>' : '') .
                        '<p style="font-size:16px;">Dear ' . htmlspecialchars($user['name']) . ',</p>' .
                        '<p>Your RentSmart subscription has been successfully renewed. Thank you for staying with us!</p>' .
                        '<p>You can continue to enjoy all features without interruption.</p>' .
                        '<p>Thank you,<br>RentSmart Team</p>' .
                        $footer .
                        '</div>';
                    $mail->send();
                } catch (\PHPMailer\PHPMailer\Exception $e) {
                    error_log('Renewal email error: ' . $e->getMessage());
                }

                $this->sendJsonResponse(200, [
                    'success' => true,
                    'message' => 'Payment received. Your account has been temporarily activated while we verify your payment.',
                    'redirect' => '/dashboard'
                ]);
                exit;

            } catch (\Exception $e) {
                $db->rollBack();
                error_log('Transaction error: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                
                $this->sendJsonResponse(500, [
                    'error' => 'An error occurred while processing your payment. Please try again.'
                ]);
                exit;
            }

        } catch (\Exception $e) {
            error_log('Subscription renewal error: ' . $e->getMessage());
            
            $this->sendJsonResponse(500, [
                'error' => $e->getMessage() ?: 'An error occurred while processing your payment. Please try again.'
            ]);
            exit;
        }
    }

    private function sendJsonResponse($statusCode, $data)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    private function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    public function status()
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                redirect('/home');
            }

            $userId = $_SESSION['user_id'];
            $subscription = $this->subscription->getUserSubscription($userId);

            if (!$subscription) {
                $_SESSION['flash_message'] = 'No active subscription found';
                $_SESSION['flash_type'] = 'warning';
                redirect('/subscription/renew');
            }

            echo view('subscription/status', [
                'title' => 'Subscription Status - RentSmart',
                'subscription' => $subscription
            ]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            if (getenv('APP_ENV') === 'development') {
                throw $e;
            }
            echo view('errors/500', [
                'title' => '500 Internal Server Error'
            ]);
        }
    }

    public function activateSubscription($userId, $planId, $endDate = null, $isTemporary = false)
    {
        try {
            // Get plan details
            $plan = $this->subscription->getPlan($planId);
            if (!$plan) {
                throw new Exception('Invalid subscription plan');
            }

            // Calculate end date if not provided
            if (!$endDate) {
                $endDate = date('Y-m-d H:i:s', strtotime('+' . $plan['duration'] . ' days'));
            }

            // Update or create subscription
            $existingSubscription = $this->subscription->findByUserId($userId);
            if ($existingSubscription) {
                $this->subscription->updateSubscriptionStatus(
                    $userId,
                    'active',
                    $endDate,
                    $isTemporary ? 'Temporary activation pending payment verification' : null
                );
            } else {
                $this->subscription->create([
                    'user_id' => $userId,
                    'plan_type' => $plan['name'],
                    'status' => 'active',
                    'current_period_ends_at' => $endDate,
                    'notes' => $isTemporary ? 'Temporary activation pending payment verification' : null
                ]);
            }

            return true;
        } catch (Exception $e) {
            error_log('Error activating subscription: ' . $e->getMessage());
            return false;
        }
    }

    public function createSubscription($data)
    {
        try {
            // Get plan details
            $plan = $this->subscription->getPlanById($data['plan_id']);
            if (!$plan) {
                throw new Exception('Invalid subscription plan');
            }

            // Calculate dates
            $now = date('Y-m-d H:i:s');
            $tempEndDate = date('Y-m-d H:i:s', strtotime('+30 minutes'));

            // Check for existing subscription for this user and plan
            $existing = $this->subscription->getActiveByUserAndPlan($data['user_id'], $data['plan_id']);
            if ($existing) {
                // Calculate new trial end date and period: now + 1 day
                $newTrialEnd = date('Y-m-d H:i:s', strtotime('+1 day'));
                $newPeriodEnd = date('Y-m-d H:i:s', strtotime('+1 day'));
                // Update the existing subscription
                $sql = "UPDATE subscriptions SET status = ?, trial_ends_at = ?, current_period_starts_at = ?, current_period_ends_at = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $this->subscription->getDb()->prepare($sql);
                $stmt->execute([
                    $data['status'],
                    $newTrialEnd,
                    $now,
                    $newPeriodEnd,
                    $existing['id']
                ]);
                return $existing['id'];
            } else {
                // Create subscription record
                $sql = "INSERT INTO subscriptions 
                        (user_id, plan_id, plan_type, status, trial_ends_at, current_period_starts_at, current_period_ends_at, created_at, updated_at)
                        VALUES (?, ?, ?, ?, NULL, ?, ?, NOW(), NOW())";
                $stmt = $this->subscription->getDb()->prepare($sql);
                $stmt->execute([
                    $data['user_id'],
                    $data['plan_id'],
                    $plan['name'],
                    $data['status'],
                    $now,
                    $tempEndDate
                ]);
                $subscriptionId = $this->subscription->getDb()->lastInsertId();
                if (!$subscriptionId) {
                    throw new Exception('Failed to create subscription');
                }
                return $subscriptionId;
            }
        } catch (Exception $e) {
            error_log('Error creating subscription: ' . $e->getMessage());
            throw $e;
        }
    }
} 