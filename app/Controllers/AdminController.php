<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\MpesaTransaction;
use App\Models\ActivityLog;
use Exception;
use DateTime;

class AdminController
{
    private $user;
    private $subscription;
    private $payment;
    private $mpesaTransaction;
    private $activityLog;

    public function __construct()
    {
        $this->user = new User();
        $this->subscription = new Subscription();
        $this->payment = new Payment();
        $this->mpesaTransaction = new MpesaTransaction();
        $this->activityLog = new ActivityLog();

        // Check if user is logged in and is admin
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Please login to continue';
            $_SESSION['flash_type'] = 'danger';
            redirect('/home');
        }
        if ($_SESSION['user_role'] !== 'admin') {
            $_SESSION['flash_message'] = 'Access denied';
            $_SESSION['flash_type'] = 'danger';
            redirect('/dashboard');
        }
    }

    public function updateSubscription()
    {
        try {
            // Verify CSRF token
            if (!verify_csrf_token()) {
                $_SESSION['flash_message'] = 'Invalid security token';
                $_SESSION['flash_type'] = 'danger';
                redirect('/admin/subscriptions');
            }

            $subscriptionId = filter_input(INPUT_POST, 'subscription_id', FILTER_SANITIZE_NUMBER_INT);
            $newUserId = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
            $planId = filter_input(INPUT_POST, 'plan_id', FILTER_SANITIZE_NUMBER_INT);
            $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
            $startAtRaw = $_POST['start_at'] ?? '';
            $endAtRaw = $_POST['end_at'] ?? '';
            $trialEndsRaw = $_POST['trial_ends_at'] ?? '';

            if (!$subscriptionId || !$newUserId || !$planId || !$status || !$startAtRaw || !$endAtRaw) {
                $_SESSION['flash_message'] = 'All fields are required';
                $_SESSION['flash_type'] = 'danger';
                redirect('/admin/subscriptions');
            }

            // Normalize datetime-local inputs to Y-m-d H:i:s
            $startAt = date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $startAtRaw)) ?: time());
            $endAt = date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $endAtRaw)) ?: time());
            $trialEndsAt = $trialEndsRaw ? date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $trialEndsRaw))) : null;

            // Get plan to ensure plan_type consistency
            $plan = $this->subscription->getPlanById($planId);
            if (!$plan) {
                $_SESSION['flash_message'] = 'Invalid plan selected';
                $_SESSION['flash_type'] = 'danger';
                redirect('/admin/subscriptions');
            }

            // Validate target user
            $targetUser = $this->user->find($newUserId);
            if (!$targetUser) {
                $_SESSION['flash_message'] = 'Selected user was not found';
                $_SESSION['flash_type'] = 'danger';
                redirect('/admin/subscriptions');
            }

            $payload = [
                'user_id' => $newUserId,
                'plan_id' => $planId,
                'plan_type' => $plan['name'],
                'status' => $status,
                'current_period_starts_at' => $startAt,
                'current_period_ends_at' => $endAt,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            if ($trialEndsAt) {
                $payload['trial_ends_at'] = $trialEndsAt;
            }

            // Use a transaction to keep subscription and related payments consistent
            $db = $this->subscription->getDb();
            $db->beginTransaction();
            try {
                $before = $this->subscription->find($subscriptionId) ?: [];
                $this->subscription->update($subscriptionId, $payload);
                // Keep existing payments consistent with the reassigned user
                $stmt = $db->prepare('UPDATE subscription_payments SET user_id = ? WHERE subscription_id = ?');
                $stmt->execute([$newUserId, $subscriptionId]);
                $db->commit();
            } catch (Exception $e) {
                if ($db->inTransaction()) { $db->rollBack(); }
                throw $e;
            }

            $changes = [];
            if (!empty($before)) {
                foreach (['user_id','plan_id','plan_type','status','current_period_starts_at','current_period_ends_at','trial_ends_at'] as $k) {
                    $beforeVal = $before[$k] ?? null;
                    $afterVal = $payload[$k] ?? ($k === 'trial_ends_at' ? ($trialEndsAt ?? null) : null);
                    if ($beforeVal != $afterVal) {
                        $changes[$k] = [$beforeVal, $afterVal];
                    }
                }
            }
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $this->activityLog->add($_SESSION['user_id'] ?? null, $_SESSION['user_role'] ?? null, 'subscription.update', 'subscription', (int)$subscriptionId, null, json_encode(['changes' => $changes]), $ip, $agent);

            $_SESSION['flash_message'] = 'Subscription updated successfully';
            $_SESSION['flash_type'] = 'success';
            redirect('/admin/subscriptions');
        } catch (Exception $e) {
            error_log($e->getMessage());
            $_SESSION['flash_message'] = 'Failed to update subscription';
            $_SESSION['flash_type'] = 'danger';
            redirect('/admin/subscriptions');
        }
    }

    public function users()
    {
        try {
            $users = $this->user->getAllUsers();
            
            echo view('admin/users', [
                'title' => 'User Management - RentSmart',
                'users' => $users
            ]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo view('errors/500', [
                'title' => '500 Internal Server Error'
            ]);
        }
    }

    public function getUser($id)
    {
        try {
            $user = $this->user->find($id);
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                return;
            }

            echo json_encode($user);
        } catch (Exception $e) {
            error_log($e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }

    public function storeUser()
    {
        try {
            // Verify CSRF token
            if (!verify_csrf_token()) {
                $_SESSION['flash_message'] = 'Invalid security token';
                $_SESSION['flash_type'] = 'danger';
                redirect('/admin/users');
            }

            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';
            $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);

            if (!$name || !$email || !$password || !$role) {
                $_SESSION['flash_message'] = 'All fields are required';
                $_SESSION['flash_type'] = 'danger';
                redirect('/admin/users');
            }

            // Check if email already exists
            if ($this->user->findByEmail($email)) {
                $_SESSION['flash_message'] = 'Email already registered';
                $_SESSION['flash_type'] = 'danger';
                redirect('/admin/users');
            }

            // Create user
            $newId = $this->user->createUser([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => $role
            ]);

            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $this->activityLog->add($_SESSION['user_id'] ?? null, $_SESSION['user_role'] ?? null, 'user.create', 'user', (int)$newId, null, json_encode(['name' => $name, 'email' => $email, 'role' => $role]), $ip, $agent);

            $_SESSION['flash_message'] = 'User created successfully';
            $_SESSION['flash_type'] = 'success';
            redirect('/admin/users');
        } catch (Exception $e) {
            error_log($e->getMessage());
            $_SESSION['flash_message'] = 'Failed to create user';
            $_SESSION['flash_type'] = 'danger';
            redirect('/admin/users');
        }
    }

    public function updateUser()
    {
        try {
            // Verify CSRF token
            if (!verify_csrf_token()) {
                $_SESSION['flash_message'] = 'Invalid security token';
                $_SESSION['flash_type'] = 'danger';
                redirect('/admin/users');
            }

            $userId = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
            $password = $_POST['password'] ?? '';

            if (!$userId || !$name || !$email || !$role) {
                $_SESSION['flash_message'] = 'Required fields are missing';
                $_SESSION['flash_type'] = 'danger';
                redirect('/admin/users');
            }

            $data = [
                'name' => $name,
                'email' => $email,
                'role' => $role
            ];

            // Only update password if provided
            if ($password) {
                $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            }

            $this->user->update($userId, $data);
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $this->activityLog->add($_SESSION['user_id'] ?? null, $_SESSION['user_role'] ?? null, 'user.update', 'user', (int)$userId, null, json_encode($data), $ip, $agent);

            $_SESSION['flash_message'] = 'User updated successfully';
            $_SESSION['flash_type'] = 'success';
            redirect('/admin/users');
        } catch (Exception $e) {
            error_log($e->getMessage());
            $_SESSION['flash_message'] = 'Failed to update user';
            $_SESSION['flash_type'] = 'danger';
            redirect('/admin/users');
        }
    }

    public function deleteUser($id)
    {
        try {
            // Verify CSRF token
            if (!verify_csrf_token()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                return;
            }

            $user = $this->user->find($id);
            if (!$user) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User not found']);
                return;
            }

            // Don't allow deleting admin users
            if ($user['role'] === 'admin') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cannot delete admin users']);
                return;
            }

            // Don't allow deleting yourself
            if ($id == $_SESSION['user_id']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
                return;
            }

            // Start transaction for atomic deletion
            $db = \App\Database\Connection::getInstance()->getConnection();
            $db->beginTransaction();

            try {
                // Delete related records in correct order
                error_log("Starting user deletion for user ID: {$id}, role: {$user['role']}");

                // Remove accounting entries authored by this user (for properties they owned, property deletion below also removes by property_id)
                error_log("Deleting journal entries for user...");
                $stmt = $db->prepare("DELETE FROM journal_entries WHERE user_id = ?");
                $stmt->execute([(int)$id]);
                error_log("Journal entries deleted successfully");
                
                // 1. Delete subscription payment logs first (foreign key to subscription_payments)
                error_log("Deleting subscription payment logs...");
                $stmt = $db->prepare("DELETE FROM subscription_payment_logs WHERE payment_id IN (
                    SELECT id FROM subscription_payments WHERE user_id = ?
                )");
                $stmt->execute([$id]);
                error_log("Subscription payment logs deleted successfully");
                
                // 2. Delete subscription payments (foreign key to users)
                error_log("Deleting subscription payments...");
                $stmt = $db->prepare("DELETE FROM subscription_payments WHERE user_id = ?");
                $stmt->execute([$id]);
                error_log("Subscription payments deleted successfully");
                
                // 2. Set manual_mpesa_payments.verified_by to NULL (foreign key to users)
                error_log("Nullifying manual mpesa payment verifier references...");
                $stmt = $db->prepare("UPDATE manual_mpesa_payments SET verified_by = NULL WHERE verified_by = ?");
                $stmt->execute([$id]);
                error_log("Manual mpesa payment references nullified");
                
                // 3. Delete subscriptions
                error_log("Deleting subscriptions...");
                $subscriptionModel = new \App\Models\Subscription();
                $subscriptionModel->deleteByUserId($id);
                error_log("Subscriptions deleted successfully");

                // Delete employee-related records
                error_log("Deleting employees and employee payments...");
                try {
                    $stmt = $db->prepare("DELETE FROM employee_payments WHERE employee_id IN (SELECT id FROM employees WHERE user_id = ?)");
                    $stmt->execute([(int)$id]);
                } catch (\Exception $e) {
                }
                try {
                    $stmt = $db->prepare("DELETE FROM employees WHERE user_id = ?");
                    $stmt->execute([(int)$id]);
                } catch (\Exception $e) {
                }
                error_log("Employees deleted successfully");

                // Delete expenses created by this user
                error_log("Deleting expenses...");
                try {
                    $stmt = $db->prepare("DELETE FROM expenses WHERE user_id = ?");
                    $stmt->execute([(int)$id]);
                } catch (\Exception $e) {
                }
                error_log("Expenses deleted successfully");

                // Delete notices/messages created by this user
                error_log("Deleting notices/messages...");
                try {
                    $stmt = $db->prepare("DELETE FROM notices WHERE user_id = ?");
                    $stmt->execute([(int)$id]);
                } catch (\Exception $e) {
                }
                try {
                    $stmt = $db->prepare("DELETE FROM messages WHERE sender_type = 'user' AND sender_id = ?");
                    $stmt->execute([(int)$id]);
                } catch (\Exception $e) {
                }
                try {
                    $stmt = $db->prepare("DELETE FROM contact_message_replies WHERE user_id = ?");
                    $stmt->execute([(int)$id]);
                } catch (\Exception $e) {
                }
                error_log("Notices/messages deleted successfully");

                // Delete payment methods owned by user and property links
                error_log("Deleting payment methods...");
                try {
                    $stmt = $db->prepare("DELETE FROM payment_method_properties WHERE payment_method_id IN (SELECT id FROM payment_methods WHERE owner_user_id = ?)");
                    $stmt->execute([(int)$id]);
                } catch (\Exception $e) {
                }
                try {
                    $stmt = $db->prepare("DELETE FROM payment_methods WHERE owner_user_id = ?");
                    $stmt->execute([(int)$id]);
                } catch (\Exception $e) {
                }
                error_log("Payment methods deleted successfully");

                // Delete realtor-module data (clients, listings, contracts, leads, payments)
                if (strtolower((string)($user['role'] ?? '')) === 'realtor') {
                    error_log("Deleting realtor module data...");

                    // Payments created for realtor contracts
                    try {
                        $stmt = $db->prepare("DELETE FROM payments WHERE realtor_user_id = ?");
                        $stmt->execute([(int)$id]);
                    } catch (\Exception $e) {
                    }

                    // Realtor contracts (and any linked payments already deleted above)
                    try {
                        $stmt = $db->prepare("DELETE FROM realtor_contracts WHERE user_id = ?");
                        $stmt->execute([(int)$id]);
                    } catch (\Exception $e) {
                    }

                    // Realtor clients
                    try {
                        $stmt = $db->prepare("DELETE FROM realtor_clients WHERE user_id = ?");
                        $stmt->execute([(int)$id]);
                    } catch (\Exception $e) {
                    }

                    // Realtor listings
                    try {
                        $stmt = $db->prepare("DELETE FROM realtor_listings WHERE user_id = ?");
                        $stmt->execute([(int)$id]);
                    } catch (\Exception $e) {
                    }

                    // Realtor leads and stages
                    try {
                        $stmt = $db->prepare("DELETE FROM realtor_leads WHERE user_id = ?");
                        $stmt->execute([(int)$id]);
                    } catch (\Exception $e) {
                    }
                    try {
                        $stmt = $db->prepare("DELETE FROM realtor_lead_stages WHERE user_id = ?");
                        $stmt->execute([(int)$id]);
                    } catch (\Exception $e) {
                    }

                    // Inquiries captured from realtor listings
                    try {
                        $stmt = $db->prepare("DELETE FROM inquiries WHERE realtor_user_id = ?");
                        $stmt->execute([(int)$id]);
                    } catch (\Exception $e) {
                    }

                    error_log("Realtor module data deleted successfully");
                }

                // Treat landlord/manager/agent as property owners: delete all their property data.
                // For caretakers/other staff: only unlink assignments.
                $isPropertyOwnerRole = in_array((string)($user['role'] ?? ''), ['landlord', 'manager', 'agent'], true);

                if ($isPropertyOwnerRole) {
                    error_log("Deleting properties for property-owner role (owner/manager/agent)...");
                    $propertyModel = new \App\Models\Property();
                    $stmt = $db->prepare("SELECT id FROM properties WHERE owner_id = ? OR manager_id = ? OR agent_id = ?");
                    $stmt->execute([(int)$id, (int)$id, (int)$id]);
                    $propIds = $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
                    foreach ($propIds as $pid) {
                        $propertyModel->delete((int)$pid);
                    }
                    error_log("Properties deleted successfully");
                } else {
                    error_log("Unlinking user from managed/assigned properties...");
                    try {
                        $stmt = $db->prepare("UPDATE properties SET manager_id = NULL WHERE manager_id = ?");
                        $stmt->execute([(int)$id]);
                    } catch (\Exception $e) {
                    }
                    try {
                        $stmt = $db->prepare("UPDATE properties SET agent_id = NULL WHERE agent_id = ?");
                        $stmt->execute([(int)$id]);
                    } catch (\Exception $e) {
                    }
                    try {
                        $stmt = $db->prepare("UPDATE properties SET caretaker_user_id = NULL WHERE caretaker_user_id = ?");
                        $stmt->execute([(int)$id]);
                    } catch (\Exception $e) {
                    }
                    error_log("Property links updated successfully");
                }

                // 6. Delete file uploads (has CASCADE, but let's be explicit)
                error_log("Deleting file uploads...");
                $stmt = $db->prepare("DELETE FROM file_uploads WHERE uploaded_by = ?");
                $stmt->execute([$id]);
                error_log("File uploads deleted successfully");

                // 7. Finally delete the user
                error_log("Deleting user record...");
                $this->user->delete($id);
                error_log("User deleted successfully");

                // Commit transaction
                $db->commit();
                error_log("Transaction committed successfully");
                
                $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                $this->activityLog->add($_SESSION['user_id'] ?? null, $_SESSION['user_role'] ?? null, 'user.delete', 'user', (int)$id, null, null, $ip, $agent);

                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            } catch (\Exception $e) {
                // Rollback on error
                error_log("Error during user deletion, rolling back: " . $e->getMessage());
                if ($db->inTransaction()) {
                    $db->rollBack();
                    error_log("Transaction rolled back");
                }
                throw $e;
            }
        } catch (Exception $e) {
            error_log("Delete user error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to delete user. Please check if there are dependent records.',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function subscriptions()
    {
        try {
            $plans = $this->subscription->getAllPlans();
            $subscriptions = $this->subscription->getAllSubscriptions();
            $users = $this->user->getAllUsers();
            
            echo view('admin/subscriptions', [
                'title' => 'Subscription Management - RentSmart',
                'plans' => $plans,
                'subscriptions' => $subscriptions,
                'users' => $users
            ]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo view('errors/500', [
                'title' => '500 Internal Server Error'
            ]);
        }
    }

    public function getPlan($id)
    {
        header('Content-Type: application/json');
        try {
            $plan = $this->subscription->getPlan($id);
            if (!$plan) {
                http_response_code(404);
                echo json_encode(['error' => 'Plan not found']);
                return;
            }

            echo json_encode($plan);
        } catch (Exception $e) {
            error_log($e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }

    public function updatePlan()
    {
        try {
            // Verify CSRF token
            if (!verify_csrf_token()) {
                $_SESSION['flash_message'] = 'Invalid security token';
                $_SESSION['flash_type'] = 'danger';
                redirect('/admin/subscriptions');
            }

            $planId = filter_input(INPUT_POST, 'plan_id', FILTER_SANITIZE_NUMBER_INT);
            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
            $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
            $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
            $features = filter_input(INPUT_POST, 'features', FILTER_SANITIZE_STRING);
            // Optional limits: treat blank/0 as unlimited (NULL)
            $propertyLimitRaw = $_POST['property_limit'] ?? '';
            $unitLimitRaw = $_POST['unit_limit'] ?? '';
            $propertyLimit = is_numeric($propertyLimitRaw) ? (int)$propertyLimitRaw : null;
            $unitLimit = is_numeric($unitLimitRaw) ? (int)$unitLimitRaw : null;
            if ($propertyLimit !== null && $propertyLimit <= 0) { $propertyLimit = null; }
            if ($unitLimit !== null && $unitLimit <= 0) { $unitLimit = null; }

            if (!$planId || !$name || $price === false || !$description || !$features) {
                $_SESSION['flash_message'] = 'All fields are required';
                $_SESSION['flash_type'] = 'danger';
                redirect('/admin/subscriptions');
            }

            $payload = [
                'name' => $name,
                'price' => $price,
                'description' => $description,
                'features' => $features
            ];
            // Include limits in payload (null => set to NULL)
            $payload['property_limit'] = $propertyLimit;
            $payload['unit_limit'] = $unitLimit;

            $this->subscription->updatePlan($planId, $payload);

            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $this->activityLog->add(
                $_SESSION['user_id'] ?? null,
                $_SESSION['user_role'] ?? null,
                'plan.update',
                'plan',
                (int)$planId,
                null,
                json_encode([
                    'name' => $name,
                    'price' => $price,
                    'property_limit' => $propertyLimit,
                    'unit_limit' => $unitLimit
                ]),
                $ip,
                $agent
            );

            $_SESSION['flash_message'] = 'Plan updated successfully';
            $_SESSION['flash_type'] = 'success';
            redirect('/admin/subscriptions');
        } catch (Exception $e) {
            error_log($e->getMessage());
            $_SESSION['flash_message'] = 'Failed to update plan';
            $_SESSION['flash_type'] = 'danger';
            redirect('/admin/subscriptions');
        }
    }

    public function extendSubscription($id)
    {
        try {
            // Verify CSRF token
            if (!verify_csrf_token()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                return;
            }

            $subscription = $this->subscription->find($id);
            if (!$subscription) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Subscription not found']);
                return;
            }

            // Get days from JSON body
            $input = json_decode(file_get_contents('php://input'), true);
            $days = isset($input['days']) && is_numeric($input['days']) ? (int)$input['days'] : 30;

            $endDate = (new DateTime($subscription['current_period_ends_at']))->modify("+{$days} days");
            $this->subscription->updateSubscriptionStatus($subscription['user_id'], 'active', $endDate->format('Y-m-d H:i:s'));

            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $this->activityLog->add($_SESSION['user_id'] ?? null, $_SESSION['user_role'] ?? null, 'subscription.extend', 'subscription', (int)$id, null, json_encode(['days' => $days, 'new_expiry' => $endDate->format('Y-m-d H:i:s')]), $ip, $agent);

            echo json_encode(['success' => true, 'new_expiry' => $endDate->format('Y-m-d H:i:s')]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
    }

    public function payments()
    {
        try {
            $payments = $this->payment->getAllPayments();
            $subscriptions = $this->subscription->getAllSubscriptions();

            // Compute expected revenue from current subscriptions (latest per user)
            $expected = 0;
            $seenUsers = [];
            foreach ($subscriptions as $sub) {
                $uid = $sub['user_id'] ?? null;
                if ($uid && isset($seenUsers[$uid])) {
                    continue;
                }
                $status = strtolower($sub['status'] ?? '');
                if ($status === 'active') {
                    $expected += (float)($sub['plan_price'] ?? 0);
                }
                if ($uid) {
                    $seenUsers[$uid] = true;
                }
            }

            echo view('admin/payments', [
                'title' => 'Payment Management - RentSmart',
                'payments' => $payments,
                'expected_revenue' => $expected
            ]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo view('errors/500', [
                'title' => '500 Internal Server Error'
            ]);
        }
    }

    public function getPayment($id)
    {
        try {
            $payment = $this->payment->getPaymentDetails($id);
            if (!$payment) {
                http_response_code(404);
                echo json_encode(['error' => 'Payment not found']);
                return;
            }

            echo json_encode($payment);
        } catch (Exception $e) {
            error_log($e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }

    public function getTransaction($id)
    {
        try {
            $transaction = $this->mpesaTransaction->getTransactionDetails($id);
            if (!$transaction) {
                http_response_code(404);
                echo json_encode(['error' => 'Transaction not found']);
                return;
            }

            echo json_encode($transaction);
        } catch (Exception $e) {
            error_log($e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }

    public function verifyManualSubscriptionPayment($manualMpesaId)
    {
        try {
            header('Content-Type: application/json');

            if (!verify_csrf_token()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $action = strtolower(trim($input['action'] ?? ''));
            if (!in_array($action, ['approve', 'reject'], true)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                return;
            }

            $db = $this->payment->getDb();
            $db->beginTransaction();

            $stmt = $db->prepare('SELECT * FROM manual_mpesa_payments WHERE id = ?');
            $stmt->execute([(int)$manualMpesaId]);
            $manual = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$manual) {
                $db->rollBack();
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Manual M-Pesa payment not found']);
                return;
            }

            $paymentId = (int)($manual['payment_id'] ?? 0);
            if ($paymentId <= 0) {
                $db->rollBack();
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid payment link']);
                return;
            }

            $payStmt = $db->prepare('SELECT * FROM subscription_payments WHERE id = ?');
            $payStmt->execute([$paymentId]);
            $payment = $payStmt->fetch(\PDO::FETCH_ASSOC);
            if (!$payment) {
                $db->rollBack();
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Subscription payment not found']);
                return;
            }

            $userId = (int)($payment['user_id'] ?? 0);
            $subscriptionId = (int)($payment['subscription_id'] ?? 0);
            if ($userId <= 0 || $subscriptionId <= 0) {
                $db->rollBack();
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid subscription payment data']);
                return;
            }

            $newVerification = $action === 'approve' ? 'verified' : 'rejected';
            $verifiedBy = $_SESSION['user_id'] ?? null;
            $notes = trim($input['notes'] ?? '');
            $updManual = $db->prepare('UPDATE manual_mpesa_payments SET verification_status = ?, verification_notes = ?, verified_at = NOW(), verified_by = ? WHERE id = ?');
            $updManual->execute([$newVerification, $notes, $verifiedBy, (int)$manualMpesaId]);

            $newPaymentStatus = $action === 'approve' ? 'completed' : 'failed';
            $updPay = $db->prepare('UPDATE subscription_payments SET status = ?, updated_at = NOW() WHERE id = ?');
            $updPay->execute([$newPaymentStatus, $paymentId]);

            if ($action === 'approve') {
                $subStmt = $db->prepare('SELECT * FROM subscriptions WHERE id = ?');
                $subStmt->execute([$subscriptionId]);
                $sub = $subStmt->fetch(\PDO::FETCH_ASSOC);
                if (!$sub) {
                    $db->rollBack();
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Subscription not found']);
                    return;
                }

                $now = new DateTime();
                $start = !empty($sub['current_period_starts_at']) ? new DateTime($sub['current_period_starts_at']) : clone $now;
                if ($start < $now) {
                    $start = clone $now;
                }
                $end = !empty($sub['current_period_ends_at']) ? new DateTime($sub['current_period_ends_at']) : (clone $start)->modify('+31 days');
                if ($end <= $start) {
                    $end = (clone $start)->modify('+31 days');
                }

                $updSub = $db->prepare('UPDATE subscriptions SET status = \'active\', current_period_starts_at = ?, current_period_ends_at = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
                $updSub->execute([$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'), $subscriptionId, $userId]);
            }

            $db->commit();

            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $this->activityLog->add($_SESSION['user_id'] ?? null, $_SESSION['user_role'] ?? null, 'manual_mpesa.verify', 'subscription_payment', (int)$paymentId, null, json_encode(['manual_id' => (int)$manualMpesaId, 'action' => $action, 'new_status' => $newPaymentStatus]), $ip, $agent);

            echo json_encode([
                'success' => true,
                'message' => $action === 'approve' ? 'Payment approved and subscription activated.' : 'Payment rejected.'
            ]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            try {
                $db = $this->payment->getDb();
                if ($db && $db->inTransaction()) {
                    $db->rollBack();
                }
            } catch (Exception $e2) {
            }
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
    }

    public function viewSubscription($id)
    {
        try {
            $subscription = $this->subscription->find($id);
            if (!$subscription) {
                echo view('errors/404', ['title' => 'Subscription Not Found']);
                return;
            }
            echo view('admin/subscription_view', [
                'title' => 'Subscription Details',
                'subscription' => $subscription
            ]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo view('errors/500', ['title' => '500 Internal Server Error']);
        }
    }

    public function getSubscription($id)
    {
        header('Content-Type: application/json');
        try {
            $subscription = $this->subscription->find($id);
            if (!$subscription) {
                http_response_code(404);
                echo json_encode(['error' => 'Subscription not found']);
                return;
            }
            echo json_encode($subscription);
        } catch (Exception $e) {
            error_log($e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }
} 