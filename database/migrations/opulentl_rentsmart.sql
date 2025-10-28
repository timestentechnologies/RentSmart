-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 14, 2025 at 09:53 AM
-- Server version: 10.11.14-MariaDB-cll-lve
-- PHP Version: 8.4.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `opulentl_rentsmart`
--

-- --------------------------------------------------------

--
-- Table structure for table `facebook_listings`
--

CREATE TABLE `facebook_listings` (
  `id` int(10) UNSIGNED NOT NULL,
  `unit_id` int(11) NOT NULL,
  `facebook_listing_id` varchar(255) NOT NULL,
  `status` enum('active','deleted','expired') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `file_uploads`
--

CREATE TABLE `file_uploads` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_type` enum('image','document','attachment') NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL,
  `entity_type` enum('property','unit','payment') NOT NULL,
  `entity_id` int(11) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `upload_path` varchar(500) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `file_uploads`
--

INSERT INTO `file_uploads` (`id`, `filename`, `original_name`, `file_type`, `mime_type`, `file_size`, `entity_type`, `entity_id`, `uploaded_by`, `upload_path`, `created_at`, `updated_at`) VALUES
(5, 'property_10_68de72c377898.jpg', 'e7790a61fecc076c99cbe1e6e91cc5d7.jpg', 'image', 'image/jpeg', 180589, 'property', 10, 1, 'uploads/properties/property_10_68de72c377898.jpg', '2025-10-02 12:40:35', '2025-10-02 12:40:35'),
(6, 'property_10_68de72c37a389.pdf', 'tenants.pdf', 'document', 'application/pdf', 2226, 'property', 10, 1, 'uploads/properties/property_10_68de72c37a389.pdf', '2025-10-02 12:40:35', '2025-10-02 12:40:35'),
(7, 'property_10_68de7da468349.png', 'September Footer (2).png', 'image', 'image/png', 233214, 'property', 10, 1, 'uploads/properties/property_10_68de7da468349.png', '2025-10-02 13:27:00', '2025-10-02 13:27:00'),
(9, 'payment_13_68de7fa26f45d.jpg', 'f0d9743733257f47fc440676f121e75a.jpg', 'attachment', 'image/jpeg', 109632, 'payment', 13, 1, 'uploads/payments/payment_13_68de7fa26f45d.jpg', '2025-10-02 13:35:30', '2025-10-02 13:35:30'),
(12, 'unit_23_68e91092601b3.jpg', '428baf703ce323bd9951247665b21c50.jpg', 'image', 'image/jpeg', 586385, 'unit', 23, 1, 'uploads/units/unit_23_68e91092601b3.jpg', '2025-10-10 13:56:34', '2025-10-10 13:56:34'),
(13, 'unit_25_68e910a13964f.jpg', 'e7790a61fecc076c99cbe1e6e91cc5d7.jpg', 'image', 'image/jpeg', 180589, 'unit', 25, 1, 'uploads/units/unit_25_68e910a13964f.jpg', '2025-10-10 13:56:49', '2025-10-10 13:56:49'),
(14, 'unit_23_68e9173c08bee.jpg', 'e7790a61fecc076c99cbe1e6e91cc5d7.jpg', 'image', 'image/jpeg', 180589, 'unit', 23, 1, 'uploads/units/unit_23_68e9173c08bee.jpg', '2025-10-10 14:25:00', '2025-10-10 14:25:00'),
(15, 'unit_25_68e917fc717b2.jpg', 'e7790a61fecc076c99cbe1e6e91cc5d7.jpg', 'image', 'image/jpeg', 180589, 'unit', 25, 1, 'uploads/units/unit_25_68e917fc717b2.jpg', '2025-10-10 14:28:12', '2025-10-10 14:28:12'),
(16, 'unit_15_68e9181c47f65.jpg', 'f0d9743733257f47fc440676f121e75a.jpg', 'image', 'image/jpeg', 109632, 'unit', 15, 1, 'uploads/units/unit_15_68e9181c47f65.jpg', '2025-10-10 14:28:44', '2025-10-10 14:28:44'),
(17, 'unit_16_68e9237ea8507.jpg', '428baf703ce323bd9951247665b21c50.jpg', 'image', 'image/jpeg', 586385, 'unit', 16, 1, 'uploads/units/unit_16_68e9237ea8507.jpg', '2025-10-10 15:17:18', '2025-10-10 15:17:18');

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `contact` varchar(150) NOT NULL,
  `preferred_date` date DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inquiries`
--

INSERT INTO `inquiries` (`id`, `unit_id`, `property_id`, `name`, `contact`, `preferred_date`, `message`, `created_at`) VALUES
(1, 15, 10, 'Alex', 'chombaalex2019@gmail.com', '2025-10-31', 'Bbbh', '2025-10-04 18:50:18');

-- --------------------------------------------------------

--
-- Table structure for table `leases`
--

CREATE TABLE `leases` (
  `id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `rent_amount` decimal(10,2) NOT NULL,
  `security_deposit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','expired','terminated') NOT NULL DEFAULT 'active',
  `payment_day` int(11) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leases`
--

INSERT INTO `leases` (`id`, `unit_id`, `tenant_id`, `start_date`, `end_date`, `rent_amount`, `security_deposit`, `status`, `payment_day`, `notes`, `created_at`, `updated_at`) VALUES
(15, 15, 25, '2025-09-28', '2026-09-28', 20000.00, 0.00, 'active', 1, '', '2025-09-28 07:24:37', '2025-09-28 10:24:37'),
(16, 18, 26, '2024-01-15', '2025-01-14', 25000.00, 50000.00, 'active', 1, '12-month lease', '2025-10-10 13:20:33', '2025-10-10 13:20:33');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_requests`
--

CREATE TABLE `maintenance_requests` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `unit_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `property_id` int(11) DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('plumbing','electrical','hvac','appliance','structural','pest_control','cleaning','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `priority` enum('low','medium','high','urgent') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `requested_date` datetime NOT NULL DEFAULT current_timestamp(),
  `scheduled_date` datetime DEFAULT NULL,
  `completed_date` datetime DEFAULT NULL,
  `assigned_to` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estimated_cost` decimal(10,2) DEFAULT NULL,
  `actual_cost` decimal(10,2) DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `maintenance_requests`
--

INSERT INTO `maintenance_requests` (`id`, `tenant_id`, `unit_id`, `unit_number`, `property_id`, `title`, `description`, `category`, `priority`, `status`, `requested_date`, `scheduled_date`, `completed_date`, `assigned_to`, `estimated_cost`, `actual_cost`, `notes`, `images`, `created_at`, `updated_at`) VALUES
(1, 25, 15, 'A101', 10, 'DOM loaded, setting up maintenance form handler dashboard:279 Maintenance form found: ​…​​ dashboard:358 Submit button found: ​Submit Request​​ dashboard:375 Submit button enabled and clickable dashboard:384 Modal shown, ensuring button is clickable dashb', 'DOM loaded, setting up maintenance form handler\r\ndashboard:279 Maintenance form found: ​…​​\r\ndashboard:358 Submit button found: ​Submit Request​​\r\ndashboard:375 Submit button enabled and clickable\r\ndashboard:384 Modal shown, ensuring button is clickable\r\ndashboard:389 Button enabled after modal shown\r\ndashboard:360 Submit button clicked directly\r\ndashboard:367 Triggering form submission\r\ndashboard:283 Form submission triggered\r\ndashboard:297 Submitting form data: {title: &#39;Leaking pipe in the Bathroom&#39;, category: &#39;plumbing&#39;, priority: &#39;medium&#39;, description: &#39;Leaking pipe in the Bathroom&#39;}\r\ndashboard:313 Response received: 200\r\ndashboard:317 Response data: {success: false, message: &#39;Tenant not found&#39;}', 'plumbing', 'medium', 'pending', '2025-09-28 10:38:53', '2025-09-28 13:43:00', NULL, 'Jacob', 600.00, 3000.00, 'all done\r\n', NULL, '2025-09-28 07:38:53', '2025-09-28 08:13:15');

-- --------------------------------------------------------

--
-- Table structure for table `manual_mpesa_payments`
--

CREATE TABLE `manual_mpesa_payments` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `transaction_code` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `verification_status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `verified_at` timestamp NULL DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verification_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `manual_mpesa_payments`
--

INSERT INTO `manual_mpesa_payments` (`id`, `payment_id`, `phone_number`, `transaction_code`, `amount`, `verification_status`, `verified_at`, `verified_by`, `verification_notes`, `created_at`, `updated_at`) VALUES
(38, 1, '0718883983', 'QWWE34TTTY', 25000.00, 'verified', NULL, NULL, 'Done', '2025-10-13 13:02:31', '2025-10-13 13:02:31');

-- --------------------------------------------------------

--
-- Table structure for table `mpesa_transactions`
--

CREATE TABLE `mpesa_transactions` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `merchant_request_id` varchar(100) DEFAULT NULL,
  `checkout_request_id` varchar(100) DEFAULT NULL,
  `phone_number` varchar(15) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `mpesa_receipt_number` varchar(50) DEFAULT NULL,
  `transaction_date` timestamp NULL DEFAULT NULL,
  `status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `result_code` varchar(10) DEFAULT NULL,
  `result_description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `odoo_sync_log`
--

CREATE TABLE `odoo_sync_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `entity_type` enum('payment','expense','tenant','property') NOT NULL,
  `entity_id` int(10) UNSIGNED NOT NULL,
  `status` enum('success','error','pending') DEFAULT 'pending',
  `external_id` varchar(255) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `synced_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `lease_id` int(11) NOT NULL,
  `utility_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_type` enum('rent','utility','deposit','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` enum('cash','check','bank_transfer','card','mpesa_manual','mpesa_stk') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','completed','failed','pending_verification') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of payment attachment filenames'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `lease_id`, `utility_id`, `amount`, `payment_date`, `payment_type`, `payment_method`, `reference_number`, `notes`, `status`, `created_at`, `attachments`) VALUES
(1, 16, NULL, 25000.00, '2025-10-13', 'rent', 'mpesa_manual', NULL, 'Rent payment: Done', 'completed', '2025-10-13 13:02:31', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('mpesa_manual','mpesa_stk','bank_transfer','cash','cheque','card') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `name`, `type`, `description`, `is_active`, `details`, `settings`, `created_at`, `updated_at`) VALUES
(1, 'M-Pesa', 'mpesa_manual', 'Mobile money payment via M-Pesa', 1, '{\"mpesa_method\": \"paybill\", \"account_number\": \"4545555445\", \"paybill_number\": \"464645545\"}', NULL, '2025-09-28 14:31:24', '2025-09-28 12:17:33'),
(2, 'Bank Transfer', 'bank_transfer', 'Direct bank transfer payment', 1, NULL, NULL, '2025-09-28 14:31:24', '2025-09-28 14:31:24'),
(3, 'Cash Payment', 'cash', 'Cash payment at office', 1, NULL, NULL, '2025-09-28 14:31:24', '2025-09-28 14:31:24'),
(4, 'Cheque Payment', 'cheque', 'Payment via cheque', 1, NULL, NULL, '2025-09-28 14:31:24', '2025-09-28 14:31:24'),
(5, 'Credit/Debit Card', 'card', 'Payment via credit or debit card', 1, NULL, NULL, '2025-09-28 14:31:24', '2025-09-28 14:31:24'),
(6, 'M-Pesa STK Push', 'mpesa_stk', 'Mobile money payment via M-Pesa (STK Push)', 1, NULL, NULL, '2025-09-28 15:08:48', '2025-09-28 15:08:48');

-- --------------------------------------------------------

--
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `id` int(11) NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `zip_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `property_type` enum('apartment','house','commercial','condo') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'apartment',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `year_built` int(11) DEFAULT NULL,
  `total_area` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `owner_id` int(11) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of property image filenames',
  `documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of property document filenames'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `properties`
--

INSERT INTO `properties` (`id`, `name`, `address`, `city`, `state`, `zip_code`, `property_type`, `description`, `year_built`, `total_area`, `created_at`, `updated_at`, `owner_id`, `manager_id`, `images`, `documents`) VALUES
(10, 'Timesten Plaza', 'Sunton Kasarani , Nairobi Kenya', 'Nairobi', 'Kenya', '00100', 'apartment', 'Apartment in Kasarani', 2020, 99999999.99, '2025-09-28 09:45:37', '2025-10-10 11:56:11', NULL, NULL, '[{\"id\":7,\"filename\":\"property_10_68de7da468349.png\",\"original_name\":\"September Footer (2).png\",\"url\":\"\\/rentsmart.timestentechnologies.co.ke\\/public\\/uploads\\/properties\\/property_10_68de7da468349.png\"},{\"id\":5,\"filename\":\"property_10_68de72c377898.jpg\",\"original_name\":\"e7790a61fecc076c99cbe1e6e91cc5d7.jpg\",\"url\":\"\\/rentsmart.timestentechnologies.co.ke\\/public\\/uploads\\/properties\\/property_10_68de72c377898.jpg\"}]', '[{\"id\":6,\"filename\":\"property_10_68de72c37a389.pdf\",\"original_name\":\"tenants.pdf\",\"url\":\"\\/rentsmart.timestentechnologies.co.ke\\/public\\/uploads\\/properties\\/property_10_68de72c37a389.pdf\"}]'),
(11, 'Sunset Apartments', '123 Main Street', 'Nairobi', 'Nairobi County', '00100', 'apartment', NULL, 2015, 5000.00, '2025-10-10 12:51:10', '2025-10-10 12:51:10', NULL, NULL, NULL, NULL),
(12, 'Green Valley Villas', '456 Valley Road', 'Kiambu', 'Kiambu County', '00200', '', NULL, 2018, 8000.00, '2025-10-10 12:51:10', '2025-10-10 12:51:10', NULL, NULL, NULL, NULL),
(13, 'Riverside Towers', '789 River Lane', 'Mombasa', 'Mombasa County', '80100', '', NULL, 2020, 12000.00, '2025-10-10 12:51:10', '2025-10-10 12:51:10', NULL, NULL, NULL, NULL),
(14, 'Palm Court Residences', '321 Palm Avenue', 'Kisumu', 'Kisumu County', '40100', 'apartment', NULL, 2017, 6000.00, '2025-10-10 12:51:10', '2025-10-10 12:51:10', NULL, NULL, NULL, NULL),
(15, 'Hilltop Mansions', '654 Hill Drive', 'Nakuru', 'Nakuru County', '20100', '', NULL, 2016, 10000.00, '2025-10-10 12:51:10', '2025-10-10 12:51:10', NULL, NULL, NULL, NULL),
(16, 'Garden Estate Homes', '987 Garden Street', 'Eldoret', 'Uasin Gishu County', '30100', '', NULL, 2019, 7000.00, '2025-10-10 12:51:10', '2025-10-10 12:51:10', NULL, NULL, NULL, NULL),
(18, 'Lakeview Apartments', '258 Lake Road', 'Kisumu', 'Kisumu County', '40200', 'apartment', NULL, 2021, 5500.00, '2025-10-10 12:51:10', '2025-10-10 12:51:10', NULL, NULL, NULL, NULL),
(19, 'Parkside Residences', '369 Park Avenue', 'Thika', 'Kiambu County', '01000', 'apartment', NULL, 2018, 6500.00, '2025-10-10 12:51:10', '2025-10-10 12:51:10', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `quickbooks_sync_log`
--

CREATE TABLE `quickbooks_sync_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `entity_type` enum('payment','expense','tenant','property') NOT NULL,
  `entity_id` int(10) UNSIGNED NOT NULL,
  `status` enum('success','error','pending') DEFAULT 'pending',
  `external_id` varchar(255) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `synced_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Rent Smart Kenya', '2025-06-24 12:27:31', '2025-06-25 06:13:11'),
(2, 'site_description', 'A powerful and easy-to-use rental management system.', '2025-06-24 12:27:31', '2025-06-25 05:54:31'),
(3, 'smtp_host', 'smtp.gmail.com', '2025-06-24 12:27:31', '2025-07-06 13:56:18'),
(4, 'smtp_port', '587', '2025-06-24 12:27:31', '2025-07-06 13:43:23'),
(5, 'smtp_user', 'timestentechnologies@gmail.com', '2025-06-24 12:27:31', '2025-07-06 13:43:23'),
(6, 'smtp_pass', 'wcvj obwt xcrh qduz', '2025-06-24 12:27:31', '2025-07-06 13:43:23'),
(7, 'sms_api_key', '', '2025-06-24 12:27:31', '2025-06-24 12:27:31'),
(8, 'sms_api_secret', '', '2025-06-24 12:27:31', '2025-06-24 12:27:31'),
(21, 'sms_provider', '', '2025-06-24 14:27:57', '2025-06-24 14:27:57'),
(9, 'site_logo', 'site_logo_1751627446.png', '2025-06-24 14:11:34', '2025-07-04 11:10:46'),
(10, 'site_favicon', 'site_favicon_1751634757.png', '2025-06-24 14:11:34', '2025-07-04 13:12:37'),
(11, 'site_keywords', '', '2025-06-24 14:11:34', '2025-06-24 14:11:34'),
(12, 'site_email', 'timestentechnologies@gmail.com', '2025-06-24 14:11:34', '2025-07-06 13:48:58'),
(13, 'site_phone', '', '2025-06-24 14:11:34', '2025-06-24 14:11:34'),
(14, 'site_address', '', '2025-06-24 14:11:34', '2025-06-24 14:11:34'),
(15, 'site_footer_text', '', '2025-06-24 14:11:34', '2025-06-24 14:11:34'),
(16, 'site_analytics_code', '', '2025-06-24 14:11:34', '2025-06-24 14:11:34'),
(17, 'maintenance_mode', '0', '2025-06-24 14:11:34', '2025-06-24 14:11:34'),
(18, 'timezone', 'Africa/Abidjan', '2025-06-24 14:11:34', '2025-06-24 14:17:30'),
(19, 'date_format', 'Y-m-d', '2025-06-24 14:11:34', '2025-06-24 14:11:34'),
(20, 'currency', 'USD', '2025-06-24 14:11:34', '2025-06-24 14:11:34'),
(22, 'mpesa_consumer_key', '', '2025-07-02 11:22:54', '2025-07-02 11:22:54'),
(23, 'mpesa_consumer_secret', '', '2025-07-02 11:22:54', '2025-07-02 11:22:54'),
(24, 'mpesa_shortcode', '', '2025-07-02 11:22:54', '2025-07-02 11:22:54'),
(25, 'mpesa_passkey', '', '2025-07-02 11:22:54', '2025-07-02 11:22:54'),
(26, 'mpesa_environment', 'sandbox', '2025-07-02 11:22:54', '2025-07-02 11:22:54'),
(27, 'mpesa_callback_url', '', '2025-07-02 11:22:54', '2025-07-02 11:22:54'),
(28, 'stripe_public_key', '', '2025-07-02 11:22:54', '2025-07-02 11:22:54'),
(29, 'stripe_secret_key', '', '2025-07-02 11:22:54', '2025-07-02 11:22:54'),
(30, 'stripe_webhook_secret', '', '2025-07-02 11:22:54', '2025-07-02 11:22:54'),
(31, 'stripe_environment', 'test', '2025-07-02 11:22:54', '2025-07-02 11:22:54'),
(32, 'paypal_client_id', '', '2025-07-02 11:22:54', '2025-07-02 11:22:54'),
(33, 'paypal_secret', '', '2025-07-02 11:22:54', '2025-07-02 11:22:54'),
(34, 'paypal_environment', 'sandbox', '2025-07-02 11:22:54', '2025-07-02 11:22:54'),
(35, 'paypal_webhook_id', '', '2025-07-02 11:22:54', '2025-07-02 11:22:54'),
(36, 'facebook_access_token', '', '2025-10-10 19:44:07', '2025-10-10 19:44:07'),
(37, 'facebook_page_id', '', '2025-10-10 19:44:07', '2025-10-10 19:44:07'),
(38, 'zoho_client_id', '', '2025-10-10 19:51:02', '2025-10-10 19:51:02'),
(39, 'zoho_client_secret', '', '2025-10-10 19:51:02', '2025-10-10 19:51:02'),
(40, 'zoho_refresh_token', '', '2025-10-10 19:51:02', '2025-10-10 19:51:02'),
(41, 'zoho_access_token', '', '2025-10-10 19:51:02', '2025-10-10 19:51:02'),
(42, 'zoho_organization_id', '', '2025-10-10 19:51:02', '2025-10-10 19:51:02'),
(43, 'qb_client_id', '', '2025-10-10 19:51:02', '2025-10-10 19:51:02'),
(44, 'qb_client_secret', '', '2025-10-10 19:51:02', '2025-10-10 19:51:02'),
(45, 'qb_refresh_token', '', '2025-10-10 19:51:02', '2025-10-10 19:51:02'),
(46, 'qb_access_token', '', '2025-10-10 19:51:02', '2025-10-10 19:51:02'),
(47, 'qb_realm_id', '', '2025-10-10 19:51:02', '2025-10-10 19:51:02'),
(48, 'qb_rent_item_id', '', '2025-10-10 19:51:02', '2025-10-10 19:51:02'),
(49, 'odoo_url', '', '2025-10-10 19:51:02', '2025-10-10 19:51:02'),
(50, 'odoo_database', '', '2025-10-10 19:51:02', '2025-10-10 19:51:02'),
(51, 'odoo_username', '', '2025-10-10 19:51:02', '2025-10-10 19:51:02'),
(52, 'odoo_password', '', '2025-10-10 19:51:02', '2025-10-10 19:51:02'),
(53, 'odoo_uid', '', '2025-10-10 19:51:02', '2025-10-10 19:51:02');

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `plan_type` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL,
  `trial_ends_at` datetime DEFAULT NULL,
  `current_period_starts_at` datetime NOT NULL,
  `current_period_ends_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_payments`
--

CREATE TABLE `subscription_payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subscription_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('mpesa','card') NOT NULL,
  `status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `transaction_reference` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_payment_logs`
--

CREATE TABLE `subscription_payment_logs` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `log_type` enum('request','response','callback','error') NOT NULL,
  `log_data` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_plans`
--

CREATE TABLE `subscription_plans` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `features` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscription_plans`
--

INSERT INTO `subscription_plans` (`id`, `name`, `price`, `description`, `features`, `created_at`, `updated_at`) VALUES
(1, 'Basic', 2999.00, 'Perfect for small property managers', 'Property Management\r\nTenant Management\r\nBasic Reporting', '2025-06-28 18:01:30', '2025-07-04 09:27:35'),
(2, 'Professional', 4999.00, 'For growing property management businesses', 'Everything in Basic\r\nAdvanced Reporting\r\nEmail Notifications\r\nDocument Storage', '2025-06-28 18:01:30', '2025-07-04 09:27:58'),
(3, 'Enterprise', 9999.99, 'Complete solution for large property managers', 'Everything in Professional\r\nAPI Access\r\nPriority Support\r\nCustom Branding', '2025-06-28 18:01:30', '2025-07-04 09:28:29');

-- --------------------------------------------------------

--
-- Table structure for table `tenants`
--

CREATE TABLE `tenants` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `property_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `id_type` varchar(50) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `registered_on` date DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `rent_amount` decimal(10,2) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tenants`
--

INSERT INTO `tenants` (`id`, `name`, `first_name`, `last_name`, `email`, `password`, `phone`, `unit_id`, `property_id`, `notes`, `emergency_contact`, `id_type`, `id_number`, `registered_on`, `created_at`, `updated_at`, `rent_amount`) VALUES
(26, 'John Kamau', 'John', 'Kamau', 'john.kamau@email.com', NULL, '+254712345678', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10', NULL, NULL, NULL),
(28, 'David Ochieng', 'David', 'Ochieng', 'david.ochieng@email.com', NULL, '+254734567890', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10', NULL, NULL, NULL),
(30, 'Peter Mwangi', 'Peter', 'Mwangi', 'peter.mwangi@email.com', NULL, '+254756789012', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10', NULL, NULL, NULL),
(32, 'James Otieno', 'James', 'Otieno', 'james.otieno@email.com', NULL, '+254778901234', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-10', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `unit_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('studio','1bhk','2bhk','3bhk','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` decimal(10,2) DEFAULT NULL,
  `rent_amount` decimal(10,2) NOT NULL,
  `status` enum('vacant','occupied','maintenance') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'vacant',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `tenant_id` int(11) DEFAULT NULL,
  `lease_start` date DEFAULT NULL,
  `lease_end` date DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of unit image filenames',
  `documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of unit document filenames'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `property_id`, `unit_number`, `type`, `size`, `rent_amount`, `status`, `created_at`, `updated_at`, `tenant_id`, `lease_start`, `lease_end`, `images`, `documents`) VALUES
(15, 10, 'A101', '2bhk', 20000.00, 20000.00, 'vacant', '2025-09-28 09:45:37', '2025-10-10 15:17:35', NULL, NULL, NULL, '[{\"id\":16,\"filename\":\"unit_15_68e9181c47f65.jpg\",\"original_name\":\"f0d9743733257f47fc440676f121e75a.jpg\",\"url\":\"\\/rentsmart.timestentechnologies.co.ke\\/public\\/uploads\\/units\\/unit_15_68e9181c47f65.jpg\"}]', '[]'),
(16, 10, 'A102', '3bhk', 222.00, 3000.00, 'vacant', '2025-10-10 08:09:32', '2025-10-10 15:17:18', NULL, NULL, NULL, '[{\"id\":17,\"filename\":\"unit_16_68e9237ea8507.jpg\",\"original_name\":\"428baf703ce323bd9951247665b21c50.jpg\",\"url\":\"\\/rentsmart.timestentechnologies.co.ke\\/public\\/uploads\\/units\\/unit_16_68e9237ea8507.jpg\"}]', '[]'),
(17, 11, 'A101', '2bhk', 850.00, 35000.00, 'vacant', '2025-10-10 12:53:28', '2025-10-10 12:53:28', NULL, NULL, NULL, NULL, NULL),
(18, 11, 'A102', '1bhk', 650.00, 25000.00, 'occupied', '2025-10-10 12:53:28', '2025-10-10 12:53:28', NULL, NULL, NULL, NULL, NULL),
(19, 12, 'V1', '3bhk', 1200.00, 65000.00, 'vacant', '2025-10-10 12:53:28', '2025-10-10 12:53:28', NULL, NULL, NULL, NULL, NULL),
(20, 13, 'T501', 'studio', 450.00, 18000.00, 'vacant', '2025-10-10 12:53:28', '2025-10-10 12:53:28', NULL, NULL, NULL, NULL, NULL),
(21, 14, 'B204', '2bhk', 900.00, 40000.00, 'occupied', '2025-10-10 12:53:28', '2025-10-10 12:53:28', NULL, NULL, NULL, NULL, NULL),
(22, 15, 'M3', '3bhk', 1500.00, 85000.00, 'vacant', '2025-10-10 12:53:28', '2025-10-10 12:53:28', NULL, NULL, NULL, NULL, NULL),
(23, 16, 'G12', '2bhk', 1000.00, 45000.00, 'vacant', '2025-10-10 12:53:28', '2025-10-10 14:25:00', NULL, NULL, NULL, '[{\"id\":14,\"filename\":\"unit_23_68e9173c08bee.jpg\",\"original_name\":\"e7790a61fecc076c99cbe1e6e91cc5d7.jpg\",\"url\":\"\\/rentsmart.timestentechnologies.co.ke\\/public\\/uploads\\/units\\/unit_23_68e9173c08bee.jpg\"},{\"id\":12,\"filename\":\"unit_23_68e91092601b3.jpg\",\"original_name\":\"428baf703ce323bd9951247665b21c50.jpg\",\"url\":\"\\/rentsmart.timestentechnologies.co.ke\\/public\\/uploads\\/units\\/unit_23_68e91092601b3.jpg\"}]', '[]'),
(25, 18, 'L205', '2bhk', 800.00, 38000.00, 'vacant', '2025-10-10 12:53:28', '2025-10-10 14:28:12', NULL, NULL, NULL, '[{\"id\":15,\"filename\":\"unit_25_68e917fc717b2.jpg\",\"original_name\":\"e7790a61fecc076c99cbe1e6e91cc5d7.jpg\",\"url\":\"\\/rentsmart.timestentechnologies.co.ke\\/public\\/uploads\\/units\\/unit_25_68e917fc717b2.jpg\"},{\"id\":13,\"filename\":\"unit_25_68e910a13964f.jpg\",\"original_name\":\"e7790a61fecc076c99cbe1e6e91cc5d7.jpg\",\"url\":\"\\/rentsmart.timestentechnologies.co.ke\\/public\\/uploads\\/units\\/unit_25_68e910a13964f.jpg\"}]', '[]'),
(26, 19, 'P108', 'studio', 500.00, 22000.00, 'vacant', '2025-10-10 12:53:28', '2025-10-10 12:53:28', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','landlord','agent','manager') NOT NULL DEFAULT 'agent',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_subscribed` tinyint(1) DEFAULT 0,
  `trial_ends_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `updated_at`, `is_subscribed`, `trial_ends_at`, `last_login_at`, `manager_id`) VALUES
(1, 'Timesten Technologies', 'timestentechnologies@gmail.com', '$2y$10$sLM.rv241DK4AiYp0e4NCO8g5bCkyn.wD.BDn0teGJcH10FF2cAhW', 'admin', '2025-06-24 09:39:55', '2025-10-13 14:56:30', 0, NULL, '2025-10-13 14:56:30', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `utilities`
--

CREATE TABLE `utilities` (
  `id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `utility_type` enum('water','electricity','gas','internet','other') NOT NULL,
  `meter_number` varchar(50) DEFAULT NULL,
  `is_metered` tinyint(1) DEFAULT 1,
  `flat_rate` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `utilities`
--

INSERT INTO `utilities` (`id`, `unit_id`, `utility_type`, `meter_number`, `is_metered`, `flat_rate`, `created_at`, `updated_at`) VALUES
(11, 15, 'electricity', NULL, 0, 50.00, '2025-09-28 13:47:39', '2025-09-28 13:47:39'),
(12, 15, 'water', '444444444', 1, NULL, '2025-09-28 13:48:46', '2025-09-28 13:49:28'),
(13, 17, 'electricity', NULL, 0, NULL, '2025-10-10 12:55:01', '2025-10-10 12:55:01'),
(14, 18, 'water', NULL, 0, NULL, '2025-10-10 12:55:01', '2025-10-10 12:55:01'),
(15, 19, 'electricity', NULL, 0, NULL, '2025-10-10 12:55:01', '2025-10-10 12:55:01'),
(16, 20, 'water', NULL, 0, NULL, '2025-10-10 12:55:01', '2025-10-10 12:55:01'),
(17, 21, 'electricity', NULL, 0, NULL, '2025-10-10 12:55:01', '2025-10-10 12:55:01'),
(18, 22, 'water', NULL, 0, NULL, '2025-10-10 12:55:01', '2025-10-10 12:55:01'),
(19, 23, 'electricity', NULL, 0, NULL, '2025-10-10 12:55:01', '2025-10-10 12:55:01'),
(20, 24, 'water', NULL, 0, NULL, '2025-10-10 12:55:01', '2025-10-10 12:55:01'),
(21, 25, 'electricity', NULL, 0, NULL, '2025-10-10 12:55:01', '2025-10-10 12:55:01'),
(22, 26, 'water', NULL, 0, NULL, '2025-10-10 12:55:01', '2025-10-10 12:55:01'),
(23, 17, 'electricity', NULL, 0, NULL, '2025-10-10 12:55:12', '2025-10-10 12:55:12'),
(24, 18, 'water', NULL, 0, NULL, '2025-10-10 12:55:12', '2025-10-10 12:55:12'),
(25, 19, 'electricity', NULL, 0, NULL, '2025-10-10 12:55:12', '2025-10-10 12:55:12'),
(26, 20, 'water', NULL, 0, NULL, '2025-10-10 12:55:12', '2025-10-10 12:55:12'),
(27, 21, 'electricity', NULL, 0, NULL, '2025-10-10 12:55:12', '2025-10-10 12:55:12'),
(28, 22, 'water', NULL, 0, NULL, '2025-10-10 12:55:12', '2025-10-10 12:55:12'),
(29, 23, 'electricity', NULL, 0, NULL, '2025-10-10 12:55:12', '2025-10-10 12:55:12'),
(30, 24, 'water', NULL, 0, NULL, '2025-10-10 12:55:12', '2025-10-10 12:55:12'),
(31, 25, 'electricity', NULL, 0, NULL, '2025-10-10 12:55:12', '2025-10-10 12:55:12'),
(32, 26, 'water', NULL, 0, NULL, '2025-10-10 12:55:12', '2025-10-10 12:55:12');

-- --------------------------------------------------------

--
-- Table structure for table `utility_rates`
--

CREATE TABLE `utility_rates` (
  `id` int(11) NOT NULL,
  `utility_type` varchar(50) NOT NULL,
  `rate_per_unit` decimal(10,2) NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `billing_method` enum('metered','flat_rate') NOT NULL DEFAULT 'flat_rate'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `utility_rates`
--

INSERT INTO `utility_rates` (`id`, `utility_type`, `rate_per_unit`, `effective_from`, `effective_to`, `billing_method`) VALUES
(1, 'Electricity', 50.00, '2025-07-06', NULL, 'flat_rate'),
(2, 'Water', 150.00, '2025-07-06', NULL, 'metered'),
(3, 'Internet', 1500.00, '2025-07-06', NULL, 'flat_rate');

-- --------------------------------------------------------

--
-- Table structure for table `utility_readings`
--

CREATE TABLE `utility_readings` (
  `id` int(11) NOT NULL,
  `utility_id` int(11) NOT NULL,
  `reading_date` date NOT NULL,
  `previous_reading` decimal(10,2) DEFAULT NULL,
  `reading_value` decimal(10,2) NOT NULL,
  `units_used` decimal(10,2) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `utility_readings`
--

INSERT INTO `utility_readings` (`id`, `utility_id`, `reading_date`, `previous_reading`, `reading_value`, `units_used`, `cost`, `created_at`) VALUES
(12, 12, '2025-09-28', 99999999.99, 545545.00, 0.00, 0.00, '2025-09-28 13:48:46'),
(13, 12, '2025-09-28', 4.00, 6.00, 2.00, 300.00, '2025-09-28 13:49:28');

-- --------------------------------------------------------

--
-- Table structure for table `zoho_sync_log`
--

CREATE TABLE `zoho_sync_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `entity_type` enum('payment','expense','tenant','property') NOT NULL,
  `entity_id` int(10) UNSIGNED NOT NULL,
  `status` enum('success','error','pending') DEFAULT 'pending',
  `external_id` varchar(255) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `synced_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `facebook_listings`
--
ALTER TABLE `facebook_listings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unit_facebook` (`unit_id`,`facebook_listing_id`),
  ADD KEY `unit_id` (`unit_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `file_uploads`
--
ALTER TABLE `file_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_uploaded_by` (`uploaded_by`),
  ADD KEY `idx_filename` (`filename`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_unit_id` (`unit_id`),
  ADD KEY `idx_property_id` (`property_id`);

--
-- Indexes for table `leases`
--
ALTER TABLE `leases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unit_id` (`unit_id`),
  ADD KEY `tenant_id` (`tenant_id`);

--
-- Indexes for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unit_id` (`unit_id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `idx_tenant_id` (`tenant_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_requested_date` (`requested_date`);

--
-- Indexes for table `manual_mpesa_payments`
--
ALTER TABLE `manual_mpesa_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_code` (`transaction_code`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indexes for table `mpesa_transactions`
--
ALTER TABLE `mpesa_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mpesa_payment` (`payment_id`);

--
-- Indexes for table `odoo_sync_log`
--
ALTER TABLE `odoo_sync_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `entity` (`entity_type`,`entity_id`),
  ADD KEY `status` (`status`),
  ADD KEY `synced_at` (`synced_at`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lease_id` (`lease_id`),
  ADD KEY `idx_utility_id` (`utility_id`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indexes for table `quickbooks_sync_log`
--
ALTER TABLE `quickbooks_sync_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `entity` (`entity_type`,`entity_id`),
  ADD KEY `status` (`status`),
  ADD KEY `synced_at` (`synced_at`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_subscription_plan` (`plan_id`);

--
-- Indexes for table `subscription_payments`
--
ALTER TABLE `subscription_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscription_payments_user_id_fk` (`user_id`),
  ADD KEY `subscription_payments_subscription_id_fk` (`subscription_id`);

--
-- Indexes for table `subscription_payment_logs`
--
ALTER TABLE `subscription_payment_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- Indexes for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tenants`
--
ALTER TABLE `tenants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_tenants_property` (`property_id`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_unit_number` (`property_id`,`unit_number`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `tenant_id` (`tenant_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `utilities`
--
ALTER TABLE `utilities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unit_id` (`unit_id`);

--
-- Indexes for table `utility_rates`
--
ALTER TABLE `utility_rates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `utility_readings`
--
ALTER TABLE `utility_readings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utility_id` (`utility_id`);

--
-- Indexes for table `zoho_sync_log`
--
ALTER TABLE `zoho_sync_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `entity` (`entity_type`,`entity_id`),
  ADD KEY `status` (`status`),
  ADD KEY `synced_at` (`synced_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `facebook_listings`
--
ALTER TABLE `facebook_listings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `file_uploads`
--
ALTER TABLE `file_uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `leases`
--
ALTER TABLE `leases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `manual_mpesa_payments`
--
ALTER TABLE `manual_mpesa_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `mpesa_transactions`
--
ALTER TABLE `mpesa_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `odoo_sync_log`
--
ALTER TABLE `odoo_sync_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `quickbooks_sync_log`
--
ALTER TABLE `quickbooks_sync_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `subscription_payments`
--
ALTER TABLE `subscription_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `subscription_payment_logs`
--
ALTER TABLE `subscription_payment_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tenants`
--
ALTER TABLE `tenants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `utilities`
--
ALTER TABLE `utilities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `utility_rates`
--
ALTER TABLE `utility_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `utility_readings`
--
ALTER TABLE `utility_readings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `zoho_sync_log`
--
ALTER TABLE `zoho_sync_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `facebook_listings`
--
ALTER TABLE `facebook_listings`
  ADD CONSTRAINT `fk_facebook_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `file_uploads`
--
ALTER TABLE `file_uploads`
  ADD CONSTRAINT `fk_file_uploads_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leases`
--
ALTER TABLE `leases`
  ADD CONSTRAINT `fk_leases_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `manual_mpesa_payments`
--
ALTER TABLE `manual_mpesa_payments`
  ADD CONSTRAINT `fk_manual_mpesa_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_manual_mpesa_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `mpesa_transactions`
--
ALTER TABLE `mpesa_transactions`
  ADD CONSTRAINT `fk_mpesa_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mpesa_transactions_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `subscription_payments` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_lease` FOREIGN KEY (`lease_id`) REFERENCES `leases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payments_utility` FOREIGN KEY (`utility_id`) REFERENCES `utilities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `fk_subscription_plan` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `subscription_payments`
--
ALTER TABLE `subscription_payments`
  ADD CONSTRAINT `subscription_payments_subscription_id_fk` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`),
  ADD CONSTRAINT `subscription_payments_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `subscription_payment_logs`
--
ALTER TABLE `subscription_payment_logs`
  ADD CONSTRAINT `subscription_payment_logs_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `subscription_payments` (`id`);

--
-- Constraints for table `units`
--
ALTER TABLE `units`
  ADD CONSTRAINT `fk_units_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
