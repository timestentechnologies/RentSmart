<?php

namespace App\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\Property;
use App\Models\Notification;
use App\Models\Lease;

class TenantMaintenanceController
{
    private $maintenanceRequest;
    private $tenant;
    private $unit;
    private $property;
    private $lease;

    public function __construct()
    {
        $this->maintenanceRequest = new MaintenanceRequest();
        $this->tenant = new Tenant();
        $this->unit = new Unit();
        $this->property = new Property();
        $this->lease = new Lease();
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

            // Get tenant's active lease to reliably determine unit/property
            $lease = $this->lease->getActiveLeaseByTenant($tenantId);
            if (!$lease) {
                throw new \Exception('No active lease found for this tenant');
            }

            $unitId = (int)($lease['unit_id'] ?? 0);
            if ($unitId <= 0) {
                throw new \Exception('No unit linked to active lease');
            }

            $unitData = $this->unit->find($unitId);
            if (!$unitData) {
                throw new \Exception('Unit not found');
            }

            $propertyId = (int)($unitData['property_id'] ?? 0);
            if ($propertyId <= 0) {
                throw new \Exception('Property not found for this unit');
            }

            // Get unit number if unit_id exists
            $unitNumber = null;
            $unitNumber = $unitData['unit_number'] ?? null;

            $data = [
                'tenant_id' => $tenantId,
                'unit_id' => $unitId,
                'unit_number' => $unitNumber,
                'property_id' => $propertyId,
                'title' => $title,
                'description' => $description,
                'category' => $category ?: 'other',
                'priority' => $priority ?: 'medium',
                'status' => 'pending',
                'requested_date' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $requestId = $this->maintenanceRequest->create($data);

            // Notify property staff (agent/manager/landlord/caretaker)
            try {
                if ($propertyId > 0) {
                    $property = $this->property->find($propertyId);
                    $recipients = [];
                    foreach (['owner_id', 'manager_id', 'agent_id', 'caretaker_user_id'] as $k) {
                        $uid = (int)($property[$k] ?? 0);
                        if ($uid > 0) {
                            $recipients[$uid] = true;
                        }
                    }

                    if (!empty($recipients)) {
                        $tenantName = trim((string)(($tenant['first_name'] ?? '') . ' ' . ($tenant['last_name'] ?? '')));
                        if ($tenantName === '') {
                            $tenantName = (string)($tenant['name'] ?? 'Tenant');
                        }
                        $propName = (string)($property['name'] ?? 'Property');
                        $unitTxt = $unitNumber ? ('Unit ' . $unitNumber) : 'Unit';

                        $notif = new Notification();
                        foreach (array_keys($recipients) as $userId) {
                            $notif->createNotification([
                                'recipient_type' => 'user',
                                'recipient_id' => (int)$userId,
                                'actor_type' => 'tenant',
                                'actor_id' => (int)$tenantId,
                                'title' => 'New Maintenance Request',
                                'body' => $tenantName . ' submitted a request at ' . $propName . ' (' . $unitTxt . '): ' . $title,
                                'link' => BASE_URL . '/maintenance',
                                'entity_type' => 'maintenance_request',
                                'entity_id' => (int)$requestId,
                                'payload' => [
                                    'tenant_id' => (int)$tenantId,
                                    'property_id' => (int)$propertyId,
                                    'unit_id' => (int)$unitId,
                                    'priority' => $data['priority'] ?? null,
                                    'category' => $data['category'] ?? null,
                                ],
                            ]);
                        }
                    }
                }
            } catch (\Throwable $notifyErr) {
                error_log('TenantMaintenanceController::create notify failed: ' . $notifyErr->getMessage());
            }

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
