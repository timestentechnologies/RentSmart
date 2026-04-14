-- Airbnb Management Feature Migration
-- Run this SQL to add Airbnb functionality to RentSmart

-- 1. Add airbnb_manager to the users role enum
-- Note: This requires modifying the existing enum. Run carefully.
ALTER TABLE `users` MODIFY COLUMN `role` ENUM('admin','landlord','agent','manager','caretaker','realtor','airbnb_manager') NOT NULL DEFAULT 'agent';

-- 2. Create airbnb_properties table to track which properties support Airbnb bookings
CREATE TABLE IF NOT EXISTS `airbnb_properties` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `property_id` INT NOT NULL,
    `is_airbnb_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `min_stay_nights` INT NOT NULL DEFAULT 1,
    `max_stay_nights` INT NOT NULL DEFAULT 30,
    `check_in_time` TIME NOT NULL DEFAULT '14:00:00',
    `check_out_time` TIME NOT NULL DEFAULT '11:00:00',
    `cleaning_fee` DECIMAL(10,2) DEFAULT 0.00,
    `security_deposit` DECIMAL(10,2) DEFAULT 0.00,
    `booking_lead_time_hours` INT DEFAULT 24,
    `instant_booking` TINYINT(1) DEFAULT 0,
    `house_rules` TEXT,
    `cancellation_policy` ENUM('flexible','moderate','strict') DEFAULT 'moderate',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_property` (`property_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create airbnb_unit_rates table for daily/seasonal pricing
CREATE TABLE IF NOT EXISTS `airbnb_unit_rates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `unit_id` INT NOT NULL,
    `base_price_per_night` DECIMAL(10,2) NOT NULL,
    `weekend_price` DECIMAL(10,2) DEFAULT NULL,
    `weekly_discount_percent` DECIMAL(5,2) DEFAULT 0.00,
    `monthly_discount_percent` DECIMAL(5,2) DEFAULT 0.00,
    `seasonal_rates_json` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`unit_id`) REFERENCES `units`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_unit` (`unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Create airbnb_bookings table for reservations
CREATE TABLE IF NOT EXISTS `airbnb_bookings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_reference` VARCHAR(20) NOT NULL UNIQUE,
    `unit_id` INT NOT NULL,
    `property_id` INT NOT NULL,
    `guest_name` VARCHAR(150) NOT NULL,
    `guest_email` VARCHAR(150) DEFAULT NULL,
    `guest_phone` VARCHAR(20) NOT NULL,
    `guest_count` INT NOT NULL DEFAULT 1,
    `check_in_date` DATE NOT NULL,
    `check_out_date` DATE NOT NULL,
    `check_in_time` TIME DEFAULT NULL,
    `check_out_time` TIME DEFAULT NULL,
    `actual_check_in` DATETIME DEFAULT NULL,
    `actual_check_out` DATETIME DEFAULT NULL,
    `nights` INT NOT NULL,
    `price_per_night` DECIMAL(10,2) NOT NULL,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `cleaning_fee` DECIMAL(10,2) DEFAULT 0.00,
    `security_deposit` DECIMAL(10,2) DEFAULT 0.00,
    `discount_amount` DECIMAL(10,2) DEFAULT 0.00,
    `tax_amount` DECIMAL(10,2) DEFAULT 0.00,
    `final_total` DECIMAL(10,2) NOT NULL,
    `status` ENUM('pending','confirmed','checked_in','checked_out','cancelled','no_show') DEFAULT 'pending',
    `booking_source` ENUM('online','walk_in','phone','email','ota') DEFAULT 'online',
    `payment_status` ENUM('pending','partial','paid','refunded') DEFAULT 'pending',
    `amount_paid` DECIMAL(10,2) DEFAULT 0.00,
    `special_requests` TEXT,
    `internal_notes` TEXT,
    `booked_by_user_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`unit_id`) REFERENCES `units`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`booked_by_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_check_in_out` (`check_in_date`, `check_out_date`),
    INDEX `idx_status` (`status`),
    INDEX `idx_unit_dates` (`unit_id`, `check_in_date`, `check_out_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Create airbnb_booking_payments table for payment tracking
CREATE TABLE IF NOT EXISTS `airbnb_booking_payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `payment_method` ENUM('cash','mpesa','bank_transfer','card','other') NOT NULL,
    `mpesa_transaction_id` VARCHAR(50) DEFAULT NULL,
    `transaction_reference` VARCHAR(100) DEFAULT NULL,
    `payment_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `notes` TEXT,
    `recorded_by_user_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`booking_id`) REFERENCES `airbnb_bookings`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`recorded_by_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Create airbnb_walkin_guests table for managing walk-in customers
CREATE TABLE IF NOT EXISTS `airbnb_walkin_guests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `property_id` INT NOT NULL,
    `guest_name` VARCHAR(150) NOT NULL,
    `guest_phone` VARCHAR(20) NOT NULL,
    `guest_email` VARCHAR(150) DEFAULT NULL,
    `guest_count` INT DEFAULT 1,
    `inquiry_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `preferred_check_in` DATE DEFAULT NULL,
    `preferred_check_out` DATE DEFAULT NULL,
    `budget_range` VARCHAR(50) DEFAULT NULL,
    `requirements` TEXT,
    `assigned_unit_id` INT DEFAULT NULL,
    `status` ENUM('inquiry','offered','converted','declined','no_show') DEFAULT 'inquiry',
    `converted_booking_id` INT DEFAULT NULL,
    `follow_up_date` DATETIME DEFAULT NULL,
    `notes` TEXT,
    `handled_by_user_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assigned_unit_id`) REFERENCES `units`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`converted_booking_id`) REFERENCES `airbnb_bookings`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`handled_by_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_status` (`status`),
    INDEX `idx_property` (`property_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Add airbnb_manager_id to properties table for assignment
ALTER TABLE `properties` ADD COLUMN `airbnb_manager_id` INT DEFAULT NULL AFTER `caretaker_user_id`;
ALTER TABLE `properties` ADD CONSTRAINT `fk_properties_airbnb_manager` FOREIGN KEY (`airbnb_manager_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;

-- 8. Add is_airbnb_eligible to units table
ALTER TABLE `units` ADD COLUMN `is_airbnb_eligible` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`;

-- 9. Create view for available Airbnb units
CREATE OR REPLACE VIEW `available_airbnb_units` AS
SELECT 
    u.id as unit_id,
    u.unit_number,
    u.type,
    u.property_id,
    p.name as property_name,
    p.address,
    p.city,
    p.state,
    p.caretaker_name,
    p.caretaker_contact,
    p.caretaker_user_id,
    p.airbnb_manager_id,
    ap.is_airbnb_enabled,
    ap.check_in_time,
    ap.check_out_time,
    ap.min_stay_nights,
    ap.max_stay_nights,
    ap.cleaning_fee,
    ap.security_deposit,
    aur.base_price_per_night,
    aur.weekend_price,
    aur.weekly_discount_percent,
    aur.monthly_discount_percent,
    u.is_airbnb_eligible
FROM units u
JOIN properties p ON u.property_id = p.id
LEFT JOIN airbnb_properties ap ON p.id = ap.property_id
LEFT JOIN airbnb_unit_rates aur ON u.id = aur.unit_id
WHERE u.is_airbnb_eligible = 1 
    AND (ap.is_airbnb_enabled = 1 OR ap.is_airbnb_enabled IS NULL);
