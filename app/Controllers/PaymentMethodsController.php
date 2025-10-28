<?php

namespace App\Controllers;

use App\Models\PaymentMethod;

class PaymentMethodsController
{
    private $paymentMethod;

    public function __construct()
    {
        $this->paymentMethod = new PaymentMethod();
    }

    /**
     * Display all payment methods
     */
    public function index()
    {
        try {
            $paymentMethods = $this->paymentMethod->getAll();
            
            echo view('admin/payment_methods', [
                'title' => 'Payment Methods - RentSmart',
                'paymentMethods' => $paymentMethods
            ]);

        } catch (\Exception $e) {
            error_log("Error in PaymentMethodsController::index: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading payment methods';
            $_SESSION['flash_type'] = 'danger';
            redirect('/dashboard');
        }
    }

    /**
     * Create new payment method
     */
    public function create()
    {
        try {
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            $name = trim($_POST['name'] ?? '');
            $type = trim($_POST['type'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $isActive = filter_input(INPUT_POST, 'is_active', FILTER_VALIDATE_BOOLEAN);

            if (!$name || !$type) {
                throw new \Exception('Name and type are required');
            }

            // Handle M-Pesa specific fields
            $details = [];
            if ($type === 'mpesa_manual') {
                $mpesaMethod = trim($_POST['mpesa_method'] ?? '');
                $details['mpesa_method'] = $mpesaMethod;
                
                if ($mpesaMethod === 'paybill') {
                    $details['paybill_number'] = trim($_POST['paybill_number'] ?? '');
                    $details['account_number'] = trim($_POST['account_number'] ?? '');
                } elseif ($mpesaMethod === 'till') {
                    $details['till_number'] = trim($_POST['till_number'] ?? '');
                }
            } elseif ($type === 'mpesa_stk') {
                $details['consumer_key'] = trim($_POST['consumer_key'] ?? '');
                $details['consumer_secret'] = trim($_POST['consumer_secret'] ?? '');
                $details['shortcode'] = trim($_POST['shortcode'] ?? '');
                $details['passkey'] = trim($_POST['passkey'] ?? '');
            }

            $data = [
                'name' => $name,
                'type' => $type,
                'description' => $description,
                'is_active' => $isActive ? 1 : 0,
                'details' => json_encode($details),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $paymentMethodId = $this->paymentMethod->create($data);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment method created successfully',
                    'payment_method_id' => $paymentMethodId
                ]);
                exit;
            }

            $_SESSION['flash_message'] = 'Payment method created successfully';
            $_SESSION['flash_type'] = 'success';
            redirect('/payment-methods');

        } catch (\Exception $e) {
            error_log("Error in PaymentMethodsController::create: " . $e->getMessage());
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
                exit;
            }

            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            redirect('/payment-methods');
        }
    }

    /**
     * Update payment method
     */
    public function update($id)
    {
        try {
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            $name = trim($_POST['name'] ?? '');
            $type = trim($_POST['type'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $isActive = filter_input(INPUT_POST, 'is_active', FILTER_VALIDATE_BOOLEAN);

            if (!$name || !$type) {
                throw new \Exception('Name and type are required');
            }

            // Handle M-Pesa specific fields
            $details = [];
            if ($type === 'mpesa_manual') {
                $mpesaMethod = trim($_POST['mpesa_method'] ?? '');
                $details['mpesa_method'] = $mpesaMethod;
                
                if ($mpesaMethod === 'paybill') {
                    $details['paybill_number'] = trim($_POST['paybill_number'] ?? '');
                    $details['account_number'] = trim($_POST['account_number'] ?? '');
                } elseif ($mpesaMethod === 'till') {
                    $details['till_number'] = trim($_POST['till_number'] ?? '');
                }
            } elseif ($type === 'mpesa_stk') {
                $details['consumer_key'] = trim($_POST['consumer_key'] ?? '');
                $details['consumer_secret'] = trim($_POST['consumer_secret'] ?? '');
                $details['shortcode'] = trim($_POST['shortcode'] ?? '');
                $details['passkey'] = trim($_POST['passkey'] ?? '');
            }

            $data = [
                'name' => $name,
                'type' => $type,
                'description' => $description,
                'is_active' => $isActive ? 1 : 0,
                'details' => json_encode($details),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->paymentMethod->update($id, $data);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment method updated successfully'
                ]);
                exit;
            }

            $_SESSION['flash_message'] = 'Payment method updated successfully';
            $_SESSION['flash_type'] = 'success';
            redirect('/payment-methods');

        } catch (\Exception $e) {
            error_log("Error in PaymentMethodsController::update: " . $e->getMessage());
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
                exit;
            }

            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            redirect('/payment-methods');
        }
    }

    /**
     * Delete payment method
     */
    public function delete($id)
    {
        try {
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            $this->paymentMethod->delete($id);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment method deleted successfully'
                ]);
                exit;
            }

            $_SESSION['flash_message'] = 'Payment method deleted successfully';
            $_SESSION['flash_type'] = 'success';
            redirect('/payment-methods');

        } catch (\Exception $e) {
            error_log("Error in PaymentMethodsController::delete: " . $e->getMessage());
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
                exit;
            }

            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            redirect('/payment-methods');
        }
    }

    /**
     * Get payment method details
     */
    public function get($id)
    {
        try {
            $paymentMethod = $this->paymentMethod->getById($id);
            
            if (!$paymentMethod) {
                throw new \Exception('Payment method not found');
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'payment_method' => $paymentMethod
            ]);
            exit;

        } catch (\Exception $e) {
            error_log("Error in PaymentMethodsController::get: " . $e->getMessage());
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }
}
