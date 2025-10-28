<?php

namespace App\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\Tenant;
use App\Models\Property;
use App\Models\Unit;

class MaintenanceController
{
    private $maintenanceRequest;
    private $tenant;
    private $property;
    private $unit;

    public function __construct()
    {
        $this->maintenanceRequest = new MaintenanceRequest();
        $this->tenant = new Tenant();
        $this->property = new Property();
        $this->unit = new Unit();
    }

    /**
     * Display all maintenance requests for admin
     */
    public function index()
    {
        try {
            $userId = $_SESSION['user_id'];
            $requests = $this->maintenanceRequest->getAllForAdmin($userId);
            $statistics = $this->maintenanceRequest->getStatistics($userId);
            
            echo view('maintenance/index', [
                'title' => 'Maintenance Requests - RentSmart',
                'requests' => $requests,
                'statistics' => $statistics
            ]);
        } catch (\Exception $e) {
            error_log("Error in MaintenanceController::index: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading maintenance requests';
            $_SESSION['flash_type'] = 'danger';
            redirect('/dashboard');
        }
    }

    /**
     * Show specific maintenance request details
     */
    public function show($id)
    {
        try {
            $userId = $_SESSION['user_id'];
            $request = $this->maintenanceRequest->getById($id, $userId);
            
            if (!$request) {
                throw new \Exception('Maintenance request not found');
            }

            echo view('maintenance/show', [
                'title' => 'Maintenance Request Details - RentSmart',
                'request' => $request
            ]);
        } catch (\Exception $e) {
            error_log("Error in MaintenanceController::show: " . $e->getMessage());
            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            redirect('/maintenance');
        }
    }

    /**
     * Update maintenance request status
     */
    public function updateStatus()
    {
        try {
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
            $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
            $assignedTo = filter_input(INPUT_POST, 'assigned_to', FILTER_SANITIZE_STRING);
            $scheduledDate = filter_input(INPUT_POST, 'scheduled_date', FILTER_SANITIZE_STRING);
            $estimatedCost = filter_input(INPUT_POST, 'estimated_cost', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $actualCost = filter_input(INPUT_POST, 'actual_cost', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

            if (!$id || !$status) {
                throw new \Exception('Request ID and status are required');
            }

            $this->maintenanceRequest->updateStatus($id, $status, $notes, $assignedTo, $scheduledDate, $estimatedCost, $actualCost);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Maintenance request updated successfully'
                ]);
                exit;
            }

            $_SESSION['flash_message'] = 'Maintenance request updated successfully';
            $_SESSION['flash_type'] = 'success';
            redirect('/maintenance');
        } catch (\Exception $e) {
            error_log("Error in MaintenanceController::updateStatus: " . $e->getMessage());
            
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
            redirect('/maintenance');
        }
    }

    /**
     * Get maintenance request by ID (AJAX)
     */
    public function get($id)
    {
        try {
            $userId = $_SESSION['user_id'];
            $request = $this->maintenanceRequest->getById($id, $userId);
            
            if (!$request) {
                throw new \Exception('Maintenance request not found');
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'request' => $request
            ]);
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Delete maintenance request
     */
    public function delete($id)
    {
        try {
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            $userId = $_SESSION['user_id'];
            $request = $this->maintenanceRequest->getById($id, $userId);
            
            if (!$request) {
                throw new \Exception('Maintenance request not found');
            }

            $this->maintenanceRequest->delete($id);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Maintenance request deleted successfully'
                ]);
                exit;
            }

            $_SESSION['flash_message'] = 'Maintenance request deleted successfully';
            $_SESSION['flash_type'] = 'success';
            redirect('/maintenance');
        } catch (\Exception $e) {
            error_log("Error in MaintenanceController::delete: " . $e->getMessage());
            
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
            redirect('/maintenance');
        }
    }
}
