<?php

namespace App\Controllers;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use App\Models\Payment;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Lease;
use App\Models\User;
use App\Models\MaintenanceRequest;
use App\Models\Setting;
use App\Models\RealtorListing;
use App\Models\RealtorLead;

class ReportsController
{
    private $payment;
    private $property;
    private $tenant;
    private $lease;
    private $user;
    private $maintenance;
    private $setting;
    private $currentUser;

    public function __construct()
    {
        $this->payment = new Payment();
        $this->property = new Property();
        $this->tenant = new Tenant();
        $this->lease = new Lease();
        $this->user = new User();
        $this->maintenance = new MaintenanceRequest();
        $this->setting = new Setting();
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Please login to continue';
            $_SESSION['flash_type'] = 'danger';
            redirect('/home');
        }
        // Load user data
        $this->currentUser = $this->user->find($_SESSION['user_id']);
    }

    public function tenantBalances()
    {
        try {
            $userId = $_SESSION['user_id'];
            $isAdmin = $this->user->isAdmin();

            $period = $_GET['period'] ?? date('Y-m');
            if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
                $period = date('Y-m');
            }
            [$year, $month] = explode('-', $period);
            $year = (int)$year; $month = (int)$month;

            $propertyId = isset($_GET['property_id']) && $_GET['property_id'] !== '' ? (int)$_GET['property_id'] : null;
            $status = $_GET['status'] ?? 'all';
            $statusFilter = in_array($status, ['paid','due','advance'], true) ? $status : null;

            // Properties for dropdown (respect visibility)
            $properties = $this->property->getAll($isAdmin ? null : $userId);

            // Compute balances
            $rows = $this->payment->getMonthlyTenantBalances($year, $month, $propertyId, $statusFilter, $userId);

            echo view('reports/tenant_balances', [
                'title' => 'Monthly Tenant Balances',
                'period' => sprintf('%04d-%02d', $year, $month),
                'selectedPropertyId' => $propertyId,
                'status' => $statusFilter ? $statusFilter : 'all',
                'rows' => $rows,
                'properties' => $properties,
            ]);
        } catch (\Throwable $e) {
            error_log("Error in ReportsController::tenantBalances: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading tenant balances';
            $_SESSION['flash_type'] = 'danger';
            echo view('errors/500', ['title' => '500 Internal Server Error']);
        }
    }

    public function index()
    {
        try {
            $userId = $_SESSION['user_id'];
            $isAdmin = $this->user->isAdmin();
            $role = strtolower((string)($this->currentUser['role'] ?? ($_SESSION['user_role'] ?? '')));
            $isRealtor = ($role === 'realtor');

            if ($isRealtor) {
                $start = date('Y-m-01');
                $end = date('Y-m-t');

                $currentMonthRevenue = $this->payment->getRevenueForPeriod($start, $end, $userId);
                $recentPayments = $this->payment->getRealtorPaymentsByDateRange($start, $end, $userId);
                $recentPayments = array_slice($recentPayments ?: [], 0, 5);

                $listingModel = new RealtorListing();
                $leadModel = new RealtorLead();

                $totalListings = $listingModel->countAll($userId);
                $soldListings = $listingModel->countByStatus($userId, 'sold');
                $notSoldListings = max(0, $totalListings - $soldListings);

                $wonLeads = method_exists($leadModel, 'countWon')
                    ? $leadModel->countWon($userId)
                    : $leadModel->countByStatus($userId, 'won');

                $stats = [
                    'total_revenue' => $currentMonthRevenue,
                    'recent_payments' => $recentPayments,
                    'listings_total' => $totalListings,
                    'listings_sold' => $soldListings,
                    'listings_not_sold' => $notSoldListings,
                    'leads_won' => $wonLeads,
                ];

                echo view('reports/index', [
                    'title' => 'Reports',
                    'stats' => $stats,
                    'propertyRevenue' => [],
                    'users' => null,
                    'isAdmin' => false,
                    'isRealtor' => true,
                ]);
                return;
            }
            
            // Get current month's revenue
            $currentMonthRevenue = $this->payment->getRevenueForPeriod(
                date('Y-m-01'), // First day of current month
                date('Y-m-t'),  // Last day of current month
                $userId
            );

            // Get outstanding balance
            $outstandingBalance = $this->payment->getOutstandingBalance($userId);
            
            // Get occupancy data
            $occupancyStats = $this->property->getOccupancyStats($isAdmin ? null : $userId);
            
            // Get recent payments
            $recentPayments = $this->payment->getRecent(5, $userId);
            
            // Get property revenue data
            $propertyRevenue = $this->payment->getPaymentsByProperty($isAdmin ? null : $userId);
            
            // Get all users if admin
            $users = $isAdmin ? $this->user->getAllUsers() : null;
            
            // Prepare stats array
            $stats = [
                'total_revenue' => $currentMonthRevenue,
                'outstanding_balance' => $outstandingBalance,
                'occupancy' => $occupancyStats,
                'recent_payments' => $recentPayments
            ];
            
            echo view('reports/index', [
                'title' => 'Reports',
                'stats' => $stats,
                'propertyRevenue' => $propertyRevenue,
                'users' => $users,
                'isAdmin' => $isAdmin,
                'isRealtor' => false
            ]);
        } catch (Exception $e) {
            error_log("Error in ReportsController::index: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading reports';
            $_SESSION['flash_type'] = 'danger';
            echo view('errors/500');
        }
    }

    public function financial()
    {
        try {
            $userId = $_SESSION['user_id'];
            $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-1 month'));
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            
            // Get financial data
            $monthlyRevenue = $this->payment->getMonthlyRevenue($userId);
            $propertyRevenue = $this->payment->getPaymentsByProperty($userId);
            
            // Generate PDF report
            $data = [
                'title' => 'Financial Report',
                'startDate' => $startDate,
                'endDate' => $endDate,
                'monthlyRevenue' => $monthlyRevenue,
                'propertyRevenue' => $propertyRevenue
            ];
            
            generatePDF('reports/pdf/financial', $data);
        } catch (Exception $e) {
            error_log("Error in ReportsController::financial: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error generating financial report';
            $_SESSION['flash_type'] = 'danger';
            redirect('/reports');
        }
    }

    public function occupancy()
    {
        try {
            $userId = $_SESSION['user_id'];
            $isAdmin = $this->user->isAdmin();
            
            // Get occupancy data
            $occupancyStats = $this->property->getOccupancyStats($isAdmin ? null : $userId);
            $propertyOccupancy = $this->property->getOccupancyByProperty($isAdmin ? null : $userId);
            
            // Generate PDF report
            $data = [
                'title' => 'Occupancy Report',
                'occupancyStats' => $occupancyStats,
                'propertyOccupancy' => $propertyOccupancy
            ];
            
            generatePDF('reports/pdf/occupancy', $data);
        } catch (Exception $e) {
            error_log("Error in ReportsController::occupancy: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error generating occupancy report';
            $_SESSION['flash_type'] = 'danger';
            redirect('/reports');
        }
    }

    public function tenant($id)
    {
        try {
            $userId = $_SESSION['user_id'];
            
            // Get tenant data
            $tenant = $this->tenant->getById($id, $userId);
            
            if (!$tenant) {
                $_SESSION['flash_message'] = 'Tenant not found or access denied';
                $_SESSION['flash_type'] = 'danger';
                return redirect('/reports');
            }
            
            // Get tenant's lease history
            $leases = $this->lease->where('tenant_id', $id, $userId);
            
            // Get tenant's payment history
            $payments = $this->payment->where('tenant_id', $id, $userId);
            
            // Generate PDF report
            $data = [
                'title' => 'Tenant Report',
                'tenant' => $tenant,
                'leases' => $leases,
                'payments' => $payments
            ];
            
            generatePDF('reports/pdf/tenant', $data);
        } catch (Exception $e) {
            error_log("Error in ReportsController::tenant: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error generating tenant report';
            $_SESSION['flash_type'] = 'danger';
            redirect('/reports');
        }
    }

    public function generateReport()
    {
        try {
            // Define BASE_URL if not defined
            if (!defined('BASE_URL')) {
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
                $base_dir = str_replace('\\', '/', dirname($script_name));
                $base_dir = $base_dir !== '/' ? $base_dir : '';
                define('BASE_URL', $protocol . '://' . $host . $base_dir);
            }
            
            $reportType = $_GET['type'] ?? '';
            $format = $_GET['format'] ?? 'html';
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-t');
            $selectedUserId = $_GET['user_id'] ?? null;
            
            error_log("GenerateReport called: type=$reportType, format=$format");
            
            // Check if admin and user_id is provided
            $isAdmin = $this->user->isAdmin();
            $userId = $isAdmin && $selectedUserId ? $selectedUserId : $_SESSION['user_id'];

            $data = [];
            switch ($reportType) {
                case 'financial':
                    $data = $this->getFinancialReport($startDate, $endDate, $userId);
                    break;
                case 'realtor_financial':
                    $data = $this->getRealtorFinancialReport($startDate, $endDate, $userId);
                    break;
                case 'occupancy':
                    $data = $this->getOccupancyReport($userId);
                    break;
                case 'tenant':
                    $data = $this->getTenantReport($userId);
                    break;
                case 'lease':
                    $data = $this->getLeaseReport($userId);
                    break;
                case 'maintenance':
                    $data = $this->getMaintenanceReport($startDate, $endDate, $userId);
                    break;
                case 'delinquency':
                    $data = $this->getDelinquencyReport($userId);
                    break;
                case 'realtor_listings':
                    $data = $this->getRealtorListingsReport($startDate, $endDate, $userId);
                    break;
                case 'realtor_won_leads':
                    $data = $this->getRealtorWonLeadsReport($startDate, $endDate, $userId);
                    break;
                default:
                    $_SESSION['flash_message'] = 'Invalid report type';
                    $_SESSION['flash_type'] = 'danger';
                    header('Location: ' . BASE_URL . '/reports');
                    exit;
            }

            // Add user info if admin
            if ($isAdmin && $selectedUserId) {
                $selectedUser = $this->user->find($selectedUserId);
                $data['user_info'] = [
                    'name' => $selectedUser['name'],
                    'email' => $selectedUser['email'],
                    'role' => $selectedUser['role']
                ];
            }
            
            // Add settings for logo and branding
            $data['settings'] = $this->setting->getAllAsAssoc();

            switch ($format) {
                case 'pdf':
                    $this->exportToPdf($data, $reportType);
                    break;
                case 'csv':
                    $this->exportToCsv($data, $reportType);
                    break;
                default:
                    echo view('reports/show', [
                        'title' => ucfirst($reportType) . ' Report',
                        'data' => $data,
                        'reportType' => $reportType,
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'isAdmin' => $isAdmin
                    ]);
            }
        } catch (\Throwable $e) {
            error_log("Error in ReportsController::generateReport: " . $e->getMessage());
            error_log("File: " . $e->getFile() . " Line: " . $e->getLine());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Display error for debugging
            http_response_code(500);
            echo "<html><body>";
            echo "<h1>Error Generating Report</h1>";
            echo "<p><strong>Report Type:</strong> " . htmlspecialchars($_GET['type'] ?? 'unknown') . "</p>";
            echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
            echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
            echo "<h3>Stack Trace:</h3>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo "</body></html>";
            exit;
        }
    }

    private function getFinancialReport($startDate, $endDate, $userId)
    {
        return [
            'revenue' => $this->payment->getRevenueByDateRange($startDate, $endDate, $userId),
            'propertyRevenue' => $this->payment->getPropertyRevenueForReport($startDate, $endDate, $userId),
            'outstandingBalance' => $this->payment->getOutstandingBalance($userId),
            'recentPayments' => $this->payment->getRecentPayments($userId),
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
    }

    private function ensureRealtorAccess(): void
    {
        $role = strtolower((string)($this->currentUser['role'] ?? ($_SESSION['user_role'] ?? '')));
        if ($role !== 'realtor') {
            throw new \Exception('Access denied');
        }
    }

    private function getRealtorFinancialReport($startDate, $endDate, $userId)
    {
        $this->ensureRealtorAccess();
        $payments = $this->payment->getRealtorPaymentsByDateRange($startDate, $endDate, $userId);
        $received = 0.0;
        foreach (($payments ?: []) as $p) {
            $st = strtolower((string)($p['status'] ?? ''));
            if (in_array($st, ['completed', 'verified'], true)) {
                $received += (float)($p['amount'] ?? 0);
            }
        }
        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'received' => $received,
            'payments' => $payments,
        ];
    }

    private function getRealtorListingsReport($startDate, $endDate, $userId)
    {
        $this->ensureRealtorAccess();
        $listingModel = new RealtorListing();
        $all = $listingModel->getAll($userId);
        $sold = [];
        $notSold = [];
        foreach (($all ?: []) as $l) {
            $status = strtolower((string)($l['status'] ?? ''));
            if ($status === 'sold') {
                $sold[] = $l;
            } else {
                $notSold[] = $l;
            }
        }
        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'total' => count($all ?: []),
            'sold' => $sold,
            'notSold' => $notSold,
        ];
    }

    private function getRealtorWonLeadsReport($startDate, $endDate, $userId)
    {
        $this->ensureRealtorAccess();
        $leadModel = new RealtorLead();
        $leads = $leadModel->getAll($userId);
        $won = [];
        foreach (($leads ?: []) as $l) {
            $status = strtolower((string)($l['status'] ?? ''));
            $isWon = ($status === 'won') || (!empty($l['converted_client_id']));
            if ($isWon) {
                $won[] = $l;
            }
        }
        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'total_won' => count($won),
            'won' => $won,
        ];
    }

    private function getOccupancyReport($userId)
    {
        return [
            'occupancyStats' => $this->property->getOccupancyStats($userId),
            'propertyOccupancy' => $this->property->getOccupancyByProperty($userId),
            'vacantUnits' => $this->property->getVacantUnits($userId),
            'occupiedUnits' => $this->property->getOccupiedUnits($userId)
        ];
    }

    private function getTenantReport($userId)
    {
        try {
            error_log("Getting tenant report for user: " . ($userId ?? 'null'));
            
            $tenants = $this->tenant->getAllTenants($userId);
            error_log("Got tenants: " . count($tenants));
            
            $leaseStats = $this->lease->getTenantLeaseStats($userId);
            error_log("Got lease stats: " . print_r($leaseStats, true));
            
            $paymentStats = $this->payment->getTenantPaymentStats($userId);
            error_log("Got payment stats: " . count($paymentStats));
            
            return [
                'tenants' => $tenants,
                'leaseStats' => $leaseStats,
                'paymentStats' => $paymentStats
            ];
        } catch (\Throwable $e) {
            error_log("Error in getTenantReport: " . $e->getMessage());
            error_log("File: " . $e->getFile() . " Line: " . $e->getLine());
            throw $e;
        }
    }

    private function getLeaseReport($userId)
    {
        return [
            'activeLeases' => $this->lease->getActiveLeases($userId),
            'expiringLeases' => $this->lease->getExpiringLeases(30, $userId),
            'leaseHistory' => $this->lease->getLeaseHistory($userId)
        ];
    }

    private function getMaintenanceReport($startDate, $endDate, $userId)
    {
        return [
            'maintenanceRequests' => $this->maintenance->getMaintenanceRequests($startDate, $endDate, $userId),
            'maintenanceCosts' => $this->maintenance->getMaintenanceCosts($startDate, $endDate, $userId),
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
    }

    private function getDelinquencyReport($userId)
    {
        return [
            'delinquentTenants' => $this->tenant->getDelinquentTenants($userId),
            'outstandingPayments' => $this->payment->getOutstandingPayments($userId),
            'paymentHistory' => $this->payment->getPaymentHistory($userId)
        ];
    }

    private function exportToCsv($data, $reportType)
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $reportType . '_report_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Add headers based on report type
        switch ($reportType) {
            case 'financial':
                fputcsv($output, ['Date', 'Property', 'Type', 'Amount', 'Status']);
                foreach ($data['payments'] as $payment) {
                    fputcsv($output, [
                        $payment['payment_date'],
                        $payment['property_name'],
                        $payment['payment_type'],
                        $payment['amount'],
                        $payment['status']
                    ]);
                }
                break;
            // Add cases for other report types
        }
        
        fclose($output);
        exit;
    }

    private function exportToPdf($data, $reportType)
    {
        try {
            // Debug: Log what data we're passing
            error_log("Generating PDF for report type: $reportType");
            error_log("Data keys: " . implode(', ', array_keys($data)));
            
            // Ensure no output has been sent
            if (headers_sent($filename, $linenum)) {
                throw new \Exception("Headers already sent in $filename on line $linenum");
            }

            // Clean any previous output
            while (ob_get_level()) {
                ob_end_clean();
            }

            if (class_exists('Mpdf\Mpdf')) {
                // Configure mPDF with specific settings
                $mpdfConfig = [
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'margin_left' => 15,
                    'margin_right' => 15,
                    'margin_top' => 16,
                    'margin_bottom' => 16,
                    'margin_header' => 9,
                    'margin_footer' => 9
                ];
                
                $mpdf = new \Mpdf\Mpdf($mpdfConfig);
                
                // Set document properties
                $mpdf->SetTitle(ucfirst($reportType) . ' Report');
                $mpdf->SetAuthor('RentSmart System');
                $mpdf->SetCreator('RentSmart System');
                
                // Start output buffering
                ob_start();
                
                try {
                    // Include the PDF template (data is available in scope)
                    require 'views/reports/pdf/' . $reportType . '.php';
                } catch (\Throwable $e) {
                    ob_end_clean();
                    error_log("Error in PDF template ($reportType): " . $e->getMessage());
                    error_log("File: " . $e->getFile() . " Line: " . $e->getLine());
                    error_log("Stack trace: " . $e->getTraceAsString());
                    
                    // Display error for debugging
                    echo "<h1>Error generating PDF</h1>";
                    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
                    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
                    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
                    exit;
                }
                
                // Get the HTML content
                $html = ob_get_clean();
                
                // Write HTML to PDF
                $mpdf->WriteHTML($html);
                
                // Set headers for PDF output
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . $reportType . '_report_' . date('Y-m-d') . '.pdf"');
                header('Cache-Control: private, max-age=0, must-revalidate');
                header('Pragma: public');
                
                // Output PDF
                $mpdf->Output('', 'I');
                exit;
            } else {
                // Fallback to HTML version
                header('Content-Type: text/html; charset=utf-8');
                
                // Start output buffering
                ob_start();
                
                // Include the PDF template
                require 'views/reports/pdf/' . $reportType . '.php';
                
                // Get the HTML content
                $html = ob_get_clean();
                
                echo '<!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset="utf-8">
                        <title>' . ucfirst($reportType) . ' Report</title>
                        <style>
                            body { 
                                font-family: Arial, sans-serif; 
                                line-height: 1.6; 
                                margin: 0;
                                padding: 20px;
                            }
                            .print-header { 
                                text-align: center; 
                                margin-bottom: 20px; 
                                padding: 20px;
                                background: #f8f9fa;
                                border-bottom: 1px solid #dee2e6;
                            }
                            .report-date { 
                                text-align: center; 
                                margin-bottom: 30px; 
                                color: #666; 
                            }
                            table { 
                                width: 100%; 
                                border-collapse: collapse; 
                                margin-bottom: 20px;
                                background: white;
                            }
                            th, td { 
                                border: 1px solid #dee2e6; 
                                padding: 12px 8px; 
                                text-align: left; 
                            }
                            th { 
                                background-color: #f8f9fa;
                                font-weight: 600;
                            }
                            .summary-box { 
                                border: 1px solid #dee2e6; 
                                padding: 20px; 
                                margin-bottom: 30px;
                                background: white;
                                border-radius: 4px;
                            }
                            @media print {
                                .no-print { display: none; }
                                body { padding: 0; }
                                table { page-break-inside: auto; }
                                tr { page-break-inside: avoid; page-break-after: auto; }
                                thead { display: table-header-group; }
                                .print-header {
                                    position: running(header);
                                    background: none;
                                }
                                @page {
                                    margin: 2cm;
                                    @top-center {
                                        content: element(header);
                                    }
                                }
                            }
                        </style>
                    </head>
                    <body>';
                echo '<div class="no-print" style="text-align: center; padding: 20px;">
                        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">Print Report</button>
                      </div>';
                echo $html;
                echo '</body></html>';
                exit;
            }
        } catch (\Throwable $e) {
            error_log("PDF Generation Error: " . $e->getMessage());
            error_log("File: " . $e->getFile() . " Line: " . $e->getLine());
            error_log("Trace: " . $e->getTraceAsString());
            
            // Display error directly
            http_response_code(500);
            echo "<html><body>";
            echo "<h1>Error Generating Report</h1>";
            echo "<p><strong>Type:</strong> " . htmlspecialchars($reportType) . "</p>";
            echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
            echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
            echo "<h3>Stack Trace:</h3>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo "</body></html>";
            exit;
        }
    }
} 