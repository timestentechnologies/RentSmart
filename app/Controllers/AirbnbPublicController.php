<?php

namespace App\Controllers;

use App\Database\Connection;
use App\Models\AirbnbBooking;
use App\Models\AirbnbProperty;
use App\Models\AirbnbUnitRate;
use App\Models\Property;
use App\Models\Unit;
use App\Models\Setting;

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
            $siteLogo = isset($settings['site_logo']) && $settings['site_logo']
                ? BASE_URL . '/public/assets/images/' . $settings['site_logo']
                : BASE_URL . '/public/assets/images/logo.png';

            // Get all Airbnb-enabled properties with their units
            $airbnbProperties = $this->getAvailableAirbnbListings();

            require 'views/airbnb/public_listing.php';
        } catch (\Exception $e) {
            error_log($e->getMessage());
            require 'views/errors/500.php';
        }
    }

    /**
     * Get available Airbnb listings
     */
    private function getAvailableAirbnbListings()
    {
        $stmt = $this->db->query("
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
            )
            ORDER BY p.name, u.unit_number
        ");
        
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
            $siteLogo = isset($settings['site_logo']) && $settings['site_logo']
                ? BASE_URL . '/public/assets/images/' . $settings['site_logo']
                : BASE_URL . '/public/assets/images/logo.png';

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
            $siteLogo = isset($settings['site_logo']) && $settings['site_logo']
                ? BASE_URL . '/public/assets/images/' . $settings['site_logo']
                : BASE_URL . '/public/assets/images/logo.png';

            require 'views/airbnb/booking_confirmation.php';
        } catch (\Exception $e) {
            error_log($e->getMessage());
            require 'views/errors/500.php';
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

            if (!$propertyId || !$checkIn || !$checkOut) {
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
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
                $isAvailable = $this->bookingModel->isUnitAvailable($unit['id'], $checkIn, $checkOut);
                
                if ($isAvailable) {
                    $rates = $this->unitRateModel->getByUnitId($unit['id']);
                    $price = $this->unitRateModel->calculatePrice($unit['id'], $checkIn, $checkOut);
                    
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
     * API: Check availability (public)
     */
    public function apiCheckAvailability()
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
                echo json_encode(['success' => false, 'available' => false, 'message' => 'Unit not found']);
                return;
            }

            // Check property Airbnb enabled
            $airbnbSettings = $this->propertyModel->getByPropertyId($unit['property_id']);
            if (!$airbnbSettings || !$airbnbSettings['is_airbnb_enabled']) {
                echo json_encode(['success' => false, 'available' => false, 'message' => 'Property not available']);
                return;
            }

            $isAvailable = $this->bookingModel->isUnitAvailable($unitId, $checkIn, $checkOut);

            echo json_encode([
                'success' => true,
                'available' => $isAvailable
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error checking availability']);
        }
    }
}
