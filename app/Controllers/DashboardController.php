<?php

namespace App\Controllers;

use App\Models\Property;
use App\Models\Tenant;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Expense;

class DashboardController
{
    private $property;
    private $tenant;
    private $lease;
    private $payment;
    private $user;
    private $subscription;
    private $expense;

    public function __construct()
    {
        $this->property = new Property();
        $this->tenant = new Tenant();
        $this->lease = new Lease();
        $this->payment = new Payment();
        $this->user = new User();
        $this->subscription = new Subscription();
        $this->expense = new Expense();
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Please login to access the dashboard';
            $_SESSION['flash_type'] = 'warning';
            header('Location: ' . BASE_URL . '/');
            exit;
        }
        
        // Load user data
        $userData = $this->user->find($_SESSION['user_id']);
        $role = strtolower($userData['role']);
        // Check subscription for non-admin users
        if ($role !== 'admin' && $role !== 'administrator') {
            $subscription = $this->subscription->getUserSubscription($_SESSION['user_id']);
            if ($subscription) {
                $_SESSION['subscription_ends_at'] = $subscription['status'] === 'trialing' 
                    ? $subscription['trial_ends_at'] 
                    : $subscription['current_period_ends_at'];
                $_SESSION['subscription_status'] = $subscription['status'];
                // If subscription is not active, set the expired flag
                if (!$this->subscription->isSubscriptionActive($_SESSION['user_id'])) {
                    $_SESSION['subscription_expired'] = true;
                } else {
                    unset($_SESSION['subscription_expired']);
                }
            } else {
                // No subscription found, redirect to renewal page
                $_SESSION['flash_message'] = 'Please set up your subscription to continue.';
                $_SESSION['flash_type'] = 'warning';
                header('Location: ' . BASE_URL . '/subscription/renew');
                exit;
            }
        }
    }

    public function index()
    {
        try {
            // Get user-specific data
            $userId = $_SESSION['user_id'];
            
            // Get user data
            $user = $this->user->find($userId);
            
            // Get recent properties
            $recentProperties = $this->property->getRecent(5, $userId);
            
            // Get active leases
            $activeLeases = $this->lease->getActiveLeases($userId);
            
            // Get expiring leases (next 30 days)
            $expiringLeases = $this->lease->getExpiringLeases(30, $userId);
            
            // Get monthly revenue data for trend
            $monthlyRevenue = $this->payment->getMonthlyRevenue($userId);
            
            // Calculate revenue growth
            $currentMonth = date('Y-m');
            $lastMonth = date('Y-m', strtotime('-1 month'));
            
            $currentMonthRevenue = 0;
            $lastMonthRevenue = 0;
            
            foreach ($monthlyRevenue as $revenue) {
                if ($revenue['month'] === $currentMonth) {
                    $currentMonthRevenue = $revenue['total_amount'];
                }
                if ($revenue['month'] === $lastMonth) {
                    $lastMonthRevenue = $revenue['total_amount'];
                }
            }
            
            $revenueGrowth = $lastMonthRevenue > 0 
                ? (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 
                : 0;
            
            // Get revenue by property
            $propertyRevenue = $this->payment->getPaymentsByProperty($userId);
            
            // Get occupancy statistics
            $occupancyStats = $this->property->getOccupancyStats($userId);
            
            // Get outstanding balance
            $outstandingBalance = $this->payment->getOutstandingBalance($userId);
            
            // Get count of tenants with outstanding balance
            $outstandingTenants = $this->payment->getOutstandingTenantsCount($userId);
            
            // Get recent payments
            $recentPayments = $this->payment->getRecent(5, $userId);
            
            // Calculate total expenses (current month) and adjust revenue by rent-balance-funded expenses
            $startOfMonth = date('Y-m-01');
            $endOfMonth = date('Y-m-t');
            $totalExpenses = $this->expense->getTotalForPeriod($userId, $startOfMonth, $endOfMonth);
            $rentBalanceExpenses = $this->expense->getTotalForPeriod($userId, $startOfMonth, $endOfMonth, 'rent_balance');

            // Net revenue: payments minus expenses that draw from rent balance
            $totalRevenue = $currentMonthRevenue - $rentBalanceExpenses;
            
            // Calculate total properties
            $totalProperties = count($this->property->getAll($userId));
            
            // Calculate total active leases
            $totalActiveLeases = count($activeLeases);
            
            require 'views/dashboard/index.php';
        } catch (Exception $e) {
            error_log("Error in DashboardController::index: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading dashboard';
            $_SESSION['flash_type'] = 'danger';
            require 'views/errors/500.php';
        }
    }
} 