<?php
namespace App\Controllers;

use App\Models\Tenant;

class TenantAuthController
{
    public function loginForm()
    {
        // Redirect to home page since login is now in modal
        header('Location: ' . BASE_URL . '/');
        exit;
    }

    public function login()
    {
        session_start();
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $tenantModel = new Tenant();
        $tenant = $tenantModel->findByEmail($email);

        if ($tenant && $tenantModel->verifyPassword($tenant, $password)) {
            $_SESSION['tenant_id'] = $tenant['id'];
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful',
                    'redirect' => BASE_URL . '/tenant/dashboard'
                ]);
            } else {
                header('Location: ' . BASE_URL . '/tenant/dashboard');
            }
        } else {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid email or password'
                ]);
            } else {
                $_SESSION['flash_message'] = 'Invalid email or password.';
                $_SESSION['flash_type'] = 'danger';
                header('Location: /');
            }
        }
        exit;
    }

    public function logout()
    {
        session_start();
        // If impersonating, switch back to admin instead of logging out
        if (isset($_SESSION['impersonating'])) {
            return $this->switchBack();
        }
        
        unset($_SESSION['tenant_id']);
        session_destroy();
        header('Location: ' . BASE_URL . '/');
        exit;
    }

    /**
     * Login as tenant (for admin/agent/landlord)
     */
    public function loginAsTenant($tenantId)
    {
        session_start();
        
        // Check if user is logged in as admin/agent/landlord
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'agent', 'landlord', 'manager'])) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit;
            }
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $tenantModel = new Tenant();
        // Enforce role-based scoping: only allow impersonation of tenants this user can access
        $tenant = $tenantModel->getById($tenantId, $_SESSION['user_id']);

        if ($tenant) {
            // Store original user info in session for switching back
            $_SESSION['original_user'] = [
                'id' => $_SESSION['user_id'],
                'role' => $_SESSION['user_role'],
                'name' => $_SESSION['user_name']
            ];
            
            // Log in as tenant
            $_SESSION['tenant_id'] = $tenant['id'];
            $_SESSION['impersonating'] = true;

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'redirect' => BASE_URL . '/tenant/dashboard'
                ]);
            } else {
                header('Location: ' . BASE_URL . '/tenant/dashboard');
            }
        } else {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Tenant not found']);
            } else {
                $_SESSION['flash_message'] = 'Tenant not found';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/tenants');
            }
        }
        exit;
    }

    /**
     * Switch back to admin/agent/landlord account
     */
    public function switchBack()
    {
        session_start();
        
        if (isset($_SESSION['original_user']) && isset($_SESSION['impersonating'])) {
            // Restore original user session
            $originalUser = $_SESSION['original_user'];
            $_SESSION['user_id'] = $originalUser['id'];
            $_SESSION['user_role'] = $originalUser['role'];
            $_SESSION['user_name'] = $originalUser['name'];
            
            // Clear impersonation data
            unset($_SESSION['tenant_id'], $_SESSION['original_user'], $_SESSION['impersonating']);
            
            header('Location: ' . BASE_URL . '/tenants');
        } else {
            // If no original user found, redirect to login
            header('Location: ' . BASE_URL . '/login');
        }
        exit;
    }
}