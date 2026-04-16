<?php

namespace App\Controllers;

use App\Database\Connection;
use App\Models\AirbnbBooking;
use App\Models\AirbnbProperty;
use App\Models\AirbnbUnitRate;
use App\Models\Property;
use App\Models\Unit;
use App\Models\Setting;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class AirbnbPublicController
{
    private $db;
    private $bookingModel;
    private $propertyModel;
    private $unitRateModel;
    private $property;
    private $unit;
    private $settings;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
        $this->bookingModel = new AirbnbBooking();
        $this->propertyModel = new AirbnbProperty();
        $this->unitRateModel = new AirbnbUnitRate();
        $this->property = new Property();
        $this->unit = new Unit();
        $this->settings = new Setting();
    }

    /**
     * Public Airbnb listing page
     */
    public function index()
    {
        try {
            // Get site settings
            $settings = $this->settings->getAllAsAssoc();
            $siteName = $settings['site_name'] ?? 'RentSmart';
            $favicon = $settings['site_favicon'] ?? '';
            $siteLogoFile = $settings['site_logo'] ?? '';
            $appsLogoFile = $settings['apps_page_logo'] ?? '';
            $siteLogo = $appsLogoFile
                ? (BASE_URL . '/public/assets/images/' . $appsLogoFile)
                : ($siteLogoFile ? (BASE_URL . '/public/assets/images/' . $siteLogoFile) : (BASE_URL . '/public/assets/images/logo.svg'));

            // Get location filter from query params
            $location = $_GET['location'] ?? null;
            
            // Get all Airbnb-enabled properties with their units (filtered by location if provided)
            $airbnbProperties = $this->getAvailableAirbnbListings($location);

            // Set active page for header highlighting
            $activePage = 'airbnb';

            require 'views/airbnb/public_listing.php';
        } catch (\Exception $e) {
            error_log($e->getMessage());
            require 'views/errors/500.php';
        }
    }

    /**
     * Get available Airbnb listings with optional location filter
     */
    private function getAvailableAirbnbListings($location = null)
    {
        $sql = "
            SELECT 
                u.id as unit_id,
                u.unit_number,
                u.type,
                u.property_id,
                u.is_airbnb_eligible,
                p.name as property_name,
                p.address,
                p.city,
                p.state,
                p.caretaker_name,
                p.caretaker_contact,
                p.description,
                ap.is_airbnb_enabled,
                ap.min_stay_nights,
                ap.max_stay_nights,
                ap.check_in_time,
                ap.check_out_time,
                ap.cleaning_fee,
                ap.security_deposit,
                ap.house_rules,
                ap.cancellation_policy,
                aur.base_price_per_night,
                aur.weekend_price,
                aur.weekly_discount_percent,
                aur.monthly_discount_percent
            FROM units u
            JOIN properties p ON u.property_id = p.id
            LEFT JOIN airbnb_properties ap ON p.id = ap.property_id
            LEFT JOIN airbnb_unit_rates aur ON u.id = aur.unit_id
            WHERE u.is_airbnb_eligible = 1 
            AND (ap.is_airbnb_enabled = 1 OR ap.is_airbnb_enabled IS NULL)
            AND p.id IN (
                SELECT DISTINCT property_id FROM airbnb_properties WHERE is_airbnb_enabled = 1
            )";
        
        $params = [];
        
        // Add location filter if provided
        if ($location && trim($location) !== '') {
            $sql .= " AND (p.city LIKE ? OR p.state LIKE ? OR p.address LIKE ? OR p.name LIKE ?)";
            $locationPattern = '%' . trim($location) . '%';
            $params = [$locationPattern, $locationPattern, $locationPattern, $locationPattern];
        }
        
        $sql .= " ORDER BY p.name, u.unit_number";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $units = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Group by property
        $properties = [];
        foreach ($units as $unit) {
            $pid = $unit['property_id'];
            if (!isset($properties[$pid])) {
                $properties[$pid] = [
                    'id' => $pid,
                    'name' => $unit['property_name'],
                    'address' => $unit['address'],
                    'city' => $unit['city'],
                    'state' => $unit['state'],
                    'caretaker_name' => $unit['caretaker_name'],
                    'caretaker_contact' => $unit['caretaker_contact'],
                    'description' => $unit['description'],
                    'min_stay_nights' => $unit['min_stay_nights'] ?? 1,
                    'max_stay_nights' => $unit['max_stay_nights'] ?? 30,
                    'check_in_time' => $unit['check_in_time'] ?? '14:00',
                    'check_out_time' => $unit['check_out_time'] ?? '11:00',
                    'cleaning_fee' => $unit['cleaning_fee'] ?? 0,
                    'security_deposit' => $unit['security_deposit'] ?? 0,
                    'house_rules' => $unit['house_rules'],
                    'cancellation_policy' => $unit['cancellation_policy'] ?? 'moderate',
                    'units' => [],
                    'images' => []
                ];
                
                // Get property images
                $this->property->id = $pid;
                $properties[$pid]['images'] = $this->property->getImages();
            }
            
            // Get unit images
            $this->unit->id = $unit['unit_id'];
            $unitImages = $this->unit->getImages ? $this->unit->getImages() : [];
            
            $properties[$pid]['units'][] = [
                'id' => $unit['unit_id'],
                'unit_number' => $unit['unit_number'],
                'type' => $unit['type'],
                'base_price' => $unit['base_price_per_night'] ?? 0,
                'weekend_price' => $unit['weekend_price'] ?? $unit['base_price_per_night'],
                'weekly_discount' => $unit['weekly_discount_percent'] ?? 0,
                'monthly_discount' => $unit['monthly_discount_percent'] ?? 0,
                'images' => $unitImages
            ];
        }
        
        return array_values($properties);
    }

    /**
     * Show property details page
     */
    public function property($propertyId)
    {
        // Force error display for debugging
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        echo "<!-- Debug: Starting property method for ID: $propertyId -->";
        
        try {
            // Check if property exists and is enabled for Airbnb
            $property = $this->property->find($propertyId);
            echo "<!-- Debug: Property found -->";
            if (!$property) {
                http_response_code(404);
                require 'views/errors/404.php';
                return;
            }

            // Check if Airbnb is enabled for this property
            echo "<!-- Debug: Checking airbnb settings -->";
            $airbnbSettings = $this->propertyModel->getByPropertyId($propertyId);
            echo "<!-- Debug: Airbnb settings checked -->";
            if (!$airbnbSettings || !$airbnbSettings['is_airbnb_enabled']) {
                http_response_code(404);
                require 'views/errors/404.php';
                return;
            }

            // Get property images from file_uploads table
            echo "<!-- Debug: Getting property images -->";
            $stmt = $this->db->prepare("SELECT * FROM file_uploads WHERE entity_type = 'property' AND entity_id = ? AND file_type = 'image' ORDER BY created_at DESC");
            $stmt->execute([$propertyId]);
            $property['images'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Add full URL to each image
            foreach ($property['images'] as &$file) {
                $uploadPath = $file['upload_path'];
                if (strpos($uploadPath, 'public/') === 0) {
                    $file['path'] = $uploadPath;
                } elseif (strpos($uploadPath, 'uploads/') === 0) {
                    $file['path'] = 'public/' . $uploadPath;
                } else {
                    $file['path'] = 'public/uploads/' . $uploadPath;
                }
            }

            // Get eligible units with rates and property settings
            echo "<!-- Debug: Getting units query -->";
            $stmt = $this->db->prepare("
                SELECT 
                    u.id,
                    u.unit_number,
                    u.type,
                    u.size,
                    u.rent_amount,
                    aur.base_price_per_night,
                    aur.weekend_price,
                    aur.weekly_discount_percent,
                    aur.monthly_discount_percent,
                    ap.cleaning_fee,
                    ap.security_deposit,
                    ap.min_stay_nights,
                    ap.max_stay_nights,
                    ap.check_in_time,
                    ap.check_out_time
                FROM units u
                LEFT JOIN airbnb_unit_rates aur ON u.id = aur.unit_id
                LEFT JOIN airbnb_properties ap ON ap.property_id = u.property_id
                WHERE u.property_id = ? AND u.is_airbnb_eligible = 1
            ");
            $stmt->execute([$propertyId]);
            echo "<!-- Debug: Units query executed -->";
            $units = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            echo "<!-- Debug: Units fetched: " . count($units) . " -->";

            // Get unit images from file_uploads table
            foreach ($units as &$unit) {
                $stmt = $this->db->prepare("SELECT * FROM file_uploads WHERE entity_type = 'unit' AND entity_id = ? AND file_type = 'image' ORDER BY created_at DESC");
                $stmt->execute([$unit['id']]);
                $files = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                
                // Add full URL to each image
                foreach ($files as &$file) {
                    $uploadPath = $file['upload_path'];
                    if (strpos($uploadPath, 'public/') === 0) {
                        $file['path'] = $uploadPath;
                    } elseif (strpos($uploadPath, 'uploads/') === 0) {
                        $file['path'] = 'public/' . $uploadPath;
                    } else {
                        $file['path'] = 'public/uploads/' . $uploadPath;
                    }
                }
                $unit['images'] = $files;
            }

            // Get settings for display
            echo "<!-- Debug: Getting settings -->";
            $settings = $this->settings->getAllAsAssoc();
            echo "<!-- Debug: Settings loaded -->";



            // Load the view
            echo "<!-- Debug: Including view -->";
            require 'views/airbnb/property.php';
            echo "<!-- Debug: View loaded successfully -->";
        } catch (\Exception $e) {
            error_log('Error loading property page: ' . $e->getMessage());
            http_response_code(500);
            // Show error details for debugging
            echo '<h2>Debug Error Details:</h2>';
            echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . '</p>';
            echo '<p><strong>Line:</strong> ' . $e->getLine() . '</p>';
            echo '<hr><p><strong>Trace:</strong></p><pre>';
            echo htmlspecialchars($e->getTraceAsString());
            echo '</pre>';
            return;
        }
    }

    /**
     * Show booking form for a specific unit
     */
    public function bookUnit()
    {
        try {
            $unitId = $_GET['unit_id'] ?? null;
            $checkIn = $_GET['check_in'] ?? null;
            $checkOut = $_GET['check_out'] ?? null;
            $guests = $_GET['guests'] ?? 1;

            if (!$unitId) {
                header('Location: ' . BASE_URL . '/airbnb');
                exit;
            }

            // Get unit details
            $unit = $this->unit->find($unitId);
            if (!$unit || !$unit['is_airbnb_eligible']) {
                http_response_code(404);
                require 'views/errors/404.php';
                return;
            }

            $property = $this->property->find($unit['property_id']);
            if (!$property) {
                http_response_code(404);
                require 'views/errors/404.php';
                return;
            }

            // Check if Airbnb is enabled for this property
            $airbnbSettings = $this->propertyModel->getByPropertyId($property['id']);
            if (!$airbnbSettings || !$airbnbSettings['is_airbnb_enabled']) {
                http_response_code(404);
                require 'views/errors/404.php';
                return;
            }

            // Get rates
            $rates = $this->unitRateModel->getByUnitId($unitId);
            $price = null;
            
            if ($checkIn && $checkOut) {
                $price = $this->unitRateModel->calculatePrice($unitId, $checkIn, $checkOut, $guests);
                if ($price) {
                    $price['cleaning_fee'] = $airbnbSettings['cleaning_fee'] ?? 0;
                    $price['security_deposit'] = $airbnbSettings['security_deposit'] ?? 0;
                    $price['final_total'] = $price['subtotal'] + $price['cleaning_fee'] + $price['security_deposit'];
                }
            }

            // Get site settings
            $settings = $this->settings->getAllAsAssoc();
            $siteName = $settings['site_name'] ?? 'RentSmart';
            $favicon = $settings['site_favicon'] ?? '';
            $siteLogoFile = $settings['site_logo'] ?? '';
            $appsLogoFile = $settings['apps_page_logo'] ?? '';
            $siteLogo = $appsLogoFile
                ? (BASE_URL . '/public/assets/images/' . $appsLogoFile)
                : ($siteLogoFile ? (BASE_URL . '/public/assets/images/' . $siteLogoFile) : (BASE_URL . '/public/assets/images/logo.svg'));

            // Check availability if dates provided
            $isAvailable = null;
            if ($checkIn && $checkOut) {
                $isAvailable = $this->bookingModel->isUnitAvailable($unitId, $checkIn, $checkOut);
            }

            require 'views/airbnb/book_unit.php';
        } catch (\Exception $e) {
            error_log($e->getMessage());
            require 'views/errors/500.php';
        }
    }

    /**
     * Process booking submission
     */
    public function submitBooking()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . BASE_URL . '/airbnb');
                exit;
            }

            $unitId = $_POST['unit_id'] ?? null;
            $checkIn = $_POST['check_in'] ?? null;
            $checkOut = $_POST['check_out'] ?? null;

            // Validate required fields
            if (!$unitId || !$checkIn || !$checkOut) {
                $_SESSION['airbnb_error'] = 'Please provide all required booking details';
                header('Location: ' . BASE_URL . '/airbnb');
                exit;
            }

            // Get unit and property
            $unit = $this->unit->find($unitId);
            if (!$unit) {
                http_response_code(404);
                require 'views/errors/404.php';
                return;
            }

            $property = $this->property->find($unit['property_id']);
            
            // Check if Airbnb is enabled
            $airbnbSettings = $this->propertyModel->getByPropertyId($property['id']);
            if (!$airbnbSettings || !$airbnbSettings['is_airbnb_enabled']) {
                $_SESSION['airbnb_error'] = 'This unit is not available for Airbnb bookings';
                header('Location: ' . BASE_URL . '/airbnb');
                exit;
            }

            // Check availability
            if (!$this->bookingModel->isUnitAvailable($unitId, $checkIn, $checkOut)) {
                $_SESSION['airbnb_error'] = 'Sorry, this unit is not available for the selected dates';
                header('Location: ' . BASE_URL . '/airbnb/book?unit_id=' . $unitId);
                exit;
            }

            // Calculate price
            $price = $this->unitRateModel->calculatePrice($unitId, $checkIn, $checkOut);
            if (!$price) {
                $_SESSION['airbnb_error'] = 'Unable to calculate booking price. Please try again.';
                header('Location: ' . BASE_URL . '/airbnb/book?unit_id=' . $unitId);
                exit;
            }

            // Get additional fees
            $cleaningFee = $airbnbSettings['cleaning_fee'] ?? 0;
            $securityDeposit = $airbnbSettings['security_deposit'] ?? 0;
            $finalTotal = $price['subtotal'] + $cleaningFee + $securityDeposit;

            // Create booking
            $bookingData = [
                'unit_id' => $unitId,
                'property_id' => $unit['property_id'],
                'guest_name' => $_POST['guest_name'] ?? '',
                'guest_email' => $_POST['guest_email'] ?? null,
                'guest_phone' => $_POST['guest_phone'] ?? '',
                'guest_count' => $_POST['guest_count'] ?? 1,
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'check_in_time' => $airbnbSettings['check_in_time'] ?? '14:00:00',
                'check_out_time' => $airbnbSettings['check_out_time'] ?? '11:00:00',
                'nights' => $price['nights'],
                'price_per_night' => ($price['base_total'] / $price['nights']),
                'total_amount' => $price['base_total'],
                'cleaning_fee' => $cleaningFee,
                'security_deposit' => $securityDeposit,
                'discount_amount' => $price['discount'],
                'tax_amount' => 0,
                'final_total' => $finalTotal,
                'status' => 'pending',
                'booking_source' => 'online',
                'payment_status' => 'pending',
                'amount_paid' => 0,
                'special_requests' => $_POST['special_requests'] ?? null
            ];

            // Validate guest info
            if (empty($bookingData['guest_name']) || empty($bookingData['guest_phone'])) {
                $_SESSION['airbnb_error'] = 'Please provide your name and phone number';
                header('Location: ' . BASE_URL . '/airbnb/book?unit_id=' . $unitId . '&check_in=' . $checkIn . '&check_out=' . $checkOut);
                exit;
            }

            $bookingId = $this->bookingModel->createBooking($bookingData);
            $booking = $this->bookingModel->findById($bookingId);

            // Redirect to confirmation page
            header('Location: ' . BASE_URL . '/airbnb/booking-confirmation/' . $booking['booking_reference']);
            exit;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $_SESSION['airbnb_error'] = 'An error occurred while processing your booking. Please try again.';
            header('Location: ' . BASE_URL . '/airbnb');
            exit;
        }
    }

    /**
     * Booking confirmation page
     */
    public function bookingConfirmation($reference)
    {
        try {
            $booking = $this->bookingModel->findByReference($reference);
            if (!$booking) {
                http_response_code(404);
                require 'views/errors/404.php';
                return;
            }

            // Get site settings
            $settings = $this->settings->getAllAsAssoc();
            $siteName = $settings['site_name'] ?? 'RentSmart';
            $favicon = $settings['site_favicon'] ?? '';
            $siteLogoFile = $settings['site_logo'] ?? '';
            $appsLogoFile = $settings['apps_page_logo'] ?? '';
            $siteLogo = $appsLogoFile
                ? (BASE_URL . '/public/assets/images/' . $appsLogoFile)
                : ($siteLogoFile ? (BASE_URL . '/public/assets/images/' . $siteLogoFile) : (BASE_URL . '/public/assets/images/logo.svg'));

            require 'views/airbnb/booking_confirmation.php';
        } catch (\Exception $e) {
            error_log($e->getMessage());
            require 'views/errors/500.php';
        }
    }

    /**
     * Download PDF Receipt
     */
    public function downloadReceipt($reference)
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        try {
            $booking = $this->bookingModel->findByReference($reference);
            if (!$booking) {
                http_response_code(404);
                echo 'Booking not found.';
                exit;
            }

            $settings = $this->settings->getAllAsAssoc();
            $siteName = $settings['site_name'] ?? 'RentSmart';
            $logoFilename = $settings['site_logo'] ?? '';
            if ($settings['apps_page_logo'] ?? '') {
                $logoFilename = $settings['apps_page_logo'];
            }
            
            $logoPath = null;
            if ($logoFilename) {
                $logoPath = __DIR__ . '/../../public/assets/images/' . $logoFilename;
            }

            if (!class_exists('Dompdf\\Dompdf')) {
                require_once __DIR__ . '/../../vendor/dompdf/dompdf/src/Dompdf.php';
            }

            ob_start();
            include __DIR__ . '/../../views/airbnb/receipt_pdf.php';
            $html = ob_get_clean();

            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $filename = 'receipt_' . $booking['booking_reference'] . '.pdf';
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $dompdf->output();
            exit;
        } catch (\Exception $e) {
            error_log('PDF Generation Error: ' . $e->getMessage());
            echo 'Error generating receipt.';
            exit;
        }
    }

    /**
     * API: Get available units for a property (public)
     */
    public function apiGetAvailableUnits()
    {
        header('Content-Type: application/json');
        
        try {
            $propertyId = $_GET['property_id'] ?? null;
            $checkIn = $_GET['check_in'] ?? null;
            $checkOut = $_GET['check_out'] ?? null;

            if (!$propertyId) {
                echo json_encode(['success' => false, 'message' => 'Property ID required']);
                return;
            }

            // Check if property has Airbnb enabled
            $airbnbSettings = $this->propertyModel->getByPropertyId($propertyId);
            if (!$airbnbSettings || !$airbnbSettings['is_airbnb_enabled']) {
                echo json_encode(['success' => false, 'message' => 'Property not available for Airbnb']);
                return;
            }

            // Get eligible units
            $stmt = $this->db->prepare("
                SELECT u.id, u.unit_number, u.type, u.rent_amount
                FROM units u
                WHERE u.property_id = ? AND u.is_airbnb_eligible = 1
            ");
            $stmt->execute([$propertyId]);
            $units = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $availableUnits = [];
            foreach ($units as $unit) {
                // If dates provided, check availability; otherwise include all eligible units
                $isAvailable = true;
                if ($checkIn && $checkOut) {
                    $isAvailable = $this->bookingModel->isUnitAvailable($unit['id'], $checkIn, $checkOut);
                }
                
                if ($isAvailable) {
                    $rates = $this->unitRateModel->getByUnitId($unit['id']);
                    $price = null;
                    if ($checkIn && $checkOut) {
                        $price = $this->unitRateModel->calculatePrice($unit['id'], $checkIn, $checkOut);
                    }
                    
                    $availableUnits[] = [
                        'id' => $unit['id'],
                        'unit_number' => $unit['unit_number'],
                        'type' => $unit['type'],
                        'base_price' => $rates['base_price_per_night'] ?? $unit['rent_amount'],
                        'total_nights' => $price ? $price['nights'] : 0,
                        'total_price' => $price ? $price['total'] : 0
                    ];
                }
            }

            echo json_encode(['success' => true, 'units' => $availableUnits]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error fetching available units']);
        }
    }

    /**
     * API: Calculate price (public)
     */
    public function apiCalculatePrice()
    {
        header('Content-Type: application/json');
        
        try {
            $unitId = $_GET['unit_id'] ?? null;
            $checkIn = $_GET['check_in'] ?? null;
            $checkOut = $_GET['check_out'] ?? null;

            if (!$unitId || !$checkIn || !$checkOut) {
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                return;
            }

            // Get unit
            $unit = $this->unit->find($unitId);
            if (!$unit || !$unit['is_airbnb_eligible']) {
                echo json_encode(['success' => false, 'message' => 'Unit not available']);
                return;
            }

            // Check property Airbnb enabled
            $airbnbSettings = $this->propertyModel->getByPropertyId($unit['property_id']);
            if (!$airbnbSettings || !$airbnbSettings['is_airbnb_enabled']) {
                echo json_encode(['success' => false, 'message' => 'Property not available']);
                return;
            }

            $price = $this->unitRateModel->calculatePrice($unitId, $checkIn, $checkOut);
            
            if (!$price) {
                echo json_encode(['success' => false, 'message' => 'Could not calculate price']);
                return;
            }

            $cleaningFee = $airbnbSettings['cleaning_fee'] ?? 0;
            $securityDeposit = $airbnbSettings['security_deposit'] ?? 0;
            $finalTotal = $price['subtotal'] + $cleaningFee + $securityDeposit;

            echo json_encode([
                'success' => true,
                'nights' => $price['nights'],
                'base_total' => $price['base_total'],
                'discount' => $price['discount'],
                'subtotal' => $price['subtotal'],
                'cleaning_fee' => $cleaningFee,
                'security_deposit' => $securityDeposit,
                'final_total' => $finalTotal
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error calculating price']);
        }
    }

    /**
     * API: Capture payment selection and generate invoice
     */
    public function capturePayment($reference)
    {
        header('Content-Type: application/json');
        
        try {
            $booking = $this->bookingModel->findByReference($reference);
            if (!$booking) {
                echo json_encode(['success' => false, 'message' => 'Booking not found']);
                return;
            }

            $method = $_POST['method'] ?? 'Pay at Office';
            $amount = (float)($booking['final_total'] ?? 0);
            
            // Get the owner/manager of the property to attribute the invoice/payment
            $propId = (int)$booking['property_id'];
            $stmt = $this->db->prepare("SELECT owner_id, manager_id, airbnb_manager_id FROM properties WHERE id = ?");
            $stmt->execute([$propId]);
            $ownerData = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            $managerUserId = $ownerData['airbnb_manager_id'] ?? $ownerData['manager_id'] ?? $ownerData['owner_id'] ?? null;

            // 1. Create Invoice
            $invModel = new \App\Models\Invoice();
            $invoiceData = [
                'tenant_id' => null, // Airbnb guests don't have tenant_id
                'issue_date' => date('Y-m-d'),
                'due_date' => date('Y-m-d'),
                'status' => 'sent',
                'notes' => 'AIRBNB_BOOKING#' . $booking['id'] . ' | Method: ' . $method,
                'user_id' => $managerUserId
            ];
            
            $items = [
                [
                    'description' => 'Airbnb Booking: ' . $booking['booking_reference'] . ' (' . $booking['nights'] . ' nights)',
                    'quantity' => 1,
                    'unit_price' => $booking['total_amount']
                ]
            ];
            
            if ($booking['cleaning_fee'] > 0) {
                $items[] = ['description' => 'Cleaning Fee', 'quantity' => 1, 'unit_price' => $booking['cleaning_fee']];
            }
            if ($booking['security_deposit'] > 0) {
                $items[] = ['description' => 'Security Deposit (Refundable)', 'quantity' => 1, 'unit_price' => $booking['security_deposit']];
            }
            if ($booking['discount_amount'] > 0) {
                $items[] = ['description' => 'Discount', 'quantity' => 1, 'unit_price' => -($booking['discount_amount'])];
            }

            $invoiceId = $invModel->createInvoice($invoiceData, $items);

            // 2. Record Payment (if Pay at Office, we might mark as pending verification, but user wants it "captured")
            // For Airbnb, we'll mark as 'paid' if it's "Pay at Office" because the manager will verify it manually 
            // but the user wants the system to capture it immediately.
            $payModel = new \App\Models\Payment();
            $paymentData = [
                'lease_id' => null,
                'amount' => $amount,
                'payment_date' => date('Y-m-d'),
                'payment_type' => 'rent',
                'payment_method' => $method,
                'reference_number' => $booking['booking_reference'],
                'status' => ($method === 'Pay at Office') ? 'pending' : 'completed', // Manager verifies office payments
                'notes' => 'Airbnb Booking Payment: ' . $booking['booking_reference'] . ' | Invoice #' . $invoiceId,
                'user_id' => $managerUserId
            ];
            
            // We need to ensure airbnb_booking_id and user_id exists in payments table
            try {
                $this->db->exec("ALTER TABLE payments ADD COLUMN airbnb_booking_id INT NULL AFTER lease_id");
                $this->db->exec("ALTER TABLE payments ADD COLUMN user_id INT NULL AFTER airbnb_booking_id");
                $this->db->exec("ALTER TABLE payments ADD INDEX idx_airbnb_booking (airbnb_booking_id)");
                $this->db->exec("ALTER TABLE payments ADD INDEX idx_payment_user (user_id)");
            } catch (\Exception $e) {}

            $sql = "INSERT INTO payments (lease_id, airbnb_booking_id, amount, payment_date, payment_type, payment_method, reference_number, status, notes, user_id) 
                    VALUES (:lease_id, :airbnb_booking_id, :amount, :payment_date, :payment_type, :payment_method, :reference_number, :status, :notes, :user_id)";
            $payStmt = $this->db->prepare($sql);
            $paymentData['airbnb_booking_id'] = $booking['id'];
            $payStmt->execute($paymentData);

            // 3. Update Booking Status
            $newStatus = ($method === 'Pay at Office') ? 'confirmed' : 'confirmed'; 
            $this->bookingModel->updateBooking($booking['id'], [
                'status' => $newStatus,
                'payment_status' => ($method === 'Pay at Office') ? 'pending' : 'paid',
                'amount_paid' => ($method === 'Pay at Office') ? 0 : $amount
            ]);

            // 4. Auto-email confirmation if email exists
            if (!empty($booking['guest_email'])) {
                $this->sendBookingConfirmationEmail($booking, $invoiceId);
            }

            echo json_encode([
                'success' => true, 
                'message' => 'Payment selection recorded successfully. Invoice generated.',
                'invoice_id' => $invoiceId
            ]);
        } catch (\Exception $e) {
            error_log('Airbnb capturePayment error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error processing payment: ' . $e->getMessage()]);
        }
    }

    /**
     * Helper: Send booking confirmation email with PDF attachment
     */
    private function sendBookingConfirmationEmail($booking, $invoiceId)
    {
        try {
            $settings = $this->settings->getAllAsAssoc();
            $siteName = $settings['site_name'] ?? 'RentSmart';
            $logoFilename = $settings['apps_page_logo'] ?? $settings['site_logo'] ?? '';
            
            $logoPath = null;
            if ($logoFilename) {
                $logoPath = __DIR__ . '/../../public/assets/images/' . $logoFilename;
            }

            // Generate PDF in memory
            if (!class_exists('Dompdf\\Dompdf')) {
                require_once __DIR__ . '/../../vendor/dompdf/dompdf/src/Dompdf.php';
            }
            ob_start();
            include __DIR__ . '/../../views/airbnb/receipt_pdf.php';
            $html = ob_get_clean();

            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdfContent = $dompdf->output();

            // Prepare Email
            require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
            require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/SMTP.php';
            require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/Exception.php';

            $mail = new PHPMailer(true);
            
            // SMTP Settings
            $mail->isSMTP();
            $mail->Host = $settings['smtp_host'] ?? '';
            $mail->SMTPAuth = true;
            $mail->Username = $settings['smtp_user'] ?? '';
            $mail->Password = $settings['smtp_pass'] ?? '';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $settings['smtp_port'] ?? 587;
            
            $fromEmail = $settings['smtp_user'] ?? $settings['site_email'] ?? 'no-reply@rentsmart.co.ke';
            $mail->setFrom($fromEmail, $siteName);
            $mail->addAddress($booking['guest_email'], $booking['guest_name']);
            
            $mail->isHTML(true);
            $mail->Subject = "Booking Confirmation: " . $booking['property_name'] . " (" . $booking['booking_reference'] . ")";
            
            $body = "<h2>Hello " . htmlspecialchars($booking['guest_name']) . ",</h2>";
            $body .= "<p>Your booking at <strong>" . htmlspecialchars($booking['property_name']) . "</strong> has been confirmed.</p>";
            $body .= "<p><strong>Reference:</strong> " . htmlspecialchars($booking['booking_reference']) . "<br>";
            $body .= "<strong>Dates:</strong> " . date('M d, Y', strtotime($booking['check_in_date'])) . " to " . date('M d, Y', strtotime($booking['check_out_date'])) . "</p>";
            $body .= "<p>Please find your booking receipt attached to this email.</p>";
            $body .= "<p>We look forward to hosting you!</p>";
            $body .= "<br><p>Best regards,<br>" . htmlspecialchars($siteName) . " Team</p>";
            
            $mail->Body = $body;
            $mail->addStringAttachment($pdfContent, 'BookingReceipt_' . $booking['booking_reference'] . '.pdf');
            
            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log('Mailing Error: ' . $e->getMessage());
            return false;
        }
    }
}
