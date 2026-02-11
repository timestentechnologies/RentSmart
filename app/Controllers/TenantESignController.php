<?php

namespace App\Controllers;

use App\Models\ESignRequest;
use App\Models\Setting;

class TenantESignController
{
    public function index()
    {
        session_start();
        if (!isset($_SESSION['tenant_id'])) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        $tenantId = (int)$_SESSION['tenant_id'];

        $settingsModel = new Setting();
        $settings = $settingsModel->getAllAsAssoc();
        $siteLogo = isset($settings['site_logo']) && $settings['site_logo']
            ? BASE_URL . '/public/assets/images/' . $settings['site_logo']
            : BASE_URL . '/public/assets/images/logo.svg';
        $siteFavicon = isset($settings['site_favicon']) && $settings['site_favicon']
            ? BASE_URL . '/public/assets/images/' . $settings['site_favicon']
            : BASE_URL . '/public/assets/images/site_favicon_1750832003.png';

        $esignModel = new ESignRequest();
        $requests = $esignModel->listForTenantRecipient($tenantId, 50);

        require_once __DIR__ . '/../../views/tenant/esign.php';
    }
}
