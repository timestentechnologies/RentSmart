<?php

namespace App\Models;

class AirbnbBooking extends Model
{
    protected $table = 'airbnb_bookings';

    public function __construct()
    {
        parent::__construct();
        $this->table = 'airbnb_bookings';
        $this->ensureTable();
    }

    private function ensureTable()
    {
        try {
            // Check if table exists
            $sql = "SHOW TABLES LIKE '{$this->table}'";
            $stmt = $this->db->query($sql);
            $tableExists = $stmt->rowCount() > 0;

            if (!$tableExists) {
                $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    booking_reference VARCHAR(20) NOT NULL UNIQUE,
                    unit_id INT NOT NULL,
                    property_id INT NOT NULL,
                    guest_name VARCHAR(150) NOT NULL,
                    guest_email VARCHAR(150) DEFAULT NULL,
                    guest_phone VARCHAR(20) NOT NULL,
                    guest_count INT NOT NULL DEFAULT 1,
                    check_in_date DATE NOT NULL,
                    check_out_date DATE NOT NULL,
                    check_in_time TIME DEFAULT NULL,
                    check_out_time TIME DEFAULT NULL,
                    actual_check_in DATETIME DEFAULT NULL,
                    actual_check_out DATETIME DEFAULT NULL,
                    nights INT NOT NULL,
                    price_per_night DECIMAL(10,2) NOT NULL,
                    total_amount DECIMAL(10,2) NOT NULL,
                    cleaning_fee DECIMAL(10,2) DEFAULT 0.00,
                    security_deposit DECIMAL(10,2) DEFAULT 0.00,
                    discount_amount DECIMAL(10,2) DEFAULT 0.00,
                    tax_amount DECIMAL(10,2) DEFAULT 0.00,
                    final_total DECIMAL(10,2) NOT NULL,
                    status ENUM('pending','confirmed','checked_in','checked_out','cancelled','no_show') DEFAULT 'pending',
                    booking_source ENUM('online','walk_in','phone','email','ota') DEFAULT 'online',
                    payment_status ENUM('pending','partial','paid','refunded') DEFAULT 'pending',
                    amount_paid DECIMAL(10,2) DEFAULT 0.00,
                    special_requests TEXT,
                    internal_notes TEXT,
                    booked_by_user_id INT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
                    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
                    FOREIGN KEY (booked_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
                    INDEX idx_check_in_out (check_in_date, check_out_date),
                    INDEX idx_status (status),
                    INDEX idx_unit_dates (unit_id, check_in_date, check_out_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                $this->db->exec($sql);
            }
        } catch (\Exception $e) {
            error_log("Error in AirbnbBooking::ensureTable: " . $e->getMessage());
        }
    }

    public function generateBookingReference()
    {
        $prefix = 'RB';
        $date = date('Ymd');
        $random = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4));
        return $prefix . $date . $random;
    }

    public function createBooking($data)
    {
        $data['booking_reference'] = $this->generateBookingReference();
        
        $sql = "INSERT INTO {$this->table} (
            booking_reference, unit_id, property_id, guest_name, guest_email, guest_phone,
            guest_count, check_in_date, check_out_date, check_in_time, check_out_time,
            nights, price_per_night, total_amount, cleaning_fee, security_deposit,
            discount_amount, tax_amount, final_total, status, booking_source,
            payment_status, amount_paid, special_requests, internal_notes, booked_by_user_id
        ) VALUES (
            :booking_reference, :unit_id, :property_id, :guest_name, :guest_email, :guest_phone,
            :guest_count, :check_in_date, :check_out_date, :check_in_time, :check_out_time,
            :nights, :price_per_night, :total_amount, :cleaning_fee, :security_deposit,
            :discount_amount, :tax_amount, :final_total, :status, :booking_source,
            :payment_status, :amount_paid, :special_requests, :internal_notes, :booked_by_user_id
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'booking_reference' => $data['booking_reference'],
            'unit_id' => $data['unit_id'],
            'property_id' => $data['property_id'],
            'guest_name' => $data['guest_name'],
            'guest_email' => $data['guest_email'] ?? null,
            'guest_phone' => $data['guest_phone'],
            'guest_count' => $data['guest_count'] ?? 1,
            'check_in_date' => $data['check_in_date'],
            'check_out_date' => $data['check_out_date'],
            'check_in_time' => $data['check_in_time'] ?? null,
            'check_out_time' => $data['check_out_time'] ?? null,
            'nights' => $data['nights'],
            'price_per_night' => $data['price_per_night'],
            'total_amount' => $data['total_amount'],
            'cleaning_fee' => $data['cleaning_fee'] ?? 0.00,
            'security_deposit' => $data['security_deposit'] ?? 0.00,
            'discount_amount' => $data['discount_amount'] ?? 0.00,
            'tax_amount' => $data['tax_amount'] ?? 0.00,
            'final_total' => $data['final_total'],
            'status' => $data['status'] ?? 'pending',
            'booking_source' => $data['booking_source'] ?? 'online',
            'payment_status' => $data['payment_status'] ?? 'pending',
            'amount_paid' => $data['amount_paid'] ?? 0.00,
            'special_requests' => $data['special_requests'] ?? null,
            'internal_notes' => $data['internal_notes'] ?? null,
            'booked_by_user_id' => $data['booked_by_user_id'] ?? null
        ]);

        return $this->db->lastInsertId();
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT b.*, u.unit_number, p.name as property_name, p.address, p.city 
            FROM {$this->table} b 
            JOIN units u ON b.unit_id = u.id 
            JOIN properties p ON b.property_id = p.id 
            WHERE b.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function findByReference($reference)
    {
        $stmt = $this->db->prepare("SELECT b.*, u.unit_number, p.name as property_name, p.address, p.city 
            FROM {$this->table} b 
            JOIN units u ON b.unit_id = u.id 
            JOIN properties p ON b.property_id = p.id 
            WHERE b.booking_reference = ?");
        $stmt->execute([$reference]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getAllBookings($filters = [])
    {
        $sql = "SELECT b.*, u.unit_number, p.name as property_name, p.address, p.city 
            FROM {$this->table} b 
            JOIN units u ON b.unit_id = u.id 
            JOIN properties p ON b.property_id = p.id 
            WHERE 1=1";
        $params = [];

        if (!empty($filters['property_id'])) {
            if (is_array($filters['property_id'])) {
                $placeholders = implode(',', array_fill(0, count($filters['property_id']), '?'));
                $sql .= " AND b.property_id IN ($placeholders)";
                $params = array_merge($params, $filters['property_id']);
            } else {
                $sql .= " AND b.property_id = ?";
                $params[] = $filters['property_id'];
            }
        }

        if (!empty($filters['unit_id'])) {
            if (is_array($filters['unit_id'])) {
                $placeholders = implode(',', array_fill(0, count($filters['unit_id']), '?'));
                $sql .= " AND b.unit_id IN ($placeholders)";
                $params = array_merge($params, $filters['unit_id']);
            } else {
                $sql .= " AND b.unit_id = ?";
                $params[] = $filters['unit_id'];
            }
        }

        if (!empty($filters['status'])) {
            $sql .= " AND b.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['check_in_from'])) {
            $sql .= " AND b.check_in_date >= ?";
            $params[] = $filters['check_in_from'];
        }

        if (!empty($filters['check_in_to'])) {
            $sql .= " AND b.check_in_date <= ?";
            $params[] = $filters['check_in_to'];
        }

        if (!empty($filters['guest_name'])) {
            $sql .= " AND b.guest_name LIKE ?";
            $params[] = '%' . $filters['guest_name'] . '%';
        }

        $sql .= " ORDER BY b.check_in_date DESC, b.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getBookingsForUser($userId, $role)
    {
        $sql = "SELECT b.*, u.unit_number, p.name as property_name, p.address, p.city 
            FROM {$this->table} b 
            JOIN units u ON b.unit_id = u.id 
            JOIN properties p ON b.property_id = p.id 
            WHERE 1=1";
        $params = [];

        $role = strtolower((string)$role);
        if (!in_array($role, ['admin', 'administrator'], true)) {
            if ($role === 'airbnb_manager') {
                $sql .= " AND p.airbnb_manager_id = ?";
                $params[] = $userId;
            } else {
                $sql .= " AND (p.owner_id = ? OR p.manager_id = ? OR p.caretaker_user_id = ? OR p.airbnb_manager_id = ?)";
                $params[] = $userId;
                $params[] = $userId;
                $params[] = $userId;
                $params[] = $userId;
            }
        }

        $sql .= " ORDER BY b.check_in_date DESC, b.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getUpcomingCheckIns($propertyIds = [], $limit = 10)
    {
        $sql = "SELECT b.*, u.unit_number, p.name as property_name 
            FROM {$this->table} b 
            JOIN units u ON b.unit_id = u.id 
            JOIN properties p ON b.property_id = p.id 
            WHERE b.status IN ('confirmed', 'pending') 
            AND b.check_in_date >= CURDATE()";
        
        $params = [];
        if (!empty($propertyIds)) {
            $placeholders = implode(',', array_fill(0, count($propertyIds), '?'));
            $sql .= " AND b.property_id IN ($placeholders)";
            $params = $propertyIds;
        } else {
            $sql .= " AND 1=0";
        }

        $sql .= " ORDER BY b.check_in_date ASC LIMIT ?";
        $params[] = $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getUpcomingCheckOuts($propertyIds = [], $limit = 10)
    {
        $sql = "SELECT b.*, u.unit_number, p.name as property_name 
            FROM {$this->table} b 
            JOIN units u ON b.unit_id = u.id 
            JOIN properties p ON b.property_id = p.id 
            WHERE b.status = 'checked_in' 
            AND b.check_out_date >= CURDATE()";
        
        $params = [];
        if (!empty($propertyIds)) {
            $placeholders = implode(',', array_fill(0, count($propertyIds), '?'));
            $sql .= " AND b.property_id IN ($placeholders)";
            $params = $propertyIds;
        } else {
            $sql .= " AND 1=0";
        }

        $sql .= " ORDER BY b.check_out_date ASC LIMIT ?";
        $params[] = $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function checkIn($bookingId, $actualTime = null)
    {
        $actualTime = $actualTime ?? date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("UPDATE {$this->table} 
            SET status = 'checked_in', actual_check_in = ? 
            WHERE id = ? AND status IN ('confirmed', 'pending')");
        return $stmt->execute([$actualTime, $bookingId]);
    }

    public function checkOut($bookingId, $actualTime = null)
    {
        $actualTime = $actualTime ?? date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("UPDATE {$this->table} 
            SET status = 'checked_out', actual_check_out = ? 
            WHERE id = ? AND status = 'checked_in'");
        return $stmt->execute([$actualTime, $bookingId]);
    }

    public function updateStatus($bookingId, $status)
    {
        $allowedStatuses = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show'];
        if (!in_array($status, $allowedStatuses)) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $bookingId]);
    }

    public function updateBooking($bookingId, $data)
    {
        $allowedFields = [
            'guest_name', 'guest_email', 'guest_phone', 'guest_count',
            'check_in_date', 'check_out_date', 'check_in_time', 'check_out_time',
            'nights', 'price_per_night', 'total_amount', 'cleaning_fee',
            'security_deposit', 'discount_amount', 'tax_amount', 'final_total',
            'status', 'payment_status', 'amount_paid', 'special_requests', 'internal_notes'
        ];

        $fields = [];
        $params = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "$key = :$key";
                $params[$key] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params['id'] = $bookingId;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function isUnitAvailable($unitId, $checkIn, $checkOut, $excludeBookingId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
            WHERE unit_id = ? 
            AND status NOT IN ('cancelled', 'no_show')
            AND (
                (check_in_date <= ? AND check_out_date > ?) OR
                (check_in_date < ? AND check_out_date >= ?) OR
                (check_in_date >= ? AND check_out_date <= ?)
            )";
        
        $params = [$unitId, $checkOut, $checkIn, $checkOut, $checkIn, $checkIn, $checkOut];
        
        if ($excludeBookingId) {
            $sql .= " AND id != ?";
            $params[] = $excludeBookingId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['count'] == 0;
    }

    public function getBookingStats($propertyIds = [], $startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? date('Y-m-01');
        $endDate = $endDate ?? date('Y-m-t');

        $sql = "SELECT 
            COUNT(*) as total_bookings,
            SUM(CASE WHEN status = 'checked_out' THEN 1 ELSE 0 END) as completed_bookings,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
            SUM(CASE WHEN status = 'checked_in' THEN 1 ELSE 0 END) as active_bookings,
            SUM(final_total) as total_revenue,
            SUM(amount_paid) as total_paid,
            SUM(nights) as total_nights
            FROM {$this->table} 
            WHERE check_in_date BETWEEN ? AND ?";
        
        $params = [$startDate, $endDate];

        if (!empty($propertyIds)) {
            $placeholders = implode(',', array_fill(0, count($propertyIds), '?'));
            $sql .= " AND property_id IN ($placeholders)";
            $params = array_merge($params, $propertyIds);
        } else {
            $sql .= " AND 1=0";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getOccupancyData($propertyIds = [], $month = null, $year = null)
    {
        $month = $month ?? date('m');
        $year = $year ?? date('Y');
        
        $startDate = "$year-$month-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        $sql = "SELECT 
            DATE(check_in_date) as date,
            COUNT(*) as bookings,
            SUM(nights) as nights_booked
            FROM {$this->table} 
            WHERE status NOT IN ('cancelled', 'no_show')
            AND (
                (check_in_date BETWEEN ? AND ?) OR
                (check_out_date BETWEEN ? AND ?) OR
                (check_in_date <= ? AND check_out_date >= ?)
            )";
        
        $params = [$startDate, $endDate, $startDate, $endDate, $startDate, $endDate];

        if (!empty($propertyIds)) {
            $placeholders = implode(',', array_fill(0, count($propertyIds), '?'));
            $sql .= " AND property_id IN ($placeholders)";
            $params = array_merge($params, $propertyIds);
        } else {
            $sql .= " AND 1=0";
        }

        $sql .= " GROUP BY DATE(check_in_date) ORDER BY date";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
