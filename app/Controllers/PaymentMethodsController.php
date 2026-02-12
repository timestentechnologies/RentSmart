<?php

namespace App\Controllers;

use App\Models\PaymentMethod;
use App\Models\Property;

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
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $userId = $_SESSION['user_id'] ?? null;
            $role = strtolower($_SESSION['user_role'] ?? '');
            $isAdmin = in_array($role, ['administrator', 'admin'], true);

            $paymentMethods = $isAdmin
                ? $this->paymentMethod->getAll()
                : $this->paymentMethod->getByUser($userId);
            
            // Properties for linking in the UI
            $propertyModel = new Property();
            $properties = $isAdmin ? $propertyModel->getAll() : $propertyModel->getAll($userId);
            
            // Map linked properties per payment method (by names)
            $propNamesById = [];
            foreach (($properties ?? []) as $p) {
                $propNamesById[(int)$p['id']] = $p['name'] ?? ('Property #' . $p['id']);
            }
            $linkedPropertiesByMethod = [];
            $linkedPropertyIdsByMethod = [];
            foreach (($paymentMethods ?? []) as $pm) {
                $ids = $this->paymentMethod->getPropertyIdsForMethod($pm['id']);
                $names = [];
                foreach ($ids as $pid) {
                    if (isset($propNamesById[$pid])) {
                        $names[] = $propNamesById[$pid];
                    }
                }
                $linkedPropertiesByMethod[$pm['id']] = $names;
                $linkedPropertyIdsByMethod[$pm['id']] = $ids;
            }
            
            echo view('admin/payment_methods', [
                'title' => 'Payment Methods - RentSmart',
                'paymentMethods' => $paymentMethods,
                'properties' => $properties,
                'linkedPropertiesByMethod' => $linkedPropertiesByMethod,
                'linkedPropertyIdsByMethod' => $linkedPropertyIdsByMethod
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
            
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $ownerUserId = $_SESSION['user_id'] ?? null;
            $role = strtolower($_SESSION['user_role'] ?? '');
            $isAdmin = in_array($role, ['administrator', 'admin'], true);

            $name = trim($_POST['name'] ?? '');
            $type = trim($_POST['type'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $isActive = filter_input(INPUT_POST, 'is_active', FILTER_VALIDATE_BOOLEAN);
            $scope = strtolower(trim((string)($_POST['scope'] ?? 'tenant')));
            if (!$isAdmin) {
                $scope = 'tenant';
            }
            if ($scope !== 'subscription') {
                $scope = 'tenant';
            }

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
                'scope' => $scope,
                // Column must exist in DB: ALTER TABLE payment_methods ADD COLUMN owner_user_id INT NULL;
                'owner_user_id' => $ownerUserId,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $paymentMethodId = $this->paymentMethod->create($data);
            // Link to properties (if provided)
            $propertyIds = $_POST['property_ids'] ?? [];
            if (!is_array($propertyIds)) { $propertyIds = []; }
            if ($scope === 'tenant') {
                $this->paymentMethod->assignProperties($paymentMethodId, $propertyIds);
            } else {
                $this->paymentMethod->assignProperties($paymentMethodId, []);
            }

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
            
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $userId = $_SESSION['user_id'] ?? null;
            $role = strtolower($_SESSION['user_role'] ?? '');
            $isAdmin = in_array($role, ['administrator', 'admin'], true);

            // Ownership guard for non-admins
            $existing = $this->paymentMethod->getById($id);
            if (!$existing) {
                throw new \Exception('Payment method not found');
            }
            if (!$isAdmin && isset($existing['owner_user_id']) && (int)$existing['owner_user_id'] !== (int)$userId) {
                throw new \Exception('You are not authorized to modify this payment method');
            }

            $name = trim($_POST['name'] ?? '');
            $type = trim($_POST['type'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $isActive = filter_input(INPUT_POST, 'is_active', FILTER_VALIDATE_BOOLEAN);
            $scope = strtolower(trim((string)($_POST['scope'] ?? ($existing['scope'] ?? 'tenant'))));
            if (!$isAdmin) {
                $scope = 'tenant';
            }
            if ($scope !== 'subscription') {
                $scope = 'tenant';
            }

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
                'scope' => $scope,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->paymentMethod->update($id, $data);
            // Update property links
            $propertyIds = $_POST['property_ids'] ?? [];
            if (!is_array($propertyIds)) { $propertyIds = []; }
            if ($scope === 'tenant') {
                $this->paymentMethod->assignProperties($id, $propertyIds);
            } else {
                $this->paymentMethod->assignProperties($id, []);
            }

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
            
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $userId = $_SESSION['user_id'] ?? null;
            $role = strtolower($_SESSION['user_role'] ?? '');
            $isAdmin = in_array($role, ['administrator', 'admin'], true);

            // Ownership guard for non-admins
            $existing = $this->paymentMethod->getById($id);
            if (!$existing) {
                throw new \Exception('Payment method not found');
            }
            if (!$isAdmin && isset($existing['owner_user_id']) && (int)$existing['owner_user_id'] !== (int)$userId) {
                throw new \Exception('You are not authorized to delete this payment method');
            }

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
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $userId = $_SESSION['user_id'] ?? null;
            $role = strtolower($_SESSION['user_role'] ?? '');
            $isAdmin = in_array($role, ['administrator', 'admin'], true);

            $paymentMethod = $this->paymentMethod->getById($id);
            
            if (!$paymentMethod) {
                throw new \Exception('Payment method not found');
            }

            // Non-admins can only view their own payment methods
            if (!$isAdmin && isset($paymentMethod['owner_user_id']) && (int)$paymentMethod['owner_user_id'] !== (int)$userId) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'You are not authorized to view this payment method'
                ]);
                exit;
            }

            // Include linked properties for the edit form
            $linkedPropertyIds = $this->paymentMethod->getPropertyIdsForMethod($id);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'payment_method' => $paymentMethod,
                'property_ids' => $linkedPropertyIds
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
