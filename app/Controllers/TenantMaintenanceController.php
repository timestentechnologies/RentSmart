<?php

namespace App\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\Property;

class TenantMaintenanceController
{
    private $maintenanceRequest;
    private $tenant;
    private $unit;
    private $property;

    public function __construct()
    {
        $this->maintenanceRequest = new MaintenanceRequest();
        $this->tenant = new Tenant();
        $this->unit = new Unit();
        $this->property = new Property();
    }

    /**
     * Display maintenance requests for tenant
     */
    public function index()
    {
        session_start();
        if (!isset($_SESSION['tenant_id'])) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        try {
            $tenantId = $_SESSION['tenant_id'];
            $requests = $this->maintenanceRequest->getByTenant($tenantId);
            
            echo view('tenant/maintenance', [
                'title' => 'Maintenance Requests - RentSmart',
                'requests' => $requests
            ]);
        } catch (\Exception $e) {
            error_log("Error in TenantMaintenanceController::index: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading maintenance requests';
            $_SESSION['flash_type'] = 'danger';
            redirect('/tenant/dashboard');
        }
    }

    /**
     * Create new maintenance request
     */
    public function create()
    {
        session_start();
        if (!isset($_SESSION['tenant_id'])) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        try {
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            $tenantId = $_SESSION['tenant_id'];
            $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
            $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
            $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
            $priority = filter_input(INPUT_POST, 'priority', FILTER_SANITIZE_STRING);

            if (!$title || !$description) {
                throw new \Exception('Title and description are required');
            }

            // Get tenant info to populate property and unit
            $tenant = $this->tenant->find($tenantId);
            if (!$tenant) {
                throw new \Exception('Tenant not found');
            }

            // Get unit number if unit_id exists
            $unitNumber = null;
            if (!empty($tenant['unit_id'])) {
                $unit = new Unit();
                $unitData = $unit->find($tenant['unit_id']);
                $unitNumber = $unitData['unit_number'] ?? null;
            }

            $data = [
                'tenant_id' => $tenantId,
                'unit_id' => $tenant['unit_id'] ?? null,
                'unit_number' => $unitNumber,
                'property_id' => $tenant['property_id'] ?? null,
                'title' => $title,
                'description' => $description,
                'category' => $category ?: 'other',
                'priority' => $priority ?: 'medium',
                'status' => 'pending',
                'requested_date' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $requestId = $this->maintenanceRequest->create($data);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Maintenance request submitted successfully',
                    'request_id' => $requestId
                ]);
                exit;
            }

            $_SESSION['flash_message'] = 'Maintenance request submitted successfully';
            $_SESSION['flash_type'] = 'success';
            redirect('/tenant/maintenance');
        } catch (\Exception $e) {
            error_log("Error in TenantMaintenanceController::create: " . $e->getMessage());
            
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
            redirect('/tenant/maintenance');
        }
    }

    /**
     * Get detailed maintenance request information
     */
    public function get($id)
    {
        session_start();
        if (!isset($_SESSION['tenant_id'])) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        try {
            $tenantId = $_SESSION['tenant_id'];
            
            // Get request details with tenant verification
            $request = $this->maintenanceRequest->getByIdForTenant($id, $tenantId);
            
            if (!$request) {
                throw new \Exception('Maintenance request not found');
            }

            // Get additional details
            $unit = null;
            $property = null;
            
            if ($request['unit_id']) {
                $unitModel = new Unit();
                $unit = $unitModel->find($request['unit_id']);
            }
            
            if ($request['property_id']) {
                $propertyModel = new Property();
                $property = $propertyModel->find($request['property_id']);
            }

            $request['unit_details'] = $unit;
            $request['property_details'] = $property;

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'request' => $request
            ]);
            exit;

        } catch (\Exception $e) {
            error_log("Error in TenantMaintenanceController::get: " . $e->getMessage());
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * Show maintenance request form
     */
    public function showForm()
    {
        session_start();
        if (!isset($_SESSION['tenant_id'])) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        echo view('tenant/maintenance_form', [
            'title' => 'Submit Maintenance Request - RentSmart'
        ]);
    }

}
