<?php

namespace App\Controllers;

use App\Database\Connection;

class JijiController
{
    private $db;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }

    /**
     * Show Jiji management page
     */
    public function manage()
    {
        try {
            requireAuth();

            require_once __DIR__ . '/../Models/Unit.php';
            require_once __DIR__ . '/../Models/Property.php';

            $unitModel = new \App\Models\Unit();
            $propertyModel = new \App\Models\Property();

            // Get vacant units for current user
            $userId = $_SESSION['user_id'];
            $isAdmin = $_SESSION['is_admin'] ?? false;

            if ($isAdmin) {
                $units = $unitModel->getVacantUnitsPublic();
            } else {
                // Get only user's vacant units
                $sql = "SELECT u.*, p.name as property_name, p.address, p.city, p.state
                        FROM units u
                        INNER JOIN properties p ON u.property_id = p.id
                        WHERE u.status = 'vacant' AND p.owner_id = ?
                        ORDER BY p.name, u.unit_number";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$userId]);
                $units = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }

            // Enhance units with image count
            $vacantUnits = [];
            foreach ($units as $unit) {
                $unitModel->id = $unit['id'];
                $unitImages = method_exists($unitModel, 'getImages') ? $unitModel->getImages() : [];
                
                $propertyModel->id = $unit['property_id'];
                $propertyImages = $propertyModel->getImages();
                
                $allImages = array_merge($unitImages, $propertyImages);
                
                $vacantUnits[] = array_merge($unit, [
                    'images' => $allImages,
                    'image_count' => count($allImages)
                ]);
            }

            require 'views/jiji/manage.php';

        } catch (\Exception $e) {
            error_log("Jiji manage page error: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading Jiji management page';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }

    /**
     * Export vacant units in Jiji-compatible CSV format
     */
    public function exportToJiji()
    {
        try {
            requireAuth();

            require_once __DIR__ . '/../Models/Unit.php';
            require_once __DIR__ . '/../Models/Property.php';

            $unitModel = new \App\Models\Unit();
            $propertyModel = new \App\Models\Property();

            // Get vacant units for current user
            $userId = $_SESSION['user_id'];
            $isAdmin = $_SESSION['is_admin'] ?? false;

            if ($isAdmin) {
                $units = $unitModel->getVacantUnitsPublic();
            } else {
                // Get only user's vacant units
                $sql = "SELECT u.*, p.name as property_name, p.address, p.city, p.state, p.description as property_description
                        FROM units u
                        INNER JOIN properties p ON u.property_id = p.id
                        WHERE u.status = 'vacant' AND p.owner_id = ?
                        ORDER BY p.name, u.unit_number";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$userId]);
                $units = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }

            if (empty($units)) {
                $_SESSION['flash_message'] = 'No vacant units available to export';
                $_SESSION['flash_type'] = 'warning';
                header('Location: ' . BASE_URL . '/vacant-units');
                exit;
            }

            // Prepare CSV data for Jiji
            $csvData = [];
            $csvData[] = [
                'Title',
                'Description',
                'Category',
                'Price',
                'Location',
                'Bedrooms',
                'Bathrooms',
                'Property Type',
                'Contact Phone',
                'Contact Email',
                'Images'
            ];

            foreach ($units as $unit) {
                $property = $propertyModel->find($unit['property_id']);
                
                // Get images
                $unitModel->id = $unit['id'];
                $unitImages = method_exists($unitModel, 'getImages') ? $unitModel->getImages() : [];
                
                $propertyModel->id = $unit['property_id'];
                $propertyImages = $propertyModel->getImages();
                
                // Collect all image URLs
                $imageUrls = [];
                foreach ($unitImages as $img) {
                    $imageUrls[] = $img['url'];
                }
                foreach ($propertyImages as $img) {
                    $imageUrls[] = $img['url'];
                }
                
                // Limit to 5 images (Jiji limit)
                $imageUrls = array_slice($imageUrls, 0, 5);
                
                // Create title
                $title = $unit['type'] . ' - ' . $unit['property_name'] . ' Unit ' . $unit['unit_number'];
                if (strlen($title) > 70) {
                    $title = substr($title, 0, 67) . '...';
                }
                
                // Create description
                $description = "Available for Rent: " . $unit['type'] . " in " . ($property['city'] ?? 'Nairobi') . "\n\n";
                $description .= "Property: " . $unit['property_name'] . "\n";
                $description .= "Unit Number: " . $unit['unit_number'] . "\n";
                $description .= "Monthly Rent: KSh " . number_format($unit['rent_amount'], 2) . "\n\n";
                
                if (!empty($property['description'])) {
                    $description .= strip_tags($property['description']) . "\n\n";
                }
                
                $description .= "Location: " . ($property['address'] ?? '') . ", " . ($property['city'] ?? 'Nairobi') . "\n\n";
                $description .= "Contact us for viewing arrangements.\n";
                $description .= "Managed by RentSmart Property Management";
                
                // Extract bedrooms from type (e.g., "2 Bedroom" -> 2)
                $bedrooms = '';
                if (preg_match('/(\d+)\s*bedroom/i', $unit['type'], $matches)) {
                    $bedrooms = $matches[1];
                }
                
                // Get user contact info
                $userStmt = $this->db->prepare("SELECT name, email FROM users WHERE id = ?");
                $userStmt->execute([$property['owner_id']]);
                $owner = $userStmt->fetch(\PDO::FETCH_ASSOC);
                
                $csvData[] = [
                    $title,
                    $description,
                    'Houses & Apartments For Rent',
                    number_format($unit['rent_amount'], 0, '', ''),
                    $property['city'] ?? 'Nairobi',
                    $bedrooms,
                    '', // Bathrooms - not in our system
                    $unit['type'],
                    '+254718883983', // Your contact number
                    'timestentechnologies@gmail.com',
                    implode('|', $imageUrls)
                ];
            }

            // Generate CSV file
            $filename = 'jiji_export_' . date('Y-m-d_His') . '.csv';
            
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
            error_log("Jiji export error: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error exporting to Jiji: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/vacant-units');
            exit;
        }
    }

    /**
     * Generate Jiji listing URL for a specific unit
     */
    public function generateJijiUrl($unitId)
    {
        try {
            requireAuth();

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

            // Generate Jiji post URL with pre-filled data
            $title = urlencode($unit['type'] . ' - ' . $property['name'] . ' Unit ' . $unit['unit_number']);
            $price = $unit['rent_amount'];
            $location = urlencode($property['city'] ?? 'Nairobi');
            
            $jijiUrl = "https://jiji.co.ke/post-ad?";
            $jijiUrl .= "category=houses-apartments-for-rent";
            $jijiUrl .= "&title=" . $title;
            $jijiUrl .= "&price=" . $price;
            $jijiUrl .= "&location=" . $location;

            // Return JSON response
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'url' => $jijiUrl,
                'message' => 'Jiji listing URL generated'
            ]);
            exit;

        } catch (\Exception $e) {
            error_log("Jiji URL generation error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * Bulk post to Jiji - Opens Jiji in new tabs for each unit
     */
    public function bulkPostToJiji()
    {
        try {
            requireAuth();

            require_once __DIR__ . '/../Models/Unit.php';
            require_once __DIR__ . '/../Models/Property.php';

            $unitModel = new \App\Models\Unit();
            $propertyModel = new \App\Models\Property();

            // Get vacant units for current user
            $userId = $_SESSION['user_id'];
            $isAdmin = $_SESSION['is_admin'] ?? false;

            if ($isAdmin) {
                $units = $unitModel->getVacantUnitsPublic();
            } else {
                $sql = "SELECT u.*, p.name as property_name, p.address, p.city, p.state
                        FROM units u
                        INNER JOIN properties p ON u.property_id = p.id
                        WHERE u.status = 'vacant' AND p.owner_id = ?
                        ORDER BY p.name, u.unit_number";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$userId]);
                $units = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }

            $jijiUrls = [];
            foreach ($units as $unit) {
                $property = $propertyModel->find($unit['property_id']);
                
                $title = urlencode($unit['type'] . ' - ' . $unit['property_name'] . ' Unit ' . $unit['unit_number']);
                $price = $unit['rent_amount'];
                $location = urlencode($property['city'] ?? 'Nairobi');
                
                $jijiUrl = "https://jiji.co.ke/post-ad?";
                $jijiUrl .= "category=houses-apartments-for-rent";
                $jijiUrl .= "&title=" . $title;
                $jijiUrl .= "&price=" . $price;
                $jijiUrl .= "&location=" . $location;
                
                $jijiUrls[] = [
                    'unit_id' => $unit['id'],
                    'unit_number' => $unit['unit_number'],
                    'property_name' => $unit['property_name'],
                    'url' => $jijiUrl
                ];
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'urls' => $jijiUrls,
                'count' => count($jijiUrls)
            ]);
            exit;

        } catch (\Exception $e) {
            error_log("Bulk Jiji post error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }
}
