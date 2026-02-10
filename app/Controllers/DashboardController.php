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

            $selectedMonth = isset($_GET['month']) ? trim((string)$_GET['month']) : date('Y-m');
            if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
                $selectedMonth = date('Y-m');
            }
            $allMonths = isset($_GET['all_months']) && (string)$_GET['all_months'] === '1';
            $selectedPropertyId = isset($_GET['property_id']) && $_GET['property_id'] !== '' ? (int)$_GET['property_id'] : null;

            $properties = $this->property->getAll($userId);
            $selectedPropertyName = null;
            if ($selectedPropertyId) {
                foreach ($properties as $pp) {
                    if ((int)($pp['id'] ?? 0) === (int)$selectedPropertyId) {
                        $selectedPropertyName = (string)($pp['name'] ?? '');
                        break;
                    }
                }
            }
            
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

            if ($selectedPropertyName !== null && $selectedPropertyName !== '') {
                $recentProperties = array_values(array_filter($recentProperties, function ($p) use ($selectedPropertyName) {
                    return isset($p['name']) && (string)$p['name'] === $selectedPropertyName;
                }));
                $activeLeases = array_values(array_filter($activeLeases, function ($l) use ($selectedPropertyName) {
                    return isset($l['property_name']) && (string)$l['property_name'] === $selectedPropertyName;
                }));
                $expiringLeases = array_values(array_filter($expiringLeases, function ($l) use ($selectedPropertyName) {
                    return isset($l['property_name']) && (string)$l['property_name'] === $selectedPropertyName;
                }));
                $recentPayments = array_values(array_filter($recentPayments, function ($p) use ($selectedPropertyName) {
                    return isset($p['property_name']) && (string)$p['property_name'] === $selectedPropertyName;
                }));
            }
            
            // Calculate total expenses (selected month) and adjust revenue by rent-balance-funded expenses
            // If all_months is enabled, compute from earliest relevant month up to end of selected month.
            $startOfMonth = $selectedMonth . '-01';
            $endOfMonth = date('Y-m-t', strtotime($startOfMonth));

            $rangeStart = $startOfMonth;
            $rangeEnd = $endOfMonth;
            $totalExpenses = $this->expense->getTotalForPeriod($userId, $rangeStart, $rangeEnd);
            $rentBalanceExpenses = $this->expense->getTotalForPeriod($userId, $rangeStart, $rangeEnd, 'rent_balance');

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

            $propertyWhere = '';
            $propertyParams = [];
            if ($selectedPropertyId) {
                $propertyWhere = " AND pr.id = ?";
                $propertyParams[] = $selectedPropertyId;
            }

            // If all_months is enabled, we start from the earliest active lease start month the user can access.
            if ($allMonths) {
                try {
                    $stmtMinLease = $db->prepare(
                        "SELECT MIN(l.start_date) AS d\n"
                        . "FROM leases l\n"
                        . "JOIN units u ON l.unit_id = u.id\n"
                        . "JOIN properties pr ON u.property_id = pr.id\n"
                        . "WHERE l.status = 'active'" . $accessWhere . $propertyWhere
                    );
                    $stmtMinLease->execute(array_merge($accessParams, $propertyParams));
                    $minLeaseDate = (string)($stmtMinLease->fetch(\PDO::FETCH_ASSOC)['d'] ?? '');
                    if ($minLeaseDate !== '') {
                        $dtMin = new \DateTime($minLeaseDate);
                        $dtMin = new \DateTime($dtMin->format('Y-m-01'));
                        $dtSelected = new \DateTime($startOfMonth);
                        if ($dtMin <= $dtSelected) {
                            $rangeStart = $dtMin->format('Y-m-d');
                            $rangeEnd = $endOfMonth;
                        }
                    }
                } catch (\Throwable $e) {
                    // keep default range
                }
            }

            // Received amounts (selected month or all_months range)
            // Use applies_to_month when present (fallback to payment_date), so late-paid rent can be counted in the correct month.
            $stmtRentReceived = $db->prepare(
                "SELECT COALESCE(SUM(CASE WHEN p.amount > 0 THEN p.amount ELSE 0 END),0) AS s\n"
                . "FROM payments p\n"
                . "JOIN leases l ON p.lease_id = l.id\n"
                . "JOIN units u ON l.unit_id = u.id\n"
                . "JOIN properties pr ON u.property_id = pr.id\n"
                . "WHERE p.payment_type = 'rent'\n"
                . "  AND p.status IN ('completed','verified')\n"
                . "  AND (\n"
                . "        (p.applies_to_month IS NOT NULL AND p.applies_to_month BETWEEN ? AND ?)\n"
                . "     OR (p.applies_to_month IS NULL AND p.payment_date BETWEEN ? AND ?)\n"
                . "  )" . $accessWhere . $propertyWhere
            );
            $stmtRentReceived->execute(array_merge([$rangeStart, $rangeEnd, $rangeStart, $rangeEnd], $accessParams, $propertyParams));
            $rentReceived = (float)($stmtRentReceived->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);

            $stmtUtilityReceived = $db->prepare(
                "SELECT COALESCE(SUM(CASE WHEN p.amount > 0 THEN p.amount ELSE 0 END),0) AS s\n"
                . "FROM payments p\n"
                . "JOIN leases l ON p.lease_id = l.id\n"
                . "JOIN units u ON l.unit_id = u.id\n"
                . "JOIN properties pr ON u.property_id = pr.id\n"
                . "WHERE p.payment_type = 'utility'\n"
                . "  AND p.status IN ('completed','verified')\n"
                . "  AND (\n"
                . "        (p.applies_to_month IS NOT NULL AND p.applies_to_month BETWEEN ? AND ?)\n"
                . "     OR (p.applies_to_month IS NULL AND p.payment_date BETWEEN ? AND ?)\n"
                . "  )" . $accessWhere . $propertyWhere
            );
            $stmtUtilityReceived->execute(array_merge([$rangeStart, $rangeEnd, $rangeStart, $rangeEnd], $accessParams, $propertyParams));
            $utilityReceived = (float)($stmtUtilityReceived->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);

            $stmtMaintenanceReceived = $db->prepare(
                "SELECT COALESCE(SUM(CASE WHEN p.amount > 0 THEN p.amount ELSE 0 END),0) AS s\n"
                . "FROM payments p\n"
                . "JOIN leases l ON p.lease_id = l.id\n"
                . "JOIN units u ON l.unit_id = u.id\n"
                . "JOIN properties pr ON u.property_id = pr.id\n"
                . "WHERE p.payment_type = 'other'\n"
                . "  AND p.status IN ('completed','verified')\n"
                . "  AND (\n"
                . "        (p.applies_to_month IS NOT NULL AND p.applies_to_month BETWEEN ? AND ?)\n"
                . "     OR (p.applies_to_month IS NULL AND p.payment_date BETWEEN ? AND ?)\n"
                . "  )\n"
                . "  AND (p.notes LIKE 'Maintenance payment:%' OR p.notes LIKE '%MAINT-%')" . $accessWhere . $propertyWhere
            );
            $stmtMaintenanceReceived->execute(array_merge([$rangeStart, $rangeEnd, $rangeStart, $rangeEnd], $accessParams, $propertyParams));
            $maintenanceReceived = (float)($stmtMaintenanceReceived->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);

            $receivedTotal = $rentReceived + $utilityReceived + $maintenanceReceived;

            // Expected amounts (selected month or all_months range) and money-not-received (Expected - Received)
            // This aligns the dashboard cards with the wallet/received figures.
            $rentExpected = 0.0;
            $utilityExpected = 0.0;
            $maintenanceExpected = 0.0;

            try {
                $stmtLeases = $db->prepare(
                    "SELECT l.id AS lease_id, l.rent_amount, l.start_date, u.id AS unit_id\n"
                    . "FROM leases l\n"
                    . "JOIN units u ON l.unit_id = u.id\n"
                    . "JOIN properties pr ON u.property_id = pr.id\n"
                    . "WHERE l.status = 'active'" . $accessWhere . $propertyWhere
                );
                $stmtLeases->execute(array_merge($accessParams, $propertyParams));
                $leasesRows = $stmtLeases->fetchAll(\PDO::FETCH_ASSOC) ?: [];

                $unitsUtilitiesStmt = $db->prepare("SELECT id, is_metered, flat_rate FROM utilities WHERE unit_id = ?");
                $utilityChargeMeteredForMonthStmt = $db->prepare(
                    "SELECT COALESCE(cost,0) AS c\n"
                    . "FROM utility_readings\n"
                    . "WHERE utility_id = ?\n"
                    . "  AND reading_date BETWEEN ? AND ?\n"
                    . "ORDER BY reading_date DESC, id DESC\n"
                    . "LIMIT 1"
                );

                $utilityChargeMeteredForRangeStmt = $db->prepare(
                    "SELECT COALESCE(SUM(cost),0) AS c\n"
                    . "FROM utility_readings\n"
                    . "WHERE utility_id = ?\n"
                    . "  AND reading_date BETWEEN ? AND ?"
                );

                $maintChargesStmt = $db->prepare(
                    "SELECT COALESCE(SUM(ABS(amount)),0) AS s\n"
                    . "FROM payments\n"
                    . "WHERE lease_id = ?\n"
                    . "  AND payment_type = 'other'\n"
                    . "  AND amount < 0\n"
                    . "  AND notes LIKE '%MAINT-%'\n"
                    . "  AND status IN ('completed','verified')\n"
                    . "  AND payment_date BETWEEN ? AND ?"
                );

                foreach ($leasesRows as $L) {
                    $rent = (float)($L['rent_amount'] ?? 0);
                    if ($rent > 0) {
                        try {
                            $leaseStart = new \DateTime((string)($L['start_date'] ?? ''));
                            $leaseStartMonth = new \DateTime($leaseStart->format('Y-m-01'));
                            $selectedMonthDt = new \DateTime($startOfMonth);
                            if ($selectedMonthDt >= $leaseStartMonth) {
                                if ($allMonths) {
                                    $monthsElapsed = ((int)$selectedMonthDt->format('Y') - (int)$leaseStartMonth->format('Y')) * 12
                                        + ((int)$selectedMonthDt->format('n') - (int)$leaseStartMonth->format('n')) + 1;
                                    $rentExpected += max(0, $monthsElapsed) * $rent;
                                } else {
                                    $rentExpected += $rent;
                                }
                            }
                        } catch (\Throwable $e) {
                            // ignore invalid dates
                        }
                    }

                    // Utilities expected for selected month (per utility: metered reading for month or flat rate)
                    $unitsUtilitiesStmt->execute([(int)$L['unit_id']]);
                    $utils = $unitsUtilitiesStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                    foreach ($utils as $ut) {
                        if ((int)($ut['is_metered'] ?? 0) === 1) {
                            if ($allMonths) {
                                $utilityChargeMeteredForRangeStmt->execute([(int)$ut['id'], $rangeStart, $rangeEnd]);
                                $utilityExpected += (float)($utilityChargeMeteredForRangeStmt->fetch(\PDO::FETCH_ASSOC)['c'] ?? 0);
                            } else {
                                $utilityChargeMeteredForMonthStmt->execute([(int)$ut['id'], $startOfMonth, $endOfMonth]);
                                $utilityExpected += (float)($utilityChargeMeteredForMonthStmt->fetch(\PDO::FETCH_ASSOC)['c'] ?? 0);
                            }
                        } else {
                            if ($allMonths) {
                                $leaseStartMonthForFlat = null;
                                try {
                                    $leaseStart = new \DateTime((string)($L['start_date'] ?? ''));
                                    $leaseStartMonthForFlat = new \DateTime($leaseStart->format('Y-m-01'));
                                } catch (\Throwable $e) {
                                    $leaseStartMonthForFlat = null;
                                }
                                if ($leaseStartMonthForFlat !== null) {
                                    $selectedMonthDt = new \DateTime($startOfMonth);
                                    if ($selectedMonthDt >= $leaseStartMonthForFlat) {
                                        $monthsElapsed = ((int)$selectedMonthDt->format('Y') - (int)$leaseStartMonthForFlat->format('Y')) * 12
                                            + ((int)$selectedMonthDt->format('n') - (int)$leaseStartMonthForFlat->format('n')) + 1;
                                        $utilityExpected += max(0, $monthsElapsed) * (float)($ut['flat_rate'] ?? 0);
                                    }
                                }
                            } else {
                                $utilityExpected += (float)($ut['flat_rate'] ?? 0);
                            }
                        }
                    }

                    // Maintenance expected for selected month/range (based on charges posted)
                    $maintChargesStmt->execute([(int)$L['lease_id'], $rangeStart, $rangeEnd]);
                    $maintenanceExpected += (float)($maintChargesStmt->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);
                }
            } catch (\Throwable $e) {
                $rentExpected = 0.0;
                $utilityExpected = 0.0;
                $maintenanceExpected = 0.0;
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

            // Active tenants: tenants assigned to a unit
            $activeTenantsCount = 0;
            try {
                $sqlActiveTenants =
                    "SELECT COUNT(DISTINCT t.id) AS c\n"
                    . "FROM tenants t\n"
                    . "JOIN units u ON t.unit_id = u.id\n"
                    . "JOIN properties pr ON u.property_id = pr.id\n"
                    . "WHERE t.unit_id IS NOT NULL";
                $paramsActiveTenants = [];
                if (!$isAdmin) {
                    $sqlActiveTenants .= " AND (pr.owner_id = ? OR pr.manager_id = ? OR pr.agent_id = ? OR pr.caretaker_user_id = ?)";
                    $paramsActiveTenants = [$userId, $userId, $userId, $userId];
                }
                if ($selectedPropertyId) {
                    $sqlActiveTenants .= " AND pr.id = ?";
                    $paramsActiveTenants[] = $selectedPropertyId;
                }
                $stmtAT = $db->prepare($sqlActiveTenants);
                $stmtAT->execute($paramsActiveTenants);
                $activeTenantsCount = (int)($stmtAT->fetch(\PDO::FETCH_ASSOC)['c'] ?? 0);
            } catch (\Throwable $e) {
                $activeTenantsCount = 0;
            }

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