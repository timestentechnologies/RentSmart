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
        } elseif (!empty($tenant['property_id'])) {
            // Tenant has a property assigned but no unit/lease
            $propertyModel = new Property();
            $property = $propertyModel->find($tenant['property_id']);
        }
        $paymentModel = new Payment();
        $rentPayments = $paymentModel->getTenantAllPayments($tenantId);
        $overdueRent = $paymentModel->getTenantOverdueRent($tenantId);
        $missedRentMonths = $paymentModel->getTenantMissedRentMonths($tenantId);
        $rentCoverage = $paymentModel->getTenantRentCoverage($tenantId);
        $utilityModel = new Utility();
        $utilities = $utilityModel->getTenantUtilities($tenantId);
        
        // Fetch maintenance requests for this tenant
        $maintenanceModel = new MaintenanceRequest();
        $maintenanceRequests = $maintenanceModel->getByTenant($tenantId);
        
        // Fetch active payment methods for the property's owner/manager/agent only
        $paymentMethodModel = new PaymentMethod();
        $ownerIds = [];
        if ($property) {
            if (!empty($property['owner_id'])) { $ownerIds[] = (int)$property['owner_id']; }
            if (!empty($property['manager_id'])) { $ownerIds[] = (int)$property['manager_id']; }
            if (!empty($property['agent_id'])) { $ownerIds[] = (int)$property['agent_id']; }
        }
        $paymentMethods = $paymentMethodModel->getActiveForUsers($ownerIds);
        
        // Fetch site logo from settings
        $settingsModel = new Setting();
        $settings = $settingsModel->getAllAsAssoc();
        $siteLogo = isset($settings['site_logo']) && $settings['site_logo']
            ? BASE_URL . '/public/assets/images/' . $settings['site_logo']
            : BASE_URL . '/public/assets/images/logo.svg';
        $siteFavicon = isset($settings['site_favicon']) && $settings['site_favicon']
            ? BASE_URL . '/public/assets/images/' . $settings['site_favicon']
            : BASE_URL . '/public/assets/images/site_favicon_1750832003.png';
        require_once __DIR__ . '/../../views/tenant/dashboard.php';
    }
} 