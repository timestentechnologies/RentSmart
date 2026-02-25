<?php

namespace App\Controllers;

use App\Database\Connection;

class HomeController
{
    private $db;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }

    public function index()
    {
        try {
            // If user is logged in, redirect to dashboard
            if (isset($_SESSION['user_id'])) {
                $redirectPath = (isset($_SESSION['user_role']) && strtolower((string)$_SESSION['user_role']) === 'realtor')
                    ? '/realtor/dashboard'
                    : '/dashboard';
                header('Location: ' . BASE_URL . $redirectPath);
                exit;
            }
            
            // Fetch subscription plans
            $stmt = $this->db->prepare("SELECT * FROM subscription_plans ORDER BY price ASC");
            $stmt->execute();
            $plans = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Parse features string into array for each plan and ensure unique display
            $seenPlans = [];
            $filteredPlans = [];
            foreach ($plans as $plan) {
                if (!in_array($plan['name'], $seenPlans)) {
                    $plan['features_array'] = explode("\r\n", $plan['features']);
                    $filteredPlans[] = $plan;
                    $seenPlans[] = $plan['name'];
                }
            }
            $plans = $filteredPlans;

            // Fetch site settings
            require_once __DIR__ . '/../Models/Setting.php';
            $settingsModel = new \App\Models\Setting();
            $settings = $settingsModel->getAllAsAssoc();

            $siteName = $settings['site_name'] ?? 'RentSmart';
            $favicon = $settings['site_favicon'] ?? '';

            // Pass settings, siteName, favicon to the view
            require 'views/home.php';
        } catch (\Exception $e) {
            error_log($e->getMessage());
            if (getenv('APP_ENV') === 'development') {
                throw $e;
            }
            require 'views/errors/500.php';
        }
    }

    public function privacy()
    {
        try {
            require 'views/privacy-policy.php';
        } catch (\Exception $e) {
            error_log($e->getMessage());
            if (getenv('APP_ENV') === 'development') {
                throw $e;
            }
            require 'views/errors/500.php';
        }
    }

    public function terms()
    {
        try {
            require 'views/terms.php';
        } catch (\Exception $e) {
            error_log($e->getMessage());
            if (getenv('APP_ENV') === 'development') {
                throw $e;
            }
            require 'views/errors/500.php';
        }
    }

    public function vacantUnits()
    {
        try {
            // Public page listing vacant units
            require_once __DIR__ . '/../Models/Unit.php';
            require_once __DIR__ . '/../Models/Property.php';
            require_once __DIR__ . '/../Models/RealtorListing.php';
            require_once __DIR__ . '/../Helpers/FileUploadHelper.php';
            require_once __DIR__ . '/../Models/Setting.php';

            $unitModel = new \App\Models\Unit();
            $propertyModel = new \App\Models\Property();
            $realtorListingModel = new \App\Models\RealtorListing();

            $settingsModel = new \App\Models\Setting();
            $rawProtectedUnits = (string)($settingsModel->get('demo_protected_unit_ids_json') ?? '[]');
            $rawProtectedProperties = (string)($settingsModel->get('demo_protected_property_ids_json') ?? '[]');
            $rawProtectedUsers = (string)($settingsModel->get('demo_protected_user_ids_json') ?? '[]');
            $protectedUnitIds = json_decode($rawProtectedUnits, true);
            $protectedPropertyIds = json_decode($rawProtectedProperties, true);
            $protectedUserIds = json_decode($rawProtectedUsers, true);
            $protectedUnitIds = is_array($protectedUnitIds) ? array_map('intval', $protectedUnitIds) : [];
            $protectedPropertyIds = is_array($protectedPropertyIds) ? array_map('intval', $protectedPropertyIds) : [];
            $protectedUserIds = is_array($protectedUserIds) ? array_map('intval', $protectedUserIds) : [];

            // Get vacant units (public - no role filters)
            $units = $unitModel->getVacantUnitsPublic();
            if (!empty($protectedUnitIds) || !empty($protectedPropertyIds)) {
                $units = array_values(array_filter($units, function ($u) use ($protectedUnitIds, $protectedPropertyIds) {
                    $uid = (int)($u['id'] ?? 0);
                    $pid = (int)($u['property_id'] ?? 0);
                    if ($uid > 0 && in_array($uid, $protectedUnitIds, true)) {
                        return false;
                    }
                    if ($pid > 0 && in_array($pid, $protectedPropertyIds, true)) {
                        return false;
                    }
                    return true;
                }));
            }

            // Public realtor listings
            $realtorListings = $realtorListingModel->getPublicAll();
            if (!empty($protectedUserIds)) {
                $realtorListings = array_values(array_filter($realtorListings, function ($l) use ($protectedUserIds) {
                    $uid = (int)($l['user_id'] ?? 0);
                    if ($uid > 0 && in_array($uid, $protectedUserIds, true)) {
                        return false;
                    }
                    return true;
                }));
            }

            // Attach listing images
            try {
                $fileUploadHelper = new \App\Helpers\FileUploadHelper();
                foreach ($realtorListings as &$rl) {
                    $imgs = $fileUploadHelper->getEntityFiles('realtor_listing', (int)($rl['id'] ?? 0), 'image');
                    $urls = [];
                    foreach (($imgs ?? []) as $img) {
                        if (!empty($img['url'])) { $urls[] = $img['url']; }
                    }
                    $rl['images'] = $urls;
                    $rl['image'] = $urls[0] ?? null;
                }
                unset($rl);
            } catch (\Exception $e) {
                error_log('Failed to attach realtor listing images: ' . $e->getMessage());
            }

            // Attach property address and images for display
            $enhancedUnits = [];
            foreach ($units as $unit) {
                $property = $propertyModel->find($unit['property_id']);
                
                // Try to get unit images first
                $unitModel->id = $unit['id'];
                $unitImages = method_exists($unitModel, 'getImages') ? $unitModel->getImages() : [];
                
                // Then try property images
                $propertyModel->id = $unit['property_id'];
                $propertyImages = $propertyModel->getImages();
                
                // Build images list with priority: Unit images > Property images > Generated placeholder
                $images = [];
                if (!empty($unitImages)) {
                    foreach ($unitImages as $img) {
                        if (!empty($img['url'])) { $images[] = $img['url']; }
                    }
                } elseif (!empty($propertyImages)) {
                    foreach ($propertyImages as $img) {
                        if (!empty($img['url'])) { $images[] = $img['url']; }
                    }
                }
                if (empty($images)) {
                    $images[] = 'https://ui-avatars.com/api/?name=' . urlencode($unit['property_name'] . ' ' . $unit['unit_number']) . '&size=400&background=4F46E5&color=fff&bold=true';
                }

                $enhancedUnits[] = [
                    'id' => (int)$unit['id'],
                    'unit_number' => $unit['unit_number'],
                    'rent_amount' => $unit['rent_amount'],
                    'type' => $unit['type'],
                    'property_name' => $unit['property_name'],
                    'address' => $property['address'] ?? '',
                    'city' => $property['city'] ?? '',
                    'state' => $property['state'] ?? '',
                    'zip_code' => $property['zip_code'] ?? '',
                    'image' => $images[0],
                    'images' => $images,
                    'caretaker_name' => $property['caretaker_name'] ?? '',
                    'caretaker_contact' => $property['caretaker_contact'] ?? '',
                ];
            }

            // Settings for header/favicon
            $settings = $settingsModel->getAllAsAssoc();
            $siteName = $settings['site_name'] ?? 'RentSmart';
            $favicon = $settings['site_favicon'] ?? '';
            $siteLogo = isset($settings['site_logo']) && $settings['site_logo']
                ? BASE_URL . '/public/assets/images/' . $settings['site_logo']
                : BASE_URL . '/public/assets/images/logo.png';

            $vacantUnits = $enhancedUnits;
            $publicRealtorListings = $realtorListings;
            require 'views/vacant_units.php';
        } catch (\Exception $e) {
            error_log($e->getMessage());
            if (getenv('APP_ENV') === 'development') {
                throw $e;
            }
            require 'views/errors/500.php';
        }
    }
}