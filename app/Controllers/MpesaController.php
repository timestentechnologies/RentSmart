<?php

namespace App\Controllers;

use App\Models\Payment;
use App\Models\MpesaTransaction;
use App\Controllers\SubscriptionController;
use Exception;
use PDO;
use PHPMailer\PHPMailer\PHPMailer;

class MpesaController
{
    private $payment;
    private $mpesaTransaction;
    private $consumerKey;
    private $consumerSecret;
    private $passkey;
    private $shortcode;
    private $callbackUrl;
    private $env;

    public function __construct()
    {
        $this->payment = new Payment();
        $this->mpesaTransaction = new MpesaTransaction();
        
        // Load M-Pesa configuration (fallback to DB settings if env not set)
        try {
            $settingsModel = new \App\Models\Setting();
            $settings = $settingsModel->getAllAsAssoc();
        } catch (\Exception $e) {
            error_log('Failed to load settings for M-Pesa config: ' . $e->getMessage());
            $settings = [];
        }


        $envValue = getenv('MPESA_ENV') ?: ($settings['mpesa_environment'] ?? 'sandbox');
        $this->env = strtolower((string)$envValue) === 'production' ? 'production' : 'sandbox';
        $this->consumerKey = getenv('MPESA_CONSUMER_KEY') ?: ($settings['mpesa_consumer_key'] ?? null);
        $this->consumerSecret = getenv('MPESA_CONSUMER_SECRET') ?: ($settings['mpesa_consumer_secret'] ?? null);
        $this->passkey = getenv('MPESA_PASSKEY') ?: ($settings['mpesa_passkey'] ?? null);
        $this->shortcode = getenv('MPESA_SHORTCODE') ?: ($settings['mpesa_shortcode'] ?? null);

        $baseAppUrl = getenv('APP_URL');
        if (!$baseAppUrl) {
            $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
            $scheme = $forwardedProto ?: ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http');
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $base = defined('BASE_URL') ? BASE_URL : '';
            // Force https when not localhost
            if (($host && $host !== 'localhost' && $host !== '127.0.0.1') && strtolower($scheme) !== 'https') {
                $scheme = 'https';
            }
            $baseAppUrl = $host ? ($scheme . '://' . $host . $base) : '';
        }
        $this->callbackUrl = rtrim((string)$baseAppUrl, '/') . '/mpesa/callback';

        // Allow explicit override via environment variable
        $callbackOverride = getenv('MPESA_CALLBACK_URL');
        if (!empty($callbackOverride)) {
            $this->callbackUrl = rtrim((string)$callbackOverride, '/');
        }

        // Localhost fallback similar to TenantPaymentController for local testing
        $isLocal = isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
        if ($isLocal && empty($callbackOverride)) {
            // Use a webhook.site URL for receiving callbacks during local development
            // You can replace this with your own webhook.site URL if desired
            $this->callbackUrl = 'https://webhook.site/8b5c7e2d-4a3f-4b1e-9c6d-1a2b3c4d5e6f';
        }
    }

    public function initiateSTK()
    {
        try {
            header('Content-Type: application/json');
            // Validate request
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                return;
            }

            // Get POST data
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['phone_number'], $data['amount'], $data['plan_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                return;
            }

            // Normalize and validate amount (STK requires integer amount)
            $amountInt = (int) round((float) $data['amount']);
            if ($amountInt <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid amount']);
                return;
            }

            // Ensure we have a valid subscription to attach this payment to
            $db = $this->payment->getDb();
            $subscriptionId = null;
            $subStmt = $db->prepare("SELECT id FROM subscriptions WHERE user_id = ? ORDER BY id DESC LIMIT 1");
            $subStmt->execute([$_SESSION['user_id']]);
            $existingSub = $subStmt->fetch(PDO::FETCH_ASSOC);
            if ($existingSub && isset($existingSub['id'])) {
                $subscriptionId = (int)$existingSub['id'];
            } else {
                // Create a subscription record if none exists yet
                $subscriptionModel = new \App\Models\Subscription();
                $plan = $subscriptionModel->getPlanById($data['plan_id']);
                if (!$plan) {
                    throw new Exception('Invalid plan selected');
                }
                $now = date('Y-m-d H:i:s');
                $end = date('Y-m-d H:i:s', strtotime('+31 days'));
                $ins = $db->prepare("INSERT INTO subscriptions (user_id, plan_id, plan_type, status, trial_ends_at, current_period_starts_at, current_period_ends_at, created_at, updated_at) VALUES (?, ?, ?, 'active', NULL, ?, ?, NOW(), NOW())");
                $ok = $ins->execute([$_SESSION['user_id'], $data['plan_id'], $plan['name'], $now, $end]);
                if (!$ok) {
                    $err = $ins->errorInfo();
                    throw new Exception('Failed to create subscription: ' . ($err[2] ?? ''));
                }
                $subscriptionId = (int)$db->lastInsertId();
            }

            // Create payment record referencing the resolved subscription ID
            $paymentId = $this->payment->create([
                'user_id' => $_SESSION['user_id'],
                'subscription_id' => $subscriptionId,
                'amount' => $amountInt,
                'payment_method' => 'mpesa',
                'status' => 'pending'
            ]);

            // Generate access token
            if (empty($this->consumerKey) || empty($this->consumerSecret)) {
                throw new Exception('M-Pesa API credentials are not configured. Please set Consumer Key/Secret in Settings or .env');
            }
            if (empty($this->shortcode) || empty($this->passkey)) {
                throw new Exception('M-Pesa Shortcode/Passkey is not configured.');
            }
            $accessToken = $this->generateAccessToken();

            // Prepare STK push
            $timestamp = date('YmdHis');
            $password = base64_encode($this->shortcode . $this->passkey . $timestamp);
            
            $stkUrl = $this->env === 'production' 
                ? 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
                : 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

            // Make STK push request
            $ch = curl_init($stkUrl);
            // Log callback URL being sent
            error_log('M-Pesa STK using CallBackURL: ' . $this->callbackUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'BusinessShortCode' => $this->shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => $amountInt,
                'PartyA' => $data['phone_number'],
                'PartyB' => $this->shortcode,
                'PhoneNumber' => $data['phone_number'],
                'CallBackURL' => $this->callbackUrl,
                'AccountReference' => 'RentSmart Subscription',
                'TransactionDesc' => 'Subscription Payment'
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                throw new Exception(curl_error($ch));
            }
            curl_close($ch);

            // Log request
            $this->payment->logPayment($paymentId, 'request', json_encode([
                'phone_number' => $data['phone_number'],
                'amount' => $data['amount']
            ]));

            // Parse response
            $result = json_decode($response, true);
            
            if ($httpCode !== 200 || !isset($result['ResponseCode']) || $result['ResponseCode'] !== '0') {
                throw new Exception($result['errorMessage'] ?? 'Failed to initiate payment');
            }

            // Log response
            $this->payment->logPayment($paymentId, 'response', $response);

            // Create M-Pesa transaction record
            $this->mpesaTransaction->create([
                'payment_id' => $paymentId,
                'merchant_request_id' => $result['MerchantRequestID'],
                'checkout_request_id' => $result['CheckoutRequestID'],
                'phone_number' => $data['phone_number'],
                'amount' => $data['amount']
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'STK push initiated successfully',
                'merchant_request_id' => $result['MerchantRequestID'] ?? null,
                'checkout_request_id' => $result['CheckoutRequestID'] ?? null
            ]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            
            if (isset($paymentId)) {
                $this->payment->logPayment($paymentId, 'error', $e->getMessage());
                $this->payment->updateStatus($paymentId, 'failed');
            }

            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to initiate payment: ' . $e->getMessage()
            ]);
        }
    }

    public function handleCallback()
    {
        try {
            // If this is a polling request from the UI, return current transaction status
            if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['checkout_request_id'])) {
                header('Content-Type: application/json');
                $checkoutId = $_GET['checkout_request_id'];
                $txn = $this->mpesaTransaction->findByCheckoutRequestId($checkoutId);
                if (!$txn) {
                    echo json_encode(['status' => 'pending', 'message' => 'Transaction not found yet']);
                    return;
                }
                $status = $txn['status'] ?? 'pending';
                echo json_encode([
                    'status' => $status,
                    'receipt_number' => $txn['mpesa_receipt_number'] ?? '',
                    'transaction_date' => $txn['transaction_date'] ?? '',
                    'result_desc' => $txn['result_description'] ?? ''
                ]);
                return;
            }

            // Get callback data
            $callbackData = json_decode(file_get_contents('php://input'), true);
            
            if (!$callbackData) {
                throw new Exception('Invalid callback data');
            }

            // Find transaction
            $transaction = $this->mpesaTransaction->findByCheckoutRequestId(
                $callbackData['Body']['stkCallback']['CheckoutRequestID']
            );

            if (!$transaction) {
                throw new Exception('Transaction not found');
            }

            // Log callback
            $this->payment->logPayment($transaction['payment_id'], 'callback', json_encode($callbackData));

            // Process callback
            $resultCode = $callbackData['Body']['stkCallback']['ResultCode'];
            
            if ($resultCode === 0) {
                // Payment successful
                $item = $callbackData['Body']['stkCallback']['CallbackMetadata']['Item'];
                $receiptNumber = '';
                $transactionDate = '';
                
                foreach ($item as $meta) {
                    if ($meta['Name'] === 'MpesaReceiptNumber') {
                        $receiptNumber = $meta['Value'];
                    } elseif ($meta['Name'] === 'TransactionDate') {
                        $transactionDate = $meta['Value'];
                    }
                }

                // Update transaction
                $this->mpesaTransaction->update($transaction['id'], [
                    'status' => 'completed',
                    'mpesa_receipt_number' => $receiptNumber,
                    'transaction_date' => date('Y-m-d H:i:s', strtotime($transactionDate)),
                    'result_code' => $resultCode,
                    'result_description' => 'Success'
                ]);

                // Update payment status
                $this->payment->updateStatus($transaction['payment_id'], 'completed');

                // Activate subscription using the underlying plan_id
                $payment = $this->payment->findById($transaction['payment_id']);
                $planId = null;
                try {
                    $db = $this->payment->getDb();
                    $planStmt = $db->prepare("SELECT plan_id FROM subscriptions WHERE id = ?");
                    $planStmt->execute([$payment['subscription_id']]);
                    $planRow = $planStmt->fetch(PDO::FETCH_ASSOC);
                    if ($planRow && isset($planRow['plan_id'])) {
                        $planId = (int)$planRow['plan_id'];
                    }
                } catch (Exception $e) {
                    error_log('Failed to fetch plan_id for subscription: ' . $e->getMessage());
                }
                $subscription = new SubscriptionController();
                $subscription->activateSubscription($payment['user_id'], $planId ?: (int)$payment['subscription_id']);
            } else {
                // Payment failed
                $this->mpesaTransaction->update($transaction['id'], [
                    'status' => 'failed',
                    'result_code' => $resultCode,
                    'result_description' => $callbackData['Body']['stkCallback']['ResultDesc']
                ]);

                $this->payment->updateStatus($transaction['payment_id'], 'failed');
            }

            // Return success response
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            error_log('M-Pesa callback error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function verifyManualPayment()
    {
        try {
            header('Content-Type: application/json');
            // Validate request
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                return;
            }

            // Verify CSRF token
            if (!verify_csrf_token()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                return;
            }

            // Get POST data
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['phone_number'], $data['transaction_code'], $data['amount'], $data['plan_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                return;
            }

            // Get database connection
            $db = $this->payment->getDb();

            // Start database transaction
            $db->beginTransaction();

            try {
                // Always fetch the latest subscription for the user (highest id)
                $subs = $db->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY id DESC LIMIT 1");
                $subs->execute([$_SESSION['user_id']]);
                $existingSub = $subs->fetch(PDO::FETCH_ASSOC);
                if (!$existingSub) {
                    error_log('No subscription found for user: ' . $_SESSION['user_id']);
                    throw new \Exception('No subscription found for user');
                }

                // Prepare subscription data but do NOT activate yet
                $subscriptionModel = new \App\Models\Subscription();
                $userId = $_SESSION['user_id'];
                $planId = $data['plan_id'];
                $plan = $subscriptionModel->getPlanById($planId);
                $now = new \DateTime();
                $newStart = clone $now;
                if ($existingSub) {
                    // If current period still active, start new period after it; keep status pending_verification
                    if (!empty($existingSub['current_period_ends_at'])) {
                        $currentEnd = new \DateTime($existingSub['current_period_ends_at']);
                        if ($now < $currentEnd) {
                            $newStart = $currentEnd;
                        }
                    }
                    $newEnd = (clone $newStart)->modify('+31 days');
                    $sql = "UPDATE subscriptions SET 
                        plan_id = ?,
                        plan_type = ?,
                        status = 'pending_verification',
                        current_period_starts_at = ?,
                        current_period_ends_at = ?,
                        updated_at = NOW()
                        WHERE id = ? AND user_id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        $planId,
                        $plan['name'],
                        $newStart->format('Y-m-d H:i:s'),
                        $newEnd->format('Y-m-d H:i:s'),
                        $existingSub['id'],
                        $userId
                    ]);
                    $subscriptionId = $existingSub['id'];
                } else {
                    // Create a pending subscription placeholder
                    $newEnd = (clone $newStart)->modify('+31 days');
                    $sql = "INSERT INTO subscriptions (user_id, plan_id, plan_type, status, trial_ends_at, current_period_starts_at, current_period_ends_at, created_at, updated_at) VALUES (?, ?, ?, 'pending_verification', NULL, ?, ?, NOW(), NOW())";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        $userId,
                        $planId,
                        $plan['name'],
                        $newStart->format('Y-m-d H:i:s'),
                        $newEnd->format('Y-m-d H:i:s')
                    ]);
                    $subscriptionId = $db->lastInsertId();
                }
                if (!$subscriptionId) {
                    error_log('No valid subscription ID for payment. ExistingSub: ' . print_r($existingSub, true));
                    throw new \Exception('No valid subscription ID for payment');
                }

                // Create payment record as pending
                $paymentId = $this->payment->create([
                    'user_id' => $userId,
                    'subscription_id' => $subscriptionId,
                    'amount' => $data['amount'],
                    'payment_method' => 'mpesa',
                    'status' => 'pending',
                    'transaction_reference' => $data['transaction_code']
                ]);

                // Create manual M-Pesa payment record (pending verification)
                $manualMpesaSql = "INSERT INTO manual_mpesa_payments 
                                (payment_id, phone_number, transaction_code, amount, verification_status, created_at, updated_at)
                                VALUES (?, ?, ?, ?, 'pending', NOW(), NOW())";
                $manualMpesaStmt = $db->prepare($manualMpesaSql);
                $manualMpesaStmt->execute([
                    $paymentId,
                    $data['phone_number'],
                    $data['transaction_code'],
                    $data['amount']
                ]);

                // Log the manual payment attempt
                $this->payment->logPayment($paymentId, 'manual_verification', json_encode([
                    'phone_number' => $data['phone_number'],
                    'transaction_code' => $data['transaction_code'],
                    'amount' => $data['amount']
                ]));

                // Commit transaction
                $db->commit();

                // Notify user and admin of pending verification
                try {
                    $settingModel = new \App\Models\Setting();
                    $settings = $settingModel->getAllAsAssoc();
                    $userModel = new \App\Models\User();
                    $user = $userModel->find($userId);
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = $settings['smtp_host'] ?? '';
                    $mail->Port = $settings['smtp_port'] ?? 587;
                    $mail->SMTPAuth = true;
                    $mail->Username = $settings['smtp_user'] ?? '';
                    $mail->Password = $settings['smtp_pass'] ?? '';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->setFrom($settings['smtp_user'] ?? '', $settings['site_name'] ?? 'RentSmart');
                    $mail->isHTML(true);
                    $logoUrl = isset($settings['site_logo']) && $settings['site_logo'] ? (defined('BASE_URL') ? (BASE_URL . '/public/assets/images/' . $settings['site_logo']) : '') : '';
                    $footer = '<div style="margin-top:30px;font-size:12px;color:#888;text-align:center;">Powered by <a href="https://timestentechnologies.co.ke" target="_blank" style="color:#888;text-decoration:none;">Timesten Technologies</a></div>';

                    // Email to user
                    $mail->clearAddresses();
                    $mail->addAddress($user['email'], $user['name']);
                    $mail->Subject = 'We received your subscription payment (pending verification)';
                    $mail->Body =
                        '<div style="max-width:500px;margin:auto;border:1px solid #eee;padding:24px;font-family:sans-serif;">'
                        . ($logoUrl ? '<div style="text-align:center;margin-bottom:24px;"><img src="' . $logoUrl . '" alt="Logo" style="max-width:180px;max-height:80px;"></div>' : '') .
                        '<p style="font-size:16px;">Dear ' . htmlspecialchars($user['name']) . ',</p>' .
                        '<p>We have received your M-Pesa payment and it is currently pending verification by our team.</p>' .
                        '<p>You will receive a confirmation once approved.</p>' .
                        '<p>Thank you,<br>RentSmart Team</p>' .
                        $footer .
                        '</div>';
                    $mail->send();

                    // Email to admin
                    if (!empty($settings['site_email'])) {
                        $mail->clearAddresses();
                        $mail->addAddress($settings['site_email'], $settings['site_name'] ?? 'Admin');
                        $mail->Subject = 'Manual subscription payment pending verification (M-Pesa)';
                        $mail->Body =
                            '<div style="max-width:500px;margin:auto;border:1px solid #eee;padding:24px;font-family:sans-serif;">'
                            . ($logoUrl ? '<div style="text-align:center;margin-bottom:24px;"><img src="' . $logoUrl . '" alt="Logo" style="max-width:180px;max-height:80px;"></div>' : '') .
                            '<p style="font-size:16px;">A new manual subscription payment was submitted and is awaiting verification.</p>' .
                            '<ul style="font-size:15px;">
                                <li><strong>User:</strong> ' . htmlspecialchars($user['name']) . ' (' . htmlspecialchars($user['email']) . ')</li>' .
                                '<li><strong>Amount:</strong> Ksh ' . htmlspecialchars($data['amount']) . '</li>' .
                                '<li><strong>Plan:</strong> ' . htmlspecialchars($plan['name']) . '</li>' .
                                '<li><strong>M-Pesa Code:</strong> ' . htmlspecialchars($data['transaction_code']) . '</li>' .
                            '</ul>' .
                            '<p>Login to the admin dashboard to verify and approve/reject this payment.</p>' .
                            $footer .
                            '</div>';
                        $mail->send();
                    }
                } catch (\PHPMailer\PHPMailer\Exception $e) {
                    error_log('Renewal/admin email error (M-Pesa): ' . $e->getMessage());
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'Payment submitted and is pending verification by admin.'
                ]);
                return;

            } catch (Exception $e) {
                // Rollback transaction on error
                $db->rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            
            if (isset($paymentId)) {
                $this->payment->logPayment($paymentId, 'error', $e->getMessage());
                $this->payment->updateStatus($paymentId, 'failed');
            }

            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to verify payment: ' . $e->getMessage()
            ]);
        }
    }

    public function checkSTKStatus()
    {
        header('Content-Type: application/json');
        try {
            // Support both JSON and form-encoded
            $data = json_decode(file_get_contents('php://input'), true);
            $checkoutRequestId = $data['checkout_request_id'] ?? ($_POST['checkout_request_id'] ?? '');
            if (!$checkoutRequestId) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Missing checkout request ID']);
                return;
            }

            $transaction = $this->mpesaTransaction->findByCheckoutRequestId($checkoutRequestId);
            if (!$transaction) {
                echo json_encode(['status' => 'pending', 'message' => 'Transaction not found yet']);
                return;
            }

            // Map response
            $status = $transaction['status'] ?? 'pending';
            $response = [
                'status' => $status,
                'message' => $transaction['result_description'] ?? '',
                'receipt_number' => $transaction['mpesa_receipt_number'] ?? '',
                'transaction_date' => $transaction['transaction_date'] ?? '',
                'result_desc' => $transaction['result_description'] ?? ''
            ];
            echo json_encode($response);
        } catch (Exception $e) {
            error_log('Error checking subscription STK status: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error checking payment status']);
        }
    }

    private function generateAccessToken()
    {
        $credentials = base64_encode($this->consumerKey . ':' . $this->consumerSecret);
        
        $url = $this->env === 'production'
            ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
            : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $credentials,
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Exception('Failed to generate access token: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if (!isset($result['access_token'])) {
            error_log('M-Pesa access token response: ' . $response);
            $errMsg = isset($result['errorMessage']) ? $result['errorMessage'] : $response;
            throw new Exception('Failed to get access token: ' . $errMsg);
        }
        
        return $result['access_token'];
    }
} 