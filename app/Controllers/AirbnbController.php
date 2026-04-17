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
use App\Models\Invoice;
use App\Models\Payment;

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
        $this->invoiceModel = new Invoice();
        $this->paymentModel = new Payment();
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
            $booking = $this->bookingModel->findById($bookingId);

            // AUTO-CREATE INVOICE
            $this->invoiceModel->createFromAirbnbBooking($booking, $auth['userId']);

            // Record initial payment if amount paid
            if ($data['amount_paid'] > 0) {
                $this->recordPayment($bookingId, [
                    'amount' => $data['amount_paid'],
                    'payment_method' => $_POST['payment_method'] ?? 'cash',
                    'notes' => 'Initial payment at booking'
                ], $auth['userId']);
            }

            // Update walk-in guest status if converted
            $walkinGuestId = $_POST['walkin_guest_id'] ?? null;
            if ($walkinGuestId) {
                $this->walkinModel->updateStatus($walkinGuestId, 'converted', $bookingId);
            }

            $_SESSION['success'] = 'Booking created successfully. Reference: ' . $booking['booking_reference'];
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
        $booking = $this->bookingModel->findById($bookingId);
        if (!$booking) return false;

        // Use central payments table
        $sql = "INSERT INTO payments (
            airbnb_booking_id, amount, payment_method, reference_number, 
            payment_date, payment_type, status, notes, user_id
        ) VALUES (?, ?, ?, ?, CURDATE(), 'airbnb_booking', 'completed', ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $bookingId,
            $data['amount'],
            $data['payment_method'],
            $data['transaction_reference'] ?? $booking['booking_reference'],
            $data['notes'] ?? 'Airbnb Payment',
            $userId
        ]);
    }

    /**
     * Get payments for a booking
     */
    private function getBookingPayments($bookingId)
    {
        $stmt = $this->db->prepare("SELECT * FROM payments WHERE airbnb_booking_id = ? ORDER BY payment_date DESC");
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
                $guest = $this->walkinModel->findById($guestId);

                // AUTO-CREATE DRAFT INVOICE
                $this->invoiceModel->createFromAirbnbWalkin($guest, $auth['userId']);

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
                    'allow_office_payments' => isset($_POST['allow_office_payments']) ? 1 : 0,
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

    /**
     * List all Airbnb-eligible units with their rates and availability
     */
    public function units()
    {
        try {
            $auth = $this->checkAirbnbAccess();
            $propertyIds = $this->getAccessiblePropertyIds();

            // Get properties for filter
            $properties = [];
            foreach ($propertyIds as $pid) {
                $p = $this->property->find($pid);
                if ($p) {
                    $properties[] = $p;
                }
            }

            // Get all units for accessible properties
            $allUnits = [];
            foreach ($propertyIds as $propertyId) {
                $units = $this->unit->getByPropertyId($propertyId);
                foreach ($units as $unit) {
                    $unit['property'] = $this->property->find($propertyId);
                    $unit['airbnb_rates'] = $this->unitRateModel->getByUnitId($unit['id']);
                    $unit['property_settings'] = $this->propertyModel->getByPropertyId($propertyId);
                    
                    // Get upcoming bookings for this unit
                    $unit['upcoming_bookings'] = $this->bookingModel->getAllBookings([
                        'unit_id' => $unit['id'],
                        'status' => 'confirmed,checked_in',
                        'check_in_from' => date('Y-m-d')
                    ]);
                    
                    $allUnits[] = $unit;
                }
            }

            // Filter by property if requested
            $filterPropertyId = $_GET['property_id'] ?? null;
            if ($filterPropertyId) {
                $allUnits = array_filter($allUnits, function($u) use ($filterPropertyId) {
                    return $u['property_id'] == $filterPropertyId;
                });
            }

            // Filter by eligibility if requested
            $filterEligible = $_GET['eligible'] ?? null;
            if ($filterEligible === 'yes') {
                $allUnits = array_filter($allUnits, function($u) {
                    return !empty($u['is_airbnb_eligible']);
                });
            } elseif ($filterEligible === 'no') {
                $allUnits = array_filter($allUnits, function($u) {
                    return empty($u['is_airbnb_eligible']);
                });
            }

            require 'views/airbnb/units.php';
        } catch (\Exception $e) {
            error_log($e->getMessage());
            require 'views/errors/500.php';
        }
    }

    /**
     * Maintenance - List maintenance requests for Airbnb properties
     */
    public function maintenance()
    {
        try {
            $auth = $this->checkAirbnbAccess();
            $userId = $auth['userId'];
            $propertyIds = $this->getAccessiblePropertyIds();

            // Get maintenance requests for accessible properties
            $maintenanceRequest = new \App\Models\MaintenanceRequest();
            $requests = [];
            $statistics = [
                'total_requests' => 0,
                'pending_requests' => 0,
                'in_progress_requests' => 0,
                'completed_requests' => 0
            ];

            if (!empty($propertyIds)) {
                // Get all requests for these properties
                $allRequests = $maintenanceRequest->getByPropertyIds($propertyIds);
                $requests = $allRequests;
                
                // Calculate statistics
                $statistics['total_requests'] = count($requests);
                foreach ($requests as $request) {
                    switch ($request['status']) {
                        case 'pending':
                            $statistics['pending_requests']++;
                            break;
                        case 'in_progress':
                            $statistics['in_progress_requests']++;
                            break;
                        case 'completed':
                            $statistics['completed_requests']++;
                            break;
                    }
                }
            }

            // Get wallet balance for funding option (default to 0 if wallet not set up)
            $walletBalance = 0.0;
            try {
                $walletModel = new \App\Models\Wallet();
                $wallet = $walletModel->getByUserId($userId);
                $walletBalance = $wallet ? (float)$wallet['balance'] : 0.0;
            } catch (\Exception $e) {
                // Wallet table may not exist, continue with 0 balance
                error_log('Wallet check failed: ' . $e->getMessage());
            }

            $title = 'Airbnb Maintenance - RentSmart';
            $properties = $this->getPropertiesForDropdown($propertyIds);
            require 'views/airbnb/maintenance.php';
        } catch (\Exception $e) {
            error_log('AirbnbController::maintenance - Error: ' . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading maintenance requests';
            $_SESSION['flash_type'] = 'danger';
            redirect('/airbnb/dashboard');
        }
    }

    /**
     * Update maintenance status with expense handling
     * Streamlined flow: Owner pays (wallet/cash) OR bills client
     */
    public function updateMaintenanceStatus()
    {
        try {
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
            $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
            $assignedTo = filter_input(INPUT_POST, 'assigned_to', FILTER_SANITIZE_STRING);
            $scheduledDate = filter_input(INPUT_POST, 'scheduled_date', FILTER_SANITIZE_STRING);
            $estimatedCost = filter_input(INPUT_POST, 'estimated_cost', FILTER_VALIDATE_FLOAT);
            $actualCost = filter_input(INPUT_POST, 'actual_cost', FILTER_VALIDATE_FLOAT);
            
            // Expense handling options
            $paymentSource = filter_input(INPUT_POST, 'payment_source', FILTER_SANITIZE_STRING) ?: 'owner_funds';
            $billToClient = isset($_POST['bill_to_client']) && $_POST['bill_to_client'] === '1';
            
            if (!$id || !$status) {
                throw new \Exception('Request ID and status are required');
            }

            $auth = $this->checkAirbnbAccess();
            $userId = $auth['userId'];
            
            $maintenanceRequest = new \App\Models\MaintenanceRequest();
            $request = $maintenanceRequest->getById($id, $userId);
            
            if (!$request) {
                throw new \Exception('Maintenance request not found');
            }

            // Update maintenance status
            $maintenanceRequest->updateStatus($id, $status, $notes, $assignedTo, $scheduledDate, $estimatedCost, $actualCost);

            // Handle expense and billing if actual cost is provided
            if ($actualCost > 0 && $status === 'completed') {
                $this->handleMaintenanceExpense($request, $actualCost, $paymentSource, $billToClient, $userId);
            }

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Maintenance request updated successfully'
                ]);
                exit;
            }

            $_SESSION['flash_message'] = 'Maintenance request updated successfully';
            $_SESSION['flash_type'] = 'success';
            redirect('/airbnb/maintenance');
            
        } catch (\Exception $e) {
            error_log('AirbnbController::updateMaintenanceStatus - Error: ' . $e->getMessage());
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
                exit;
            }

            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            redirect('/airbnb/maintenance');
        }
    }

    /**
     * Handle maintenance expense - streamlined flow
     * Option 1: Owner pays (from wallet or cash/bank)
     * Option 2: Bill client (add to guest/tenant bill)
     */
    private function handleMaintenanceExpense($request, $actualCost, $paymentSource, $billToClient, $userId)
    {
        $expenseModel = new \App\Models\Expense();
        
        // Create expense record
        $expenseData = [
            'user_id' => $userId,
            'property_id' => $request['property_id'] ?? null,
            'unit_id' => $request['unit_id'] ?? null,
            'category' => 'maintenance',
            'amount' => $actualCost,
            'expense_date' => date('Y-m-d'),
            'payment_method' => $billToClient ? 'other' : ($paymentSource === 'wallet' ? 'wallet' : 'cash'),
            'source_of_funds' => $billToClient ? 'tenant_charge' : $paymentSource,
            'notes' => 'Airbnb Maintenance #' . $request['id'] . ': ' . ($request['title'] ?? ''),
            'reference_type' => 'maintenance',
            'reference_id' => $request['id']
        ];

        // Check for existing expense
        $existing = $expenseModel->findByReference('maintenance', $request['id']);
        if ($existing) {
            $expenseId = $existing['id'];
            $expenseModel->updateExpense($expenseId, $expenseData);
        } else {
            $expenseId = $expenseModel->insertExpense($expenseData);
        }

        // Handle wallet deduction if owner pays from wallet
        if ($paymentSource === 'wallet' && !$billToClient) {
            try {
                $walletModel = new \App\Models\Wallet();
                $wallet = $walletModel->getByUserId($userId);
                
                if ($wallet && $wallet['balance'] >= $actualCost) {
                    $walletModel->deduct($userId, $actualCost, 
                        'Maintenance expense for property: ' . ($request['property_name'] ?? 'N/A'),
                        'maintenance_expense',
                        $expenseId
                    );
                } else {
                    throw new \Exception('Insufficient wallet balance. Available: ' . 
                        ($wallet ? number_format($wallet['balance'], 2) : '0.00'));
                }
            } catch (\Exception $e) {
                // If wallet system is not available, treat as owner funds
                if (strpos($e->getMessage(), 'Wallet system not available') !== false) {
                    // Update expense to reflect owner funds instead
                    $expenseModel->updateExpense($expenseId, array_merge($expenseData, [
                        'payment_method' => 'cash',
                        'source_of_funds' => 'owner_funds'
                    ]));
                } else {
                    throw $e;
                }
            }
        }

        // Handle billing to client (add charge to guest bill)
        if ($billToClient) {
            $this->addMaintenanceChargeToGuest($request, $actualCost, $userId);
        }

        // Post to ledger
        $this->postMaintenanceToLedger($expenseData, $expenseId, $userId, $billToClient);
    }

    /**
     * Add maintenance charge to guest/tenant bill
     */
    private function addMaintenanceChargeToGuest($request, $amount, $userId)
    {
        // For Airbnb bookings, create an additional charge
        if (!empty($request['booking_id'])) {
            $bookingModel = new AirbnbBooking();
            $booking = $bookingModel->getById($request['booking_id']);
            
            if ($booking) {
                // Add charge to booking
                $chargeData = [
                    'booking_id' => $booking['id'],
                    'charge_type' => 'maintenance',
                    'description' => 'Maintenance: ' . ($request['title'] ?? 'Maintenance fee'),
                    'amount' => $amount,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                // Store additional charges (requires new table or field)
                // For now, we'll create an invoice item
                $invoiceModel = new \App\Models\Invoice();
                $invoiceModel->createAirbnbAdditionalCharge($chargeData);
            }
        }
        
        // Also create a negative payment to increase tenant/guest balance if they're in the system
        if (!empty($request['resolved_tenant_id'])) {
            $paymentModel = new \App\Models\Payment();
            $lease = $paymentModel->getActiveLease((int)$request['resolved_tenant_id'], $userId);
            
            if ($lease) {
                // Check if charge already exists
                $db = $paymentModel->getDb();
                $chk = $db->prepare("SELECT id FROM payments WHERE lease_id = ? AND notes LIKE ? LIMIT 1");
                $chk->execute([$lease['id'], '%MAINT-AIRBNB-' . $request['id'] . '%']);
                $existsAdj = $chk->fetch(\PDO::FETCH_ASSOC);
                
                if (!$existsAdj) {
                    $paymentModel->createRentPayment([
                        'lease_id' => $lease['id'],
                        'amount' => -abs($amount),
                        'payment_date' => date('Y-m-d'),
                        'payment_type' => 'other',
                        'payment_method' => 'other',
                        'notes' => 'Maintenance charge to guest MAINT-AIRBNB-' . $request['id'] . ': ' . ($request['title'] ?? ''),
                        'status' => 'completed'
                    ]);
                }
            }
        }
    }

    /**
     * Post maintenance expense to ledger
     */
    private function postMaintenanceToLedger($expenseData, $expenseId, $userId, $billToClient)
    {
        try {
            $ledger = new \App\Models\LedgerEntry();
            if (!$ledger->referenceExists('expense', $expenseId)) {
                $accModel = new \App\Models\Account();
                
                if ($billToClient) {
                    // Debit AR (tenant owes us), Credit Maintenance Revenue
                    $arAcc = $accModel->findByCode('1200'); // Accounts Receivable
                    $revAcc = $accModel->findByCode('4000'); // Revenue
                    
                    if ($arAcc && $revAcc) {
                        $desc = 'Maintenance charge to guest - Exp #' . $expenseId;
                        $date = $expenseData['expense_date'] ?? date('Y-m-d');
                        
                        // Debit AR
                        $ledger->post([
                            'entry_date' => $date,
                            'account_id' => (int)$arAcc['id'],
                            'description' => $desc,
                            'debit' => $expenseData['amount'],
                            'credit' => 0,
                            'user_id' => $userId,
                            'property_id' => $expenseData['property_id'],
                            'reference_type' => 'expense',
                            'reference_id' => $expenseId,
                        ]);
                        
                        // Credit Revenue
                        $ledger->post([
                            'entry_date' => $date,
                            'account_id' => (int)$revAcc['id'],
                            'description' => $desc,
                            'debit' => 0,
                            'credit' => $expenseData['amount'],
                            'user_id' => $userId,
                            'property_id' => $expenseData['property_id'],
                            'reference_type' => 'expense',
                            'reference_id' => $expenseId,
                        ]);
                    }
                } else {
                    // Owner paid - Debit Expense, Credit Cash/Wallet
                    $cash = $accModel->findByCode('1000');
                    $expAcc = $accModel->findByCode('5000');
                    
                    if ($cash && $expAcc) {
                        $desc = 'Maintenance expense #' . $expenseId;
                        $date = $expenseData['expense_date'] ?? date('Y-m-d');
                        $amount = (float)$expenseData['amount'];
                        
                        // Debit Expense
                        $ledger->post([
                            'entry_date' => $date,
                            'account_id' => (int)$expAcc['id'],
                            'description' => $desc,
                            'debit' => $amount,
                            'credit' => 0,
                            'user_id' => $userId,
                            'property_id' => $expenseData['property_id'],
                            'reference_type' => 'expense',
                            'reference_id' => $expenseId,
                        ]);
                        
                        // Credit Cash
                        $ledger->post([
                            'entry_date' => $date,
                            'account_id' => (int)$cash['id'],
                            'description' => $desc,
                            'debit' => 0,
                            'credit' => $amount,
                            'user_id' => $userId,
                            'property_id' => $expenseData['property_id'],
                            'reference_type' => 'expense',
                            'reference_id' => $expenseId,
                        ]);
                    }
                }
            }
        } catch (\Exception $le) {
            error_log('Airbnb maintenance ledger post failed: ' . $le->getMessage());
        }
    }

    /**
     * Helper: Get properties for dropdown
     */
    private function getPropertiesForDropdown($propertyIds)
    {
        $properties = [];
        foreach ($propertyIds as $pid) {
            $p = $this->property->find($pid);
            if ($p) {
                $properties[] = $p;
            }
        }
        return $properties;
    }

    /**
     * Create maintenance request form
     */
    public function createMaintenance()
    {
        try {
            $auth = $this->checkAirbnbAccess();
            $userId = $auth['userId'];
            $propertyIds = $this->getAccessiblePropertyIds();

            // Get properties with units for dropdown
            $properties = [];
            foreach ($propertyIds as $pid) {
                $p = $this->property->find($pid);
                if ($p) {
                    // Get units for this property using Unit model
                    $units = $this->unit->getByPropertyId($pid);
                    
                    // Filter for Airbnb eligible units and ensure sequential array
                    $p['units'] = array_values(array_filter($units ?? [], function($u) {
                        return !empty($u['is_airbnb_eligible']);
                    }));
                    
                    $properties[] = $p;
                    error_log('Property ' . ($p['name'] ?? 'ID: '.$pid) . ' has ' . count($p['units']) . ' Airbnb eligible units');
                } else {
                    error_log('Property ID ' . $pid . ' not found');
                }
            }
            // Ensure properties is a sequential array
            $properties = array_values($properties);
            error_log('Total properties formatted for view: ' . count($properties));

            // Get wallet balance for payment method dropdown
            $walletBalance = 0;
            try {
                $wallet = new \App\Models\Wallet();
                $walletData = $wallet->getByUserId($userId);
                if ($walletData) {
                    $walletBalance = $walletData['balance'] ?? 0;
                }
            } catch (\Exception $e) {
                // Wallet might not exist yet, default to 0
                error_log('AirbnbController::createMaintenance - Wallet error: ' . $e->getMessage());
            }

            require 'views/airbnb/create_maintenance.php';
        } catch (\Exception $e) {
            error_log('AirbnbController::createMaintenance - Error: ' . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading maintenance form';
            $_SESSION['flash_type'] = 'danger';
            redirect('/airbnb/maintenance');
        }
    }

    /**
     * Store maintenance request
     */
    public function storeMaintenance()
    {
        try {
            $auth = $this->checkAirbnbAccess();
            $userId = $auth['userId'];

            // Get form data
            $propertyId = filter_input(INPUT_POST, 'property_id', FILTER_VALIDATE_INT);
            $unitId = filter_input(INPUT_POST, 'unit_id', FILTER_VALIDATE_INT);
            $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
            $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
            $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
            $priority = filter_input(INPUT_POST, 'priority', FILTER_SANITIZE_STRING) ?: 'medium';
            $estimatedCost = filter_input(INPUT_POST, 'estimated_cost', FILTER_VALIDATE_FLOAT);

            if (!$propertyId || !$title || !$description) {
                throw new \Exception('Property, title, and description are required');
            }

            // Verify access to property
            $propertyIds = $this->getAccessiblePropertyIds();
            if (!in_array($propertyId, $propertyIds)) {
                throw new \Exception('You do not have access to this property');
            }

            // Create maintenance request
            $maintenanceRequest = new \App\Models\MaintenanceRequest();
            $data = [
                'property_id' => $propertyId,
                'unit_id' => $unitId ?: null,
                'title' => $title,
                'description' => $description,
                'category' => $category ?: 'other',
                'priority' => $priority,
                'status' => 'pending',
                'estimated_cost' => $estimatedCost ?: null,
                'requested_date' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $maintenanceRequest->create($data);

            $_SESSION['flash_message'] = 'Maintenance request created successfully';
            $_SESSION['flash_type'] = 'success';
            redirect('/airbnb/maintenance');

        } catch (\Exception $e) {
            error_log('AirbnbController::storeMaintenance - Error: ' . $e->getMessage());
            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            redirect('/airbnb/maintenance/create');
        }
    }
}
