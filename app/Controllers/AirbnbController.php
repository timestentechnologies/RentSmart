<?php

namespace App\Controllers;

use App\Database\Connection;
use App\Models\AirbnbBooking;
use App\Models\AirbnbProperty;
use App\Models\AirbnbUnitRate;
use App\Models\AirbnbWalkinGuest;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;

class AirbnbController
{
    private $db;
    private $bookingModel;
    private $propertyModel;
    private $unitRateModel;
    private $walkinModel;
    private $property;
    private $unit;
    private $user;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
        $this->bookingModel = new AirbnbBooking();
        $this->propertyModel = new AirbnbProperty();
        $this->unitRateModel = new AirbnbUnitRate();
        $this->walkinModel = new AirbnbWalkinGuest();
        $this->property = new Property();
        $this->unit = new Unit();
        $this->user = new User();
    }

    /**
     * Check if user has access to Airbnb features
     */
    private function checkAirbnbAccess()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $userRole = $_SESSION['user_role'] ?? '';
        
        $allowedRoles = ['admin', 'administrator', 'landlord', 'manager', 'airbnb_manager', 'caretaker'];
        if (!in_array(strtolower($userRole), $allowedRoles)) {
            http_response_code(403);
            require 'views/errors/403.php';
            exit;
        }

        return ['userId' => $userId, 'userRole' => $userRole];
    }

    /**
     * Get accessible property IDs for current user
     */
    private function getAccessiblePropertyIds()
    {
        $auth = $this->checkAirbnbAccess();
        $this->user->find($auth['userId']);
        return $this->user->getAccessiblePropertyIds();
    }

    /**
     * Dashboard - Overview of Airbnb operations
     */
    public function dashboard()
    {
        try {
            $auth = $this->checkAirbnbAccess();
            $propertyIds = $this->getAccessiblePropertyIds();

            // Get stats
            $stats = $this->bookingModel->getBookingStats($propertyIds);
            $walkinStats = $this->walkinModel->getStats($propertyIds);

            // Get upcoming check-ins and check-outs
            $upcomingCheckIns = $this->bookingModel->getUpcomingCheckIns($propertyIds, 10);
            $upcomingCheckOuts = $this->bookingModel->getUpcomingCheckOuts($propertyIds, 10);

            // Get recent bookings
            $recentBookings = $this->bookingModel->getBookingsForUser($auth['userId'], $auth['userRole']);
            $recentBookings = array_slice($recentBookings, 0, 10);

            // Get pending walk-in guests
            $walkinGuests = $this->walkinModel->getWalkinGuestsForUser($auth['userId'], $auth['userRole']);
            $pendingWalkins = array_filter($walkinGuests, function($g) {
                return in_array($g['status'], ['inquiry', 'offered']);
            });
            $pendingWalkins = array_slice($pendingWalkins, 0, 10);

            // Get occupancy data for current month
            $occupancyData = $this->bookingModel->getOccupancyData($propertyIds);

            // Get properties with Airbnb enabled
            $airbnbProperties = $this->propertyModel->getAirbnbEnabledProperties($auth['userId'], $auth['userRole']);

            require 'views/airbnb/dashboard.php';
        } catch (\Exception $e) {
            error_log($e->getMessage());
            require 'views/errors/500.php';
        }
    }

    /**
     * List all bookings
     */
    public function bookings()
    {
        try {
            $auth = $this->checkAirbnbAccess();
            
            $filters = [
                'status' => $_GET['status'] ?? null,
                'check_in_from' => $_GET['check_in_from'] ?? null,
                'check_in_to' => $_GET['check_in_to'] ?? null,
                'guest_name' => $_GET['guest_name'] ?? null,
                'property_id' => $_GET['property_id'] ?? null,
                'unit_id' => $_GET['unit_id'] ?? null
            ];
            
            // Remove empty filters
            $filters = array_filter($filters);

            // Get bookings for user
            $bookings = $this->bookingModel->getBookingsForUser($auth['userId'], $auth['userRole']);
            
            // Apply additional filters
            if (!empty($filters)) {
                $bookings = array_filter($bookings, function($b) use ($filters) {
                    if (!empty($filters['status']) && $b['status'] !== $filters['status']) {
                        return false;
                    }
                    if (!empty($filters['guest_name']) && stripos($b['guest_name'], $filters['guest_name']) === false) {
                        return false;
                    }
                    return true;
                });
            }

            // Get properties for filter dropdown
            $propertyIds = $this->getAccessiblePropertyIds();
            $properties = [];
            foreach ($propertyIds as $pid) {
                $p = $this->property->find($pid);
                if ($p) {
                    $properties[] = $p;
                }
            }

            require 'views/airbnb/bookings.php';
        } catch (\Exception $e) {
            error_log($e->getMessage());
            require 'views/errors/500.php';
        }
    }

    /**
     * Show booking details
     */
    public function bookingDetails($id)
    {
        try {
            $auth = $this->checkAirbnbAccess();
            
            $booking = $this->bookingModel->findById($id);
            if (!$booking) {
                http_response_code(404);
                require 'views/errors/404.php';
                return;
            }

            // Check access
            $propertyIds = $this->getAccessiblePropertyIds();
            if (!in_array($booking['property_id'], $propertyIds)) {
                http_response_code(403);
                require 'views/errors/403.php';
                return;
            }

            // Get payments for this booking
            $payments = $this->getBookingPayments($id);

            require 'views/airbnb/booking_details.php';
        } catch (\Exception $e) {
            error_log($e->getMessage());
            require 'views/errors/500.php';
        }
    }

    /**
     * Create new booking form
     */
    public function createBooking()
    {
        try {
            $auth = $this->checkAirbnbAccess();
            
            $propertyIds = $this->getAccessiblePropertyIds();
            $properties = [];
            foreach ($propertyIds as $pid) {
                $p = $this->property->find($pid);
                if ($p) {
                    $properties[] = $p;
                }
            }

            $preselectedUnitId = $_GET['unit_id'] ?? null;
            $preselectedPropertyId = $_GET['property_id'] ?? null;

            require 'views/airbnb/create_booking.php';
        } catch (\Exception $e) {
            error_log($e->getMessage());
            require 'views/errors/500.php';
        }
    }

    /**
     * Store new booking
     */
    public function storeBooking()
    {
        try {
            $auth = $this->checkAirbnbAccess();
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . BASE_URL . '/airbnb/bookings');
                exit;
            }

            $data = [
                'unit_id' => $_POST['unit_id'] ?? null,
                'property_id' => $_POST['property_id'] ?? null,
                'guest_name' => $_POST['guest_name'] ?? '',
                'guest_email' => $_POST['guest_email'] ?? null,
                'guest_phone' => $_POST['guest_phone'] ?? '',
                'guest_count' => $_POST['guest_count'] ?? 1,
                'check_in_date' => $_POST['check_in_date'] ?? '',
                'check_out_date' => $_POST['check_out_date'] ?? '',
                'check_in_time' => $_POST['check_in_time'] ?? null,
                'check_out_time' => $_POST['check_out_time'] ?? null,
                'nights' => $_POST['nights'] ?? 0,
                'price_per_night' => $_POST['price_per_night'] ?? 0,
                'total_amount' => $_POST['total_amount'] ?? 0,
                'cleaning_fee' => $_POST['cleaning_fee'] ?? 0,
                'security_deposit' => $_POST['security_deposit'] ?? 0,
                'discount_amount' => $_POST['discount_amount'] ?? 0,
                'tax_amount' => $_POST['tax_amount'] ?? 0,
                'final_total' => $_POST['final_total'] ?? 0,
                'status' => $_POST['status'] ?? 'confirmed',
                'booking_source' => $_POST['booking_source'] ?? 'walk_in',
                'payment_status' => $_POST['payment_status'] ?? 'pending',
                'amount_paid' => $_POST['amount_paid'] ?? 0,
                'special_requests' => $_POST['special_requests'] ?? null,
                'internal_notes' => $_POST['internal_notes'] ?? null,
                'booked_by_user_id' => $auth['userId']
            ];

            // Validate required fields
            if (empty($data['unit_id']) || empty($data['guest_name']) || empty($data['guest_phone']) || 
                empty($data['check_in_date']) || empty($data['check_out_date'])) {
                $_SESSION['error'] = 'Please fill in all required fields';
                header('Location: ' . BASE_URL . '/airbnb/bookings/create');
                exit;
            }

            // Check unit availability
            if (!$this->bookingModel->isUnitAvailable($data['unit_id'], $data['check_in_date'], $data['check_out_date'])) {
                $_SESSION['error'] = 'Unit is not available for the selected dates';
                header('Location: ' . BASE_URL . '/airbnb/bookings/create');
                exit;
            }

            // Create booking
            $bookingId = $this->bookingModel->createBooking($data);

            // Record initial payment if amount paid
            if ($data['amount_paid'] > 0) {
                $this->recordPayment($bookingId, [
                    'amount' => $data['amount_paid'],
                    'payment_method' => $_POST['payment_method'] ?? 'cash',
                    'notes' => 'Initial payment at booking'
                ], $auth['userId']);
            }

            $_SESSION['success'] = 'Booking created successfully. Reference: ' . $this->bookingModel->findById($bookingId)['booking_reference'];
            header('Location: ' . BASE_URL . '/airbnb/bookings/' . $bookingId);
            exit;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $_SESSION['error'] = 'Failed to create booking: ' . $e->getMessage();
            header('Location: ' . BASE_URL . '/airbnb/bookings/create');
            exit;
        }
    }

    /**
     * Update booking
     */
    public function updateBooking($id)
    {
        try {
            $auth = $this->checkAirbnbAccess();
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . BASE_URL . '/airbnb/bookings/' . $id);
                exit;
            }

            $booking = $this->bookingModel->findById($id);
            if (!$booking) {
                http_response_code(404);
                require 'views/errors/404.php';
                return;
            }

            // Check access
            $propertyIds = $this->getAccessiblePropertyIds();
            if (!in_array($booking['property_id'], $propertyIds)) {
                http_response_code(403);
                require 'views/errors/403.php';
                return;
            }

            $data = [
                'guest_name' => $_POST['guest_name'] ?? $booking['guest_name'],
                'guest_email' => $_POST['guest_email'] ?? $booking['guest_email'],
                'guest_phone' => $_POST['guest_phone'] ?? $booking['guest_phone'],
                'guest_count' => $_POST['guest_count'] ?? $booking['guest_count'],
                'check_in_date' => $_POST['check_in_date'] ?? $booking['check_in_date'],
                'check_out_date' => $_POST['check_out_date'] ?? $booking['check_out_date'],
                'check_in_time' => $_POST['check_in_time'] ?? $booking['check_in_time'],
                'check_out_time' => $_POST['check_out_time'] ?? $booking['check_out_time'],
                'nights' => $_POST['nights'] ?? $booking['nights'],
                'price_per_night' => $_POST['price_per_night'] ?? $booking['price_per_night'],
                'total_amount' => $_POST['total_amount'] ?? $booking['total_amount'],
                'cleaning_fee' => $_POST['cleaning_fee'] ?? $booking['cleaning_fee'],
                'security_deposit' => $_POST['security_deposit'] ?? $booking['security_deposit'],
                'discount_amount' => $_POST['discount_amount'] ?? $booking['discount_amount'],
                'tax_amount' => $_POST['tax_amount'] ?? $booking['tax_amount'],
                'final_total' => $_POST['final_total'] ?? $booking['final_total'],
                'status' => $_POST['status'] ?? $booking['status'],
                'payment_status' => $_POST['payment_status'] ?? $booking['payment_status'],
                'special_requests' => $_POST['special_requests'] ?? $booking['special_requests'],
                'internal_notes' => $_POST['internal_notes'] ?? $booking['internal_notes']
            ];

            $this->bookingModel->updateBooking($id, $data);

            $_SESSION['success'] = 'Booking updated successfully';
            header('Location: ' . BASE_URL . '/airbnb/bookings/' . $id);
            exit;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $_SESSION['error'] = 'Failed to update booking';
            header('Location: ' . BASE_URL . '/airbnb/bookings/' . $id);
            exit;
        }
    }

    /**
     * Check in guest
     */
    public function checkIn($id)
    {
        try {
            $auth = $this->checkAirbnbAccess();
            
            $booking = $this->bookingModel->findById($id);
            if (!$booking) {
                http_response_code(404);
                require 'views/errors/404.php';
                return;
            }

            // Check access
            $propertyIds = $this->getAccessiblePropertyIds();
            if (!in_array($booking['property_id'], $propertyIds)) {
                http_response_code(403);
                require 'views/errors/403.php';
                return;
            }

            $this->bookingModel->checkIn($id);

            $_SESSION['success'] = 'Guest checked in successfully';
            header('Location: ' . BASE_URL . '/airbnb/bookings/' . $id);
            exit;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $_SESSION['error'] = 'Failed to check in guest';
            header('Location: ' . BASE_URL . '/airbnb/bookings/' . $id);
            exit;
        }
    }

    /**
     * Check out guest
     */
    public function checkOut($id)
    {
        try {
            $auth = $this->checkAirbnbAccess();
            
            $booking = $this->bookingModel->findById($id);
            if (!$booking) {
                http_response_code(404);
                require 'views/errors/404.php';
                return;
            }

            // Check access
            $propertyIds = $this->getAccessiblePropertyIds();
            if (!in_array($booking['property_id'], $propertyIds)) {
                http_response_code(403);
                require 'views/errors/403.php';
                return;
            }

            $this->bookingModel->checkOut($id);

            $_SESSION['success'] = 'Guest checked out successfully';
            header('Location: ' . BASE_URL . '/airbnb/bookings/' . $id);
            exit;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $_SESSION['error'] = 'Failed to check out guest';
            header('Location: ' . BASE_URL . '/airbnb/bookings/' . $id);
            exit;
        }
    }

    /**
     * Cancel booking
     */
    public function cancelBooking($id)
    {
        try {
            $auth = $this->checkAirbnbAccess();
            
            $booking = $this->bookingModel->findById($id);
            if (!$booking) {
                http_response_code(404);
                require 'views/errors/404.php';
                return;
            }

            // Check access
            $propertyIds = $this->getAccessiblePropertyIds();
            if (!in_array($booking['property_id'], $propertyIds)) {
                http_response_code(403);
                require 'views/errors/403.php';
                return;
            }

            $this->bookingModel->updateStatus($id, 'cancelled');

            $_SESSION['success'] = 'Booking cancelled successfully';
            header('Location: ' . BASE_URL . '/airbnb/bookings');
            exit;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $_SESSION['error'] = 'Failed to cancel booking';
            header('Location: ' . BASE_URL . '/airbnb/bookings/' . $id);
            exit;
        }
    }

    /**
     * Record payment for booking
     */
    private function recordPayment($bookingId, $data, $userId)
    {
        $sql = "INSERT INTO airbnb_booking_payments (
            booking_id, amount, payment_method, transaction_reference, 
            payment_date, notes, recorded_by_user_id
        ) VALUES (?, ?, ?, ?, NOW(), ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $bookingId,
            $data['amount'],
            $data['payment_method'],
            $data['transaction_reference'] ?? null,
            $data['notes'] ?? null,
            $userId
        ]);
    }

    /**
     * Get payments for a booking
     */
    private function getBookingPayments($bookingId)
    {
        $stmt = $this->db->prepare("SELECT * FROM airbnb_booking_payments WHERE booking_id = ? ORDER BY payment_date DESC");
        $stmt->execute([$bookingId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Add payment to booking
     */
    public function addPayment($bookingId)
    {
        try {
            $auth = $this->checkAirbnbAccess();
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . BASE_URL . '/airbnb/bookings/' . $bookingId);
                exit;
            }

            $booking = $this->bookingModel->findById($bookingId);
            if (!$booking) {
                http_response_code(404);
                require 'views/errors/404.php';
                return;
            }

            // Check access
            $propertyIds = $this->getAccessiblePropertyIds();
            if (!in_array($booking['property_id'], $propertyIds)) {
                http_response_code(403);
                require 'views/errors/403.php';
                return;
            }

            $amount = (float)($_POST['amount'] ?? 0);
            $paymentMethod = $_POST['payment_method'] ?? 'cash';
            $notes = $_POST['notes'] ?? '';
            $transactionReference = $_POST['transaction_reference'] ?? null;

            if ($amount <= 0) {
                $_SESSION['error'] = 'Invalid payment amount';
                header('Location: ' . BASE_URL . '/airbnb/bookings/' . $bookingId);
                exit;
            }

            $this->recordPayment($bookingId, [
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'transaction_reference' => $transactionReference,
                'notes' => $notes
            ], $auth['userId']);

            // Update booking payment status
            $newAmountPaid = $booking['amount_paid'] + $amount;
            $paymentStatus = 'partial';
            if ($newAmountPaid >= $booking['final_total']) {
                $paymentStatus = 'paid';
            }

            $this->bookingModel->updateBooking($bookingId, [
                'amount_paid' => $newAmountPaid,
                'payment_status' => $paymentStatus
            ]);

            $_SESSION['success'] = 'Payment recorded successfully';
            header('Location: ' . BASE_URL . '/airbnb/bookings/' . $bookingId);
            exit;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $_SESSION['error'] = 'Failed to record payment';
            header('Location: ' . BASE_URL . '/airbnb/bookings/' . $bookingId);
            exit;
        }
    }

    /**
     * Walk-in guests management
     */
    public function walkinGuests()
    {
        try {
            $auth = $this->checkAirbnbAccess();
            
            $filters = [
                'status' => $_GET['status'] ?? null,
                'property_id' => $_GET['property_id'] ?? null
            ];
            $filters = array_filter($filters);

            $guests = $this->walkinModel->getWalkinGuestsForUser($auth['userId'], $auth['userRole']);
            
            if (!empty($filters)) {
                $guests = array_filter($guests, function($g) use ($filters) {
                    if (!empty($filters['status']) && $g['status'] !== $filters['status']) {
                        return false;
                    }
                    return true;
                });
            }

            // Get properties for filter
            $propertyIds = $this->getAccessiblePropertyIds();
            $properties = [];
            foreach ($propertyIds as $pid) {
                $p = $this->property->find($pid);
                if ($p) {
                    $properties[] = $p;
                }
            }

            require 'views/airbnb/walkin_guests.php';
        } catch (\Exception $e) {
            error_log($e->getMessage());
            require 'views/errors/500.php';
        }
    }

    /**
     * Create walk-in guest
     */
    public function createWalkinGuest()
    {
        try {
            $auth = $this->checkAirbnbAccess();
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = [
                    'property_id' => $_POST['property_id'] ?? null,
                    'guest_name' => $_POST['guest_name'] ?? '',
                    'guest_phone' => $_POST['guest_phone'] ?? '',
                    'guest_email' => $_POST['guest_email'] ?? null,
                    'guest_count' => $_POST['guest_count'] ?? 1,
                    'preferred_check_in' => $_POST['preferred_check_in'] ?? null,
                    'preferred_check_out' => $_POST['preferred_check_out'] ?? null,
                    'budget_range' => $_POST['budget_range'] ?? null,
                    'requirements' => $_POST['requirements'] ?? null,
                    'assigned_unit_id' => $_POST['assigned_unit_id'] ?? null,
                    'status' => 'inquiry',
                    'follow_up_date' => $_POST['follow_up_date'] ?? null,
                    'notes' => $_POST['notes'] ?? null,
                    'handled_by_user_id' => $auth['userId']
                ];

                if (empty($data['property_id']) || empty($data['guest_name']) || empty($data['guest_phone'])) {
                    $_SESSION['error'] = 'Please fill in all required fields';
                    header('Location: ' . BASE_URL . '/airbnb/walkin-guests/create');
                    exit;
                }

                $guestId = $this->walkinModel->create($data);

                $_SESSION['success'] = 'Walk-in guest inquiry recorded successfully';
                header('Location: ' . BASE_URL . '/airbnb/walkin-guests');
                exit;
            }

            // Get properties for dropdown
            $propertyIds = $this->getAccessiblePropertyIds();
            $properties = [];
            foreach ($propertyIds as $pid) {
                $p = $this->property->find($pid);
                if ($p) {
                    $properties[] = $p;
                }
            }

            require 'views/airbnb/create_walkin.php';
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $_SESSION['error'] = 'Failed to create walk-in guest inquiry';
            header('Location: ' . BASE_URL . '/airbnb/walkin-guests');
            exit;
        }
    }

    /**
     * Update walk-in guest status
     */
    public function updateWalkinGuest($id)
    {
        try {
            $auth = $this->checkAirbnbAccess();
            
            $guest = $this->walkinModel->findById($id);
            if (!$guest) {
                http_response_code(404);
                require 'views/errors/404.php';
                return;
            }

            // Check access
            $propertyIds = $this->getAccessiblePropertyIds();
            if (!in_array($guest['property_id'], $propertyIds)) {
                http_response_code(403);
                require 'views/errors/403.php';
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = [
                    'guest_name' => $_POST['guest_name'] ?? $guest['guest_name'],
                    'guest_phone' => $_POST['guest_phone'] ?? $guest['guest_phone'],
                    'guest_email' => $_POST['guest_email'] ?? $guest['guest_email'],
                    'guest_count' => $_POST['guest_count'] ?? $guest['guest_count'],
                    'preferred_check_in' => $_POST['preferred_check_in'] ?? $guest['preferred_check_in'],
                    'preferred_check_out' => $_POST['preferred_check_out'] ?? $guest['preferred_check_out'],
                    'budget_range' => $_POST['budget_range'] ?? $guest['budget_range'],
                    'requirements' => $_POST['requirements'] ?? $guest['requirements'],
                    'assigned_unit_id' => $_POST['assigned_unit_id'] ?? $guest['assigned_unit_id'],
                    'status' => $_POST['status'] ?? $guest['status'],
                    'follow_up_date' => $_POST['follow_up_date'] ?? $guest['follow_up_date'],
                    'notes' => $_POST['notes'] ?? $guest['notes']
                ];

                $this->walkinModel->updateWalkinGuest($id, $data);

                $_SESSION['success'] = 'Walk-in guest updated successfully';
                header('Location: ' . BASE_URL . '/airbnb/walkin-guests');
                exit;
            }

            // Get properties for dropdown
            $propertyIds = $this->getAccessiblePropertyIds();
            $properties = [];
            foreach ($propertyIds as $pid) {
                $p = $this->property->find($pid);
                if ($p) {
                    $properties[] = $p;
                }
            }

            require 'views/airbnb/edit_walkin.php';
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $_SESSION['error'] = 'Failed to update walk-in guest';
            header('Location: ' . BASE_URL . '/airbnb/walkin-guests');
            exit;
        }
    }

    /**
     * Convert walk-in guest to booking
     */
    public function convertWalkinToBooking($walkinId)
    {
        try {
            $auth = $this->checkAirbnbAccess();
            
            $guest = $this->walkinModel->findById($walkinId);
            if (!$guest) {
                http_response_code(404);
                require 'views/errors/404.php';
                return;
            }

            // Check access
            $propertyIds = $this->getAccessiblePropertyIds();
            if (!in_array($guest['property_id'], $propertyIds)) {
                http_response_code(403);
                require 'views/errors/403.php';
                return;
            }

            // Get properties and units for form
            $properties = [];
            foreach ($propertyIds as $pid) {
                $p = $this->property->find($pid);
                if ($p) {
                    $properties[] = $p;
                }
            }

            $preselectedGuest = $guest;
            $preselectedUnitId = $guest['assigned_unit_id'];
            $preselectedPropertyId = $guest['property_id'];

            require 'views/airbnb/convert_walkin.php';
        } catch (\Exception $e) {
            error_log($e->getMessage());
            require 'views/errors/500.php';
        }
    }

    /**
     * Property Airbnb settings
     */
    public function propertySettings($propertyId = null)
    {
        try {
            $auth = $this->checkAirbnbAccess();
            
            $propertyIds = $this->getAccessiblePropertyIds();
            
            // If no property ID specified, show list
            if (!$propertyId) {
                $properties = [];
                foreach ($propertyIds as $pid) {
                    $p = $this->property->find($pid);
                    if ($p) {
                        $p['airbnb_settings'] = $this->propertyModel->getByPropertyId($pid);
                        $properties[] = $p;
                    }
                }
                require 'views/airbnb/property_settings_list.php';
                return;
            }

            // Check access to this specific property
            if (!in_array($propertyId, $propertyIds)) {
                http_response_code(403);
                require 'views/errors/403.php';
                return;
            }

            $property = $this->property->find($propertyId);
            if (!$property) {
                http_response_code(404);
                require 'views/errors/404.php';
                return;
            }

            $settings = $this->propertyModel->getByPropertyId($propertyId);

            // Get units for this property
            $units = $this->unit->getByPropertyId($propertyId);
            foreach ($units as &$u) {
                $u['airbnb_rates'] = $this->unitRateModel->getByUnitId($u['id']);
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $settingsData = [
                    'is_airbnb_enabled' => isset($_POST['is_airbnb_enabled']) ? 1 : 0,
                    'min_stay_nights' => $_POST['min_stay_nights'] ?? 1,
                    'max_stay_nights' => $_POST['max_stay_nights'] ?? 30,
                    'check_in_time' => $_POST['check_in_time'] ?? '14:00:00',
                    'check_out_time' => $_POST['check_out_time'] ?? '11:00:00',
                    'cleaning_fee' => $_POST['cleaning_fee'] ?? 0.00,
                    'security_deposit' => $_POST['security_deposit'] ?? 0.00,
                    'booking_lead_time_hours' => $_POST['booking_lead_time_hours'] ?? 24,
                    'instant_booking' => isset($_POST['instant_booking']) ? 1 : 0,
                    'house_rules' => $_POST['house_rules'] ?? null,
                    'cancellation_policy' => $_POST['cancellation_policy'] ?? 'moderate'
                ];

                $this->propertyModel->createOrUpdate($propertyId, $settingsData);

                // Update unit rates and Airbnb eligibility
                if (!empty($_POST['unit_rates'])) {
                    foreach ($_POST['unit_rates'] as $unitId => $rateData) {
                        $rateInfo = [
                            'base_price_per_night' => $rateData['base_price'] ?? 0,
                            'weekend_price' => $rateData['weekend_price'] ?? null,
                            'weekly_discount_percent' => $rateData['weekly_discount'] ?? 0,
                            'monthly_discount_percent' => $rateData['monthly_discount'] ?? 0
                        ];
                        $this->unitRateModel->createOrUpdate($unitId, $rateInfo);

                        // Update unit eligibility
                        $isEligible = isset($_POST['unit_eligible'][$unitId]) ? 1 : 0;
                        $this->db->prepare("UPDATE units SET is_airbnb_eligible = ? WHERE id = ?")
                            ->execute([$isEligible, $unitId]);
                    }
                }

                $_SESSION['success'] = 'Property Airbnb settings updated successfully';
                header('Location: ' . BASE_URL . '/airbnb/property-settings/' . $propertyId);
                exit;
            }

            require 'views/airbnb/property_settings.php';
        } catch (\Exception $e) {
            error_log($e->getMessage());
            require 'views/errors/500.php';
        }
    }

    /**
     * API: Get available units for a property
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

            // Get units for property
            $units = $this->unit->getByPropertyId($propertyId);
            
            $availableUnits = [];
            foreach ($units as $unit) {
                // Skip if not Airbnb eligible
                if (empty($unit['is_airbnb_eligible'])) {
                    continue;
                }

                // Check availability
                $isAvailable = $this->bookingModel->isUnitAvailable($unit['id'], $checkIn, $checkOut);
                
                if ($isAvailable) {
                    // Get rates
                    $rates = $this->unitRateModel->getByUnitId($unit['id']);
                    $price = $this->unitRateModel->calculatePrice($unit['id'], $checkIn, $checkOut);
                    
                    $availableUnits[] = [
                        'id' => $unit['id'],
                        'unit_number' => $unit['unit_number'],
                        'type' => $unit['type'],
                        'base_price' => $rates['base_price_per_night'] ?? $unit['rent_amount'],
                        'calculated_price' => $price
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
     * API: Calculate booking price
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

            $price = $this->unitRateModel->calculatePrice($unitId, $checkIn, $checkOut);
            
            if (!$price) {
                echo json_encode(['success' => false, 'message' => 'Could not calculate price']);
                return;
            }

            // Get property settings for additional fees
            $unit = $this->unit->find($unitId);
            $propertySettings = $this->propertyModel->getByPropertyId($unit['property_id']);
            
            $cleaningFee = $propertySettings['cleaning_fee'] ?? 0;
            $securityDeposit = $propertySettings['security_deposit'] ?? 0;

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
     * API: Check unit availability
     */
    public function apiCheckAvailability()
    {
        header('Content-Type: application/json');
        
        try {
            $unitId = $_GET['unit_id'] ?? null;
            $checkIn = $_GET['check_in'] ?? null;
            $checkOut = $_GET['check_out'] ?? null;
            $excludeBookingId = $_GET['exclude_booking_id'] ?? null;

            if (!$unitId || !$checkIn || !$checkOut) {
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                return;
            }

            $isAvailable = $this->bookingModel->isUnitAvailable($unitId, $checkIn, $checkOut, $excludeBookingId);

            echo json_encode([
                'success' => true,
                'available' => $isAvailable
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error checking availability']);
        }
    }

    /**
     * API: Get unit calendar/occupancy
     */
    public function apiGetUnitCalendar()
    {
        header('Content-Type: application/json');
        
        try {
            $unitId = $_GET['unit_id'] ?? null;
            $month = $_GET['month'] ?? date('m');
            $year = $_GET['year'] ?? date('Y');

            if (!$unitId) {
                echo json_encode(['success' => false, 'message' => 'Missing unit_id']);
                return;
            }

            $startDate = "$year-$month-01";
            $endDate = date('Y-m-t', strtotime($startDate));

            // Get bookings for this unit in the month
            $bookings = $this->bookingModel->getAllBookings([
                'unit_id' => $unitId,
                'check_in_from' => $startDate,
                'check_in_to' => $endDate
            ]);

            $events = [];
            foreach ($bookings as $booking) {
                $events[] = [
                    'id' => $booking['id'],
                    'title' => $booking['guest_name'],
                    'start' => $booking['check_in_date'],
                    'end' => $booking['check_out_date'],
                    'status' => $booking['status']
                ];
            }

            echo json_encode(['success' => true, 'events' => $events]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error fetching calendar']);
        }
    }
}
