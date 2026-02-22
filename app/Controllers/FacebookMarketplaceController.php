<?php

namespace App\Controllers;

use App\Database\Connection;
use App\Models\RealtorListing;

class FacebookMarketplaceController
{
    private $db;
    private $accessToken;
    private $pageId;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
        
        // Get Facebook credentials from settings
        $stmt = $this->db->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('facebook_access_token', 'facebook_page_id')");
        $stmt->execute();
        $settings = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        
        $this->accessToken = $settings['facebook_access_token'] ?? null;
        $this->pageId = $settings['facebook_page_id'] ?? null;
    }

    /**
     * Show Facebook Marketplace management page
     */
    public function manage()
    {
        try {
            requireAuth();

            $role = strtolower((string)($_SESSION['user_role'] ?? ''));
            if ($role === 'realtor') {
                $userId = (int)($_SESSION['user_id'] ?? 0);
                $listingModel = new RealtorListing();
                $rows = $listingModel->getAllNotSold($userId);
                $vacantUnits = [];
                foreach (($rows ?? []) as $r) {
                    $vacantUnits[] = [
                        'id' => (int)($r['id'] ?? 0),
                        'property_name' => (string)($r['title'] ?? ''),
                        'unit_number' => '#'.(int)($r['id'] ?? 0),
                        'type' => (string)($r['listing_type'] ?? ''),
                        'rent_amount' => (float)($r['price'] ?? 0),
                        'image_count' => 0,
                        'is_posted' => false,
                        '__is_realtor_listing' => 1,
                    ];
                }

                $isConfigured = !empty($this->accessToken) && !empty($this->pageId);
                $accessToken = $this->accessToken;
                $pageId = $this->pageId;

                require 'views/integrations/facebook_marketplace.php';
                return;
            }

            require_once __DIR__ . '/../Models/Unit.php';
            require_once __DIR__ . '/../Models/Property.php';

            $unitModel = new \App\Models\Unit();
            $propertyModel = new \App\Models\Property();

            $userId = $_SESSION['user_id'];
            $isAdmin = $_SESSION['is_admin'] ?? false;

            if ($isAdmin) {
                $units = $unitModel->getVacantUnitsPublic();
            } else {
                $units = $unitModel->getVacantUnits($userId);
            }

            // Get Facebook posting status for each unit
            $vacantUnits = [];
            foreach ($units as $unit) {
                $unitModel->id = $unit['id'];
                $unitImages = method_exists($unitModel, 'getImages') ? $unitModel->getImages() : [];
                
                $propertyModel->id = $unit['property_id'];
                $propertyImages = $propertyModel->getImages();
                
                $allImages = array_merge($unitImages, $propertyImages);
                
                // Check if posted to Facebook
                $stmt = $this->db->prepare("SELECT * FROM facebook_listings WHERE unit_id = ? ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$unit['id']]);
                $fbListing = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                $vacantUnits[] = array_merge($unit, [
                    'images' => $allImages,
                    'image_count' => count($allImages),
                    'fb_listing' => $fbListing,
                    'is_posted' => !empty($fbListing)
                ]);
            }

            $isConfigured = !empty($this->accessToken) && !empty($this->pageId);

            require 'views/integrations/facebook_marketplace.php';

        } catch (\Exception $e) {
            error_log("Facebook Marketplace error: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading Facebook Marketplace page';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }

    /**
     * Post a unit to Facebook Marketplace
     */
    public function postUnit()
    {
        try {
            requireAuth();

            if (!$this->accessToken || !$this->pageId) {
                throw new \Exception('Facebook Marketplace not configured');
            }

            $unitId = $_POST['unit_id'] ?? null;
            if (!$unitId) {
                throw new \Exception('Unit ID required');
            }

            require_once __DIR__ . '/../Models/Unit.php';
            require_once __DIR__ . '/../Models/Property.php';

            $unitModel = new \App\Models\Unit();
            $propertyModel = new \App\Models\Property();

            $unit = $unitModel->find($unitId);
            if (!$unit) {
                throw new \Exception('Unit not found');
            }

            $property = $propertyModel->find($unit['property_id']);

            // Check ownership
            $userId = $_SESSION['user_id'];
            $isAdmin = $_SESSION['is_admin'] ?? false;
            
            if (!$isAdmin && $property['owner_id'] != $userId) {
                throw new \Exception('Unauthorized access');
            }

            // Get images
            $unitModel->id = $unitId;
            $unitImages = method_exists($unitModel, 'getImages') ? $unitModel->getImages() : [];
            
            $propertyModel->id = $unit['property_id'];
            $propertyImages = $propertyModel->getImages();
            
            $allImages = array_merge($unitImages, $propertyImages);
            
            if (empty($allImages)) {
                throw new \Exception('At least one image is required for Facebook Marketplace');
            }

            // Prepare listing data
            $title = $unit['type'] . ' for Rent - ' . $property['name'];
            if (strlen($title) > 100) {
                $title = substr($title, 0, 97) . '...';
            }

            $description = "Available for Rent\n\n";
            $description .= "Property: " . $property['name'] . "\n";
            $description .= "Unit: " . $unit['unit_number'] . "\n";
            $description .= "Type: " . $unit['type'] . "\n";
            $description .= "Monthly Rent: KSh " . number_format($unit['rent_amount'], 2) . "\n\n";
            
            if (!empty($property['description'])) {
                $description .= strip_tags($property['description']) . "\n\n";
            }
            
            $description .= "Location: " . ($property['address'] ?? '') . ", " . ($property['city'] ?? 'Nairobi') . "\n\n";
            $description .= "Contact: +254718883983\n";
            $description .= "Email: timestentechnologies@gmail.com";

            // Post to Facebook Marketplace via Graph API
            $listingData = [
                'name' => $title,
                'description' => $description,
                'price' => $unit['rent_amount'],
                'currency' => 'KES',
                'availability' => 'available',
                'condition' => 'new',
                'category' => 'PROPERTY_FOR_RENT',
                'location' => [
                    'city' => $property['city'] ?? 'Nairobi',
                    'country' => 'Kenya'
                ]
            ];

            // Upload images first
            $imageIds = [];
            foreach (array_slice($allImages, 0, 10) as $image) {
                $imageUrl = $image['url'];
                $imageId = $this->uploadImageToFacebook($imageUrl);
                if ($imageId) {
                    $imageIds[] = $imageId;
                }
            }

            if (!empty($imageIds)) {
                $listingData['images'] = $imageIds;
            }

            // Create marketplace listing
            $response = $this->callFacebookAPI(
                "/{$this->pageId}/marketplace_listings",
                'POST',
                $listingData
            );

            if (isset($response['id'])) {
                // Save to database
                $stmt = $this->db->prepare("
                    INSERT INTO facebook_listings (unit_id, facebook_listing_id, status, created_at)
                    VALUES (?, ?, 'active', NOW())
                    ON DUPLICATE KEY UPDATE 
                        facebook_listing_id = VALUES(facebook_listing_id),
                        status = 'active',
                        updated_at = NOW()
                ");
                $stmt->execute([$unitId, $response['id']]);

                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Posted to Facebook Marketplace successfully',
                    'listing_id' => $response['id']
                ]);
            } else {
                throw new \Exception('Failed to create Facebook listing');
            }

        } catch (\Exception $e) {
            error_log("Facebook post error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Delete a listing from Facebook Marketplace
     */
    public function deleteUnit()
    {
        try {
            requireAuth();

            $unitId = $_POST['unit_id'] ?? null;
            if (!$unitId) {
                throw new \Exception('Unit ID required');
            }

            // Get Facebook listing ID
            $stmt = $this->db->prepare("SELECT facebook_listing_id FROM facebook_listings WHERE unit_id = ? AND status = 'active'");
            $stmt->execute([$unitId]);
            $listing = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$listing) {
                throw new \Exception('No active Facebook listing found');
            }

            // Delete from Facebook
            $response = $this->callFacebookAPI(
                "/{$listing['facebook_listing_id']}",
                'DELETE'
            );

            // Update database
            $stmt = $this->db->prepare("UPDATE facebook_listings SET status = 'deleted', updated_at = NOW() WHERE unit_id = ?");
            $stmt->execute([$unitId]);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Removed from Facebook Marketplace'
            ]);

        } catch (\Exception $e) {
            error_log("Facebook delete error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Save Facebook configuration
     */
    public function saveConfig()
    {
        try {
            requireAuth();

            $accessToken = $_POST['access_token'] ?? '';
            $pageId = $_POST['page_id'] ?? '';

            if (empty($accessToken) || empty($pageId)) {
                throw new \Exception('Access token and Page ID are required');
            }

            // Verify token is valid
            $response = $this->callFacebookAPI('/me', 'GET', [], $accessToken);
            if (!isset($response['id'])) {
                throw new \Exception('Invalid access token');
            }

            // Save to database
            $stmt = $this->db->prepare("
                INSERT INTO settings (setting_key, setting_value, updated_at)
                VALUES ('facebook_access_token', ?, NOW()),
                       ('facebook_page_id', ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value),
                    updated_at = NOW()
            ");
            $stmt->execute([$accessToken, $pageId]);

            $_SESSION['flash_message'] = 'Facebook Marketplace configured successfully';
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . BASE_URL . '/integrations/facebook');
            exit;

        } catch (\Exception $e) {
            error_log("Facebook config error: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/integrations/facebook');
            exit;
        }
    }

    /**
     * Upload image to Facebook
     */
    private function uploadImageToFacebook($imageUrl)
    {
        try {
            $response = $this->callFacebookAPI(
                "/{$this->pageId}/photos",
                'POST',
                [
                    'url' => $imageUrl,
                    'published' => false
                ]
            );

            return $response['id'] ?? null;
        } catch (\Exception $e) {
            error_log("Image upload error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Call Facebook Graph API
     */
    private function callFacebookAPI($endpoint, $method = 'GET', $params = [], $token = null)
    {
        $token = $token ?? $this->accessToken;
        $url = "https://graph.facebook.com/v18.0" . $endpoint;

        $params['access_token'] = $token;

        $ch = curl_init();

        if ($method === 'GET') {
            $url .= '?' . http_build_query($params);
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            
            if ($method === 'DELETE') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            }
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);

        if ($httpCode >= 400 || isset($data['error'])) {
            $errorMsg = $data['error']['message'] ?? 'Unknown error';
            throw new \Exception("Facebook API Error: " . $errorMsg);
        }

        return $data;
    }
}
