<?php
// Start output buffering
ob_start();

// Configure error reporting
error_reporting(E_ALL);
ini_set('display_startup_errors', 0);
ini_set('display_errors', 0);  // Prevent errors from displaying on screen
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Custom error handler
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno] $errstr on line $errline in file $errfile");
    return true; // Don't execute PHP's internal error handler
}
set_error_handler('customErrorHandler');

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/helpers.php';

// Start session
session_start();

// Check for .env file
if (!file_exists(__DIR__ . '/.env')) {
    error_log('Missing .env file');
    die('Configuration error. Please contact administrator.');
}

// Load environment variables
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/');
    $dotenv->load();
} catch (Exception $e) {
    error_log('Error loading .env file: ' . $e->getMessage());
    die('Configuration error. Please contact administrator.');
}

// Toggle display of errors based on environment
$appEnv = getenv('APP_ENV') ?: 'production';
ini_set('display_errors', strtolower($appEnv) === 'development' ? '1' : '0');

// Define base URL constant if not already defined
// Handle both localhost and cPanel environments
if (!defined('BASE_URL')) {
    $http_host = $_SERVER['HTTP_HOST'] ?? '';
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    
    // Detect if we're on cPanel production (domain-based hosting)
    // Check if the domain is the production domain
    if (strpos($http_host, 'rentsmart.timestentechnologies.co.ke') !== false) {
        // On cPanel addon domain, the domain points directly to this directory
        // So the base URL should be empty (root)
        $base_url = '';
        error_log("BASE_URL Detection: Production domain detected, BASE_URL set to empty string");
    } else {
        // Localhost or other environments - app might be in a subdirectory
        $base_dir = dirname($script_name);
        $base_url = $base_dir !== '/' ? $base_dir : '';
        error_log("BASE_URL Detection: Non-production environment, BASE_URL set to: " . $base_url);
    }
    
    define('BASE_URL', $base_url);
    error_log("BASE_URL final value: '" . BASE_URL . "'");
}

// Parse the URL
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base_path = parse_url(BASE_URL, PHP_URL_PATH);
$base_path = is_string($base_path) ? $base_path : '';
$uri = is_string($uri) ? $uri : '/';
$uri = $base_path !== '' && strpos($uri, $base_path) === 0 ? substr($uri, strlen($base_path)) : $uri;
$uri = trim($uri, '/');
$uri = rtrim($uri, '/');

if (empty($uri)) {
    // If user is logged in, redirect to dashboard
    if (isset($_SESSION['user_id'])) {
        $uri = 'dashboard';
    } else {
        $uri = 'home';
    }
}

$method = $_SERVER['REQUEST_METHOD'];

// Routes
$routes = [
    // M-Pesa routes
    'subscription/initiate-stk' => ['controller' => 'MpesaController', 'action' => 'initiateSTK'],
    'mpesa/callback' => ['controller' => 'MpesaController', 'action' => 'handleCallback'],
    'subscription/verify-mpesa' => ['controller' => 'MpesaController', 'action' => 'verifyManualPayment'],

    'home' => ['controller' => 'HomeController', 'action' => 'index'],
    'vacant-units' => ['controller' => 'HomeController', 'action' => 'vacantUnits'],
    'jiji' => ['controller' => 'JijiController', 'action' => 'manage'],
    'jiji/export' => ['controller' => 'JijiController', 'action' => 'exportToJiji'],
    'jiji/generate-url' => ['controller' => 'JijiController', 'action' => 'generateJijiUrl'],
    'jiji/bulk-post' => ['controller' => 'JijiController', 'action' => 'bulkPostToJiji'],
    
    // Integration routes
    'integrations/facebook' => ['controller' => 'FacebookMarketplaceController', 'action' => 'manage'],
    'integrations/facebook/post' => ['controller' => 'FacebookMarketplaceController', 'action' => 'postUnit'],
    'integrations/facebook/delete' => ['controller' => 'FacebookMarketplaceController', 'action' => 'deleteUnit'],
    'integrations/facebook/config' => ['controller' => 'FacebookMarketplaceController', 'action' => 'saveConfig'],
    
    'integrations/marketplaces' => ['controller' => 'MarketplaceExportController', 'action' => 'manage'],
    'integrations/export/universal' => ['controller' => 'MarketplaceExportController', 'action' => 'exportUniversal'],
    'integrations/export/(\w+)' => ['controller' => 'MarketplaceExportController', 'action' => 'exportPlatform'],
    
    'integrations/zoho' => ['controller' => 'ZohoIntegrationController', 'action' => 'manage'],
    'integrations/zoho/sync-payments' => ['controller' => 'ZohoIntegrationController', 'action' => 'syncPayments'],
    'integrations/zoho/sync-expenses' => ['controller' => 'ZohoIntegrationController', 'action' => 'syncExpenses'],
    'integrations/zoho/config' => ['controller' => 'ZohoIntegrationController', 'action' => 'saveConfig'],
    
    'integrations/quickbooks' => ['controller' => 'QuickBooksIntegrationController', 'action' => 'manage'],
    'integrations/quickbooks/sync-payments' => ['controller' => 'QuickBooksIntegrationController', 'action' => 'syncPayments'],
    'integrations/quickbooks/sync-expenses' => ['controller' => 'QuickBooksIntegrationController', 'action' => 'syncExpenses'],
    'integrations/quickbooks/config' => ['controller' => 'QuickBooksIntegrationController', 'action' => 'saveConfig'],
    
    'integrations/odoo' => ['controller' => 'OdooIntegrationController', 'action' => 'manage'],
    'integrations/odoo/sync-payments' => ['controller' => 'OdooIntegrationController', 'action' => 'syncPayments'],
    'integrations/odoo/sync-expenses' => ['controller' => 'OdooIntegrationController', 'action' => 'syncExpenses'],
    'integrations/odoo/config' => ['controller' => 'OdooIntegrationController', 'action' => 'saveConfig'],
    
    'privacy-policy' => ['controller' => 'HomeController', 'action' => 'privacy'],
    'terms' => ['controller' => 'HomeController', 'action' => 'terms'],
    'dashboard' => ['controller' => 'DashboardController', 'action' => 'index'],
    'login' => [
        'GET' => ['controller' => 'AuthController', 'action' => 'showLogin'],
        'POST' => ['controller' => 'AuthController', 'action' => 'login'],
    ],
    'register' => [
        'GET' => ['controller' => 'AuthController', 'action' => 'showRegister'],
        'POST' => ['controller' => 'AuthController', 'action' => 'register'],
    ],
    'forgot-password' => [
        'GET' => ['controller' => 'AuthController', 'action' => 'showForgotPassword'],
        'POST' => ['controller' => 'AuthController', 'action' => 'sendResetLink'],
    ],
    'reset-password/([A-Za-z0-9]+)' => ['controller' => 'AuthController', 'action' => 'showResetForm'],
    'reset-password' => [
        'POST' => ['controller' => 'AuthController', 'action' => 'resetPassword'],
    ],
    'logout' => ['controller' => 'AuthController', 'action' => 'logout'],
    'subscription/renew' => [
        'GET' => ['controller' => 'SubscriptionController', 'action' => 'showRenew'],
        'POST' => ['controller' => 'SubscriptionController', 'action' => 'renew'],
    ],
    'subscription/status' => ['controller' => 'SubscriptionController', 'action' => 'status'],
    'subscription/invoice/(\d+)' => ['controller' => 'SubscriptionController', 'action' => 'invoice'],
    'subscription/invoice/current' => ['controller' => 'SubscriptionController', 'action' => 'invoiceCurrent'],
    'activity-logs' => ['controller' => 'LogsController', 'action' => 'index'],
    'activity-logs/export/(csv|xlsx)' => ['controller' => 'LogsController', 'action' => 'export'],
    'activity-logs/clear' => [
        'POST' => ['controller' => 'LogsController', 'action' => 'clearAll'],
    ],
    
    // Admin routes
    'admin/users' => ['controller' => 'AdminController', 'action' => 'users'],
    'admin/users/store' => ['controller' => 'AdminController', 'action' => 'storeUser'],
    'admin/users/get/(\d+)' => ['controller' => 'AdminController', 'action' => 'getUser'],
    'admin/users/update' => ['controller' => 'AdminController', 'action' => 'updateUser'],
    'admin/users/delete/(\d+)' => ['controller' => 'AdminController', 'action' => 'deleteUser'],
    
    'admin/subscriptions' => ['controller' => 'AdminController', 'action' => 'subscriptions'],
    'admin/subscriptions/get-plan/(\d+)' => ['controller' => 'AdminController', 'action' => 'getPlan'],
    'admin/subscriptions/update-plan' => ['controller' => 'AdminController', 'action' => 'updatePlan'],
    'admin/subscriptions/update' => ['controller' => 'AdminController', 'action' => 'updateSubscription'],
    'admin/subscriptions/extend/(\d+)' => ['controller' => 'AdminController', 'action' => 'extendSubscription'],
    'admin/subscriptions/view/(\d+)' => ['controller' => 'AdminController', 'action' => 'viewSubscription'],
    'admin/subscriptions/get-subscription/(\d+)' => ['controller' => 'AdminController', 'action' => 'getSubscription'],
    
    'admin/payments' => ['controller' => 'AdminController', 'action' => 'payments'],
    'admin/payments/get/(\d+)' => ['controller' => 'AdminController', 'action' => 'getPayment'],
    'admin/payments/transaction/(\d+)' => ['controller' => 'AdminController', 'action' => 'getTransaction'],
    'admin/payments/manual-mpesa/(\d+)/verify' => ['controller' => 'AdminController', 'action' => 'verifyManualSubscriptionPayment'],
    
    'properties' => ['controller' => 'PropertyController', 'action' => 'index'],
    'properties/create' => ['controller' => 'PropertyController', 'action' => 'create'],
    'properties/store' => ['controller' => 'PropertyController', 'action' => 'store'],
    'properties/edit/(\d+)' => ['controller' => 'PropertyController', 'action' => 'edit'],
    'properties/update/(\d+)' => ['controller' => 'PropertyController', 'action' => 'update'],
    'properties/delete/(\d+)' => ['controller' => 'PropertyController', 'action' => 'delete'],
    'properties/get/(\d+)' => ['controller' => 'PropertyController', 'action' => 'get'],
    'properties/export/(csv|xlsx|pdf)' => ['controller' => 'PropertyController', 'action' => 'export'],
    'properties/template' => ['controller' => 'PropertyController', 'action' => 'template'],
    'properties/import' => ['controller' => 'PropertyController', 'action' => 'import'],
    'properties/(\d+)' => ['controller' => 'PropertyController', 'action' => 'show'],
    'properties/(\d+)/units' => ['controller' => 'UnitsController', 'action' => 'getByProperty'],
    'properties/(\d+)/files' => ['controller' => 'PropertyController', 'action' => 'getFiles'],
    'units' => ['controller' => 'UnitsController', 'action' => 'index'],
    'units/export/(csv|xlsx|pdf)' => ['controller' => 'UnitsController', 'action' => 'export'],
    'units/template' => ['controller' => 'UnitsController', 'action' => 'template'],
    'units/import' => ['controller' => 'UnitsController', 'action' => 'import'],
    'units/store' => ['controller' => 'UnitsController', 'action' => 'store'],
    'units/get/(\d+)' => ['controller' => 'UnitsController', 'action' => 'get'],
    'units/update/(\d+)' => ['controller' => 'UnitsController', 'action' => 'update'],
    'units/delete/(\d+)' => ['controller' => 'UnitsController', 'action' => 'delete'],
    'units/get/1' => ['controller' => 'UnitsController', 'action' => 'get'],
    'units/get/2' => ['controller' => 'UnitsController', 'action' => 'get'],
    'units/get/3' => ['controller' => 'UnitsController', 'action' => 'get'],
    'units/update/1' => ['controller' => 'UnitsController', 'action' => 'update'],
    'units/update/2' => ['controller' => 'UnitsController', 'action' => 'update'],
    'units/update/3' => ['controller' => 'UnitsController', 'action' => 'update'],
    'units/delete/1' => ['controller' => 'UnitsController', 'action' => 'delete'],
    'units/delete/2' => ['controller' => 'UnitsController', 'action' => 'delete'],
    'units/delete/3' => ['controller' => 'UnitsController', 'action' => 'delete'],
    'units/(\d+)/files' => ['controller' => 'UnitsController', 'action' => 'getFiles'],
    'units/(\d+)/tenant' => ['controller' => 'UnitsController', 'action' => 'getTenant'],
    'tenants' => [
        'GET' => ['controller' => 'TenantsController', 'action' => 'index'],
        'POST' => ['controller' => 'TenantsController', 'action' => 'store'],
    ],
    'tenants/export/(csv|xlsx|pdf)' => ['controller' => 'TenantsController', 'action' => 'export'],
    'tenants/template' => ['controller' => 'TenantsController', 'action' => 'template'],
    'tenants/import' => ['controller' => 'TenantsController', 'action' => 'import'],
    'tenants/create' => ['controller' => 'TenantsController', 'action' => 'create'],
    'tenants/edit' => ['controller' => 'TenantsController', 'action' => 'edit'],
    'tenants/update/(\d+)' => ['controller' => 'TenantsController', 'action' => 'update'],
    'tenants/delete/(\d+)' => ['controller' => 'TenantsController', 'action' => 'delete'],
    'tenants/get/(\d+)' => ['controller' => 'TenantsController', 'action' => 'get'],
    'tenants/whatsapp-credentials/(\d+)' => ['controller' => 'TenantsController', 'action' => 'whatsappCredentials'],
    'admin/tenants/login-as/(\d+)' => ['controller' => 'TenantAuthController', 'action' => 'loginAsTenant', 'params' => ['id']],
    'admin/switch-back' => ['controller' => 'TenantAuthController', 'action' => 'switchBack'],
    'payments' => ['controller' => 'PaymentsController', 'action' => 'index'],
    'payments/export/(csv|xlsx|pdf)' => ['controller' => 'PaymentsController', 'action' => 'export'],
    'payments/template' => ['controller' => 'PaymentsController', 'action' => 'template'],
    'payments/import' => ['controller' => 'PaymentsController', 'action' => 'import'],
    'payments/store' => ['controller' => 'PaymentsController', 'action' => 'store'],
    'payments/get/(\d+)' => ['controller' => 'PaymentsController', 'action' => 'get'],
    'payments/update/(\d+)' => ['controller' => 'PaymentsController', 'action' => 'update'],
    'payments/delete/(\d+)' => ['controller' => 'PaymentsController', 'action' => 'delete'],
    'payments/mpesa/(\d+)' => ['controller' => 'PaymentsController', 'action' => 'getMpesaTransaction'],
    'payments/receipt/(\d+)' => ['controller' => 'PaymentsController', 'action' => 'receipt'],
    'payments/(\d+)/files' => ['controller' => 'PaymentsController', 'action' => 'getFiles'],
    'files' => ['controller' => 'FileController', 'action' => 'index'],
    'debug/files' => ['controller' => 'FileController', 'action' => 'debugFiles'],
    'files/upload' => ['controller' => 'FileController', 'action' => 'upload'],
    'files/search' => ['controller' => 'FileController', 'action' => 'search'],
    'files/share' => ['controller' => 'FileController', 'action' => 'share'],
    'files/delete/(\d+)' => ['controller' => 'FileController', 'action' => 'delete'],
    // Expenses routes
    'expenses' => ['controller' => 'ExpensesController', 'action' => 'index'],
    'expenses/store' => ['controller' => 'ExpensesController', 'action' => 'store'],
    'expenses/get/(\d+)' => ['controller' => 'ExpensesController', 'action' => 'get'],
    'expenses/update/(\d+)' => ['controller' => 'ExpensesController', 'action' => 'update'],
    'expenses/delete/(\d+)' => ['controller' => 'ExpensesController', 'action' => 'delete'],
    // Employees routes
    'employees' => ['controller' => 'EmployeesController', 'action' => 'index'],
    'employees/store' => ['controller' => 'EmployeesController', 'action' => 'store'],
    'employees/get/(\d+)' => ['controller' => 'EmployeesController', 'action' => 'get'],
    'employees/update/(\d+)' => ['controller' => 'EmployeesController', 'action' => 'update'],
    'employees/delete/(\d+)' => ['controller' => 'EmployeesController', 'action' => 'delete'],
    'employees/pay/(\d+)' => ['controller' => 'EmployeesController', 'action' => 'pay'],
    'reports' => ['controller' => 'ReportsController', 'action' => 'index'],
    'reports/tenant-balances' => ['controller' => 'ReportsController', 'action' => 'tenantBalances'],
    'reports/generate' => ['controller' => 'ReportsController', 'action' => 'generateReport'],
    'admin/inquiries' => ['controller' => 'InquiriesController', 'action' => 'index'],
    'admin/contact-messages' => ['controller' => 'ContactMessagesController', 'action' => 'index'],
    'admin/contact-messages/show/(\d+)' => ['controller' => 'ContactMessagesController', 'action' => 'show'],
    'admin/contact-messages/reply/(\d+)' => ['controller' => 'ContactMessagesController', 'action' => 'reply'],
    'settings' => ['controller' => 'SettingsController', 'action' => 'index'],
    'settings/email' => ['controller' => 'SettingsController', 'action' => 'email'],
    'settings/ai' => ['controller' => 'SettingsController', 'action' => 'ai'],
    'settings/update' => ['controller' => 'SettingsController', 'action' => 'update'],
    'settings/updateProfile' => ['controller' => 'SettingsController', 'action' => 'updateProfile'],
    'settings/updateMail' => ['controller' => 'SettingsController', 'action' => 'updateMail'],
    'settings/updateAI' => ['controller' => 'SettingsController', 'action' => 'updateAI'],
    'settings/testEmail' => ['controller' => 'SettingsController', 'action' => 'testEmail'],
    'settings/testSMS' => ['controller' => 'SettingsController', 'action' => 'testSMS'],
    'settings/backup' => ['controller' => 'SettingsController', 'action' => 'backup'],
    'settings/restore' => ['controller' => 'SettingsController', 'action' => 'restore'],
    'settings/payments' => ['controller' => 'SettingsController', 'action' => 'payments'],
    'settings/updatePayments' => ['controller' => 'SettingsController', 'action' => 'updatePayments'],
    'settings/public-pages' => ['controller' => 'SettingsController', 'action' => 'publicPages'],
    'settings/public_pages' => ['controller' => 'SettingsController', 'action' => 'publicPages'],
    'settings/updatePublicPages' => ['controller' => 'SettingsController', 'action' => 'updatePublicPages'],
    'settings/update_public_pages' => ['controller' => 'SettingsController', 'action' => 'updatePublicPages'],
    'settings/testMpesa' => ['controller' => 'SettingsController', 'action' => 'testMpesa'],
    'settings/testStripe' => ['controller' => 'SettingsController', 'action' => 'testStripe'],
    'settings/testPaypal' => ['controller' => 'SettingsController', 'action' => 'testPaypal'],

    'branding' => [
        'GET' => ['controller' => 'BrandingController', 'action' => 'index'],
        'POST' => ['controller' => 'BrandingController', 'action' => 'update'],
    ],
    'branding/update' => ['controller' => 'BrandingController', 'action' => 'update'],

    'ai/chat' => ['controller' => 'AiController', 'action' => 'chat'],
    // Accounting routes
    'accounting' => ['controller' => 'AccountingController', 'action' => 'index'],
    'accounting/accounts' => ['controller' => 'AccountingController', 'action' => 'accounts'],
    'accounting/storeAccount' => ['controller' => 'AccountingController', 'action' => 'storeAccount'],
    'accounting/ledger' => ['controller' => 'AccountingController', 'action' => 'ledger'],
    'accounting/ledger/export-csv' => ['controller' => 'AccountingController', 'action' => 'exportLedgerCsv'],
    'accounting/ledger/export-pdf' => ['controller' => 'AccountingController', 'action' => 'exportLedgerPdf'],
    'accounting/trial-balance' => ['controller' => 'AccountingController', 'action' => 'trialBalance'],
    'accounting/trialBalance' => ['controller' => 'AccountingController', 'action' => 'trialBalance'],
    'accounting/trial-balance/export-csv' => ['controller' => 'AccountingController', 'action' => 'exportTrialBalanceCsv'],
    'accounting/trial-balance/export-pdf' => ['controller' => 'AccountingController', 'action' => 'exportTrialBalancePdf'],
    'accounting/balance-sheet' => ['controller' => 'AccountingController', 'action' => 'balanceSheet'],
    'accounting/balanceSheet' => ['controller' => 'AccountingController', 'action' => 'balanceSheet'],
    'accounting/balance-sheet/export-csv' => ['controller' => 'AccountingController', 'action' => 'exportBalanceSheetCsv'],
    'accounting/balance-sheet/export-pdf' => ['controller' => 'AccountingController', 'action' => 'exportBalanceSheetPdf'],
    'accounting/profit-loss' => ['controller' => 'AccountingController', 'action' => 'profitLoss'],
    'accounting/profitLoss' => ['controller' => 'AccountingController', 'action' => 'profitLoss'],
    'accounting/profit-loss/export-csv' => ['controller' => 'AccountingController', 'action' => 'exportProfitLossCsv'],
    'accounting/profit-loss/export-pdf' => ['controller' => 'AccountingController', 'action' => 'exportProfitLossPdf'],
    'accounting/statements' => ['controller' => 'AccountingController', 'action' => 'statements'],
    'accounting/statements/export-csv' => ['controller' => 'AccountingController', 'action' => 'exportStatementsCsv'],
    'accounting/statements/export-pdf' => ['controller' => 'AccountingController', 'action' => 'exportStatementsPdf'],
    'accounting/backfillLedger' => ['controller' => 'AccountingController', 'action' => 'backfillLedger'],
    // Invoices
    'invoices' => ['controller' => 'InvoicesController', 'action' => 'index'],
    'invoices/create' => ['controller' => 'InvoicesController', 'action' => 'create'],
    'invoices/store' => ['controller' => 'InvoicesController', 'action' => 'store'],
    'invoices/show/(\d+)' => ['controller' => 'InvoicesController', 'action' => 'show'],
    'invoices/pdf/(\d+)' => ['controller' => 'InvoicesController', 'action' => 'pdf'],
    'invoices/email/(\d+)' => ['controller' => 'InvoicesController', 'action' => 'email'],
    'invoices/delete/(\d+)' => ['controller' => 'InvoicesController', 'action' => 'delete'],
    'invoices/unarchive/(\d+)' => ['controller' => 'InvoicesController', 'action' => 'unarchive'],
    'invoices/unvoid/(\d+)' => ['controller' => 'InvoicesController', 'action' => 'unvoid'],
    'invoices/post/(\d+)' => ['controller' => 'InvoicesController', 'action' => 'post'],
    // E-Signature
    'esign' => ['controller' => 'ESignController', 'action' => 'index'],
    'esign/create' => ['controller' => 'ESignController', 'action' => 'create'],
    'esign/store' => ['controller' => 'ESignController', 'action' => 'store'],
    'esign/show/(\d+)' => ['controller' => 'ESignController', 'action' => 'show'],
    'esign/sign/([A-Za-z0-9]+)' => ['controller' => 'ESignController', 'action' => 'sign'],
    'esign/submit/([A-Za-z0-9]+)' => ['controller' => 'ESignController', 'action' => 'submit'],
    'esign/decline/([A-Za-z0-9]+)' => ['controller' => 'ESignController', 'action' => 'decline'],
    // Messaging
    'messaging' => ['controller' => 'MessagingController', 'action' => 'index'],
    'messaging/thread' => ['controller' => 'MessagingController', 'action' => 'thread'],
    'messaging/send' => ['controller' => 'MessagingController', 'action' => 'send'],
    // Notifications
    'notifications/feed' => ['controller' => 'NotificationsController', 'action' => 'feed'],
    'notifications/unread-count' => ['controller' => 'NotificationsController', 'action' => 'unreadCount'],
    'notifications/list' => ['controller' => 'NotificationsController', 'action' => 'list'],
    'push/vapid-public-key' => ['controller' => 'PushController', 'action' => 'vapidPublicKey'],
    'push/subscribe' => ['controller' => 'PushController', 'action' => 'subscribe'],
    'push/unsubscribe' => ['controller' => 'PushController', 'action' => 'unsubscribe'],
    'notifications/mark-read' => [
        'POST' => ['controller' => 'NotificationsController', 'action' => 'markRead'],
    ],
    'notifications/mark-all-read' => [
        'POST' => ['controller' => 'NotificationsController', 'action' => 'markAllRead'],
    ],
    // Notices
    'notices' => ['controller' => 'NoticesController', 'action' => 'index'],
    'notices/store' => ['controller' => 'NoticesController', 'action' => 'store'],
    'notices/delete/(\d+)' => ['controller' => 'NoticesController', 'action' => 'delete'],
    'tenant/notices' => ['controller' => 'NoticesController', 'action' => 'tenant'],
    // Contact
    'contact' => ['controller' => 'ContactController', 'action' => 'index'],
    'contact/submit' => ['controller' => 'ContactController', 'action' => 'submit'],
    // Docs (public)
    'docs' => ['controller' => 'DocsController', 'action' => 'index'],
    'leases' => ['controller' => 'LeasesController', 'action' => 'index'],
    'leases/export/(csv|xlsx|pdf)' => ['controller' => 'LeasesController', 'action' => 'export'],
    'leases/template' => ['controller' => 'LeasesController', 'action' => 'template'],
    'leases/import' => ['controller' => 'LeasesController', 'action' => 'import'],
    'leases/store' => ['controller' => 'LeasesController', 'action' => 'store'],
    'leases/edit/(\d+)' => ['controller' => 'LeasesController', 'action' => 'edit'],
    'leases/update/(\d+)' => ['controller' => 'LeasesController', 'action' => 'update'],
    'leases/delete/(\d+)' => ['controller' => 'LeasesController', 'action' => 'delete'],
    'leases/units/(\d+)' => ['controller' => 'LeasesController', 'action' => 'getUnitsByProperty'],
    'utility-rates/store' => ['controller' => 'UtilityRatesController', 'action' => 'store'],
    'utility-rates/delete/(\d+)' => [
        'POST' => ['controller' => 'UtilityRatesController', 'action' => 'delete'],
    ],
    'utilities' => ['controller' => 'UtilitiesController', 'action' => 'index'],
    'utilities/create' => ['controller' => 'UtilitiesController', 'action' => 'create'],
    'utilities/store' => ['controller' => 'UtilitiesController', 'action' => 'store'],
    'utilities/types-by-property/(\d+)' => ['controller' => 'UtilitiesController', 'action' => 'typesByProperty'],
    'utilities/edit/(\d+)' => ['controller' => 'UtilitiesController', 'action' => 'edit'],
    'utilities/update/(\d+)' => ['controller' => 'UtilitiesController', 'action' => 'update'],
    'utilities/delete/(\d+)' => [
        'POST' => ['controller' => 'UtilitiesController', 'action' => 'delete'],
    ],
    'utilities/export/(csv|xlsx|pdf)' => ['controller' => 'UtilitiesController', 'action' => 'export'],
    'utilities/template' => ['controller' => 'UtilitiesController', 'action' => 'template'],
    'utilities/import' => ['controller' => 'UtilitiesController', 'action' => 'import'],
    // Tenant Portal routes
    'tenant/login' => [
        'GET' => ['controller' => 'TenantAuthController', 'action' => 'loginForm'],
        'POST' => ['controller' => 'TenantAuthController', 'action' => 'login'],
    ],
    'tenant/logout' => ['controller' => 'TenantAuthController', 'action' => 'logout'],
    'tenant/dashboard' => ['controller' => 'TenantPortalController', 'action' => 'dashboard'],
    // Tenant Messaging routes
    'tenant/messaging' => ['controller' => 'TenantMessagingController', 'action' => 'index'],
    'tenant/messaging/thread' => ['controller' => 'TenantMessagingController', 'action' => 'thread'],
    'tenant/messaging/send' => ['controller' => 'TenantMessagingController', 'action' => 'send'],
    
    // Tenant Maintenance routes
    'tenant/maintenance' => ['controller' => 'TenantMaintenanceController', 'action' => 'index'],
    'tenant/maintenance/create' => ['controller' => 'TenantMaintenanceController', 'action' => 'create'],
    'tenant/maintenance/get/(\d+)' => ['controller' => 'TenantMaintenanceController', 'action' => 'get'],

    'tenant/esign' => ['controller' => 'TenantESignController', 'action' => 'index'],
    // Tenant Notifications
    'tenant/notifications/unread-count' => ['controller' => 'NotificationsController', 'action' => 'tenantUnreadCount'],
    'tenant/notifications/list' => ['controller' => 'NotificationsController', 'action' => 'tenantList'],
    'tenant/notifications/mark-read' => ['controller' => 'NotificationsController', 'action' => 'tenantMarkRead'],
    'tenant/notifications/mark-all-read' => ['controller' => 'NotificationsController', 'action' => 'tenantMarkAllRead'],
    // Tenant Web Push
    'tenant/push/vapid-public-key' => ['controller' => 'PushController', 'action' => 'tenantVapidPublicKey'],
    'tenant/push/subscribe' => ['controller' => 'PushController', 'action' => 'tenantSubscribe'],
    'tenant/push/unsubscribe' => ['controller' => 'PushController', 'action' => 'tenantUnsubscribe'],
    'tenant/payment/process' => ['controller' => 'TenantPaymentController', 'action' => 'process'],
    'tenant/payment/initiate-stk' => ['controller' => 'TenantPaymentController', 'action' => 'initiateSTK'],
    'tenant/payment/check-stk-status' => ['controller' => 'TenantPaymentController', 'action' => 'checkSTKStatus'],
    'tenant/payment/stk-callback' => ['controller' => 'TenantPaymentController', 'action' => 'handleSTKCallback'],
    'tenant/payment/history' => ['controller' => 'TenantPaymentController', 'action' => 'history'],
    'tenant/payment/receipt/(\d+)' => ['controller' => 'TenantPaymentController', 'action' => 'receipt'],

    'tenant/invoices/pdf/(\d+)' => ['controller' => 'TenantInvoicesController', 'action' => 'pdf'],

    'tenant/statements/pdf' => ['controller' => 'TenantStatementsController', 'action' => 'pdf'],
    
    // Admin Maintenance routes
    'maintenance' => ['controller' => 'MaintenanceController', 'action' => 'index'],
    'maintenance/show/(\d+)' => ['controller' => 'MaintenanceController', 'action' => 'show'],
    'maintenance/get/(\d+)' => ['controller' => 'MaintenanceController', 'action' => 'get'],
    'maintenance/update-status' => ['controller' => 'MaintenanceController', 'action' => 'updateStatus'],
    'maintenance/delete/(\d+)' => ['controller' => 'MaintenanceController', 'action' => 'delete'],
    
    // Payment Methods routes
    'payment-methods' => ['controller' => 'PaymentMethodsController', 'action' => 'index'],
    'payment-methods/create' => ['controller' => 'PaymentMethodsController', 'action' => 'create'],
    'payment-methods/update/(\d+)' => ['controller' => 'PaymentMethodsController', 'action' => 'update'],
    'payment-methods/delete/(\d+)' => ['controller' => 'PaymentMethodsController', 'action' => 'delete'],
    'payment-methods/get/(\d+)' => ['controller' => 'PaymentMethodsController', 'action' => 'get'],
    
    // M-Pesa Verification routes
    'mpesa-verification' => ['controller' => 'MpesaVerificationController', 'action' => 'index'],
    'mpesa-verification/verify/(\d+)' => ['controller' => 'MpesaVerificationController', 'action' => 'verify'],

    // Admin Debug routes
    'admin/debug/tenant-payment-error' => ['controller' => 'AdminDebugController', 'action' => 'tenantPaymentError'],
    'admin/debug/realtor-payments' => ['controller' => 'AdminDebugController', 'action' => 'realtorPayments'],
    'admin/debug/render-payments' => ['controller' => 'AdminDebugController', 'action' => 'renderPayments'],
    // Public inquiry
    'inquiries/store' => ['controller' => 'InquiryController', 'action' => 'store'],

    // Realtor routes
    'realtor/dashboard' => ['controller' => 'RealtorDashboardController', 'action' => 'index'],
    'realtor/listings' => ['controller' => 'RealtorListingsController', 'action' => 'index'],
    'realtor/listings/store' => ['controller' => 'RealtorListingsController', 'action' => 'store'],
    'realtor/listings/get/(\d+)' => ['controller' => 'RealtorListingsController', 'action' => 'get'],
    'realtor/listings/update/(\d+)' => ['controller' => 'RealtorListingsController', 'action' => 'update'],
    'realtor/listings/delete/(\d+)' => ['controller' => 'RealtorListingsController', 'action' => 'delete'],

    'realtor/clients' => ['controller' => 'RealtorClientsController', 'action' => 'index'],
    'realtor/clients/store' => ['controller' => 'RealtorClientsController', 'action' => 'store'],
    'realtor/clients/get/(\d+)' => ['controller' => 'RealtorClientsController', 'action' => 'get'],
    'realtor/clients/update/(\d+)' => ['controller' => 'RealtorClientsController', 'action' => 'update'],
    'realtor/clients/delete/(\d+)' => ['controller' => 'RealtorClientsController', 'action' => 'delete'],

    'realtor/leads' => ['controller' => 'RealtorLeadsController', 'action' => 'index'],
    'realtor/leads/store' => ['controller' => 'RealtorLeadsController', 'action' => 'store'],
    'realtor/leads/get/(\d+)' => ['controller' => 'RealtorLeadsController', 'action' => 'get'],
    'realtor/leads/update/(\d+)' => ['controller' => 'RealtorLeadsController', 'action' => 'update'],
    'realtor/leads/convert/(\d+)' => ['controller' => 'RealtorLeadsController', 'action' => 'convert'],
    'realtor/leads/delete/(\d+)' => ['controller' => 'RealtorLeadsController', 'action' => 'delete'],

    'realtor/leads/stages' => ['controller' => 'RealtorLeadsController', 'action' => 'stages'],
    'realtor/leads/stages/store' => ['controller' => 'RealtorLeadsController', 'action' => 'storeStage'],
    'realtor/leads/stages/update/(\d+)' => ['controller' => 'RealtorLeadsController', 'action' => 'updateStage'],
    'realtor/leads/stages/delete/(\d+)' => ['controller' => 'RealtorLeadsController', 'action' => 'deleteStage'],

    'realtor/contracts' => ['controller' => 'RealtorContractsController', 'action' => 'index'],
    'realtor/contracts/store' => ['controller' => 'RealtorContractsController', 'action' => 'store'],
    'realtor/contracts/show/(\d+)' => ['controller' => 'RealtorContractsController', 'action' => 'show'],
];

// Protected routes that require authentication
$protectedRoutes = [
    // M-Pesa routes
    'subscription/initiate-stk',
    'subscription/verify-mpesa',
    'mpesa/callback',

    // Jiji integration routes
    'jiji',
    'jiji/export',
    'jiji/generate-url',
    'jiji/bulk-post',
    
    // Integration routes
    'integrations/facebook',
    'integrations/facebook/post',
    'integrations/facebook/delete',
    'integrations/facebook/config',
    'integrations/marketplaces',
    'integrations/export/universal',
    'integrations/export/(\w+)',
    'integrations/zoho',
    'integrations/zoho/sync-payments',
    'integrations/zoho/sync-expenses',
    'integrations/zoho/config',
    'integrations/quickbooks',
    'integrations/quickbooks/sync-payments',
    'integrations/quickbooks/sync-expenses',
    'integrations/quickbooks/config',
    'integrations/odoo',
    'integrations/odoo/sync-payments',
    'integrations/odoo/sync-expenses',
    'integrations/odoo/config',

    'dashboard',
    'properties',
    'properties/create',
    'properties/store',
    'properties/edit/(\d+)',
    'properties/update/(\d+)',
    'properties/delete/(\d+)',
    'properties/get/(\d+)',
    'properties/(\d+)',
    'properties/(\d+)/units',
    'properties/(\d+)/files',
    'properties/export/(csv|xlsx|pdf)',
    'properties/template',
    'properties/import',
    'units',
    'units/store',
    'units/get/(\d+)',
    'units/update/(\d+)',
    'units/delete/(\d+)',
    'units/(\d+)/files',
    'units/(\d+)/tenant',
    'units/export/(csv|xlsx|pdf)',
    'units/template',
    'units/import',
    'tenants',
    'tenants/create',
    'tenants/store',
    'tenants/edit',
    'tenants/update/(\d+)',
    'tenants/delete/(\d+)',
    'tenants/get/(\d+)',
    'tenants/export/(csv|xlsx|pdf)',
    'tenants/template',
    'tenants/import',
    'admin/tenants/login-as/(\d+)',
    'admin/switch-back',
    'payments',
    'payments/store',
    'payments/get/(\d+)',
    'payments/update/(\d+)',
    'payments/delete/(\d+)',
    'payments/receipt/(\d+)',
    'payments/(\d+)/files',
    'payments/mpesa/(\d+)',
    'payments/export/(csv|xlsx|pdf)',
    'payments/template',
    'payments/import',
    // Expenses
    'expenses',
    'expenses/store',
    'expenses/get/(\d+)',
    'expenses/update/(\d+)',
    'expenses/delete/(\d+)',
    // Employees
    'employees',
    'employees/store',
    'employees/get/(\d+)',
    'employees/update/(\d+)',
    'employees/delete/(\d+)',
    'employees/pay/(\d+)',
    'files',
    'debug/files',
    'files/upload',
    'files/search',
    'files/delete/(\d+)',
    'reports',
    'reports/tenant-balances',
    'reports/generate',
    'settings',
    'settings/email',
    'settings/update',
    'settings/updateProfile',
    'settings/updateMail',
    'settings/testEmail',
    'settings/testSMS',
    'settings/backup',
    'settings/restore',
    'settings/payments',
    'settings/updatePayments',
    'settings/public-pages',
    'settings/public_pages',
    'settings/updatePublicPages',
    'settings/update_public_pages',
    'settings/testMpesa',
    'settings/testStripe',
    'settings/testPaypal',
    'branding',
    'branding/update',
    'leases',
    'leases/store',
    'leases/edit/(\d+)',
    'leases/update/(\d+)',
    'leases/delete/(\d+)',
    'leases/units/(\d+)',
    'leases/export/(csv|xlsx|pdf)',
    'leases/template',
    'leases/import',
    'utility-rates/store',
    'utility-rates/delete/(\d+)',
    'utilities',
    'utilities/create',
    'utilities/store',
    'utilities/types-by-property/(\d+)',
    'utilities/edit/(\d+)',
    'utilities/update/(\d+)',
    'utilities/delete/(\d+)',
    'utilities/export/(csv|xlsx|pdf)',
    'utilities/template',
    'utilities/import',
    // Messaging & Notices
    'messaging',
    'messaging/thread',
    'messaging/send',
    'notifications/feed',
    'notifications/unread-count',
    'notifications/list',
    'notifications/mark-read',
    'notifications/mark-all-read',
    // Web Push
    'push/vapid-public-key',
    'push/subscribe',
    'push/unsubscribe',
    'notices',
    'notices/store',
    'notices/delete/(\d+)',
    // Accounting & Invoices
    'accounting',
    'accounting/accounts',
    'accounting/accounts/store',
    'accounting/ledger',
    'accounting/trial-balance',
    'accounting/balance-sheet',
    'accounting/profit-loss',
    'accounting/statements',
    'invoices',
    'invoices/create',
    'invoices/store',
    'invoices/show/(\d+)',
    'invoices/pdf/(\d+)',
    'invoices/email/(\d+)',
    'invoices/delete/(\d+)',
    'invoices/post/(\d+)',
    // E-Signature (protected UI)
    'esign',
    'esign/create',
    'esign/store',
    'esign/show/(\d+)',
    'subscription/status',
    'subscription/renew',
    'subscription/invoice/(\d+)',
    'subscription/invoice/current',
    
    // Maintenance routes
    'maintenance',
    'maintenance/show/(\d+)',
    'maintenance/get/(\d+)',
    'maintenance/update-status',
    'maintenance/delete/(\d+)',
    
    // Payment Methods routes
    'payment-methods',
    'payment-methods/create',
    'payment-methods/update/(\d+)',
    'payment-methods/delete/(\d+)',
    'payment-methods/get/(\d+)',
    
    // M-Pesa Verification routes
    'mpesa-verification',
    'mpesa-verification/verify/(\d+)',
    
    // Admin routes
    'admin/users',
    'admin/users/store',
    'admin/users/get/(\d+)',
    'admin/users/update',
    'admin/users/delete/(\d+)',
    'admin/subscriptions',
    'admin/subscriptions/get-plan/(\d+)',
    'admin/subscriptions/update-plan',
    'admin/subscriptions/update',
    'admin/subscriptions/extend/(\d+)',
    'admin/subscriptions/view/(\d+)',
    'admin/subscriptions/get-subscription/(\d+)',
    'admin/payments',
    'admin/payments/get/(\d+)',
    'admin/payments/transaction/(\d+)',
    'admin/inquiries',
    'admin/contact-messages',
    'admin/contact-messages/show/(\d+)',
    'admin/debug/realtor-payments',
    'admin/contact-messages/reply/(\d+)',
    'admin/debug/render-payments',
    'activity-logs',
    'activity-logs/export/(csv|xlsx)'
    ,
    // Realtor
    'realtor/dashboard',
    'realtor/listings',
    'realtor/listings/store',
    'realtor/listings/get/(\d+)',
    'realtor/listings/update/(\d+)',
    'realtor/listings/delete/(\d+)',
    'realtor/clients',
    'realtor/clients/store',
    'realtor/clients/get/(\d+)',
    'realtor/clients/update/(\d+)',
    'realtor/clients/delete/(\d+)',
    'realtor/leads',
    'realtor/leads/store',
    'realtor/leads/get/(\d+)',
    'realtor/leads/update/(\d+)',
    'realtor/leads/convert/(\d+)',
    'realtor/leads/delete/(\d+)',

    'realtor/contracts',
    'realtor/contracts/store',
    'realtor/contracts/show/(\d+)'
];

// Check if the current route requires authentication
$requiresAuth = false;
foreach ($protectedRoutes as $route) {
    if (preg_match('#^' . $route . '$#', $uri)) {
        $requiresAuth = true;
        break;
    }
}

if ($requiresAuth) {
    requireAuth();
}

// Log page views for authenticated users (GET requests)
try {
    if (isset($_SESSION['user_id']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $activityLog = new \App\Models\ActivityLog();
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $details = [
            'uri' => $uri,
            'method' => $method,
            'query' => $_GET ?? [],
            'referer' => $_SERVER['HTTP_REFERER'] ?? null
        ];
        $activityLog->add(
            $_SESSION['user_id'],
            $_SESSION['user_role'] ?? null,
            'page.view',
            'page',
            null,
            null,
            json_encode($details),
            $ip,
            $agent
        );
    }
} catch (Exception $e) { error_log('page.view log failed: ' . $e->getMessage()); }

try {
    // Add debug logging for routing analysis
    error_log("DEBUG: REQUEST_URI = " . $_SERVER['REQUEST_URI']);
    error_log("DEBUG: BASE_URL = " . BASE_URL);
    error_log("DEBUG: Parsed $uri = " . $uri);

    // Extract ID from URL for edit/delete/get actions
    $id = null;
    $base_route = $uri;
    
    error_log("Routing - Original URI: " . $uri);
    error_log("Routing - Request Method: " . $method);
    $hdrs = function_exists('getallheaders') ? getallheaders() : $_SERVER;
    error_log("Routing - Request Headers: " . print_r($hdrs, true));
    
    // Check for routes with numeric IDs
    // Note: Order matters - check more specific patterns first
    if (preg_match('/^(units|properties|payments)\/(\d+)\/(files|tenant)$/', $uri, $matches)) {
        // Handle routes like units/16/files, properties/5/files, payments/3/files, units/16/tenant
        // Keep the pattern format for matching against route definitions
        $base_route = $matches[1] . '/(\d+)/' . $matches[3];
        $id = $matches[2];
        error_log("Routing - Matched entity files/tenant route: base_route = {$base_route}, id = {$id}, uri = {$uri}");
    } else if (preg_match('/^properties\/(\d+)\/units$/', $uri, $matches)) {
        // Handle properties/5/units
        $base_route = 'properties/(\d+)/units';
        $id = $matches[1];
        error_log("Routing - Matched property units route: base_route = {$base_route}, id = {$id}, uri = {$uri}");
    } else if (preg_match('/^tenant\/(payment|maintenance)\/(receipt|get)\/(\d+)$/', $uri, $matches)) {
        // Handle tenant routes like tenant/payment/receipt/30, tenant/maintenance/get/5
        $base_route = 'tenant/' . $matches[1] . '/' . $matches[2] . '/(\d+)';
        $id = $matches[3];
        error_log("Routing - Matched tenant route with action and ID: base_route = {$base_route}, id = {$id}, uri = {$uri}");
    } else if (preg_match('/^(units|properties|tenants|payments|expenses|employees|leases|utilities|maintenance|payment-methods|files|admin\/users|admin\/subscriptions|admin\/payments|mpesa-verification)\/(get|update|delete|edit|show|verify|transaction|get-plan|get-subscription|extend|view|pay)\/(\d+)$/', $uri, $matches)) {
        // Handle routes like units/get/16, properties/update/5, admin/users/get/3, etc.
        $base_route = $matches[1] . '/' . $matches[2] . '/(\d+)';
        $id = $matches[3];
        error_log("Routing - Matched route with action and ID: base_route = {$base_route}, id = {$id}, uri = {$uri}");
    } else if (preg_match('/^(properties|payments|leases)\/(\d+)$/', $uri, $matches)) {
        // Handle simple routes like properties/5, payments/10
        $base_route = $matches[1] . '/(\d+)';
        $id = $matches[2];
        error_log("Routing - Matched simple route with ID: base_route = {$base_route}, id = {$id}, uri = {$uri}");
    }

    // Route to appropriate controller
    $matched = false;
    foreach ($routes as $pattern => $route) {
        error_log("Routing - Checking pattern: {$pattern} against base_route: {$base_route}");
        if ($pattern === $base_route || preg_match('#^' . $pattern . '$#', $base_route, $matches)) {
            $matched = true;
            error_log("Routing - Matched route pattern: {$pattern} for base_route: {$base_route}");

            if (is_array($route) && isset($route[$method])) {
                $controllerName = 'App\\Controllers\\' . $route[$method]['controller'];
                $actionName = $route[$method]['action'];
            } else {
                $controllerName = 'App\\Controllers\\' . $route['controller'];
                $actionName = $route['action'];
            }

            error_log("Routing - Using controller: {$controllerName}, action: {$actionName}");

            if (!class_exists($controllerName)) {
                error_log("Routing - Controller not found: {$controllerName}");
                throw new Exception("Controller {$controllerName} not found");
            }

            $controller = new $controllerName();
            
            if (!method_exists($controller, $actionName)) {
                error_log("Routing - Method not found: {$actionName} in controller {$controllerName}");
                throw new Exception("Method {$actionName} not found in controller {$controllerName}");
            }

            // Pass ID parameter if it exists in the matches
            if (isset($matches[1])) {
                error_log("Routing - Calling {$actionName} with ID: {$matches[1]}");
                $controller->$actionName($matches[1]);
            } else if ($id !== null) {
                error_log("Routing - Calling {$actionName} with ID: {$id}");
                $controller->$actionName($id);
            } else {
                error_log("Routing - Calling {$actionName} without ID");
                $controller->$actionName();
            }
            break;
        }
    }

    if (!$matched) {
        error_log("Routing - No route matched for URI: {$uri}");
        error_log("Routing - Available routes: " . print_r($routes, true));
        // 404 Not Found
        header("HTTP/1.0 404 Not Found");
        echo view('errors/404', ['title' => '404 Not Found']);
    }
} catch (\Throwable $e) {
    $globalErrorId = null;
    try {
        $globalErrorId = bin2hex(random_bytes(4));
    } catch (\Throwable $ignore) {
        $globalErrorId = (string)time();
    }

    error_log("Error in routing | error_id={$globalErrorId} | msg=" . $e->getMessage());
    error_log("Error in routing | error_id={$globalErrorId} | trace=" . $e->getTraceAsString());

    try {
        $requestPath = trim((string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');
        if ($requestPath === 'tenant/payment/process') {
            $debugPayload = [
                'ts' => date('c'),
                'error_id' => $globalErrorId,
                'step' => 'global_router',
                'message' => $e->getMessage(),
                'pdo' => ($e instanceof \PDOException) ? ($e->errorInfo ?? null) : null,
                'trace' => $e->getTraceAsString(),
            ];
            $debugFile = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'rentsmart_tenant_payment_last_error.json';
            @file_put_contents($debugFile, json_encode($debugPayload));
        }
    } catch (\Throwable $ignore) {
    }
    if (getenv('APP_ENV') === 'development') {
        throw $e;
    }
    // 500 Internal Server Error
    $acceptHeader = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (strpos($acceptHeader, 'application/json') !== false);
    if ($isAjax) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Server error. Error ID: ' . $globalErrorId,
            'error_id' => $globalErrorId
        ]);
        exit;
    }
    header("HTTP/1.0 500 Internal Server Error");
    echo view('errors/500', ['title' => '500 Internal Server Error']);
}