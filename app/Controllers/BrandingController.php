<?php

namespace App\Controllers;

use App\Models\Setting;
use Exception;

class BrandingController
{
    private $settings;

    public function __construct()
    {
        $this->settings = new Setting();

        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Please login to continue';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        $role = strtolower((string)($_SESSION['user_role'] ?? ''));
        if (!in_array($role, ['manager', 'agent', 'landlord', 'realtor'], true)) {
            $_SESSION['flash_message'] = 'Access denied';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }

    public function index()
    {
        try {
            $userId = (int)($_SESSION['user_id'] ?? 0);
            $companyNameKey = 'company_name_user_' . $userId;
            $companyLogoKey = 'company_logo_user_' . $userId;

            $settings = $this->settings->getAllAsAssoc();

            $companyName = $settings[$companyNameKey] ?? '';
            $companyLogo = $settings[$companyLogoKey] ?? '';

            echo view('branding/index', [
                'title' => 'Company Branding',
                'companyName' => $companyName,
                'companyLogo' => $companyLogo,
            ]);
        } catch (Exception $e) {
            error_log('Error loading branding page: ' . $e->getMessage());
            echo view('errors/500', [
                'title' => '500 Internal Server Error'
            ]);
        }
    }

    public function update()
    {
        try {
            if (!verify_csrf_token()) {
                $_SESSION['flash_message'] = 'Invalid security token';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/branding');
                exit;
            }

            $userId = (int)($_SESSION['user_id'] ?? 0);
            $companyNameKey = 'company_name_user_' . $userId;
            $companyLogoKey = 'company_logo_user_' . $userId;

            $companyName = isset($_POST['company_name']) ? trim((string)$_POST['company_name']) : '';
            $this->settings->updateByKey($companyNameKey, $companyName);

            if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['company_logo'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (!in_array($ext, $allowed, true)) {
                    throw new Exception('Invalid logo file type. Allowed: jpg, jpeg, png, gif, webp');
                }

                $newName = 'company_logo_' . $userId . '_' . time() . '.' . $ext;
                $targetPath = __DIR__ . '/../../public/assets/images/' . $newName;

                if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                    throw new Exception('Failed to upload company logo');
                }

                $oldFile = $this->settings->get($companyLogoKey);
                if (!empty($oldFile)) {
                    $oldPath = __DIR__ . '/../../public/assets/images/' . $oldFile;
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                $this->settings->updateByKey($companyLogoKey, $newName);
            }

            $_SESSION['flash_message'] = 'Company branding updated successfully';
            $_SESSION['flash_type'] = 'success';
        } catch (Exception $e) {
            error_log('Error updating company branding: ' . $e->getMessage());
            $_SESSION['flash_message'] = 'Error updating company branding: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }

        header('Location: ' . BASE_URL . '/branding');
        exit;
    }
}
