-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 12, 2026 at 04:47 AM
-- Server version: 11.8.6-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u233781988_mygatebell`
--

-- --------------------------------------------------------

--
-- Table structure for table `amenities`
--

CREATE TABLE `amenities` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `capacity` int(11) DEFAULT 1,
  `booking_fee` decimal(10,2) DEFAULT 0.00,
  `cancellation_fee` decimal(10,2) DEFAULT 0.00,
  `cancellation_policy` text DEFAULT NULL,
  `society_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `amenity_bookings`
--

CREATE TABLE `amenity_bookings` (
  `id` int(11) NOT NULL,
  `amenity_id` int(11) DEFAULT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('requested','confirmed','cancelled','completed') DEFAULT 'requested',
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('pending','paid','refunded') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `society_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `target_group_id` int(11) DEFAULT NULL,
  `send_via` enum('app','email','sms','all') DEFAULT 'app',
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `is_draft` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_cost` decimal(10,2) DEFAULT NULL,
  `current_value` decimal(10,2) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `status` enum('active','maintenance','disposed') DEFAULT 'active',
  `assigned_to` int(11) DEFAULT NULL,
  `society_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_categories`
--

CREATE TABLE `asset_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `society_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buildings`
--

CREATE TABLE `buildings` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `society_id` int(11) DEFAULT NULL,
  `total_floors` int(11) DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `buildings`
--

INSERT INTO `buildings` (`id`, `name`, `society_id`, `total_floors`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Building A', 1, 6, 'Main Residential building', '2025-12-16 12:28:30', '2025-12-16 12:28:30'),
(2, 'Building B', 1, 8, 'Premium Residential building', '2025-12-17 07:10:01', '2025-12-17 07:10:01'),
(3, 'Emrald', 2, 8, 'Premium Residential building', '2025-12-18 12:19:16', '2025-12-18 12:19:16'),
(4, 'Topaz', 2, 6, 'Main Residential building', '2025-12-18 12:24:51', '2025-12-18 12:24:51'),
(5, 'Crystal', 2, 5, 'Behind Emrald Residential building', '2025-12-18 12:25:34', '2025-12-18 12:25:34'),
(6, 'Amber', 2, 5, 'Residential building for Bacholers', '2025-12-18 12:25:56', '2025-12-18 12:25:56');

-- --------------------------------------------------------

--
-- Table structure for table `charge_heads`
--

CREATE TABLE `charge_heads` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `charge_type` enum('fixed','per_area','per_person','slab') NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `slab_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`slab_details`)),
  `gst_rate` decimal(5,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `society_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chart_of_accounts`
--

CREATE TABLE `chart_of_accounts` (
  `id` int(11) NOT NULL,
  `account_code` varchar(20) DEFAULT NULL,
  `account_name` varchar(100) NOT NULL,
  `account_type` enum('asset','liability','equity','income','expense') NOT NULL,
  `parent_account_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `society_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `covid_guidelines`
--

CREATE TABLE `covid_guidelines` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `society_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `covid_test_results`
--

CREATE TABLE `covid_test_results` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `test_type` enum('rt-pcr','rapid_antigen','other') DEFAULT NULL,
  `test_result` enum('positive','negative') DEFAULT NULL,
  `test_date` date DEFAULT NULL,
  `report_url` varchar(255) DEFAULT NULL,
  `society_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `covid_vaccination_records`
--

CREATE TABLE `covid_vaccination_records` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `vaccine_name` varchar(100) DEFAULT NULL,
  `dose_number` int(11) DEFAULT NULL,
  `vaccination_date` date DEFAULT NULL,
  `certificate_url` varchar(255) DEFAULT NULL,
  `society_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `data_privacy_requests`
--

CREATE TABLE `data_privacy_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `request_type` enum('data_export','data_delete') NOT NULL,
  `status` enum('pending','processing','completed','rejected') DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `recipient_email` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `status` enum('sent','failed','delivered') DEFAULT 'sent',
  `provider_response` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emergency_contacts`
--

CREATE TABLE `emergency_contacts` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contact_type` enum('police','fire','ambulance','hospital','other') NOT NULL,
  `society_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `family_members`
--

CREATE TABLE `family_members` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `relation` varchar(50) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `family_members`
--

INSERT INTO `family_members` (`id`, `resident_id`, `name`, `relation`, `phone`, `is_active`, `image_url`, `created_at`) VALUES
(1, 4, 'Rohini Khule', 'Mother', '9325821320', 1, 'https://example.com/photo.jpg', '2025-12-20 09:46:33'),
(2, 4, 'Balkrushna Khule', 'Father', '9511634826', 1, 'https://example.com/photo.jpg', '2025-12-20 09:46:58'),
(3, 4, 'Gitanjali Khule', 'Sister', '9359038360', 1, 'https://example.com/photo.jpg', '2025-12-20 09:47:22'),
(4, 4, 'Gitanjali Khule', 'Sister', '9359038360', 1, 'https://example.com/photo.jpg', '2025-12-20 09:50:45'),
(5, 4, 'Dummy Family member', 'Sister', '9087678965', 0, 'https://example.com/photo.jpg', '2025-12-20 09:51:52');

-- --------------------------------------------------------

--
-- Table structure for table `financial_years`
--

CREATE TABLE `financial_years` (
  `id` int(11) NOT NULL,
  `year_start` date NOT NULL,
  `year_end` date NOT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `society_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `flats`
--

CREATE TABLE `flats` (
  `id` int(11) NOT NULL,
  `flat_number` varchar(20) NOT NULL,
  `floor_number` varchar(10) DEFAULT NULL,
  `building_id` int(11) DEFAULT NULL,
  `area_sqft` decimal(10,2) DEFAULT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `society_id` int(11) DEFAULT NULL,
  `is_occupied` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_role` enum('owner','renting_family','renting_flatmates') NOT NULL,
  `occupancy_status` enum('residing','let_out','empty') DEFAULT NULL,
  `document_url` varchar(255) DEFAULT NULL,
  `verification_status` enum('pending','verified','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `flats`
--

INSERT INTO `flats` (`id`, `flat_number`, `floor_number`, `building_id`, `area_sqft`, `owner_id`, `tenant_id`, `society_id`, `is_occupied`, `created_at`, `updated_at`, `user_role`, `occupancy_status`, `document_url`, `verification_status`) VALUES
(1, '101', '1', 1, 1050.00, 4, NULL, 1, 1, '2025-12-17 07:07:04', '2025-12-17 12:33:39', 'owner', 'residing', '../uploads/verification/6942a32336a94_1765974819.pdf', 'pending'),
(2, '102', '1', 1, 950.00, 4, NULL, 1, 1, '2025-12-17 07:07:04', '2025-12-17 12:41:09', 'owner', 'residing', '../uploads/verification/6942a4e559832_1765975269.pdf', 'pending'),
(3, '103', '1', 1, 1100.00, 4, NULL, 1, 1, '2025-12-17 07:07:04', '2025-12-22 07:00:14', 'owner', 'residing', './uploads/verification/6948ec7e2269f_1766386814.pdf', 'pending'),
(4, '104', '1', 1, 1000.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(5, '105', '1', 1, 1150.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(6, '201', '2', 1, 1050.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(7, '202', '2', 1, 950.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(8, '203', '2', 1, 1100.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(9, '204', '2', 1, 1000.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(10, '205', '2', 1, 1150.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(11, '301', '3', 1, 1050.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(12, '302', '3', 1, 950.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(13, '303', '3', 1, 1100.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(14, '304', '3', 1, 1000.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(15, '305', '3', 1, 1150.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(16, '401', '4', 1, 1050.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(17, '402', '4', 1, 950.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(18, '403', '4', 1, 1100.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(19, '404', '4', 1, 1000.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(20, '405', '4', 1, 1150.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(21, '501', '5', 1, 1050.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(22, '502', '5', 1, 950.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(23, '503', '5', 1, 1100.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(24, '504', '5', 1, 1000.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(25, '505', '5', 1, 1150.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(26, '601', '6', 1, 1050.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(27, '602', '6', 1, 950.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(28, '603', '6', 1, 1100.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(29, '604', '6', 1, 1000.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(30, '605', '6', 1, 1150.00, NULL, NULL, 1, 0, '2025-12-17 07:07:04', '2025-12-17 07:07:04', 'owner', NULL, NULL, 'pending'),
(31, '101', '1', 2, 1200.00, NULL, NULL, 1, 0, '2025-12-17 07:10:18', '2025-12-17 07:10:18', 'owner', NULL, NULL, 'pending'),
(32, '102', '1', 2, 1200.00, NULL, NULL, 1, 0, '2025-12-17 07:10:18', '2025-12-17 07:10:18', 'owner', NULL, NULL, 'pending'),
(33, '103', '1', 2, 1200.00, NULL, NULL, 1, 0, '2025-12-17 07:10:18', '2025-12-17 07:10:18', 'owner', NULL, NULL, 'pending'),
(34, '104', '1', 2, 1200.00, NULL, NULL, 1, 0, '2025-12-17 07:10:18', '2025-12-17 07:10:18', 'owner', NULL, NULL, 'pending'),
(35, '201', '2', 2, 1150.00, NULL, NULL, 1, 0, '2025-12-17 07:10:18', '2025-12-17 07:10:18', 'owner', NULL, NULL, 'pending'),
(36, '202', '2', 2, 1150.00, NULL, NULL, 1, 0, '2025-12-17 07:10:18', '2025-12-17 07:10:18', 'owner', NULL, NULL, 'pending'),
(37, '203', '2', 2, 1150.00, NULL, NULL, 1, 0, '2025-12-17 07:10:18', '2025-12-17 07:10:18', 'owner', NULL, NULL, 'pending'),
(38, '204', '2', 2, 1150.00, NULL, NULL, 1, 0, '2025-12-17 07:10:18', '2025-12-17 07:10:18', 'owner', NULL, NULL, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `society_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group_members`
--

CREATE TABLE `group_members` (
  `id` int(11) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `role` enum('member','moderator','admin') DEFAULT 'member',
  `joined_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `quantity_in_stock` decimal(10,2) DEFAULT 0.00,
  `reorder_level` decimal(10,2) DEFAULT 0.00,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `supplier` varchar(100) DEFAULT NULL,
  `society_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `transaction_type` enum('in','out') NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `reference_type` enum('purchase','consumption','transfer','adjustment') DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `flat_id` int(11) DEFAULT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `society_id` int(11) DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `total_gst` decimal(10,2) DEFAULT 0.00,
  `total_discount` decimal(10,2) DEFAULT 0.00,
  `arrears_amount` decimal(10,2) DEFAULT 0.00,
  `fine_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('draft','sent','partially_paid','paid','overdue','cancelled') DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `charge_head_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT 1.00,
  `unit_price` decimal(10,2) NOT NULL,
  `gst_rate` decimal(5,2) DEFAULT 0.00,
  `gst_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marketplace_categories`
--

CREATE TABLE `marketplace_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_category_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `buyer_id` int(11) DEFAULT NULL,
  `society_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `shipping_address` text DEFAULT NULL,
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parking_spots`
--

CREATE TABLE `parking_spots` (
  `id` int(11) NOT NULL,
  `spot_number` varchar(20) NOT NULL,
  `spot_type` enum('resident','visitor','disabled','vip') DEFAULT 'resident',
  `is_occupied` tinyint(1) DEFAULT 0,
  `vehicle_id` int(11) DEFAULT NULL,
  `society_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `society_id` int(11) DEFAULT NULL,
  `payment_method` enum('upi','net_banking','credit_card','debit_card','cash','cheque','bank_transfer') NOT NULL,
  `payment_gateway` varchar(50) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `transaction_status` enum('pending','success','failed','refunded') DEFAULT 'pending',
  `payment_date` timestamp NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pets`
--

CREATE TABLE `pets` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `pet_type_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `breed` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `vaccination_status` enum('up_to_date','pending','not_vaccinated') DEFAULT 'pending',
  `image_url` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `society_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pets`
--

INSERT INTO `pets` (`id`, `resident_id`, `pet_type_id`, `name`, `breed`, `age`, `weight`, `vaccination_status`, `image_url`, `notes`, `society_id`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 4, 1, 'Bruno', 'German Shepherd', 3, 25.50, 'up_to_date', '', '', 1, 1, '2025-12-17 10:31:50', '2025-12-17 10:31:50');

-- --------------------------------------------------------

--
-- Table structure for table `pet_types`
--

CREATE TABLE `pet_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pet_types`
--

INSERT INTO `pet_types` (`id`, `name`, `description`) VALUES
(1, 'Dog', 'German Shepherd');

-- --------------------------------------------------------

--
-- Table structure for table `polls`
--

CREATE TABLE `polls` (
  `id` int(11) NOT NULL,
  `question` text NOT NULL,
  `poll_type` enum('public','secret') DEFAULT 'public',
  `society_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `starts_at` timestamp NULL DEFAULT current_timestamp(),
  `ends_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `poll_options`
--

CREATE TABLE `poll_options` (
  `id` int(11) NOT NULL,
  `poll_id` int(11) DEFAULT NULL,
  `option_text` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `poll_votes`
--

CREATE TABLE `poll_votes` (
  `id` int(11) NOT NULL,
  `poll_id` int(11) DEFAULT NULL,
  `option_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `voted_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount_price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `min_order_quantity` int(11) DEFAULT 1,
  `max_order_quantity` int(11) DEFAULT NULL,
  `image_urls` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`image_urls`)),
  `seller_id` int(11) DEFAULT NULL,
  `society_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receipts`
--

CREATE TABLE `receipts` (
  `id` int(11) NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `society_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `receipt_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `society_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `permission_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_alerts`
--

CREATE TABLE `security_alerts` (
  `id` int(11) NOT NULL,
  `alert_type` enum('suspicious_activity','unauthorized_access','emergency','other') NOT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `reported_by` int(11) DEFAULT NULL,
  `society_id` int(11) DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `image_url` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `provider_name` varchar(100) DEFAULT NULL,
  `contact_info` varchar(255) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT NULL,
  `society_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_bookings`
--

CREATE TABLE `service_bookings` (
  `id` int(11) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `status` enum('requested','confirmed','cancelled','completed') DEFAULT 'requested',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_categories`
--

CREATE TABLE `service_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_logs`
--

CREATE TABLE `sms_logs` (
  `id` int(11) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `message` text NOT NULL,
  `status` enum('sent','failed','delivered') DEFAULT 'sent',
  `provider_response` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `societies`
--

CREATE TABLE `societies` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `towers` int(11) DEFAULT 1,
  `total_flats` int(11) DEFAULT 0,
  `admin_id` int(11) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(15) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `plan` enum('starter','professional','enterprise') DEFAULT 'starter',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('pending','approved','verified','rejected','suspended') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `societies`
--

INSERT INTO `societies` (`id`, `name`, `code`, `address`, `city`, `state`, `country`, `pincode`, `towers`, `total_flats`, `admin_id`, `contact_person`, `contact_phone`, `contact_email`, `plan`, `created_at`, `updated_at`, `status`) VALUES
(1, 'Blue Horizon Apartments', NULL, 'Sector 7, Aundh-Baner Link Road', 'Pune', 'Maharashtra', 'India', '411007', 1, 0, NULL, 'Ms. Priya Nair', '9456789012', 'contact@bluehorizonpune.com', 'starter', '2025-12-16 11:55:27', '2026-05-11 07:43:45', 'approved'),
(2, 'Riddhi-Siddhi Tiara', NULL, 'Sinhgad College Campus', 'Pune', 'Maharashtra', 'India', '411046', 1, 0, NULL, '8909478371', '9876543210', 'contact@riddhisiddhitiara.com', 'starter', '2025-12-16 11:55:39', '2025-12-16 11:55:39', 'pending'),
(3, 'Ocean Breeze Towers', NULL, 'Palm Beach Road, Sector 14, Vashi', 'Navi Mumbai', 'Maharashtra', 'India', '400703', 1, 0, NULL, 'Mr. Amit Shah', '9823456789', 'admin@oceanbreezetowers.com', 'starter', '2025-12-18 09:41:12', '2026-05-11 07:43:49', 'verified'),
(4, 'Prestige Lakeside Habitat', NULL, 'Whitefield Main Road, Varthur Lake', 'Bangalore', 'Karnataka', 'India', '560066', 1, 0, NULL, 'Ms. Shruti Rao', '8765432109', 'info@prestigelakeside.in', 'starter', '2025-12-18 09:41:28', '2025-12-18 09:41:28', 'pending'),
(8, 'Raheja Vivarea', NULL, 'Mahalaxmi East, Jacob Circle', 'Mumbai', 'Maharashtra', 'India', '400011', 1, 0, NULL, 'Mr. Rohan Kapoor', '8654321098', 'info@rahejaviarea.com', 'starter', '2025-12-18 09:42:04', '2025-12-18 09:42:04', 'pending'),
(9, 'Sobha Dream Acres', NULL, 'Balagere, Panathur Road', 'Bangalore', 'Karnataka', 'India', '560087', 1, 0, NULL, 'Mr. Anil Kumar', '7789012345', 'secretary@sobhadreamacres.in', 'starter', '2025-12-18 09:42:11', '2025-12-18 09:42:11', 'pending'),
(10, 'Eldeco Eden Park', NULL, 'Nehru Place Extension, Greater Kailash', 'New Delhi', 'Delhi', 'India', '110019', 1, 0, NULL, 'Mrs. Ritu Malhotra', '9013456782', 'admin@eldecoedenpark.co.in', 'starter', '2025-12-18 09:42:18', '2026-05-11 07:43:54', 'rejected'),
(11, 'Phoenix Golf Edge', NULL, 'Gachibowli Financial District', 'Hyderabad', 'Telangana', 'India', '500032', 1, 0, NULL, 'Mr. Suresh Babu', '8234567891', 'contact@phoenixgolfedge.com', 'starter', '2025-12-18 09:42:32', '2025-12-18 09:42:32', 'pending'),
(12, 'Adarsh Palm Retreat', NULL, 'Outer Ring Road, Bellandur', 'Bangalore', 'Karnataka', 'India', '560103', 1, 0, NULL, 'Ms. Divya Nair', '9456781234', 'manager@adarshpalmretreat.in', 'starter', '2025-12-18 09:42:40', '2025-12-18 09:42:40', 'pending'),
(13, 'Riverview Condominiums', NULL, '125 Hudson Yards, Midtown West', 'New York', 'NY', 'USA', '10001', 1, 0, NULL, 'Mr. James Carter', '+12125550188', 'admin@riverviewcondosnyc.com', 'starter', '2025-12-18 10:00:32', '2025-12-18 10:00:32', 'pending'),
(14, 'Skyline Vista Residences', NULL, '350 Fifth Avenue, Midtown', 'New York', 'NY', 'USA', '10118', 1, 0, NULL, 'Mr. Robert Hayes', '+12127363100', 'admin@skylinevista.nyc', 'starter', '2025-12-18 10:00:44', '2025-12-18 10:00:44', 'pending'),
(15, 'Thames Riverside Apartments', NULL, 'One St George Wharf, Vauxhall', 'London', '', 'United Kingdom', 'SW8 2LE', 1, 0, NULL, 'Ms. Olivia Bennett', '+442079285555', 'management@thamesriverside.co.uk', 'starter', '2025-12-18 10:00:51', '2026-05-11 07:43:57', 'suspended'),
(16, 'Aurora Bay Condominiums', NULL, '100 Queens Quay East', 'Toronto', 'Ontario', 'Canada', 'M5E 1V5', 1, 0, NULL, 'Mr. Liam Chen', '+14165550192', 'info@aurorabay.ca', 'starter', '2025-12-18 10:01:00', '2025-12-18 10:01:00', 'pending'),
(17, 'Harbourfront Elite Towers', NULL, '88 Harbour Street', 'Sydney', 'NSW', 'Australia', '2000', 1, 0, NULL, 'Ms. Emily Watson', '+61292641234', 'admin@harbourfrontelite.com.au', 'starter', '2025-12-18 10:01:11', '2025-12-18 10:01:11', 'pending'),
(18, 'Emerald Hill Residences', NULL, '8 Emerald Hill Road', 'Singapore', '', 'Singapore', '229307', 1, 0, NULL, 'Mr. Daniel Lim', '+6567338899', 'contact@emeraldhill.sg', 'starter', '2025-12-18 10:01:17', '2025-12-18 10:01:17', 'pending'),
(19, 'Palm Jumeirah Villas', NULL, 'Frond O, The Palm Jumeirah', 'Dubai', '', 'United Arab Emirates', '', 1, 0, NULL, 'Ms. Noor Ahmed', '+97145678901', 'manager@palmjumeirahvillas.ae', 'starter', '2025-12-18 10:01:23', '2025-12-18 10:01:23', 'pending'),
(20, 'Shibuya Sky Residences', NULL, '2-24-12 Shibuya, Shibuya-ku', 'Tokyo', '', 'Japan', '150-0002', 1, 0, NULL, 'Ms. Yumi Sato', '+81354231111', 'admin@shibuyasky.jp', 'starter', '2025-12-18 10:01:32', '2025-12-18 10:01:32', 'pending'),
(21, 'Montmartre Heights', NULL, '12 Rue de l\'Abreuvoir, 18th Arrondissement', 'Paris', '', 'France', '75018', 1, 0, NULL, 'Mr. Louis Moreau', '+33142645555', 'contact@montmartreheights.fr', 'starter', '2025-12-18 10:01:37', '2026-05-11 07:44:00', 'verified'),
(22, 'Barcelona Seafront Residences', NULL, 'Passeig Marítim 45, Barceloneta', 'Barcelona', 'Catalonia', 'Spain', '08003', 1, 0, NULL, 'Ms. Sofia Ramirez', '+34932956789', 'info@barcelonaseafront.es', 'starter', '2025-12-18 10:01:42', '2025-12-18 10:01:42', 'pending'),
(23, 'Chao Phraya Riverside Condo', NULL, '123 Charoen Krung Road, Bang Rak', 'Bangkok', '', 'Thailand', '10500', 1, 0, NULL, 'Mr. Thanawat Srisuk', '+6621234567', 'management@chaophrayariverside.th', 'starter', '2025-12-18 10:01:49', '2025-12-18 10:01:49', 'pending'),
(24, 'Ocean Pearl Residency', NULL, 'Plot 45, Sector 17, Palm Beach Road, Nerul', 'Navi Mumbai', 'Maharashtra', 'India', '400706', 1, 0, NULL, 'Mr. Vikram Desai', '+919820012345', 'admin@oceanpearlresidency.in', 'starter', '2025-12-18 10:28:23', '2026-05-11 07:44:05', 'approved'),
(25, 'Celestial Heights Towers', NULL, 'Off Veera Desai Road, Andheri West', 'Mumbai', 'Maharashtra', 'India', '400058', 1, 0, NULL, 'Ms. Priya Malhotra', '+912226789012', 'management@celestialheightsmumbai.com', 'starter', '2025-12-18 10:28:29', '2025-12-18 10:28:29', 'pending'),
(26, 'Imperial Crown Cooperative', NULL, 'Near Infinity Mall, New Link Road, Malad West', 'Mumbai', 'Maharashtra', 'India', '400064', 1, 0, NULL, 'Mr. Arjun Mehta', '+919867543210', 'secretary@imperialcrown.co.in', 'starter', '2025-12-18 10:28:40', '2025-12-18 10:28:40', 'pending'),
(27, 'Marine Vista Apartments', NULL, 'Carter Road, Bandra West', 'Mumbai', 'Maharashtra', 'India', '400050', 1, 0, NULL, 'Ms. Natasha Fernandes', '+919820156789', 'info@marinevistabanddra.in', 'starter', '2025-12-18 10:28:48', '2026-05-11 07:44:10', 'approved'),
(28, 'Sunset Bay Enclave', NULL, 'Juhu Tara Road, Near JW Marriott', 'Mumbai', 'Maharashtra', 'India', '400049', 1, 0, NULL, 'Mr. Rohan Kapoor', '+912226147890', 'contact@sunsetbayjuhu.com', 'starter', '2025-12-18 10:28:52', '2025-12-18 10:28:52', 'pending'),
(29, 'Greenwood Elite Residency', NULL, 'Sector 62, Near Fortis Hospital', 'Noida', 'Uttar Pradesh', 'India', '201301', 1, 0, NULL, 'Mr. Rajiv Sharma', '+919810023456', 'manager@greenwoodelite.in', 'starter', '2025-12-18 10:29:02', '2025-12-18 10:29:02', 'pending'),
(30, 'Heritage Grand Apartments', NULL, 'Golf Course Road, DLF Phase 5', 'Gurugram', 'Haryana', 'India', '122002', 1, 0, NULL, 'Ms. Anjali Verma', '+911244567890', 'info@heritagegrandgurugram.com', 'starter', '2025-12-18 10:29:08', '2025-12-18 10:29:08', 'pending'),
(31, 'Silver Oak Residency', NULL, 'Vasant Kunj, Sector D', 'New Delhi', 'Delhi', 'India', '110070', 1, 0, NULL, 'Mr. Karan Singh', '+919811134567', 'admin@silveroakvasantkunj.in', 'starter', '2025-12-18 10:29:22', '2025-12-18 10:29:22', 'pending'),
(32, 'Central Park Towers', NULL, 'Sector 48, Sohna Road', 'Gurugram', 'Haryana', 'India', '122018', 1, 0, NULL, 'Ms. Riya Gupta', '+919876543210', 'contact@centralparktowers.co.in', 'starter', '2025-12-18 10:29:30', '2025-12-18 10:29:30', 'pending'),
(33, 'Riverside Greens Society', NULL, 'Mayur Vihar Phase 1, Near Noida Link Road', 'Delhi', 'Delhi', 'India', '110091', 1, 0, NULL, 'Mr. Sameer Ahuja', '+911122756789', 'secretary@riversidegreens.in', 'starter', '2025-12-18 10:29:35', '2025-12-18 10:29:35', 'pending'),
(34, 'Prestige Boulevard', NULL, 'Dwarka Sector 22', 'New Delhi', 'Delhi', 'India', '110077', 1, 0, NULL, 'Ms. Sneha Rao', '+919891234567', 'management@prestigeboulevarddwarka.in', 'starter', '2025-12-18 10:29:40', '2025-12-18 10:29:40', 'pending'),
(35, 'Sun Universe', NULL, 'Near pluse hospital, Navle Bridge', 'Pune', 'Maharashtra', 'India', '411041', 1, 0, NULL, 'Mr. Ashok Patil', '+919050505050', 'admin@sununiverse.com', 'starter', '2026-05-07 09:55:04', '2026-05-07 09:55:04', 'pending'),
(36, 'Avenir Residency', NULL, 'ieuf foeiwf', 'kejfnw', 'qkejfwn', 'India', '234567', 1, 0, NULL, 'Facilities Pune', '+919080908090', 'facilities.pune@coepd.com', 'professional', '2026-05-08 10:34:13', '2026-05-08 10:34:13', 'pending'),
(37, 'Example 1 society', NULL, 'ieuf foeiwf', 'kejfnw', 'qkejfwn', 'India', '234567', 1, 0, NULL, 'Facilities Pune', '+919383673712', 'oiuytrffgc@gmail.com', 'professional', '2026-05-11 08:25:41', '2026-05-11 08:25:41', 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `society_registrations`
--

CREATE TABLE `society_registrations` (
  `id` int(11) NOT NULL,
  `society_name` varchar(150) NOT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'India',
  `pincode` varchar(10) DEFAULT NULL,
  `towers` int(11) DEFAULT 1,
  `total_flats` int(11) DEFAULT 0,
  `contact_name` varchar(100) NOT NULL,
  `contact_email` varchar(100) NOT NULL,
  `contact_phone` varchar(20) NOT NULL,
  `gst` varchar(20) DEFAULT NULL,
  `pan` varchar(20) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','new','under_review','approved','rejected') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `ticket_number` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `resident_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `society_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_comments`
--

CREATE TABLE `ticket_comments` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `app_user_id` char(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','resident','guard','staff','super_admin') DEFAULT 'resident',
  `society_id` int(11) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','blocked','pending_verification') DEFAULT 'pending_verification',
  `cover_image_url` varchar(255) DEFAULT NULL COMMENT 'URL for user cover/banner image',
  `resident_type` enum('owner','tenant','family_member','other') DEFAULT NULL COMMENT 'Type of resident',
  `bio` text DEFAULT NULL COMMENT 'Short biography or about section',
  `profession` varchar(150) DEFAULT NULL COMMENT 'Profession or work',
  `hometown` varchar(150) DEFAULT NULL COMMENT 'Hometown or place of origin',
  `google_id` varchar(255) DEFAULT NULL,
  `facebook_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `app_user_id`, `name`, `email`, `phone`, `password`, `role`, `society_id`, `profile_image`, `status`, `cover_image_url`, `resident_type`, `bio`, `profession`, `hometown`, `google_id`, `facebook_id`, `created_at`, `updated_at`) VALUES
(1, 'USR-00001', 'Super Admin', NULL, '1122334455', '$2y$10$Cxq2/s.Rg79urt1HWsvu1usNOw6Hu.F1rSbUwa2MaLfOJNObXxT.q', 'super_admin', NULL, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-16 11:54:31', '2025-12-22 05:00:44'),
(2, 'USR-00002', 'Society Admin', NULL, '6677889900', '$2y$10$khVBYka/9sx2r47p6R7HeuXRo/VeIeFe2hhw4rsphwOvX5jAXRCUG', 'admin', 1, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-16 11:56:25', '2026-05-08 08:59:29'),
(3, 'USR-00003', 'Riddhi-Siddhi Admin', NULL, '6677889911', '$2y$10$pO.MbJ5oUMFq7IGfEiQf9e3tC383A/7bJmJG4tC2oPESFcyRtlxtG', 'admin', 2, NULL, 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-16 12:09:39', '2025-12-22 05:00:44'),
(4, 'USR-00004', 'Shivam Khule', NULL, '8010155144', '$2y$10$U3pDiSfAmV7.zfZv.bRa/.apBOM8yKA59cpsV6QWdJGda5sFue8Mu', 'resident', 1, NULL, 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-17 10:00:43', '2026-05-07 09:09:11'),
(5, 'USR-00005', 'John Doe', NULL, '9999999999', 'hashed_password', 'resident', NULL, NULL, 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-22 05:04:04', '2025-12-22 05:04:04'),
(6, 'RAK-83661', 'Sanket Jadhav', NULL, '9087908790', '$2y$10$.0ZbeTHqFAIXimdbKObQBu3uXqlB.ekvyBsniDgUGEHaaLXg9mYWW', 'resident', 1, NULL, 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-22 05:08:42', '2025-12-22 05:08:42'),
(7, 'SDJ-395406', 'Updated Name', 'newemail@example.com', '8976351526', '$2y$10$oLRwXvXBt5PeLVncGl97g.vXX/FOXeDPiWM50vuxxjzbDP99W8zoq', 'resident', 1, 'https://example.com/image.jpg', 'pending_verification', 'https://example.com/cover.jpg', 'owner', 'Flutter developer and society resident', 'IT Engineer', 'Ahilyanagar', NULL, NULL, '2025-12-22 05:10:11', '2025-12-22 06:18:51'),
(11, 'VMG-909750', 'Super Admin 2', 'superadmin2@gmail.com', '1122334466', '$2y$10$ZOnj1361lqNm9G1osy5ys.Tmv2RYnDgb0BZx34OwePMiG/CqPWXPK', 'super_admin', NULL, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-07 08:57:45', '2026-05-07 08:57:45'),
(12, 'FSL-240673', 'Radha Recidency Admin', 'radhares@gmail.com', '8908908900', '$2y$10$hZj98iUOjtmVbvBS/QHzEO3H1jjJlkVGnTMqMqTkKUbKg4RPXAJmC', 'admin', 2, NULL, 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-07 08:58:23', '2026-05-07 08:58:23'),
(13, 'XWL-128643', 'Sachin Patil', 'sachin@gmail.com', '9090909090', '$2y$10$9.cYsf/e3FSTvOexM4gwIOb5O6DoSioF1g0yaGelYyN7gBksSzr6.', 'resident', 1, NULL, 'pending_verification', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-07 08:58:38', '2026-05-07 08:58:38'),
(14, '', 'Facilities Pune', 'facilities.pune@coepd.com', '9080908090', '$2y$10$C76darC5DCDJ7PytWQoD..bF3BufpTFi4jALPsYkPRi16HivJi8V6', 'admin', 36, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-08 10:34:13', '2026-05-08 10:34:13');

-- --------------------------------------------------------

--
-- Table structure for table `user_otps`
--

CREATE TABLE `user_otps` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `otp` varchar(6) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `society_id` int(11) DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `vehicle_type_id` int(11) DEFAULT NULL,
  `make` varchar(50) DEFAULT NULL,
  `model` varchar(50) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `registration_number` varchar(20) NOT NULL,
  `parking_spot` varchar(20) DEFAULT NULL,
  `is_parked` tinyint(1) DEFAULT 0,
  `society_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `resident_id`, `vehicle_type_id`, `make`, `model`, `color`, `registration_number`, `parking_spot`, `is_parked`, `society_id`, `created_at`, `updated_at`) VALUES
(1, 4, 1, 'Toyota', 'Camry', 'Black', 'MH01AB1234', 'P102', 0, 1, '2025-12-17 11:33:41', '2025-12-17 11:36:14');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_types`
--

CREATE TABLE `vehicle_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `monthly_charge` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vehicle_types`
--

INSERT INTO `vehicle_types` (`id`, `name`, `description`, `monthly_charge`) VALUES
(1, 'Car', 'Four wheeler car', 1000.00);

-- --------------------------------------------------------

--
-- Table structure for table `visitors`
--

CREATE TABLE `visitors` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `purpose` varchar(100) DEFAULT NULL,
  `visit_date` date DEFAULT NULL,
  `visit_time` time DEFAULT NULL,
  `expected_exit_time` time DEFAULT NULL,
  `actual_exit_time` time DEFAULT NULL,
  `status` enum('pending','approved','rejected','entered','exited') DEFAULT 'pending',
  `visitor_type` enum('guest','delivery','service','other') DEFAULT 'guest',
  `resident_id` int(11) DEFAULT NULL,
  `guard_id` int(11) DEFAULT NULL,
  `society_id` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `amenities`
--
ALTER TABLE `amenities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `society_id` (`society_id`);

--
-- Indexes for table `amenity_bookings`
--
ALTER TABLE `amenity_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `amenity_id` (`amenity_id`),
  ADD KEY `resident_id` (`resident_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `society_id` (`society_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `target_group_id` (`target_group_id`);

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `idx_assets_society` (`society_id`);

--
-- Indexes for table `asset_categories`
--
ALTER TABLE `asset_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `society_id` (`society_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_logs_user` (`user_id`),
  ADD KEY `idx_audit_logs_action` (`action`);

--
-- Indexes for table `buildings`
--
ALTER TABLE `buildings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_buildings_society` (`society_id`);

--
-- Indexes for table `charge_heads`
--
ALTER TABLE `charge_heads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `society_id` (`society_id`);

--
-- Indexes for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account_code` (`account_code`),
  ADD KEY `parent_account_id` (`parent_account_id`),
  ADD KEY `society_id` (`society_id`);

--
-- Indexes for table `covid_guidelines`
--
ALTER TABLE `covid_guidelines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `society_id` (`society_id`);

--
-- Indexes for table `covid_test_results`
--
ALTER TABLE `covid_test_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resident_id` (`resident_id`),
  ADD KEY `society_id` (`society_id`);

--
-- Indexes for table `covid_vaccination_records`
--
ALTER TABLE `covid_vaccination_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resident_id` (`resident_id`),
  ADD KEY `society_id` (`society_id`);

--
-- Indexes for table `data_privacy_requests`
--
ALTER TABLE `data_privacy_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `processed_by` (`processed_by`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `society_id` (`society_id`);

--
-- Indexes for table `family_members`
--
ALTER TABLE `family_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resident_id` (`resident_id`);

--
-- Indexes for table `financial_years`
--
ALTER TABLE `financial_years`
  ADD PRIMARY KEY (`id`),
  ADD KEY `society_id` (`society_id`);

--
-- Indexes for table `flats`
--
ALTER TABLE `flats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `tenant_id` (`tenant_id`),
  ADD KEY `idx_flats_building` (`building_id`),
  ADD KEY `idx_flats_society` (`society_id`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `society_id` (`society_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `society_id` (`society_id`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `flat_id` (`flat_id`),
  ADD KEY `society_id` (`society_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_invoices_resident` (`resident_id`),
  ADD KEY `idx_invoices_status` (`status`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `charge_head_id` (`charge_head_id`);

--
-- Indexes for table `marketplace_categories`
--
ALTER TABLE `marketplace_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_category_id` (`parent_category_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user` (`user_id`),
  ADD KEY `idx_notifications_read` (`is_read`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `society_id` (`society_id`),
  ADD KEY `idx_orders_buyer` (`buyer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `parking_spots`
--
ALTER TABLE `parking_spots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `society_id` (`society_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_reference` (`payment_reference`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `society_id` (`society_id`),
  ADD KEY `idx_payments_resident` (`resident_id`),
  ADD KEY `idx_payments_status` (`transaction_status`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pets`
--
ALTER TABLE `pets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resident_id` (`resident_id`),
  ADD KEY `pet_type_id` (`pet_type_id`),
  ADD KEY `society_id` (`society_id`);

--
-- Indexes for table `pet_types`
--
ALTER TABLE `pet_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `polls`
--
ALTER TABLE `polls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `society_id` (`society_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `poll_options`
--
ALTER TABLE `poll_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `poll_id` (`poll_id`);

--
-- Indexes for table `poll_votes`
--
ALTER TABLE `poll_votes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `poll_id` (`poll_id`),
  ADD KEY `option_id` (`option_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `idx_products_society` (`society_id`);

--
-- Indexes for table `receipts`
--
ALTER TABLE `receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `resident_id` (`resident_id`),
  ADD KEY `society_id` (`society_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `society_id` (`society_id`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `security_alerts`
--
ALTER TABLE `security_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reported_by` (`reported_by`),
  ADD KEY `society_id` (`society_id`),
  ADD KEY `resolved_by` (`resolved_by`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `society_id` (`society_id`);

--
-- Indexes for table `service_bookings`
--
ALTER TABLE `service_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `resident_id` (`resident_id`);

--
-- Indexes for table `service_categories`
--
ALTER TABLE `service_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `societies`
--
ALTER TABLE `societies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `society_registrations`
--
ALTER TABLE `society_registrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_number` (`ticket_number`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `society_id` (`society_id`),
  ADD KEY `idx_tickets_resident` (`resident_id`),
  ADD KEY `idx_tickets_status` (`status`);

--
-- Indexes for table `ticket_comments`
--
ALTER TABLE `ticket_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_app_user_id` (`app_user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `google_id` (`google_id`),
  ADD UNIQUE KEY `facebook_id` (`facebook_id`),
  ADD KEY `society_id` (`society_id`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_phone` (`phone`);

--
-- Indexes for table `user_otps`
--
ALTER TABLE `user_otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`,`otp`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `society_id` (`society_id`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `registration_number` (`registration_number`),
  ADD KEY `vehicle_type_id` (`vehicle_type_id`),
  ADD KEY `society_id` (`society_id`),
  ADD KEY `idx_vehicles_resident` (`resident_id`);

--
-- Indexes for table `vehicle_types`
--
ALTER TABLE `vehicle_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `visitors`
--
ALTER TABLE `visitors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `guard_id` (`guard_id`),
  ADD KEY `society_id` (`society_id`),
  ADD KEY `idx_visitors_resident` (`resident_id`),
  ADD KEY `idx_visitors_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `amenities`
--
ALTER TABLE `amenities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `amenity_bookings`
--
ALTER TABLE `amenity_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_categories`
--
ALTER TABLE `asset_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buildings`
--
ALTER TABLE `buildings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `charge_heads`
--
ALTER TABLE `charge_heads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `covid_guidelines`
--
ALTER TABLE `covid_guidelines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `covid_test_results`
--
ALTER TABLE `covid_test_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `covid_vaccination_records`
--
ALTER TABLE `covid_vaccination_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `data_privacy_requests`
--
ALTER TABLE `data_privacy_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `family_members`
--
ALTER TABLE `family_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `financial_years`
--
ALTER TABLE `financial_years`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `flats`
--
ALTER TABLE `flats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `group_members`
--
ALTER TABLE `group_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `marketplace_categories`
--
ALTER TABLE `marketplace_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parking_spots`
--
ALTER TABLE `parking_spots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pets`
--
ALTER TABLE `pets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pet_types`
--
ALTER TABLE `pet_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `polls`
--
ALTER TABLE `polls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `poll_options`
--
ALTER TABLE `poll_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `poll_votes`
--
ALTER TABLE `poll_votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `receipts`
--
ALTER TABLE `receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_alerts`
--
ALTER TABLE `security_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_bookings`
--
ALTER TABLE `service_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_categories`
--
ALTER TABLE `service_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_logs`
--
ALTER TABLE `sms_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `societies`
--
ALTER TABLE `societies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `society_registrations`
--
ALTER TABLE `society_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ticket_comments`
--
ALTER TABLE `ticket_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `user_otps`
--
ALTER TABLE `user_otps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `vehicle_types`
--
ALTER TABLE `vehicle_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `visitors`
--
ALTER TABLE `visitors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `amenities`
--
ALTER TABLE `amenities`
  ADD CONSTRAINT `amenities_ibfk_1` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `amenity_bookings`
--
ALTER TABLE `amenity_bookings`
  ADD CONSTRAINT `amenity_bookings_ibfk_1` FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `amenity_bookings_ibfk_2` FOREIGN KEY (`resident_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `announcements_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `announcements_ibfk_3` FOREIGN KEY (`target_group_id`) REFERENCES `groups` (`id`);

--
-- Constraints for table `assets`
--
ALTER TABLE `assets`
  ADD CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `asset_categories` (`id`),
  ADD CONSTRAINT `assets_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `assets_ibfk_3` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `asset_categories`
--
ALTER TABLE `asset_categories`
  ADD CONSTRAINT `asset_categories_ibfk_1` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `buildings`
--
ALTER TABLE `buildings`
  ADD CONSTRAINT `buildings_ibfk_1` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `charge_heads`
--
ALTER TABLE `charge_heads`
  ADD CONSTRAINT `charge_heads_ibfk_1` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  ADD CONSTRAINT `chart_of_accounts_ibfk_1` FOREIGN KEY (`parent_account_id`) REFERENCES `chart_of_accounts` (`id`),
  ADD CONSTRAINT `chart_of_accounts_ibfk_2` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `covid_guidelines`
--
ALTER TABLE `covid_guidelines`
  ADD CONSTRAINT `covid_guidelines_ibfk_1` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `covid_test_results`
--
ALTER TABLE `covid_test_results`
  ADD CONSTRAINT `covid_test_results_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `covid_test_results_ibfk_2` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `covid_vaccination_records`
--
ALTER TABLE `covid_vaccination_records`
  ADD CONSTRAINT `covid_vaccination_records_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `covid_vaccination_records_ibfk_2` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `data_privacy_requests`
--
ALTER TABLE `data_privacy_requests`
  ADD CONSTRAINT `data_privacy_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `data_privacy_requests_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  ADD CONSTRAINT `emergency_contacts_ibfk_1` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `family_members`
--
ALTER TABLE `family_members`
  ADD CONSTRAINT `family_members_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `financial_years`
--
ALTER TABLE `financial_years`
  ADD CONSTRAINT `financial_years_ibfk_1` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `flats`
--
ALTER TABLE `flats`
  ADD CONSTRAINT `flats_ibfk_1` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `flats_ibfk_2` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `flats_ibfk_3` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `flats_ibfk_4` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `groups_ibfk_1` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `groups_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `group_members`
--
ALTER TABLE `group_members`
  ADD CONSTRAINT `group_members_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `inventory_transactions_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`),
  ADD CONSTRAINT `inventory_transactions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`flat_id`) REFERENCES `flats` (`id`),
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`resident_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `invoices_ibfk_3` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoices_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_items_ibfk_2` FOREIGN KEY (`charge_head_id`) REFERENCES `charge_heads` (`id`);

--
-- Constraints for table `marketplace_categories`
--
ALTER TABLE `marketplace_categories`
  ADD CONSTRAINT `marketplace_categories_ibfk_1` FOREIGN KEY (`parent_category_id`) REFERENCES `marketplace_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `parking_spots`
--
ALTER TABLE `parking_spots`
  ADD CONSTRAINT `parking_spots_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`),
  ADD CONSTRAINT `parking_spots_ibfk_2` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`resident_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pets`
--
ALTER TABLE `pets`
  ADD CONSTRAINT `pets_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `pets_ibfk_2` FOREIGN KEY (`pet_type_id`) REFERENCES `pet_types` (`id`),
  ADD CONSTRAINT `pets_ibfk_3` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `polls`
--
ALTER TABLE `polls`
  ADD CONSTRAINT `polls_ibfk_1` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `polls_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `poll_options`
--
ALTER TABLE `poll_options`
  ADD CONSTRAINT `poll_options_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `poll_votes`
--
ALTER TABLE `poll_votes`
  ADD CONSTRAINT `poll_votes_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `poll_votes_ibfk_2` FOREIGN KEY (`option_id`) REFERENCES `poll_options` (`id`),
  ADD CONSTRAINT `poll_votes_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `marketplace_categories` (`id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `products_ibfk_3` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `receipts`
--
ALTER TABLE `receipts`
  ADD CONSTRAINT `receipts_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`),
  ADD CONSTRAINT `receipts_ibfk_2` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  ADD CONSTRAINT `receipts_ibfk_3` FOREIGN KEY (`resident_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `receipts_ibfk_4` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `roles`
--
ALTER TABLE `roles`
  ADD CONSTRAINT `roles_ibfk_1` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `security_alerts`
--
ALTER TABLE `security_alerts`
  ADD CONSTRAINT `security_alerts_ibfk_1` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `security_alerts_ibfk_2` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `security_alerts_ibfk_3` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `service_categories` (`id`),
  ADD CONSTRAINT `services_ibfk_2` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `service_bookings`
--
ALTER TABLE `service_bookings`
  ADD CONSTRAINT `service_bookings_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  ADD CONSTRAINT `service_bookings_ibfk_2` FOREIGN KEY (`resident_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ticket_comments`
--
ALTER TABLE `ticket_comments`
  ADD CONSTRAINT `ticket_comments_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ticket_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_otps`
--
ALTER TABLE `user_otps`
  ADD CONSTRAINT `user_otps_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_3` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_roles_ibfk_4` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `vehicles_ibfk_2` FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_types` (`id`),
  ADD CONSTRAINT `vehicles_ibfk_3` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `visitors`
--
ALTER TABLE `visitors`
  ADD CONSTRAINT `visitors_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `visitors_ibfk_2` FOREIGN KEY (`guard_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `visitors_ibfk_3` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
