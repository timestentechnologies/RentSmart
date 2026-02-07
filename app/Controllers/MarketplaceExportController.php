<?php

namespace App\Controllers;

use App\Database\Connection;

class MarketplaceExportController
{
    private $db;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }

    /**
     * Show marketplace export management page
     */
    public function manage()
    {
        try {
            requireAuth();

            require_once __DIR__ . '/../Models/Unit.php';
            $unitModel = new \App\Models\Unit();

            $userId = $_SESSION['user_id'];
            $isAdmin = $_SESSION['is_admin'] ?? false;

            if ($isAdmin) {
                $units = $unitModel->getVacantUnitsPublic();
            } else {
                $units = $unitModel->getVacantUnits($userId);
            }

            $vacantCount = count($units);

            require 'views/integrations/marketplaces.php';

        } catch (\Exception $e) {
            error_log("Marketplace manage error: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading marketplace export page';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }

    /**
     * Export vacant units in universal CSV format for multiple platforms
     * Compatible with: Jiji, PigiaMe, BuyRentKenya, OLX, Property24
     */
    public function exportUniversal()
    {
        try {
            requireAuth();

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

            if (empty($units)) {
                $_SESSION['flash_message'] = 'No vacant units available to export';
                $_SESSION['flash_type'] = 'warning';
                header('Location: ' . BASE_URL . '/integrations/marketplaces');
                exit;
            }

            // Universal CSV format
            $csvData = [];
            $csvData[] = [
                'Platform',
                'Title',
                'Description',
                'Category',
                'Property Type',
                'Price (KSh)',
                'Currency',
                'Bedrooms',
                'Bathrooms',
                'Location (City)',
                'Full Address',
                'Contact Name',
                'Contact Phone',
                'Contact Email',
                'Image 1',
                'Image 2',
                'Image 3',
                'Image 4',
                'Image 5',
                'Property Name',
                'Unit Number',
                'Unit Type'
            ];

            foreach ($units as $unit) {
                $property = $propertyModel->find($unit['property_id']);
                
                // Get images
                $unitModel->id = $unit['id'];
                $unitImages = method_exists($unitModel, 'getImages') ? $unitModel->getImages() : [];
                
                $propertyModel->id = $unit['property_id'];
                $propertyImages = $propertyModel->getImages();
                
                $allImages = array_merge($unitImages, $propertyImages);
                $imageUrls = array_map(function($img) { return $img['url']; }, $allImages);
                
                // Pad images array to 5
                $imageUrls = array_pad(array_slice($imageUrls, 0, 5), 5, '');
                
                // Create title
                $title = $unit['type'] . ' for Rent - ' . $unit['property_name'];
                if (strlen($title) > 100) {
                    $title = substr($title, 0, 97) . '...';
                }
                
                // Create description
                $description = "🏠 Available for Rent\n\n";
                $description .= "📍 Property: " . $unit['property_name'] . "\n";
                $description .= "🔑 Unit: " . $unit['unit_number'] . "\n";
                $description .= "🏡 Type: " . $unit['type'] . "\n";
                $description .= "💰 Monthly Rent: KSh " . number_format($unit['rent_amount'], 2) . "\n\n";
                
                if (!empty($property['description'])) {
                    $description .= "📝 Description:\n" . strip_tags($property['description']) . "\n\n";
                }
                
                $description .= "📍 Location: " . ($property['address'] ?? '') . ", " . ($property['city'] ?? 'Nairobi') . ", Kenya\n\n";
                $description .= "📞 Contact: +254 718 883 983\n";
                $description .= "📧 Email: timestentechnologies@gmail.com\n\n";
                $description .= "✅ Managed by RentSmart Property Management\n";
                $description .= "🌐 View more properties at: " . BASE_URL;
                
                // Extract bedrooms
                $bedrooms = '';
                if (preg_match('/(\d+)\s*bedroom/i', $unit['type'], $matches)) {
                    $bedrooms = $matches[1];
                } elseif (stripos($unit['type'], 'studio') !== false) {
                    $bedrooms = '0';
                } elseif (stripos($unit['type'], 'bedsitter') !== false) {
                    $bedrooms = '1';
                }
                
                // Get user info
                $userStmt = $this->db->prepare("SELECT name, email FROM users WHERE id = ?");
                $userStmt->execute([$property['owner_id']]);
                $owner = $userStmt->fetch(\PDO::FETCH_ASSOC);
                
                // Add row for each platform
                $platforms = ['Jiji', 'PigiaMe', 'BuyRentKenya', 'OLX', 'Property24'];
                
                foreach ($platforms as $platform) {
                    $csvData[] = [
                        $platform,
                        $title,
                        $description,
                        'Houses & Apartments For Rent',
                        $unit['type'],
                        number_format($unit['rent_amount'], 0, '', ''),
                        'KES',
                        $bedrooms,
                        '', // Bathrooms
                        $property['city'] ?? 'Nairobi',
                        ($property['address'] ?? '') . ', ' . ($property['city'] ?? 'Nairobi'),
                        $owner['name'] ?? 'RentSmart',
                        '+254718883983',
                        'timestentechnologies@gmail.com',
                        $imageUrls[0],
                        $imageUrls[1],
                        $imageUrls[2],
                        $imageUrls[3],
                        $imageUrls[4],
                        $unit['property_name'],
                        $unit['unit_number'],
                        $unit['type']
                    ];
                }
            }

            // Generate CSV
            $filename = 'marketplace_export_' . date('Y-m-d_His') . '.csv';
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            foreach ($csvData as $row) {
                fputcsv($output, $row);
            }
            
            fclose($output);
            exit;

        } catch (\Exception $e) {
            error_log("Universal export error: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error exporting: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/integrations/marketplaces');
            exit;
        }
    }

    /**
     * Export for specific platform
     */
    public function exportPlatform($platform)
    {
        try {
            requireAuth();

            $validPlatforms = ['jiji', 'pigiame', 'buyrentkenya', 'olx', 'property24'];
            $platform = strtolower($platform);
            
            if (!in_array($platform, $validPlatforms)) {
                throw new \Exception('Invalid platform');
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

            if (empty($units)) {
                $_SESSION['flash_message'] = 'No vacant units available';
                $_SESSION['flash_type'] = 'warning';
                header('Location: ' . BASE_URL . '/integrations/marketplaces');
                exit;
            }

            // Platform-specific CSV format
            $csvData = $this->generatePlatformCSV($platform, $units, $unitModel, $propertyModel);

            // Generate CSV
            $filename = $platform . '_export_' . date('Y-m-d_His') . '.csv';
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            foreach ($csvData as $row) {
                fputcsv($output, $row);
            }
            
            fclose($output);
            exit;

        } catch (\Exception $e) {
            error_log("Platform export error: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/integrations/marketplaces');
            exit;
        }
    }

    /**
     * Generate platform-specific CSV
     */
    private function generatePlatformCSV($platform, $units, $unitModel, $propertyModel)
    {
        $csvData = [];
        
        // Platform-specific headers
        switch ($platform) {
            case 'jiji':
            case 'pigiame':
            case 'olx':
                $csvData[] = ['Title', 'Description', 'Category', 'Price', 'Location', 'Bedrooms', 'Contact Phone', 'Contact Email', 'Images'];
                break;
            case 'buyrentkenya':
                $csvData[] = ['Title', 'Description', 'Property Type', 'Price', 'City', 'Address', 'Bedrooms', 'Bathrooms', 'Contact', 'Email', 'Images'];
                break;
            case 'property24':
                $csvData[] = ['Listing Title', 'Description', 'Property Type', 'Monthly Rent', 'Location', 'Bedrooms', 'Bathrooms', 'Contact Number', 'Email', 'Photo URLs'];
                break;
        }

        foreach ($units as $unit) {
            $property = $propertyModel->find($unit['property_id']);
            
            $unitModel->id = $unit['id'];
            $unitImages = method_exists($unitModel, 'getImages') ? $unitModel->getImages() : [];
            
            $propertyModel->id = $unit['property_id'];
            $propertyImages = $propertyModel->getImages();
            
            $allImages = array_merge($unitImages, $propertyImages);
            $imageUrls = array_map(function($img) { return $img['url']; }, $allImages);
            $imagesString = implode('|', array_slice($imageUrls, 0, 10));
            
            $title = $unit['type'] . ' - ' . $unit['property_name'] . ' Unit ' . $unit['unit_number'];
            $description = "Available for Rent\n\nProperty: " . $unit['property_name'] . "\nUnit: " . $unit['unit_number'] . "\nRent: KSh " . number_format($unit['rent_amount'], 2) . "\n\nContact: +254718883983";
            
            $bedrooms = '';
            if (preg_match('/(\d+)\s*bedroom/i', $unit['type'], $matches)) {
                $bedrooms = $matches[1];
            }
            
            switch ($platform) {
                case 'jiji':
                case 'pigiame':
                case 'olx':
                    $csvData[] = [
                        $title,
                        $description,
                        'Houses & Apartments For Rent',
                        $unit['rent_amount'],
                        $property['city'] ?? 'Nairobi',
                        $bedrooms,
                        '+254718883983',
                        'timestentechnologies@gmail.com',
                        $imagesString
                    ];
                    break;
                case 'buyrentkenya':
                case 'property24':
                    $csvData[] = [
                        $title,
                        $description,
                        $unit['type'],
                        $unit['rent_amount'],
                        $property['city'] ?? 'Nairobi',
                        $property['address'] ?? '',
                        $bedrooms,
                        '', // Bathrooms
                        '+254718883983',
                        'timestentechnologies@gmail.com',
                        $imagesString
                    ];
                    break;
            }
        }

        return $csvData;
    }
}
