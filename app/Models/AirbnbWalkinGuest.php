<?php

namespace App\Models;

class AirbnbWalkinGuest extends Model
{
    protected $table = 'airbnb_walkin_guests';

    public function __construct()
    {
        parent::__construct();
        $this->table = 'airbnb_walkin_guests';
        $this->ensureTable();
    }

    private function ensureTable()
    {
        try {
            $sql = "SHOW TABLES LIKE '{$this->table}'";
            $stmt = $this->db->query($sql);
            $tableExists = $stmt->rowCount() > 0;

            if (!$tableExists) {
                $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    property_id INT NOT NULL,
                    guest_name VARCHAR(150) NOT NULL,
                    guest_phone VARCHAR(20) NOT NULL,
                    guest_email VARCHAR(150) DEFAULT NULL,
                    guest_count INT DEFAULT 1,
                    inquiry_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                    preferred_check_in DATE DEFAULT NULL,
                    preferred_check_out DATE DEFAULT NULL,
                    budget_range VARCHAR(50) DEFAULT NULL,
                    requirements TEXT,
                    assigned_unit_id INT DEFAULT NULL,
                    status ENUM('inquiry','offered','converted','declined','no_show') DEFAULT 'inquiry',
                    converted_booking_id INT DEFAULT NULL,
                    follow_up_date DATETIME DEFAULT NULL,
                    notes TEXT,
                    handled_by_user_id INT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
                    FOREIGN KEY (assigned_unit_id) REFERENCES units(id) ON DELETE SET NULL,
                    FOREIGN KEY (converted_booking_id) REFERENCES airbnb_bookings(id) ON DELETE SET NULL,
                    FOREIGN KEY (handled_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
                    INDEX idx_status (status),
                    INDEX idx_property (property_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                $this->db->exec($sql);
            }
        } catch (\Exception $e) {
            error_log("Error in AirbnbWalkinGuest::ensureTable: " . $e->getMessage());
        }
    }

    public function create($data)
    {
        $sql = "INSERT INTO {$this->table} (
            property_id, guest_name, guest_phone, guest_email, guest_count,
            preferred_check_in, preferred_check_out, budget_range, requirements,
            assigned_unit_id, status, follow_up_date, notes, handled_by_user_id
        ) VALUES (
            :property_id, :guest_name, :guest_phone, :guest_email, :guest_count,
            :preferred_check_in, :preferred_check_out, :budget_range, :requirements,
            :assigned_unit_id, :status, :follow_up_date, :notes, :handled_by_user_id
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'property_id' => $data['property_id'],
            'guest_name' => $data['guest_name'],
            'guest_phone' => $data['guest_phone'],
            'guest_email' => $data['guest_email'] ?? null,
            'guest_count' => $data['guest_count'] ?? 1,
            'preferred_check_in' => $data['preferred_check_in'] ?? null,
            'preferred_check_out' => $data['preferred_check_out'] ?? null,
            'budget_range' => $data['budget_range'] ?? null,
            'requirements' => $data['requirements'] ?? null,
            'assigned_unit_id' => $data['assigned_unit_id'] ?? null,
            'status' => $data['status'] ?? 'inquiry',
            'follow_up_date' => $data['follow_up_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'handled_by_user_id' => $data['handled_by_user_id'] ?? null
        ]);

        return $this->db->lastInsertId();
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT w.*, p.name as property_name, u.unit_number 
            FROM {$this->table} w 
            JOIN properties p ON w.property_id = p.id 
            LEFT JOIN units u ON w.assigned_unit_id = u.id 
            WHERE w.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getAllWalkinGuests($filters = [])
    {
        $sql = "SELECT w.*, p.name as property_name, u.unit_number 
            FROM {$this->table} w 
            JOIN properties p ON w.property_id = p.id 
            LEFT JOIN units u ON w.assigned_unit_id = u.id 
            WHERE 1=1";
        $params = [];

        if (!empty($filters['property_id'])) {
            $sql .= " AND w.property_id = ?";
            $params[] = $filters['property_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND w.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['handled_by_user_id'])) {
            $sql .= " AND w.handled_by_user_id = ?";
            $params[] = $filters['handled_by_user_id'];
        }

        if (!empty($filters['follow_up_from'])) {
            $sql .= " AND w.follow_up_date >= ?";
            $params[] = $filters['follow_up_from'];
        }

        if (!empty($filters['follow_up_to'])) {
            $sql .= " AND w.follow_up_date <= ?";
            $params[] = $filters['follow_up_to'];
        }

        $sql .= " ORDER BY w.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getWalkinGuestsForUser($userId, $role)
    {
        $sql = "SELECT w.*, p.name as property_name, u.unit_number 
            FROM {$this->table} w 
            JOIN properties p ON w.property_id = p.id 
            LEFT JOIN units u ON w.assigned_unit_id = u.id 
            WHERE 1=1";
        $params = [];

        $role = strtolower((string)$role);
        if (!in_array($role, ['admin', 'administrator'], true)) {
            if ($role === 'airbnb_manager') {
                $sql .= " AND p.airbnb_manager_id = ?";
                $params[] = $userId;
            } else {
                $sql .= " AND (p.owner_id = ? OR p.manager_id = ? OR p.caretaker_user_id = ? OR p.airbnb_manager_id = ? OR w.handled_by_user_id = ?)";
                $params[] = $userId;
                $params[] = $userId;
                $params[] = $userId;
                $params[] = $userId;
                $params[] = $userId;
            }
        }

        $sql .= " ORDER BY w.follow_up_date ASC, w.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $status, $bookingId = null)
    {
        $allowedStatuses = ['inquiry', 'offered', 'converted', 'declined', 'no_show'];
        if (!in_array($status, $allowedStatuses)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET status = ?";
        $params = [$status];

        if ($bookingId && $status === 'converted') {
            $sql .= ", converted_booking_id = ?";
            $params[] = $bookingId;
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function updateWalkinGuest($id, $data)
    {
        $allowedFields = [
            'guest_name', 'guest_phone', 'guest_email', 'guest_count',
            'preferred_check_in', 'preferred_check_out', 'budget_range',
            'requirements', 'assigned_unit_id', 'status', 'follow_up_date',
            'notes', 'handled_by_user_id', 'converted_booking_id'
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

        $params['id'] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function getFollowUpReminders($userId, $days = 7)
    {
        $sql = "SELECT w.*, p.name as property_name, u.unit_number 
            FROM {$this->table} w 
            JOIN properties p ON w.property_id = p.id 
            LEFT JOIN units u ON w.assigned_unit_id = u.id 
            WHERE w.handled_by_user_id = ? 
            AND w.status IN ('inquiry', 'offered')
            AND w.follow_up_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
            AND w.follow_up_date >= CURDATE()
            ORDER BY w.follow_up_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $days]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getStats($propertyIds = [])
    {
        $sql = "SELECT 
            COUNT(*) as total_inquiries,
            SUM(CASE WHEN status = 'inquiry' THEN 1 ELSE 0 END) as pending_inquiries,
            SUM(CASE WHEN status = 'offered' THEN 1 ELSE 0 END) as offered_count,
            SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted_count,
            SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined_count,
            SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) as no_show_count,
            ROUND(AVG(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) * 100, 2) as conversion_rate
            FROM {$this->table} 
            WHERE 1=1";
        
        $params = [];
        if (!empty($propertyIds)) {
            $placeholders = implode(',', array_fill(0, count($propertyIds), '?'));
            $sql .= " AND property_id IN ($placeholders)";
            $params = $propertyIds;
        } else {
            $sql .= " AND 1=0";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
