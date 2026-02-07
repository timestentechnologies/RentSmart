<?php
namespace App\Controllers;

use App\Models\Tenant;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Unit;
use App\Models\Property;
use App\Models\Utility;
use App\Models\Setting;
use App\Models\MaintenanceRequest;
use App\Models\PaymentMethod;
use App\Models\Notice;
use App\Models\Message;

class TenantPortalController
{
    public function dashboard()
    {
        session_start();
        if (!isset($_SESSION['tenant_id'])) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }
        $tenantId = $_SESSION['tenant_id'];
        $tenantModel = new Tenant();
        $tenant = $tenantModel->find($tenantId);
        $leaseModel = new Lease();
        $lease = $leaseModel->getActiveLeaseByTenant($tenantId);
        $unit = null;
        $property = null;
        if ($lease) {
            $unitModel = new Unit();
            $unit = $unitModel->find($lease['unit_id']);
            $propertyModel = new Property();
            $property = $propertyModel->find($unit['property_id']);
        }
        $paymentModel = new Payment();
        $rentPayments = $paymentModel->getTenantAllPayments($tenantId);
        $overdueRent = $paymentModel->getTenantOverdueRent($tenantId);
        $missedRentMonths = $paymentModel->getTenantMissedRentMonths($tenantId);
        $rentCoverage = $paymentModel->getTenantRentCoverage($tenantId);
        $maintenanceOutstanding = $paymentModel->getTenantMaintenanceOutstanding($tenantId);
        $utilityModel = new Utility();
        $utilities = $utilityModel->getTenantUtilities($tenantId);
        
        // Fetch maintenance requests for this tenant
        $maintenanceModel = new MaintenanceRequest();
        $maintenanceRequests = $maintenanceModel->getByTenant($tenantId);
        
        // Fetch active payment methods linked to this property only
        $paymentMethodModel = new PaymentMethod();
        $paymentMethods = $property ? $paymentMethodModel->getActiveForProperty((int)$property['id']) : [];
        
        // Fetch site logo from settings
        $settingsModel = new Setting();
        $settings = $settingsModel->getAllAsAssoc();
        $siteLogo = isset($settings['site_logo']) && $settings['site_logo']
            ? BASE_URL . '/public/assets/images/' . $settings['site_logo']
            : BASE_URL . '/public/assets/images/logo.svg';
        $siteFavicon = isset($settings['site_favicon']) && $settings['site_favicon']
            ? BASE_URL . '/public/assets/images/' . $settings['site_favicon']
            : BASE_URL . '/public/assets/images/site_favicon_1750832003.png';

        // Tenant notices
        $noticeModel = new Notice();
        $tenantNotices = $noticeModel->getVisibleForTenant((int)$tenantId);

        // Recent messages for this tenant
        $msgModel = new Message();
        $stmt = $msgModel->getDb()->prepare("SELECT * FROM messages WHERE (receiver_type = 'tenant' AND receiver_id = ?) OR (sender_type = 'tenant' AND sender_id = ?) ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([(int)$tenantId, (int)$tenantId]);
        $tenantMessages = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        require_once __DIR__ . '/../../views/tenant/dashboard.php';
    }
} 