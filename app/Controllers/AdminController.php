<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\MpesaTransaction;
use Exception;
use DateTime;

class AdminController
{
    private $user;
    private $subscription;
    private $payment;
    private $mpesaTransaction;

    public function __construct()
    {
        $this->user = new User();
        $this->subscription = new Subscription();
        $this->payment = new Payment();
        $this->mpesaTransaction = new MpesaTransaction();

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
            $this->user->createUser([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => $role
            ]);

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
                
                // 4. If landlord, delete properties (cascades to units, leases, payments, mpesa transactions)
                if ($user['role'] === 'landlord') {
                    error_log("Deleting properties for landlord...");
                    $propertyModel = new \App\Models\Property();
                    $propertyModel->deleteByOwnerId($id);
                    error_log("Properties deleted successfully");
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
            
            echo view('admin/subscriptions', [
                'title' => 'Subscription Management - RentSmart',
                'plans' => $plans,
                'subscriptions' => $subscriptions
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

            if (!$planId || !$name || !$price || !$description || !$features) {
                $_SESSION['flash_message'] = 'All fields are required';
                $_SESSION['flash_type'] = 'danger';
                redirect('/admin/subscriptions');
            }

            $this->subscription->updatePlan($planId, [
                'name' => $name,
                'price' => $price,
                'description' => $description,
                'features' => $features
            ]);

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
            $mpesa_transactions = $this->mpesaTransaction->getAllTransactions();
            
            echo view('admin/payments', [
                'title' => 'Payment Management - RentSmart',
                'payments' => $payments,
                'mpesa_transactions' => $mpesa_transactions
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