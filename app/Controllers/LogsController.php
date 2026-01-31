<?php

namespace App\Controllers;

use App\Models\ActivityLog;
use App\Models\Property;
use App\Models\User;
use Exception;

class LogsController
{
    private $activityLog;
    private $propertyModel;
    private $userModel;

    public function __construct()
    {
        // Require authentication
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Please login to continue';
            $_SESSION['flash_type'] = 'danger';
            redirect('/home');
        }

        $this->activityLog = new ActivityLog();
        $this->propertyModel = new Property();
        $this->userModel = new User();
        $this->userModel->find($_SESSION['user_id']);
    }

    public function index()
    {
        try {
            $userId = $_SESSION['user_id'];
            $role = strtolower($_SESSION['user_role'] ?? '');

            // Filters
            $filters = [];
            $filters['action'] = isset($_GET['action']) && $_GET['action'] !== '' ? trim($_GET['action']) : null;
            $filters['entity_type'] = isset($_GET['entity_type']) && $_GET['entity_type'] !== '' ? trim($_GET['entity_type']) : null;
            $filters['property_id'] = isset($_GET['property_id']) && is_numeric($_GET['property_id']) ? (int)$_GET['property_id'] : null;
            $filters['start_date'] = isset($_GET['start_date']) && $_GET['start_date'] !== '' ? $_GET['start_date'] . ' 00:00:00' : null;
            $filters['end_date'] = isset($_GET['end_date']) && $_GET['end_date'] !== '' ? $_GET['end_date'] . ' 23:59:59' : null;
            if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) { $filters['user_id'] = (int)$_GET['user_id']; }

            // Get logs based on role
            $isAdmin = in_array($role, ['admin','administrator']);
            if ($isAdmin) {
                $logs = $this->activityLog->getLogs(array_filter($filters));
                $properties = $this->propertyModel->getAll();
                $users = $this->userModel->getAllUsers();
            } else {
                $logs = $this->activityLog->getLogsForUserScope($userId, array_filter($filters));
                $properties = $this->propertyModel->getAll($userId);
                $users = [];
            }

            echo view('logs/index', [
                'title' => 'Activity Logs - RentSmart',
                'logs' => $logs,
                'properties' => $properties,
                'isAdmin' => $isAdmin,
                'users' => $users
            ]);
        } catch (Exception $e) {
            error_log('LogsController@index error: ' . $e->getMessage());
            echo view('errors/500', [
                'title' => '500 Internal Server Error'
            ]);
        }
    }
}
