-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 07, 2026 at 10:12 PM
-- Server version: 10.11.15-MariaDB-cll-lve
-- PHP Version: 8.4.17

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
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('asset','liability','equity','revenue','expense') NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `code`, `name`, `type`, `parent_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '1000', 'Cash', 'asset', NULL, 1, '2026-02-04 08:46:32', '2026-02-04 08:46:32'),
(2, '1100', 'Accounts Receivable', 'asset', NULL, 1, '2026-02-04 08:46:32', '2026-02-04 08:46:32'),
(3, '2000', 'Accounts Payable', 'liability', NULL, 1, '2026-02-04 08:46:32', '2026-02-04 08:46:32'),
(4, '3000', 'Owner\'s Equity', 'equity', NULL, 1, '2026-02-04 08:46:32', '2026-02-04 08:46:32'),
(5, '4000', 'Rental Income', 'revenue', NULL, 1, '2026-02-04 08:46:32', '2026-02-04 08:46:32'),
(6, '5000', 'Maintenance Expense', 'expense', NULL, 1, '2026-02-04 08:46:32', '2026-02-04 08:46:32');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `action` varchar(150) NOT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `property_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(64) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_message_replies`
--

CREATE TABLE `contact_message_replies` (
  `id` int(11) NOT NULL,
  `contact_message_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reply_message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT 0.00,
  `property_id` int(11) DEFAULT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'general',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_payments`
--

CREATE TABLE `employee_payments` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `pay_date` date NOT NULL,
  `payment_method` enum('cash','check','bank_transfer','card','mpesa','other') NOT NULL DEFAULT 'cash',
  `source_of_funds` enum('rent_balance','cash','bank','mpesa','owner_funds','other') NOT NULL DEFAULT 'cash',
  `notes` text DEFAULT NULL,
  `expense_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esign_requests`
--

CREATE TABLE `esign_requests` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `requester_user_id` int(11) NOT NULL,
  `recipient_type` enum('user','tenant') NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `token` varchar(64) NOT NULL,
  `status` enum('pending','signed','declined','expired') NOT NULL DEFAULT 'pending',
  `expires_at` datetime DEFAULT NULL,
  `signed_at` datetime DEFAULT NULL,
  `declined_at` datetime DEFAULT NULL,
  `signer_name` varchar(150) DEFAULT NULL,
  `signature_image` longtext DEFAULT NULL,
  `signature_ip` varchar(64) DEFAULT NULL,
  `signature_user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `signature_type` enum('draw','upload','initials') DEFAULT NULL,
  `initials` varchar(50) DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `signed_document_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `property_id` int(11) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `payment_method` enum('cash','check','bank_transfer','card','mpesa','other') NOT NULL DEFAULT 'cash',
  `source_of_funds` enum('rent_balance','cash','bank','mpesa','owner_funds','other') NOT NULL DEFAULT 'cash',
  `notes` text DEFAULT NULL,
  `attachments` longtext DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Table structure for table `file_shares`
--

CREATE TABLE `file_shares` (
  `id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `recipient_type` enum('user','tenant') NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
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

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `number` varchar(50) NOT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `issue_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('draft','sent','partial','paid','void') NOT NULL DEFAULT 'sent',
  `notes` text DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) DEFAULT NULL,
  `tax_amount` decimal(15,2) DEFAULT NULL,
  `total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `posted_at` datetime DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `realtor_listings`
--

CREATE TABLE `realtor_listings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `listing_type` enum('plot','commercial_apartment','residential_apartment') NOT NULL DEFAULT 'plot',
  `location` varchar(255) NOT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','inactive','sold','rented') NOT NULL DEFAULT 'active',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `realtor_clients`
--

CREATE TABLE `realtor_clients` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `realtor_leads`
--

CREATE TABLE `realtor_leads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `status` enum('new','contacted','won','lost') NOT NULL DEFAULT 'new',
  `notes` text DEFAULT NULL,
  `converted_client_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(15,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `journal_entries`
--

CREATE TABLE `journal_entries` (
  `id` int(11) NOT NULL,
  `entry_date` date NOT NULL,
  `account_id` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `user_id` int(11) DEFAULT NULL,
  `property_id` int(11) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_type` enum('user','tenant') NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_type` enum('user','tenant') NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `body` text NOT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Table structure for table `notices`
--

CREATE TABLE `notices` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `property_id` int(11) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `pinned` tinyint(1) NOT NULL DEFAULT 0,
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
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
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

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL,
  `owner_user_id` int(10) UNSIGNED DEFAULT NULL,
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

INSERT INTO `payment_methods` (`id`, `owner_user_id`, `name`, `type`, `description`, `is_active`, `details`, `settings`, `created_at`, `updated_at`) VALUES
(1, 1, 'M-Pesa', 'mpesa_manual', 'Mobile money payment via M-Pesa', 1, '{\"mpesa_method\":\"till\",\"till_number\":\"9151051\"}', NULL, '2025-09-28 14:31:24', '2026-02-01 08:48:04'),
(2, 1, 'Bank Transfer', 'bank_transfer', 'Direct bank transfer payment', 1, NULL, NULL, '2025-09-28 14:31:24', '2026-01-29 08:12:23'),
(3, 1, 'Cash Payment', 'cash', 'Cash payment at office', 1, NULL, NULL, '2025-09-28 14:31:24', '2026-01-29 08:12:23'),
(4, 1, 'Cheque Payment', 'cheque', 'Payment via cheque', 1, NULL, NULL, '2025-09-28 14:31:24', '2026-01-29 08:12:23'),
(5, 1, 'Credit/Debit Card', 'card', 'Payment via credit or debit card', 0, '[]', NULL, '2025-09-28 14:31:24', '2026-02-01 08:47:10'),
(6, 1, 'M-Pesa STK Push', 'mpesa_stk', 'Mobile money payment via M-Pesa (STK Push)', 0, '{\"consumer_key\":\"SC7pK4ORAvZOUElAuEXCQMGmGutLdbgDsUcaYwJOf7L9Buqw\",\"consumer_secret\":\"sXhE8AmVamipNKooYAIEsZGhDqWHkHyOGbfwXDdj6nqXiQZecvKr016jpmfAgh21\",\"shortcode\":\"174379\",\"passkey\":\"bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919\"}', NULL, '2025-09-28 15:08:48', '2026-02-01 08:47:22');

-- --------------------------------------------------------

--
-- Table structure for table `payment_method_properties`
--

CREATE TABLE `payment_method_properties` (
  `id` int(11) NOT NULL,
  `payment_method_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `caretaker_name` varchar(255) DEFAULT NULL,
  `caretaker_contact` varchar(255) DEFAULT NULL,
  `year_built` int(11) DEFAULT NULL,
  `total_area` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `owner_id` int(11) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `caretaker_user_id` int(11) DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of property image filenames',
  `documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of property document filenames'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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
(12, 'site_email', 'rentsmart@timestentechnologies.co.ke', '2025-06-24 14:11:34', '2025-11-26 05:13:56'),
(13, 'site_phone', '', '2025-06-24 14:11:34', '2025-06-24 14:11:34'),
(14, 'site_address', '', '2025-06-24 14:11:34', '2025-06-24 14:11:34'),
(15, 'site_footer_text', '', '2025-06-24 14:11:34', '2025-06-24 14:11:34'),
(16, 'site_analytics_code', '', '2025-06-24 14:11:34', '2025-06-24 14:11:34'),
(17, 'maintenance_mode', '0', '2025-06-24 14:11:34', '2025-06-24 14:11:34'),
(18, 'timezone', 'Africa/Abidjan', '2025-06-24 14:11:34', '2025-06-24 14:17:30'),
(19, 'date_format', 'Y-m-d', '2025-06-24 14:11:34', '2025-06-24 14:11:34'),
(20, 'currency', 'USD', '2025-06-24 14:11:34', '2025-06-24 14:11:34'),
(22, 'mpesa_consumer_key', 'SC7pK4ORAvZOUElAuEXCQMGmGutLdbgDsUcaYwJOf7L9Buqw', '2025-07-02 11:22:54', '2025-10-15 09:31:48'),
(23, 'mpesa_consumer_secret', 'sXhE8AmVamipNKooYAIEsZGhDqWHkHyOGbfwXDdj6nqXiQZecvKr016jpmfAgh21', '2025-07-02 11:22:54', '2025-10-15 09:31:48'),
(24, 'mpesa_shortcode', '174379', '2025-07-02 11:22:54', '2025-10-15 13:10:25'),
(25, 'mpesa_passkey', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919', '2025-07-02 11:22:54', '2025-10-15 13:11:12'),
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
(53, 'odoo_uid', '', '2025-10-10 19:51:02', '2025-10-10 19:51:02'),
(54, 'ai_enabled', '1', '2026-02-01 11:42:52', '2026-02-01 11:43:02'),
(55, 'ai_provider', 'google', '2026-02-01 11:42:52', '2026-02-01 11:46:17'),
(56, 'openai_api_key', '', '2026-02-01 11:42:52', '2026-02-01 11:42:52'),
(57, 'openai_model', 'gpt-4.1-mini', '2026-02-01 11:42:52', '2026-02-01 11:42:52'),
(58, 'google_api_key', 'AIzaSyCSGur1P3FHU9yGNyUYl2m1uciVQYWIWao', '2026-02-01 11:42:52', '2026-02-01 11:46:17'),
(59, 'google_model', 'gemini-3-flash-preview', '2026-02-01 11:42:52', '2026-02-01 11:42:52'),
(60, 'ai_system_prompt', 'You are RentSmart Support AI. Help users with property management tasks and app guidance.', '2026-02-01 11:42:52', '2026-02-01 11:42:52');

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

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `user_id`, `plan_id`, `plan_type`, `status`, `trial_ends_at`, `current_period_starts_at`, `current_period_ends_at`, `created_at`, `updated_at`) VALUES
(44, 33, 1, 'Basic', 'trialing', '2026-02-01 17:35:33', '2025-12-03 17:35:33', '2026-02-01 17:35:33', '2025-12-03 17:35:33', '2025-12-03 17:35:33'),
(45, 34, 1, 'Basic', 'trialing', '2026-02-03 14:14:11', '2025-12-05 14:14:11', '2026-02-03 14:14:11', '2025-12-05 14:14:11', '2025-12-05 14:14:11'),
(46, 35, 1, 'Basic', 'trialing', '2026-02-04 06:31:47', '2025-12-06 06:31:47', '2026-02-04 06:31:47', '2025-12-06 06:31:47', '2025-12-06 06:31:47'),
(49, 38, 1, 'Basic', 'trialing', '2026-01-29 08:47:23', '2026-01-22 08:47:23', '2026-01-29 08:47:23', '2026-01-22 08:47:23', '2026-01-22 08:47:23'),
(50, 39, 1, 'Basic', 'trialing', '2026-02-02 10:43:00', '2026-01-26 10:43:00', '2026-02-02 10:43:00', '2026-01-26 10:43:20', '2026-01-31 03:30:20');

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
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `property_limit` int(11) DEFAULT NULL,
  `unit_limit` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscription_plans`
--

INSERT INTO `subscription_plans` (`id`, `name`, `price`, `description`, `features`, `created_at`, `updated_at`, `property_limit`, `unit_limit`) VALUES
(1, 'Starter', 3500.00, 'For individual landlords managing a small portfolio replacing spreadsheets.', 'Property & unit profiles with occupancy tracking\r\nTenant profiles & lease details\r\nManual rent tracking, arrears & rent summaries\r\nMaintenance request logging & status tracking\r\nAssign caretaker per property\r\nStaff records (caretaker, gardener, fundi, cleaner)\r\nStaff payment records\r\nBasic property expense tracking\r\nTenant portal (rent balance & statements)\r\nDocument upload (leases, agreements)\r\nEmail support ', '2025-06-28 18:01:30', '2026-02-05 17:45:23', 5, 100),
(2, 'Professional', 7000.00, 'For growing property management businesses', 'Everything in Starter\r\nPartial & advance payment handling\r\nReal-time arrears tracking\r\nSMS & email rent reminders\r\nPayment confirmation notifications\r\nTenant portal with statements & balances\r\nMaintenance requests with task assignment & follow-ups\r\nProperty-level expense tracking\r\nMonthly income & financial reports\r\nExport reports to Excel & PDF\r\nPriority email support', '2025-06-28 18:01:30', '2026-02-05 17:46:15', 20, 350),
(3, 'Premium', 15000.00, 'Complete solution for large property managers', 'Everything in Professional\r\nBulk property & tenant actions\r\nAdvanced vacancy & unit analytics\r\nExpense tracking with net income reports\r\nCustom date-range financial reporting\r\nAdvanced maintenance & task tracking\r\nStaff management & staff payment control\r\nBulk SMS & email messaging\r\nActivity logs & audit trails\r\nAccounting-ready exports (Excel, CSV, PDF)\r\nPriority Email & WhatsApp support\r\nAssisted onboarding', '2025-06-28 18:01:30', '2026-02-05 17:46:43', 50, 800),
(4, 'Enterprise', 50000.00, 'Large portfolios, real estate firms & institutional landlords.', '50+ properties with unlimited scale\r\nHigh-volume tenants & transactions\r\nCustom workflows & automation\r\nAPI access & system integrations\r\nCustom reports & dashboards\r\nAdvanced staff & payroll management\r\nDedicated account manager\r\nSLA & uptime guarantees\r\nDedicated onboarding & training\r\nPriority support & escalation\r\nBest for: Large portfolios, real estate firms & institutional landlords.', '2025-06-28 18:01:30', '2026-02-05 17:48:11', NULL, NULL);

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

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','landlord','agent','manager','caretaker') NOT NULL DEFAULT 'agent',
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

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `address`, `password`, `role`, `created_at`, `updated_at`, `is_subscribed`, `trial_ends_at`, `last_login_at`, `manager_id`) VALUES
(1, 'Timesten', 'timestentechnologies@gmail.com', NULL, NULL, '$2y$10$IEOMuSINp31OH2hvV9NAJeL4X48sWCrPyEtO.7GfsJyYAP0ViLQS2', 'admin', '2025-11-15 14:12:36', '2026-02-07 18:57:38', 0, '0000-00-00 00:00:00', '2026-02-07 18:57:38', 0),
(33, 'Daniel ojijo', 'ojijo@homesuniversal.com', NULL, NULL, '$2y$10$Jdh8zUH0LCJ0alY413bjHeVuQuBGabzufnS0u9lbJA9hGt9WWyHDW', 'agent', '2025-12-03 17:35:33', '2025-12-03 17:35:33', 1, '2026-02-01 17:35:33', NULL, NULL),
(34, 'Kevin', 'kevinnderitu@homesuniversal.com', NULL, NULL, '$2y$10$w2HYU.0y554oKNEQeNWwzOWN980HQv2bPj0B.8lKdTndRVFjPNmwW', 'agent', '2025-12-05 14:14:11', '2025-12-06 06:31:09', 1, '2026-02-03 14:14:11', '2025-12-06 06:31:09', NULL),
(35, 'Kevin', 'kevinnderitu96@gmail.com', NULL, NULL, '$2y$10$pj0xeY6SaR6Vk6jRC53vZOeZzj4ALBr/mVyBrehQ.FdzlOFLFcceu', 'agent', '2025-12-06 06:31:47', '2025-12-06 06:31:47', 1, '2026-02-04 06:31:47', NULL, NULL),
(38, 'solomon Elondi', 'solomakanga@gmail.com', '0758576757', 'Njoro molo highway', '$2y$10$0k970g0VG/vO7SugcpUxROUHxT5.UryP3QWYOO4.S.tE1ekmvazAO', 'landlord', '2026-01-22 08:47:23', '2026-01-22 08:47:23', 1, '2026-01-29 08:47:23', NULL, NULL),
(39, 'TED WILLIAMS', 'williamsted1001@gmail.com', '0798125596', '30100', '$2y$10$6xOa9GNZPeYEy3FLr4MniOOlbTbWT2AWz4qIQ6zXuCvZeAoipQkP2', 'landlord', '2026-01-26 10:43:20', '2026-01-30 19:13:11', 1, '2026-02-02 10:43:20', '2026-01-30 19:13:11', NULL);

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
  `billing_method` enum('metered','flat_rate') NOT NULL DEFAULT 'flat_rate',
  `user_id` int(11) DEFAULT NULL,
  `property_id` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_parent` (`parent_id`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_property_id` (`property_id`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `contact_message_replies`
--
ALTER TABLE `contact_message_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_message` (`contact_message_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee_payments`
--
ALTER TABLE `employee_payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `esign_requests`
--
ALTER TABLE `esign_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_requester` (`requester_user_id`),
  ADD KEY `idx_recipient` (`recipient_type`,`recipient_id`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `facebook_listings`
--
ALTER TABLE `facebook_listings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unit_facebook` (`unit_id`,`facebook_listing_id`),
  ADD KEY `unit_id` (`unit_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `file_shares`
--
ALTER TABLE `file_shares`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_share` (`file_id`,`recipient_type`,`recipient_id`),
  ADD KEY `idx_file` (`file_id`),
  ADD KEY `idx_recipient` (`recipient_type`,`recipient_id`);

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
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `number` (`number`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_issue_date` (`issue_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_archived_at` (`archived_at`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_invoice` (`invoice_id`);

--
-- Indexes for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_date` (`entry_date`),
  ADD KEY `idx_account` (`account_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`);

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
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sender` (`sender_type`,`sender_id`),
  ADD KEY `idx_receiver` (`receiver_type`,`receiver_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `mpesa_transactions`
--
ALTER TABLE `mpesa_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mpesa_payment` (`payment_id`);

--
-- Indexes for table `notices`
--
ALTER TABLE `notices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_prop` (`property_id`),
  ADD KEY `idx_unit` (`unit_id`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `odoo_sync_log`
--
ALTER TABLE `odoo_sync_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `entity` (`entity_type`,`entity_id`),
  ADD KEY `status` (`status`),
  ADD KEY `synced_at` (`synced_at`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_token` (`token`);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment_methods_owner_user_id` (`owner_user_id`);

--
-- Indexes for table `payment_method_properties`
--
ALTER TABLE `payment_method_properties`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_method_property` (`payment_method_id`,`property_id`),
  ADD KEY `idx_property` (`property_id`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `manager_id` (`manager_id`),
  ADD KEY `idx_properties_agent_id` (`agent_id`);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_utility_rates_user_id` (`user_id`),
  ADD KEY `idx_utility_rates_type_user_from` (`utility_type`,`user_id`,`effective_from`),
  ADD KEY `idx_utility_rates_type_from` (`utility_type`,`effective_from`);

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
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1922;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `contact_message_replies`
--
ALTER TABLE `contact_message_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `employee_payments`
--
ALTER TABLE `employee_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `esign_requests`
--
ALTER TABLE `esign_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `facebook_listings`
--
ALTER TABLE `facebook_listings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `file_shares`
--
ALTER TABLE `file_shares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `file_uploads`
--
ALTER TABLE `file_uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `journal_entries`
--
ALTER TABLE `journal_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `leases`
--
ALTER TABLE `leases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `manual_mpesa_payments`
--
ALTER TABLE `manual_mpesa_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `mpesa_transactions`
--
ALTER TABLE `mpesa_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `notices`
--
ALTER TABLE `notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `odoo_sync_log`
--
ALTER TABLE `odoo_sync_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `payment_method_properties`
--
ALTER TABLE `payment_method_properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `quickbooks_sync_log`
--
ALTER TABLE `quickbooks_sync_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `subscription_payments`
--
ALTER TABLE `subscription_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `subscription_payment_logs`
--
ALTER TABLE `subscription_payment_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tenants`
--
ALTER TABLE `tenants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `utilities`
--
ALTER TABLE `utilities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `utility_rates`
--
ALTER TABLE `utility_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `utility_readings`
--
ALTER TABLE `utility_readings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

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
  ADD CONSTRAINT `fk_manual_mpesa_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_lease` FOREIGN KEY (`lease_id`) REFERENCES `leases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payments_utility` FOREIGN KEY (`utility_id`) REFERENCES `utilities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `properties`
--
ALTER TABLE `properties`
  ADD CONSTRAINT `fk_properties_agent_id` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
