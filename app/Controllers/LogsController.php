<?php

namespace App\Controllers;

use App\Models\ActivityLog;
use App\Models\Property;
use App\Models\Subscription;
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

            $isAdmin = in_array($role, ['admin','administrator']);
            if (!$isAdmin) {
                $isLandlord = ($role === 'landlord');
                $sub = new Subscription();
                $isEnterprise = $sub->isEnterprisePlan($userId);
                if (!$isLandlord || !$isEnterprise) {
                    $_SESSION['flash_message'] = 'Access denied';
                    $_SESSION['flash_type'] = 'danger';
                    redirect('/dashboard');
                }
            }

            // Filters
            $filters = [];
            $filters['action'] = isset($_GET['action']) && $_GET['action'] !== '' ? trim($_GET['action']) : null;
            $filters['entity_type'] = isset($_GET['entity_type']) && $_GET['entity_type'] !== '' ? trim($_GET['entity_type']) : null;
            $filters['property_id'] = isset($_GET['property_id']) && is_numeric($_GET['property_id']) ? (int)$_GET['property_id'] : null;
            $filters['start_date'] = isset($_GET['start_date']) && $_GET['start_date'] !== '' ? $_GET['start_date'] . ' 00:00:00' : null;
            $filters['end_date'] = isset($_GET['end_date']) && $_GET['end_date'] !== '' ? $_GET['end_date'] . ' 23:59:59' : null;
            if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) { $filters['user_id'] = (int)$_GET['user_id']; }

            // Get logs based on role
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

    public function export($format = 'csv')
    {
        try {
            $userId = $_SESSION['user_id'];
            $role = strtolower($_SESSION['user_role'] ?? '');

            $isAdmin = in_array($role, ['admin','administrator']);
            if (!$isAdmin) {
                $isLandlord = ($role === 'landlord');
                $sub = new Subscription();
                $isEnterprise = $sub->isEnterprisePlan($userId);
                if (!$isLandlord || !$isEnterprise) {
                    $_SESSION['flash_message'] = 'Access denied';
                    $_SESSION['flash_type'] = 'danger';
                    redirect('/dashboard');
                }
            }

            // Same filters as index
            $filters = [];
            $filters['action'] = isset($_GET['action']) && $_GET['action'] !== '' ? trim($_GET['action']) : null;
            $filters['entity_type'] = isset($_GET['entity_type']) && $_GET['entity_type'] !== '' ? trim($_GET['entity_type']) : null;
            $filters['property_id'] = isset($_GET['property_id']) && is_numeric($_GET['property_id']) ? (int)$_GET['property_id'] : null;
            $filters['start_date'] = isset($_GET['start_date']) && $_GET['start_date'] !== '' ? $_GET['start_date'] . ' 00:00:00' : null;
            $filters['end_date'] = isset($_GET['end_date']) && $_GET['end_date'] !== '' ? $_GET['end_date'] . ' 23:59:59' : null;
            if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) { $filters['user_id'] = (int)$_GET['user_id']; }

            if ($isAdmin) {
                $logs = $this->activityLog->getLogs(array_filter($filters));
            } else {
                $logs = $this->activityLog->getLogsForUserScope($userId, array_filter($filters));
            }

            if ($format === 'csv') {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="activity_logs.csv"');
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Date','User','Email','Role','Action','Entity','Entity ID','Property ID','IP','Details']);
                foreach ($logs as $l) {
                    fputcsv($out, [
                        $l['created_at'] ?? '',
                        $l['user_name'] ?? 'System',
                        $l['user_email'] ?? '',
                        $l['role'] ?? '',
                        $l['action'] ?? '',
                        $l['entity_type'] ?? '',
                        $l['entity_id'] ?? '',
                        $l['property_id'] ?? '',
                        $l['ip_address'] ?? '',
                        $l['details'] ?? ''
                    ]);
                }
                fclose($out);
                exit;
            }

            if ($format === 'xlsx') {
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment; filename="activity_logs.xls"');
                echo "<table border='1'>";
                echo '<tr><th>Date</th><th>User</th><th>Email</th><th>Role</th><th>Action</th><th>Entity</th><th>Entity ID</th><th>Property ID</th><th>IP</th><th>Details</th></tr>';
                foreach ($logs as $l) {
                    echo '<tr>'
                        .'<td>'.htmlspecialchars($l['created_at'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($l['user_name'] ?? 'System').'</td>'
                        .'<td>'.htmlspecialchars($l['user_email'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($l['role'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($l['action'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($l['entity_type'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($l['entity_id'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($l['property_id'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($l['ip_address'] ?? '').'</td>'
                        .'<td>'.htmlspecialchars($l['details'] ?? '').'</td>'
                        .'</tr>';
                }
                echo '</table>';
                exit;
            }

            http_response_code(400);
            echo 'Unsupported format';
        } catch (Exception $e) {
            error_log('LogsController@export error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Export error';
        }
        exit;
    }

    public function clearAll()
    {
        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                throw new Exception('Invalid request');
            }
            if (!function_exists('verify_csrf_token') || !verify_csrf_token()) {
                throw new Exception('Invalid CSRF token');
            }

            $userId = $_SESSION['user_id'];
            $role = strtolower($_SESSION['user_role'] ?? '');
            $isAdmin = in_array($role, ['admin','administrator']);

            if (!$isAdmin) {
                $isLandlord = ($role === 'landlord');
                $sub = new Subscription();
                $isEnterprise = $sub->isEnterprisePlan($userId);
                if (!$isLandlord || !$isEnterprise) {
                    $_SESSION['flash_message'] = 'Access denied';
                    $_SESSION['flash_type'] = 'danger';
                    redirect('/dashboard');
                }
            }

            $deleted = $isAdmin ? $this->activityLog->clearAll() : $this->activityLog->clearForUserScope($userId);
            $_SESSION['flash_message'] = "Deleted {$deleted} log entries";
            $_SESSION['flash_type'] = 'success';
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'Failed to clear logs: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
        redirect('/activity-logs');
    }
}
