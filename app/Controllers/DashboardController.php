<?php

namespace App\Controllers;

use App\Models\Property;
use App\Models\Tenant;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Expense;
use App\Models\Unit;

class DashboardController
{
    private $property;
    private $tenant;
    private $lease;
    private $payment;
    private $user;
    private $subscription;
    private $expense;
    private $unit;

    public function __construct()
    {
        $this->property = new Property();
        $this->tenant = new Tenant();
        $this->lease = new Lease();
        $this->payment = new Payment();
        $this->user = new User();
        $this->subscription = new Subscription();
        $this->expense = new Expense();
        $this->unit = new Unit();
        
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
        // Check subscription for non-admin users (allow caretakers, same as requireAuth)
        if (!in_array($role, ['admin', 'administrator', 'caretaker'])) {
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

            $db = $this->payment->getDb();

            $roleUser = new User();
            $roleUser->find($userId);
            $isAdmin = $roleUser->isAdmin();

            $accessWhere = '';
            $accessParams = [];
            if (!$isAdmin) {
                $accessWhere = " AND (pr.owner_id = ? OR pr.manager_id = ? OR pr.agent_id = ? OR pr.caretaker_user_id = ?)";
                $accessParams = [$userId, $userId, $userId, $userId];
            }

            // Received amounts (current month)
            $stmtRentReceived = $db->prepare(
                "SELECT COALESCE(SUM(CASE WHEN p.amount > 0 THEN p.amount ELSE 0 END),0) AS s\n"
                . "FROM payments p\n"
                . "JOIN leases l ON p.lease_id = l.id\n"
                . "JOIN units u ON l.unit_id = u.id\n"
                . "JOIN properties pr ON u.property_id = pr.id\n"
                . "WHERE p.payment_type = 'rent'\n"
                . "  AND p.status IN ('completed','verified')\n"
                . "  AND p.payment_date BETWEEN ? AND ?" . $accessWhere
            );
            $stmtRentReceived->execute(array_merge([$startOfMonth, $endOfMonth], $accessParams));
            $rentReceived = (float)($stmtRentReceived->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);

            $stmtUtilityReceived = $db->prepare(
                "SELECT COALESCE(SUM(CASE WHEN p.amount > 0 THEN p.amount ELSE 0 END),0) AS s\n"
                . "FROM payments p\n"
                . "JOIN leases l ON p.lease_id = l.id\n"
                . "JOIN units u ON l.unit_id = u.id\n"
                . "JOIN properties pr ON u.property_id = pr.id\n"
                . "WHERE p.payment_type = 'utility'\n"
                . "  AND p.status IN ('completed','verified')\n"
                . "  AND p.payment_date BETWEEN ? AND ?" . $accessWhere
            );
            $stmtUtilityReceived->execute(array_merge([$startOfMonth, $endOfMonth], $accessParams));
            $utilityReceived = (float)($stmtUtilityReceived->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);

            $stmtMaintenanceReceived = $db->prepare(
                "SELECT COALESCE(SUM(CASE WHEN p.amount > 0 THEN p.amount ELSE 0 END),0) AS s\n"
                . "FROM payments p\n"
                . "JOIN leases l ON p.lease_id = l.id\n"
                . "JOIN units u ON l.unit_id = u.id\n"
                . "JOIN properties pr ON u.property_id = pr.id\n"
                . "WHERE p.payment_type = 'other'\n"
                . "  AND p.status IN ('completed','verified')\n"
                . "  AND p.payment_date BETWEEN ? AND ?\n"
                . "  AND (p.notes LIKE 'Maintenance payment:%' OR p.notes LIKE '%MAINT-%')" . $accessWhere
            );
            $stmtMaintenanceReceived->execute(array_merge([$startOfMonth, $endOfMonth], $accessParams));
            $maintenanceReceived = (float)($stmtMaintenanceReceived->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);

            $receivedTotal = $rentReceived + $utilityReceived + $maintenanceReceived;

            // Expected (billed) for current month
            $stmtRentExpected = $db->prepare(
                "SELECT COALESCE(SUM(l.rent_amount),0) AS s\n"
                . "FROM leases l\n"
                . "JOIN units u ON l.unit_id = u.id\n"
                . "JOIN properties pr ON u.property_id = pr.id\n"
                . "WHERE l.status = 'active'" . $accessWhere
            );
            $stmtRentExpected->execute($accessParams);
            $rentExpected = (float)($stmtRentExpected->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);

            // Maintenance billed is stored as negative rent adjustments tagged MAINT-
            $stmtMaintenanceExpected = $db->prepare(
                "SELECT COALESCE(SUM(ABS(p.amount)),0) AS s\n"
                . "FROM payments p\n"
                . "JOIN leases l ON p.lease_id = l.id\n"
                . "JOIN units u ON l.unit_id = u.id\n"
                . "JOIN properties pr ON u.property_id = pr.id\n"
                . "WHERE p.payment_type = 'rent'\n"
                . "  AND p.amount < 0\n"
                . "  AND p.notes LIKE '%MAINT-%'\n"
                . "  AND p.status IN ('completed','verified')\n"
                . "  AND p.payment_date BETWEEN ? AND ?" . $accessWhere
            );
            $stmtMaintenanceExpected->execute(array_merge([$startOfMonth, $endOfMonth], $accessParams));
            $maintenanceExpected = (float)($stmtMaintenanceExpected->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);

            // Utilities expected for current month = latest reading cost within month (metered) or flat rate (flat)
            $utilityExpected = 0.0;
            try {
                $sqlUtilities = "SELECT ut.id, ut.is_metered, ut.flat_rate\n"
                    . "FROM utilities ut\n"
                    . "JOIN units un ON ut.unit_id = un.id\n"
                    . "JOIN leases l ON l.unit_id = un.id AND l.status = 'active'\n"
                    . "JOIN properties pr ON un.property_id = pr.id\n"
                    . "WHERE 1=1";
                $uParams = [];
                if (!$isAdmin) {
                    $sqlUtilities .= " AND (pr.owner_id = ? OR pr.manager_id = ? OR pr.agent_id = ? OR pr.caretaker_user_id = ?)";
                    $uParams = [$userId, $userId, $userId, $userId];
                }
                $sqlUtilities .= " GROUP BY ut.id ORDER BY ut.id";
                $stmtUtilities = $db->prepare($sqlUtilities);
                $stmtUtilities->execute($uParams);
                $utilitiesRows = $stmtUtilities->fetchAll(\PDO::FETCH_ASSOC) ?: [];

                $stmtReadingCost = $db->prepare(
                    "SELECT ur.cost\n"
                    . "FROM utility_readings ur\n"
                    . "WHERE ur.utility_id = ?\n"
                    . "  AND ur.reading_date BETWEEN ? AND ?\n"
                    . "ORDER BY ur.reading_date DESC, ur.id DESC LIMIT 1"
                );

                foreach ($utilitiesRows as $rowU) {
                    if (!empty($rowU['is_metered'])) {
                        $stmtReadingCost->execute([(int)$rowU['id'], $startOfMonth, $endOfMonth]);
                        $costRow = $stmtReadingCost->fetch(\PDO::FETCH_ASSOC) ?: [];
                        $utilityExpected += (float)($costRow['cost'] ?? 0);
                    } else {
                        $utilityExpected += (float)($rowU['flat_rate'] ?? 0);
                    }
                }
            } catch (\Exception $e) {
                $utilityExpected = 0.0;
            }

            $expectedTotal = $rentExpected + $utilityExpected + $maintenanceExpected;

            $notReceivedRent = max($rentExpected - $rentReceived, 0.0);
            $notReceivedUtility = max($utilityExpected - $utilityReceived, 0.0);
            $notReceivedMaintenance = max($maintenanceExpected - $maintenanceReceived, 0.0);
            $notReceivedTotal = $notReceivedRent + $notReceivedUtility + $notReceivedMaintenance;

            // Wallet: all received money less rent-balance deductions
            $walletTotal = max($receivedTotal - (float)$rentBalanceExpenses, 0.0);

            // Calculate total properties
            $totalProperties = count($this->property->getAll($userId));

            $totalTenants = count($this->tenant->getAll($userId));
            $totalUnits = count($this->unit->getAll($userId));
            $totalExpiringLeases = is_array($expiringLeases) ? count($expiringLeases) : 0;
            
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