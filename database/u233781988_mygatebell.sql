-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 14, 2026 at 10:52 AM
-- Server version: 11.8.9-MariaDB-log
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

--
-- Dumping data for table `amenities`
--

INSERT INTO `amenities` (`id`, `name`, `description`, `image_url`, `capacity`, `booking_fee`, `cancellation_fee`, `cancellation_policy`, `society_id`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 'Swimming Pool', 'Society\'s swimming pool', 'https://images.pexels.com/photos/7222171/pexels-photo-7222171.jpeg?auto=compress&cs=tinysrgb&dpr=1&w=500', 10, 100.00, 0.00, NULL, 1, 1, '2026-06-02 08:22:38', '2026-06-02 08:22:38'),
(3, 'Tennis Court', 'Typically made of rigid acrylic materials, these popular options feature high visual contrast like bright blue or green surfaces as seen at major tournaments like the Miami Open.', 'https://tigerturf.com/in/wp-content/uploads/2019/11/How-to-build-a-tennis-court.jpg', 8, 150.00, 0.00, NULL, 1, 1, '2026-06-16 09:42:43', '2026-06-16 09:42:43'),
(4, 'Badminton Court', 'Description for Badminton Court.', 'https://amzn-s3-mygatebell.s3.us-east-1.amazonaws.com/amenities/uid2_1791894511_6ace23ef8f62c.jpg', 8, 120.00, 0.00, NULL, 1, 1, '2026-10-13 12:28:54', '2026-10-13 12:28:54');

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
  `status` enum('requested','confirmed','cancelled','completed','already_booked') DEFAULT 'requested',
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('pending','paid','refunded') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `amenity_bookings`
--

INSERT INTO `amenity_bookings` (`id`, `amenity_id`, `resident_id`, `booking_date`, `start_time`, `end_time`, `status`, `total_amount`, `payment_status`, `notes`, `created_at`, `updated_at`) VALUES
(4, 3, 12, '2026-06-17', '11:00:00', '12:00:00', 'confirmed', 150.00, 'pending', NULL, '2026-06-16 09:44:22', '2026-06-16 09:44:53'),
(5, 3, 12, '2026-09-15', '21:00:00', '22:00:00', 'requested', 150.00, 'pending', NULL, '2026-09-15 15:07:35', '2026-09-15 15:07:35'),
(6, 2, 12, '2026-09-15', '17:00:00', '18:00:00', 'cancelled', 100.00, 'pending', NULL, '2026-09-15 15:08:02', '2026-10-13 12:21:56'),
(7, 2, 12, '2026-09-19', '13:00:00', '14:00:00', 'confirmed', 100.00, 'pending', NULL, '2026-09-15 15:08:21', '2026-10-13 12:21:58'),
(8, 3, 12, '2026-09-15', '06:00:00', '07:00:00', 'confirmed', 150.00, 'pending', NULL, '2026-09-15 21:25:03', '2026-10-13 12:21:59');

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

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `society_id`, `created_by`, `target_group_id`, `send_via`, `scheduled_at`, `sent_at`, `is_draft`, `created_at`, `updated_at`) VALUES
(1, 'Clubhouse Maintenance', 'The clubhouse will remain closed for renovation work from June 10th to June 15th. We regret the inconvenience caused.', 1, 2, NULL, 'app', NULL, NULL, 0, '2026-05-18 12:29:14', '2026-06-01 09:51:20'),
(2, 'Water Tank Cleaning Notice', 'The overhead and underground water tanks will be cleaned on Sunday. Water supply may be interrupted between 10:00 AM and 2:00 PM. Residents are requested to store sufficient water in advance.', 1, 2, NULL, 'app', NULL, NULL, 0, '2026-06-01 09:49:45', '2026-06-01 09:49:56');

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
(1, 'A', 1, 10, NULL, '2026-05-18 08:30:23', '2026-05-18 08:30:23'),
(2, 'Amber', 1, 5, 'Fourth building', '2026-06-26 10:26:11', '2026-06-26 10:26:11'),
(3, 'Maheen', 1, 1, '', '2026-07-29 14:21:18', '2026-07-29 14:21:18'),
(4, 'Xyz', 1, 1, '', '2026-07-29 14:29:09', '2026-07-29 14:29:09'),
(5, 'chaitanya castle', 1, 6, '', '2026-10-12 14:07:45', '2026-10-12 14:07:45');

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

--
-- Dumping data for table `charge_heads`
--

INSERT INTO `charge_heads` (`id`, `name`, `description`, `charge_type`, `amount`, `slab_details`, `gst_rate`, `is_active`, `society_id`, `created_at`, `updated_at`) VALUES
(1, 'Maintenance', NULL, 'fixed', 2500.00, NULL, 0.00, 1, 1, '2026-05-26 09:05:13', '2026-05-26 09:05:13'),
(2, 'Water Charges', NULL, 'fixed', 500.00, NULL, 0.00, 1, 1, '2026-05-26 09:05:13', '2026-05-26 09:05:13'),
(3, 'Parking', NULL, 'fixed', 500.00, NULL, 0.00, 1, 1, '2026-05-26 09:05:13', '2026-05-26 09:05:13'),
(4, 'Shivam Khule', 'wewv wfwq', 'per_person', 100.00, NULL, 18.00, 1, 1, '2026-06-26 08:43:26', '2026-06-26 08:43:26');

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
-- Table structure for table `community_posts`
--

CREATE TABLE `community_posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `society_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `likes_count` int(11) DEFAULT 0,
  `comments_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `community_posts`
--

INSERT INTO `community_posts` (`id`, `user_id`, `society_id`, `content`, `image`, `likes_count`, `comments_count`, `created_at`, `updated_at`) VALUES
(7, 12, 1, 'hi all residents', NULL, 1, 0, '2026-09-16 12:31:45', '2026-10-07 06:17:39');

-- --------------------------------------------------------

--
-- Table structure for table `community_post_comments`
--

CREATE TABLE `community_post_comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `community_post_likes`
--

CREATE TABLE `community_post_likes` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `community_post_likes`
--

INSERT INTO `community_post_likes` (`id`, `post_id`, `user_id`, `created_at`) VALUES
(6, 7, 12, '2026-10-07 11:47:38');

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
-- Table structure for table `device_tokens`
--

CREATE TABLE `device_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `device_token` varchar(255) NOT NULL,
  `device_type` enum('android','ios','web') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `device_tokens`
--

INSERT INTO `device_tokens` (`id`, `user_id`, `device_token`, `device_type`, `created_at`, `updated_at`) VALUES
(7, 12, 'cumf2qi0QESgbVRENLYL3s:APA91bFVC5HF22YHYxPfUNn4AttzOEPqYW68hUD7aDgsiIpTuSGWKR78ziKoyPXjefmL_vXPE0MI_zQnzWd4phYWNXVYJv1dwcR50sgdvChpx7Gt4lFZ5NI', 'android', '2026-06-23 07:56:48', '2026-06-23 07:56:48'),
(10, 12, 'dgxbc6CsShyQOP3T6Jtk7p:APA91bEaWbHNto-FqWYrtPGV_w0JQbniE2wlehaLe4Eh_990cydSLo3EXMb2E0-6jefPiCB-BB0LN9QOShbbWLMRYh2rj-4q-70T2eyxegEVU8f74sSoWYk', 'android', '2026-06-24 05:14:46', '2026-07-02 09:43:42'),
(11, 12, 'd3QWTc_VT1yLRRSlBy1coj:APA91bEuuLQdb1mQDsyzTgErdVJSKtX7Yor1Uf2mFgTceotMnHbJlL83-pNMrYvVe6wCeazUyu8lItAjHY2rTti1XKeU0UAsbe8KXSeNsLDgGFdA-bxNWx0', 'android', '2026-06-25 06:59:07', '2026-06-25 06:59:07'),
(12, 2, 'fJibc1Dn3YYMBkadq6AHQY:APA91bH3OmeC4vdaYYD8D48_rvqNZdcf-DmzhFsNqQPR-R4F_EfU94bz-f7Xp64P_8MRSBAEiUp2cJtCMhB7OdbuLmdG57hKSbtAcSZG3H-blSibDkUkZi8', 'web', '2026-06-26 07:08:41', '2026-06-26 07:08:41'),
(13, 2, 'fJibc1Dn3YYMBkadq6AHQY:APA91bEz9GNiM_MoNfESf2lNPqNjs5ENH0GsjBgxmPs5P6QBLpJr-qlBHML-4SOB5dbSGx7xt-dR3AWzCuM0n9JLIbe9oUudrsd58ahGBLJDn_Yv3rCBFXU', 'web', '2026-06-26 07:08:42', '2026-06-26 07:08:42'),
(14, 2, 'fJibc1Dn3YYMBkadq6AHQY:APA91bHEIwlx-q-1Ca9_gvv3hxSv54V0DfTUoihQ-3BnG3mpJne9JDLHJDwQ2WIsoDRia5gbPzIt6bRkyciGnmYqf5j9Y-vkXSmgRPqY5jMnvAGLN9OnRuE', 'web', '2026-06-26 07:08:43', '2026-06-26 07:08:43'),
(15, 2, 'epg-5WcjhJNDcMuNkber3Z:APA91bEzMLv-6N4BfjVLOkidXkcIhUQqqTYRf7mOoGXaOSO8dHEo5378aTIfGJsB6fwC8Ky4C-TdSo5AxQGh2Td8MEFS9BWw12ZiTZXCHfW52dM1ka3kn1g', 'web', '2026-06-26 08:37:22', '2026-06-27 04:50:15'),
(41, 2, 'fJibc1Dn3YYMBkadq6AHQY:APA91bF5G07Eas7ivmmzMSC6681svfbnaZgbDLq3MJxvca2BmBOlbQymd5pLSkylzJnOXRT1qJNSBpGUQZLM8K527gw0-mV642Ep8w5-CnDyG7BKfDtkf7w', 'web', '2026-06-26 09:05:06', '2026-06-26 09:05:06'),
(42, 2, 'fJibc1Dn3YYMBkadq6AHQY:APA91bGu4GKB0ua7pmlgZFlMXfd0ZRlpazQk3yLFx-4IIYtkp1RWdFRxopRYnQZH5_huAs0LnIq0kxxaWZY_HshLVl0zrDE1GtaB450b3LtFtQXS8swlfko', 'web', '2026-06-26 09:05:07', '2026-06-26 09:05:07'),
(43, 2, 'fJibc1Dn3YYMBkadq6AHQY:APA91bFlkeAXTtE1HYTFgEUvsLwE4Mn0NLybYjYEegZ4fyQdP6eHkkbd2dsrp8yJMCy_6TPvrhEVnoaHPj0F6FNtBLmzfHXmIM0rH0OTVxRyQeMond7tPOo', 'web', '2026-06-26 09:05:08', '2026-06-26 10:03:24'),
(117, 2, 'e7hcCZRxri5XhjRP1VbiqL:APA91bGGEGb613cUGeT3wP9K-zUqvAO0pfd6x4ECGqyRzYFCzpxLpvUMPmMzQDLpwkhlmRkv6GzQZ2i0c8iLk0PZG3HZ7BT-MQ_dIfm7E4L1d3Kg5i1zLHY', 'web', '2026-06-27 07:17:05', '2026-06-27 07:17:05'),
(118, 3, 'e7hcCZRxri5XhjRP1VbiqL:APA91bGGEGb613cUGeT3wP9K-zUqvAO0pfd6x4ECGqyRzYFCzpxLpvUMPmMzQDLpwkhlmRkv6GzQZ2i0c8iLk0PZG3HZ7BT-MQ_dIfm7E4L1d3Kg5i1zLHY', 'web', '2026-06-27 07:31:13', '2026-06-27 07:31:14'),
(120, 2, 'c3GaHVpjp5zzFhb_RlH9kF:APA91bGJ_hHT4aDA7A2J4Tr1oYg-mfDw3bn3ghN1TZ7xy0jqlWbAKHt5Cn9_mfvlut_7smv3N4BH_LzHJd0oke9F99KY-HRpCmZEhMjZMQcnBJGg2dlg_3Y', 'web', '2026-06-27 09:51:26', '2026-06-27 09:53:48'),
(129, 12, 'ca3tmDZkTL22MEioFdl9IL:APA91bFA8VB935TIzNhzj8elkjeWL2P1Plgl5wBtdn11TxQ3u5P7FX66mRRd8wjfve5bZv7zdXK3sFnNlefse4ljVX5cQqZSuQ4n2UAVd8EE-OjOZ2NcT50', 'android', '2026-07-01 08:59:43', '2026-07-01 08:59:43'),
(130, 2, 'dLdywy69YWhxSQ3Ibh4USB:APA91bF66tLmuYsRXO4q_EfibbL8MJzyVw7VFae5OnOZfkDjgWGlGtC55ondi9ymSowI7BK2V1OdspJV9EY1b3bHptwgtgsO_JZBGTpxBLZg9n0TXQEHZLw', 'web', '2026-07-01 09:08:14', '2026-07-01 09:08:14'),
(131, 2, 'dLdywy69YWhxSQ3Ibh4USB:APA91bFQy5tXRFwzQ-v7HGUnv_LFo2J_28iRvqagjuF_2SEJAWExHZ2OD6uWjuNeZ_5674xn-JLbkxBGf62JgFJsYzsEFucdb28oeykldbPPRqFw7a1-MOI', 'web', '2026-07-01 09:08:14', '2026-07-01 09:08:14'),
(132, 2, 'dLdywy69YWhxSQ3Ibh4USB:APA91bFsZlVoO4IlabVAtUKNuaTrYsqJY0VyWT3AAIHwuLBtiGL7z26yXlN54cJykuIcuLRU_iZ00GhLw44P7Zm-KfMTibcBmRTD6Vj5CM8JAscgaXpLnY8', 'web', '2026-07-01 09:08:15', '2026-07-01 09:08:15'),
(133, 2, 'dLdywy69YWhxSQ3Ibh4USB:APA91bGKUcLkS-87WZFK_fKkUmC2TCX1ncfkzmaAlUp5bfnYEVgGGhT7iAxp5fd9zqsIthvQiWpgC9mXcfp-0Wsk1wP2JXRvhY0au61Y0OttryRDiLhJN7M', 'web', '2026-07-01 09:17:17', '2026-07-01 09:17:17'),
(134, 2, 'dLdywy69YWhxSQ3Ibh4USB:APA91bEQa9fV0EISpvLBJjGy065ybUQJg5GnyKLbV4MxPRaixzbl3Zdn7wpqYNlx-x7wYP2PAM1pIXfNGOKm_0E7Wh1Tci6oCwWDhjX4xAINra3-ND7Q94g', 'web', '2026-07-01 09:17:17', '2026-07-01 09:17:17'),
(135, 2, 'dLdywy69YWhxSQ3Ibh4USB:APA91bEqxfO9yuOvFX6OEQIU-kuZxM3_9wEjiCVWq3LUgS-WNneMtdc_F5lORLjpVzMjhUbyrTQvbDfoEEcJ1N_CxXgD94YBfLxf59t9xsnxSbmlT96c2TI', 'web', '2026-07-01 09:17:17', '2026-07-01 09:17:17'),
(136, 12, 'cF8ch6KnSD-woY8Q0JXBcE:APA91bH7bW1fI__QBk2RDg79UXFcCgnFkPi9Xm5QBMLSCoXAeGwqA_0lwzBhnNqoVPCw-CJlfb6P50LkN-pyggAb407nmGMNVgXFIwOvXjSEyluulwpsMBw', 'android', '2026-07-02 09:14:22', '2026-07-02 09:14:22'),
(140, 2, 'dayU1mNJRJMOtBR5WmsWRf:APA91bH8j8aljZACqKmLtqpsg5SqlK9y0o1Oipx0jvM-KNTWWTeXjpFRTx_sKPITBug9lnKQ2wgqBB8K5PmXX1-JTwiTJXQoB65ksLMJgoV7LFwIUKk24ow', 'web', '2026-07-03 09:25:21', '2026-07-03 09:25:21'),
(141, 2, 'dayU1mNJRJMOtBR5WmsWRf:APA91bH_oYcA0RiF0SPR2-RYDDtlAVCYyiwbXnQ3G7sYeCUMGMTvhK2dYOSoCZKMUoy6ciJvj1btpZl1uX3OC1QwfpNdxsXTMOFwKosFUA8G78jevYrycC4', 'web', '2026-07-03 09:25:22', '2026-07-03 09:25:22'),
(142, 2, 'dayU1mNJRJMOtBR5WmsWRf:APA91bGHCCvL0FZgqV12KRtUaKnRiFZyptg_lxHthmXcYk1EgN6KpqtI6m3g7tf8hUV5CFXKniMw8_7Kj_EFibAxuIvERr5ZGR-qXTa4nn7ylBgID9rUhyA', 'web', '2026-07-03 09:26:43', '2026-07-03 09:26:43'),
(143, 2, 'dayU1mNJRJMOtBR5WmsWRf:APA91bEs0DwE9Z4rnTsBXN_jTg-Ly7bxg23zJdNxws2rOLo8hUmaCbmUbDkd7Nwd-a4AAl8Qlpn2I1GdY7YLW-0sWShswgZReu9dAFcyB9-BIoTJfE-M5kQ', 'web', '2026-07-03 09:26:44', '2026-07-04 08:32:08'),
(158, 12, 'flVZ6ZL5Qz6OGhlZWe5adn:APA91bHUavQv4Z4A-Q2zCud4_r1dnXIFGheWOh6iqzzMGz-5bTdFIfhXWVJVd0huP3YywncgjSZ9atRzvZ2LzcApK1-wTygbzhO5WR_jQq5ZNJ7gwePOn2Y', 'android', '2026-07-04 04:38:58', '2026-07-04 04:38:58'),
(178, 12, 'eqI7jSDZSemoxFDt3IVMRC:APA91bE2E4gclpNTf5JgpPmbNxehGux22meT5Vb3zmyd8VvJpJkXNQrgEOQrO3cT0ix2QUM6gkpX5gbazMglQtBfvT0j6VZh7SzGCzBS7UzOo6ICwRqq6cU', 'android', '2026-07-06 04:38:58', '2026-07-06 04:38:58'),
(179, 21, 'cvSsGHFeTPiBKdb4cD2lQV:APA91bFJzMPkZN0v-nvXf1z2Yq4cuobKKiG4b5KSPhlgx5E_9qIqdGdJmPh7Rdg-1gC2oKLXAWbtsBkmRpupB90__EHryLb83dkDNn_YtqhBw7uRou9BrrU', 'android', '2026-07-06 04:54:20', '2026-07-06 04:54:20'),
(180, 12, 'eFDE5zzYTdKEK3GTapnzZB:APA91bGJTPnkmWI7qCsMohEJS2I052vgaQLlvgjgV266e-3BG2EopK_QmrprHg_tXa2jKtX6F27Cd5FrrJaXqE7UqGWSIYMRxMEBb5JPlVXGDklQpmO6azc', 'android', '2026-07-07 03:59:35', '2026-07-07 03:59:35'),
(181, 21, 'dhNPklNeSAGgdGbrkJlB0K:APA91bEUTdXQXHzcgROKxqidlvoGMtTCX9CZRID2iEN8EG2hJj0j5V341VfzTbBiMKarREf2QCaUjXwNYwaTGqW2q04b-iiONdK8R5X0swo1c-MCNkmFdeI', 'android', '2026-07-07 05:18:19', '2026-07-07 05:18:19'),
(182, 2, 'eTBFsmlt6TgL-psSN84Ae4:APA91bF6JWTrX22Vc9HoZxIIOFrdlakN7g72VikdMCP2fHWFCrAvrkovcWT9u3oVDrMejA1e0Wa-afPBQyTJk1s2F7_GHaY8DAFziCj9ktpc5RJLFaLqPAk', 'web', '2026-07-07 05:57:38', '2026-07-07 05:57:38'),
(183, 2, 'eTBFsmlt6TgL-psSN84Ae4:APA91bEhlHjNP3AyibDemu2QIXNehts2GiB29Hn7GOR6c5RqDv4sG5mc_MYaKw72jWsfSNhrcf5OCxTOuXBnVTHhCphG2h_ECIsuCyJkjABzM0azP8Db5F8', 'web', '2026-07-07 05:57:40', '2026-07-07 05:57:40'),
(184, 2, 'eTBFsmlt6TgL-psSN84Ae4:APA91bEvCPvNOs9NazGxp7trAaO1-BI7VTwnx9RhVdwjl_-LVgUyS_KWmJynx2rgmt5OYsVE_i8rfQM9-zzK829lDpWLJPt69DCUFpyYTsgpCHcnyn4TgRo', 'web', '2026-07-07 05:57:41', '2026-07-07 05:57:48'),
(187, 21, 'cTPAa6zHSHCGClFawqsMAV:APA91bGcTmEo5akXUHFqDS_2uYRRXGit6Rdke96hR4UU1tcMvngMfyQ5cdMFHebIyoME1Laht9M7G8Kd3elDcR6dZcisUfx7pJt2kLiyzfOHafnp466wF5E', 'android', '2026-07-07 06:02:42', '2026-07-07 06:02:42'),
(188, 12, 'em1VBRbOS-m7FQ2kGU14wK:APA91bEgCb_pX5RciAXYQELcQfJkSL95UkKDgsQoahHhDPyH1A_HmatDOmtcmcwjxGnXMkLTjQ529PBJHAk3s9lG2ujh4MDB4CK-GI2l6qBLTNZR0YzgDy8', 'android', '2026-07-07 12:44:15', '2026-07-07 12:44:15'),
(189, 12, 'd_8WBrjISNOO0Z8heuD-cK:APA91bGeFX6HRaQgzvBu_VjG1hRs4jgN1X8_Bt_tok9IT4yUYTiGHwnLRA2gDAIMXSZDOA-X24NlTGcx_jcFE7j1w3EXMtzDgoI8TnAdlJGZDV8btKLmNWw', 'android', '2026-07-07 12:46:27', '2026-07-07 12:46:27'),
(190, 12, 'eaDE-9MkSkm-vjZSOCdj3h:APA91bEyFwL4JOdUU0VC3rwvc-rJ8klkPX60gQrxh7sD_x_6tSw54IfTwS4X1ORAIrKtDvlymQxiv1PdWI9FnhnawpxLh0T4OasqbL79PefGvqQgqX6Ky1U', 'android', '2026-07-07 12:49:00', '2026-07-07 12:49:00'),
(191, 12, 'eXz2TzXdQ62Hrtoj4h-T74:APA91bGfgnxP82y-3Lwv9Lf7GiPfZmlISdy60r_d0B69SbTevOLLwAjr64pZYQj9HVXKPZLoLc5j2hDk7Kq9ld__QNCr0GBTC-CxTDjIErdmnCLwVX_ggXw', 'android', '2026-07-08 04:12:07', '2026-07-08 04:12:07'),
(192, 12, 'cbhL5ctFRb6kBWrVqdv4uS:APA91bEb_2Oprc6MCN5SOTZMozCdsSDvfCZhKldnbi-ejtfXXscVVjn7R8g5N5MSDZXMgRwow18Yl6jWgwqxWaPGm3nKI8whccFWQUyEiID2BjhSSRRl85Q', 'android', '2026-07-09 04:34:34', '2026-07-09 04:34:34'),
(194, 2, 'dCtE00UtMdm46_-AkspOAg:APA91bH2QL5nr-_w8as96FF5u4BEQFBFw4q5Nb-8xXrSQs8NYZzz7YjoIw6jiT62fuYCdgRGb9pIDiL1FnYkpHcwyw_VUcSn81jpzxOdF7ZEAt1rsn_B7yw', 'web', '2026-07-13 05:36:15', '2026-07-13 05:36:15'),
(195, 2, 'dCtE00UtMdm46_-AkspOAg:APA91bGoFlc8QcDanrx0xaCb2ZXA1aCSBsvjQJSywlXYECPz6r17_YmUu_ST4c14L5Iki79H8OvlKSUvKJ9O9qR00-fzfA_RBaYuQOs8ZULPE9cQhRX3s2U', 'web', '2026-07-13 05:36:16', '2026-07-13 05:41:14'),
(197, 21, 'd_qTAuucSDiW6wZMvXBj75:APA91bFhQNJwWtr2hdiNVnKs1zbGW2kRA0qhe6IhhEkVOD8eQjcs-bi3l3sx4TFBnXW5MGcOHcqCbEGQggB0Bel3DWjGULFtyIWRoV1MhKm4-JQuENHpx5A', 'android', '2026-07-13 05:58:43', '2026-07-13 05:58:43'),
(198, 1, 'dCtE00UtMdm46_-AkspOAg:APA91bGoFlc8QcDanrx0xaCb2ZXA1aCSBsvjQJSywlXYECPz6r17_YmUu_ST4c14L5Iki79H8OvlKSUvKJ9O9qR00-fzfA_RBaYuQOs8ZULPE9cQhRX3s2U', 'web', '2026-07-13 06:10:05', '2026-07-13 06:10:07'),
(203, 2, 'fL4HSJ7ucrsGjJXgrNyInr:APA91bFIBwgVuHif-FlzbZ8r5vgfKbFkzjX0Re_fDwvkiHHjHjWgKrgkUmyfR3Xp9OckDT_KSoMvfEe7mMtvifxnGTh0JTlVQrxyRXJivPKGeSDIZQCWpAs', 'web', '2026-07-31 08:25:36', '2026-07-31 08:25:36'),
(204, 2, 'fL4HSJ7ucrsGjJXgrNyInr:APA91bHNuk6pvXWZkSduMNyELbwVSteMA3fbaA2rIJCNsDdtDqv0o38LK57rlVouA2yw4Loko5AyMCOxDAQGDG_2KdWaElyR--KuAaPDvvqvfDhqSYNLD-M', 'web', '2026-07-31 08:25:36', '2026-08-10 06:07:34'),
(211, 21, 'ffQ1Do2rTYado8aSa3MXhk:APA91bE3ZXqYWPs6B04skvklguiayz4oQPK4nwKIaRO741xUyqz3DaHe1ErqotvoZjoHm-naGOhXTb9b7ufCsQU-_6-xhLqF0j_OPLTv6ST1hzpkzf4Zd4Q', 'android', '2026-07-31 08:51:55', '2026-07-31 08:51:55'),
(212, 12, 'f4G1Xx32ShuGELv8oml7kR:APA91bF1KGW3v9I5OIloC4qh1JOWaDJoj3hdwVAYj5vYrC737HWQ6WxKHTo7jSGSwAM-ZTbSXQDMQMWQEoy1wRmyxLHmJmM-F1m8qhzJ7NSrvXNzxL5Yt88', 'android', '2026-08-01 04:10:10', '2026-08-01 04:10:10'),
(213, 12, 'ds-vxZSPQimKoKtzfFAH6U:APA91bEH1WjNKP9qsy0mdwDHwC5B4ZjAyKVB4yuL8AZCsLL0w1Im-u8rFh4WOvv8abIdcp3Sl1xDK84cTdpDOvTEomgUcU3Hjby-6091LJFjA4Qm8ua4Z34', 'android', '2026-08-01 05:25:18', '2026-08-01 05:25:18'),
(214, 12, 'fXncdz5bQDq6yriwZCLACR:APA91bEzMFWytN4cfAGFg4qjMNUQC49oE4ECnIPC4LeK57a7UzutII-BfsYSx9w9IuhIvAsEOTFOjquNFe16Nw4X8fpwm9R-9xcocKJwLF6SIK5utgmT_XI', 'android', '2026-08-01 06:00:51', '2026-08-01 06:00:51'),
(215, 12, 'eevOXA7-RSS17ays2rfbb1:APA91bEFUFY7dbwVWez2Y2owVyy0uddEq0TfqJ0lB6mPaYJULcPCO1FvmzyWxcwTXn_rZ6FdIw1v-BMpORDUDIO0InKv3J7SmRn5uydn6MUHSY4pnojP0SQ', 'android', '2026-08-03 04:10:45', '2026-08-03 04:10:45'),
(216, 12, 'fto6_sVXSiiBJtRTErfCEb:APA91bG4j6zyhxuXGCAXU6RHKR0h_oNqIk6dZ6KKo52TRy_0VvLscD-wv3Y8B4oDhAjle8Y-0AjkadRNlQzEw8sCUZCqmriQzyuBc0OoGg8jT5wf4fYXNM8', 'android', '2026-08-03 05:26:13', '2026-08-03 05:26:13'),
(217, 12, 'dQdmqHpESgGf6y3EV9cLXO:APA91bFMvK_iYmcGSfqpIy90Ae2qkQaMANr9fh57QyfBzPQ7K1PdeFDxZPr7Lecald64PkY5265idg4MvAa1TACjnaWUonSHS5flf-3XRLrYKZAy9aoLvHY', 'android', '2026-08-03 05:33:00', '2026-08-03 05:33:00'),
(218, 12, 'eppbgAkcSh6xs-_3plzx3b:APA91bHuQ34aFwXnVnQ5iMWjasV5TOPWKkSqkXYyK_9SDCgpdnohfEv89pF5K9hI5moz0N409gy8c3MGcrJr5LwfKGFtsqanoGnUBBxou2x-ENJcCKH9O_A', 'android', '2026-08-08 07:42:18', '2026-08-08 07:42:18'),
(219, 12, 'c1-5m3x8TxGJi7U0kYFJai:APA91bHVHH4RBM_AWZX3bM5VLJuJq8FKAV0urEyJfLarK-D57v_ueU434SvyGXJ62HR_IEyCfMzrdko0N_-oL5PS3TVfWttCLX258CTxaZU_c_NIsnja3jc', 'android', '2026-08-08 09:46:23', '2026-08-08 09:46:23'),
(225, 12, 'ed_Zy00KR-2yeB56xXrL2D:APA91bGUEnj7NmIyjNKbtfms8Nfvpy7vs_aMlizrTFWHSICUy-OsEYiZUQXy26pzbBCKCN77_sHhO3uVgbFxg2LaUUluB7H2XXics5dlQFrfPm2ntHxzJpk', 'android', '2026-09-13 13:13:53', '2026-09-13 13:13:53'),
(233, 12, 'fJXzamRKTTqoIZKIZlqg_G:APA91bGZl_vv-fc9qOkq5a7ge60MloL3Sf0w9tCjupAw-vl6qAAeitXd9cX2AicurGcLoxqqzXbCyyTFf87Cd2E7J03Q-6KIoTx-mNk7XG0PuwId4A74_SU', 'android', '2026-09-16 05:35:14', '2026-09-16 05:35:14'),
(234, 2, 'eJRRy2nCmZSGjV80vTEvL-:APA91bEqKl7vvs2O5guFULUwMcih6ECspmKl0mCdeKUkFHRvJVoccPZ5XyLl3Pjjc_EzCMB8j71nZKR3l6nLo3BQlvb7KzpwHRfXIk_o2GufG3q1Gn__6DE', 'web', '2026-09-18 07:34:56', '2026-09-18 07:44:42'),
(238, 24, 'ePfAzNjjQee4V5JaVUv35d:APA91bHxzfbbkoXkch7zm4U8P1Cdj3lhvrUsxjmosd4d3mEm-c6mVJLkHOnHNd6ZPhgUA810uEoIBWX0pxocl1_KVg8rXWSeo6aPm7mQlckvqR4DbGXrzrg', 'android', '2026-09-21 14:00:57', '2026-09-21 14:00:57'),
(240, 12, 'eUBkD42ebKlvPcJRPGcEfC:APA91bErxJNKxf46SH6KekkDX0-JZgoVcQNP60rTlSTslsQDK2vWBwbnXc8p-TYuousw_LM4-dDTxtaS4JUCZ6WEs3X-tbJn1RwP_jtL8AhDZEPUhOY93JU', 'web', '2026-10-07 07:19:15', '2026-10-07 07:19:15'),
(241, 4, 'eUBkD42ebKlvPcJRPGcEfC:APA91bErxJNKxf46SH6KekkDX0-JZgoVcQNP60rTlSTslsQDK2vWBwbnXc8p-TYuousw_LM4-dDTxtaS4JUCZ6WEs3X-tbJn1RwP_jtL8AhDZEPUhOY93JU', 'web', '2026-10-07 07:19:49', '2026-10-07 07:19:50'),
(244, 2, 'fu5Asknwb8wpOq6MdACwN-:APA91bHYlLMWIDyW8iqLFvO0iUzc4JiyrMvM14a6sZ_H5K1N_S04ib2fURJa00z7YvTi0B52WsTweQYQGnV6myhEiyNq_nnQ4DPltt5YOSjVgjFvFPaSIUM', 'web', '2026-10-08 05:24:03', '2026-10-08 05:24:03'),
(245, 2, 'fu5Asknwb8wpOq6MdACwN-:APA91bGk9J6OejCf9G_gqc28oftSKoGURFamFaXYcD61-H0v8j1VRqAdRFDgshWZNAc3J2g7-ADO8KsviGwGYUAZvkabbRYz37_Kto2nmb2sl9P9-XQXlxY', 'web', '2026-10-08 05:24:04', '2026-10-08 05:24:04'),
(246, 2, 'fu5Asknwb8wpOq6MdACwN-:APA91bG8OrdpBTqnwlmHiCrUp5QZIfv6RwN-39S1iSLiKnXQSs7sp9l-BTu0Pg-HBQY5a5HKw3iWW7LsoLoao9G8EhzhZlR4fHvGcdydg_An8Cn-SxF4_Ew', 'web', '2026-10-08 05:24:04', '2026-10-08 05:24:04'),
(247, 21, 'dolAv2G2SyirvCSKrwj_Ly:APA91bGKYWygTolM9GZCEwSZs6xpqteGo20LyfEAyJmoghZdTBxczSSTvqe0BKNBqwJnHb-qTjGNLjxn8t05v17OLnCN_hJUFyqOWwz91EFs1wCCgMewxg4', 'android', '2026-10-08 05:24:20', '2026-10-08 05:24:20'),
(248, 12, 'd1bzne1hTqGGjJWwUJoCkl:APA91bED1icUuxuLqakh1utH4PHPN0M6nMK0z96z30SkanhMsgQO_Szp3tQJ99re6Dr2vaLQH1Ep3I-45FYGsDr3DyKREfZzie2wrbTNDtzTt3_rTI63ixA', 'android', '2026-10-08 06:01:02', '2026-10-08 06:01:02'),
(249, 12, 'evPId-NLSb2fnmUsaSIMgL:APA91bEHQ5ZRWpFzN3I6Yt77HXldHCuPfJFwOTltY6F68j_VOV66XFi27D4Hh9dI1HYIcEFW88hU82GciGJWCyMfyQyHndwINwk4RfoLheG79FeDoiKvKLs', 'android', '2026-10-08 06:50:49', '2026-10-08 06:50:49'),
(258, 21, 'coSlaTgAQiintRFvbolgY5:APA91bFAB_eFwZZCXd0nTAs_bk0hdxxmJwlfjkw12MHT6znzsVYPa9Gu8Gfkib5gFvDOc8grqWpjDiOP3nOh3gI-W0b19JwYU_bHhRPvH93gZvu53c8ODEY', 'android', '2026-10-12 08:46:23', '2026-10-12 08:46:23'),
(259, 12, 'e9oG1xvJTNOLJwzgHE2Wqs:APA91bGoIhKKFcosayEaPCys5ShJftS22bz1wh0z1p9c5oq93_fecgxMtG3i2Whbx9neU0crhgtmnKvZ3_tiM1s-FvrWVl7mBn21rQjgnRitRqDwVdyVVPQ', 'android', '2026-10-12 08:48:13', '2026-10-12 08:48:13'),
(260, 2, 'fu5Asknwb8wpOq6MdACwN-:APA91bEvpx-7Gg9rSqCVFxvhjjt02LLlI5zThSEIyOXod2DSA3Zy7C41jK2sG6q76rIunDCjde0vqNtxMbDjiI286clBiK6tX1KLFOESGC_Tb3MXKiBjs9s', 'web', '2026-10-12 10:57:26', '2026-10-12 10:57:26'),
(261, 2, 'fu5Asknwb8wpOq6MdACwN-:APA91bEOEANuJNEKNk0S5Od_gA1qHlmGrOd7Uy27oeqP81il0rC-zObFMZt0jTQ1UlORsmQh4EnDfYddIFrgphGk_QKb7K8WcXvXQc_Y7fkiwncwetJPiDM', 'web', '2026-10-12 10:57:26', '2026-10-12 10:57:26'),
(262, 2, 'fu5Asknwb8wpOq6MdACwN-:APA91bGsifBEnl3TXq3edgim0CuspJIw5ovwQXD_BsHEvXfSEfMYAr15xXnx_GtFfgOPhDQoDOE0-7Lk8xtapaBXqDx388KnYPkvgI75ILdpZGdXcOxLBsk', 'web', '2026-10-12 10:57:27', '2026-10-14 09:33:32'),
(275, 12, 'c5OqdUXuTraK3YHN-BOIKH:APA91bE3T26q-w3trC2UqJfHcyqFu4xRUUHwQOTySsH_t2tWPtj9M62BhMXhZ8spYZKnfteFCNhCuM1DV_LmQWVvnqX0XX0qHHF4LtVFNJwMPr6uyNp7k4U', 'android', '2026-10-12 12:27:34', '2026-10-12 12:27:34'),
(278, 39, 'cesB8AhDQFWGieoZC1Zedb:APA91bHdBHPDQfpQ9Ec2__ZNjgwIGEGT05WJsfh-H3TYnIiC6-52BXXoCZrzDPernlzIG6abtPoUGeD5xSJcug1nTfjGAiY6qGbZhO8pcLkEmI_umbGW6HY', 'android', '2026-10-12 17:30:51', '2026-10-12 17:30:51'),
(279, 12, 'cnzRRsKVSyW7zyz1VTgbaE:APA91bG13ZWNDaLB7Qc08VnfZlLgyEqEMN4-nAQuxlTNBktWrMKVvjEFQGhEd-L5ovzFn7mm0kHmeqmuQ6fmds0-cHjrWxwvmgc27vApJWe57u247ck0GXA', 'android', '2026-10-13 04:09:25', '2026-10-13 04:09:25'),
(353, 12, 'cOZYtPFzQ1mnLeEQpeo4R3:APA91bErTC95bVdhFkzCUGKRQ0hjM9XRsNln4dxjPluWyXMqrOT5Gh6HK9wCcDyVOaduDiqSblyndwAUk0bqFhuX_kB1oglhtM5nF5dNBis1Jtxj5mj1jB4', 'android', '2026-10-13 09:59:56', '2026-10-13 09:59:56');

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
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `society_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT 'Event',
  `event_date` date NOT NULL,
  `event_time` varchar(100) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `organizer` varchar(255) DEFAULT NULL,
  `price` varchar(100) DEFAULT 'Free',
  `cover_image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `attendees` int(11) DEFAULT 0,
  `rating` decimal(3,1) DEFAULT 0.0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `society_id`, `user_id`, `title`, `category`, `event_date`, `event_time`, `location`, `organizer`, `price`, `cover_image`, `description`, `tags`, `attendees`, `rating`, `created_at`, `updated_at`) VALUES
(2, 1, 2, 'Diwali Celebration', 'Event', '2026-06-29', '11:20:00', 'Clubhouse', 'Society Commette', '100', 'https://amzn-s3-mygatebell.s3.us-east-1.amazonaws.com/events/uid2_1781931232_6a361ce0180f2.png', 'Diwali celebration event.', 'Festival, Diya, Enjoy, Sparkals, Together, Witness', 2, 0.0, '2026-06-20 05:43:49', '2026-10-12 21:28:35'),
(3, 1, 2, 'Dasara celebration', 'Event', '2026-10-12', '', 'Maharashtra', '', 'Free', '', '', '', 1, 0.0, '2026-10-12 09:50:55', '2026-10-12 21:28:15'),
(4, 1, 2, 'navartari celebration', 'Event', '2026-10-12', '', '', '', 'Free', '', '', '', 1, 0.0, '2026-10-12 15:34:59', '2026-10-12 21:27:55');

-- --------------------------------------------------------

--
-- Table structure for table `event_attendees`
--

CREATE TABLE `event_attendees` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('going','maybe','not_going') DEFAULT 'going',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_attendees`
--

INSERT INTO `event_attendees` (`id`, `event_id`, `user_id`, `status`, `created_at`) VALUES
(1, 2, 12, 'going', '2026-06-20 06:56:32'),
(2, 4, 26, 'going', '2026-10-12 21:27:55'),
(3, 3, 26, 'going', '2026-10-12 21:28:15'),
(4, 2, 26, 'going', '2026-10-12 21:28:35');

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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `member_type` varchar(20) DEFAULT 'Adult'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `family_members`
--

INSERT INTO `family_members` (`id`, `resident_id`, `name`, `relation`, `phone`, `is_active`, `image_url`, `created_at`, `member_type`) VALUES
(1, 12, 'Aayushi Wane', 'Sister', '+918208107030', 0, NULL, '2026-05-26 03:53:19', 'Adult'),
(3, 12, 'Satoshi Nakamoto', 'Father', '+919636587414', 1, 'https://amzn-s3-mygatebell.s3.us-east-1.amazonaws.com/profiles/uid12_1781754477_6a336a6d08b2c.jpg', '2026-06-18 03:48:24', 'Adult');

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
  `flat_type` enum('1RK','1BHK','2BHK','3BHK','4BHK','4BHK+') NOT NULL DEFAULT '2BHK',
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

INSERT INTO `flats` (`id`, `flat_number`, `flat_type`, `floor_number`, `building_id`, `area_sqft`, `owner_id`, `tenant_id`, `society_id`, `is_occupied`, `created_at`, `updated_at`, `user_role`, `occupancy_status`, `document_url`, `verification_status`) VALUES
(2, '102', '2BHK', '1', 1, 750.00, 19, 13, 1, 0, '2026-05-18 11:40:43', '2026-05-19 09:01:44', 'owner', 'residing', NULL, 'pending'),
(5, '103', '1BHK', '1', 1, 850.00, 12, NULL, 1, 1, '2026-05-19 06:30:15', '2026-05-19 06:30:15', 'owner', 'residing', NULL, 'pending'),
(6, '104', '2BHK', '1', 1, 800.00, 13, 20, 1, 1, '2026-05-19 06:32:03', '2026-05-19 09:52:46', 'renting_family', 'residing', NULL, 'pending'),
(7, '102', '2BHK', '2', 2, 50.00, 2, NULL, 1, 1, '2026-07-29 14:31:14', '2026-07-29 14:31:14', 'owner', 'residing', NULL, 'pending'),
(8, '106', '2BHK', NULL, 1, NULL, NULL, 2, 1, 1, '2026-07-29 14:34:28', '2026-07-29 14:34:28', 'renting_family', 'residing', NULL, 'pending'),
(9, '101', '3BHK', '1', 4, 1850.00, 2, NULL, 1, 1, '2026-10-12 14:04:35', '2026-10-12 14:04:35', 'owner', 'empty', NULL, 'pending');

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
-- Table structure for table `guard_attendance`
--

CREATE TABLE `guard_attendance` (
  `id` int(11) NOT NULL,
  `guard_id` int(11) NOT NULL,
  `society_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `in_time` datetime DEFAULT NULL,
  `out_time` datetime DEFAULT NULL,
  `status` enum('present','absent','half_day','off') NOT NULL DEFAULT 'present',
  `notes` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `guard_attendance`
--

INSERT INTO `guard_attendance` (`id`, `guard_id`, `society_id`, `date`, `in_time`, `out_time`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 21, 1, '2026-06-03', '2026-06-03 05:57:07', NULL, 'present', NULL, '2026-06-03 05:57:07', '2026-06-03 06:24:21'),
(2, 21, 1, '2026-06-09', '2026-06-09 12:36:41', NULL, 'present', NULL, '2026-06-09 12:36:41', '2026-06-09 12:36:41'),
(3, 21, 1, '2026-06-15', '2026-06-15 06:43:04', NULL, 'present', NULL, '2026-06-15 06:43:04', '2026-06-15 06:43:04'),
(4, 21, 1, '2026-06-17', '2026-06-17 04:50:09', NULL, 'present', NULL, '2026-06-17 04:50:09', '2026-06-17 04:50:09'),
(5, 21, 1, '2026-06-23', '2026-06-23 05:46:18', NULL, 'present', NULL, '2026-06-23 05:46:18', '2026-06-23 05:46:18'),
(6, 21, 1, '2026-07-03', '2026-07-03 07:12:22', NULL, 'present', NULL, '2026-07-03 07:12:22', '2026-07-03 07:12:22'),
(7, 21, 1, '2026-07-06', '2026-07-06 04:54:47', NULL, 'present', NULL, '2026-07-06 04:54:47', '2026-07-06 04:54:47'),
(8, 21, 1, '2026-09-15', '2026-09-15 11:50:54', '2026-09-15 11:51:14', 'present', NULL, '2026-09-15 17:20:54', '2026-09-15 17:21:14'),
(9, 21, 1, '2026-10-12', '2026-10-12 10:13:10', '2026-10-12 10:13:38', 'present', NULL, '2026-10-12 15:43:09', '2026-10-12 15:43:37'),
(10, 39, 1, '2026-10-12', '2026-10-12 18:05:12', '2026-10-12 18:05:23', 'present', NULL, '2026-10-12 23:35:09', '2026-10-12 23:35:23');

-- --------------------------------------------------------

--
-- Table structure for table `guard_vehicle_entries`
--

CREATE TABLE `guard_vehicle_entries` (
  `id` int(11) NOT NULL,
  `vehicle_type` varchar(100) NOT NULL,
  `vehicle_number` varchar(20) NOT NULL,
  `driver_name` varchar(150) NOT NULL,
  `driver_phone` varchar(15) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `guard_id` int(11) NOT NULL,
  `society_id` int(11) NOT NULL,
  `status` enum('inside','exited') NOT NULL DEFAULT 'inside',
  `entry_time` datetime NOT NULL DEFAULT current_timestamp(),
  `exit_time` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `guard_vehicle_entries`
--

INSERT INTO `guard_vehicle_entries` (`id`, `vehicle_type`, `vehicle_number`, `driver_name`, `driver_phone`, `purpose`, `resident_id`, `guard_id`, `society_id`, `status`, `entry_time`, `exit_time`, `created_at`, `updated_at`) VALUES
(1, 'Water tanker', 'MH12UH5679', 'kjctbicyc6', '8574253698', 'to fill big water tank', NULL, 21, 1, 'exited', '2026-06-03 06:22:55', '2026-06-03 06:28:22', '2026-06-03 06:22:55', '2026-06-03 06:28:22'),
(2, 'garbage truck', 'MHFK', 'srao', '852369147963', 'garbage', NULL, 21, 1, 'exited', '2026-09-15 10:11:04', '2026-09-15 10:11:27', '2026-09-15 15:41:03', '2026-09-15 15:41:26'),
(3, 'garbage', 'MH578', 'RAVI', '9632587419', 'garbage', NULL, 21, 1, 'exited', '2026-09-21 17:03:39', '2026-09-21 17:06:56', '2026-09-21 22:33:38', '2026-09-21 22:36:55'),
(4, 'Water tanker', 'MH12FH8629', 'Kiran Sharma', '8758632547', 'Fill common water tank', NULL, 21, 1, 'inside', '2026-10-09 04:52:56', NULL, '2026-10-09 10:22:55', '2026-10-09 10:22:55'),
(5, 'Water tanker', 'MH12ID6972', 'Unmesh Korde', '9685253647', 'Fill common water tank', NULL, 21, 1, 'exited', '2026-10-09 05:09:55', '2026-10-09 05:25:20', '2026-10-09 10:39:52', '2026-10-09 10:55:18'),
(6, 'Cleaning service vehicle', 'MH16HC4693', 'Kiran Mehta', '7808524147', 'Clean society premices', NULL, 21, 1, 'inside', '2026-10-09 05:24:25', NULL, '2026-10-09 10:54:24', '2026-10-09 10:54:24');

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
  `status` enum('draft','sent','pending','paid','overdue','cancelled') DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_number`, `flat_id`, `resident_id`, `society_id`, `invoice_date`, `due_date`, `total_amount`, `total_gst`, `total_discount`, `arrears_amount`, `fine_amount`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(6, 'INV-1-000001', 5, NULL, 1, '2026-05-01', '2026-05-10', 3500.00, 0.00, 0.00, 0.00, 0.00, 'paid', NULL, 2, '2026-05-26 12:44:42', '2026-06-24 04:35:09'),
(7, 'INV-1-000002', 6, NULL, 1, '2026-05-01', '2026-05-10', 5000.00, 0.00, 0.00, 0.00, 0.00, 'paid', NULL, 2, '2026-05-26 12:44:42', '2026-06-24 04:57:35'),
(11, 'INV-1-000003', 5, NULL, 1, '2026-06-01', '2026-06-10', 3500.00, 0.00, 0.00, 0.00, 0.00, 'paid', NULL, 2, '2026-06-24 05:48:42', '2026-06-26 08:41:22'),
(12, 'INV-1-000004', 6, NULL, 1, '2026-06-01', '2026-06-10', 3500.00, 0.00, 0.00, 0.00, 0.00, 'pending', NULL, 2, '2026-06-24 05:48:43', '2026-07-07 04:12:45'),
(13, 'INV-1-000005', 5, NULL, 1, '2026-06-01', '2026-06-10', 3500.00, 0.00, 0.00, 0.00, 0.00, 'paid', NULL, 2, '2026-06-24 05:59:07', '2026-09-12 16:14:54'),
(14, 'INV-1-000006', 6, NULL, 1, '2026-06-01', '2026-06-10', 3500.00, 0.00, 0.00, 0.00, 0.00, 'pending', NULL, 2, '2026-06-24 05:59:08', '2026-07-07 04:12:53'),
(15, 'INV-1-000007', 5, NULL, 1, '2026-06-26', '2026-06-29', 1300.00, 0.00, 0.00, 0.00, 0.00, 'paid', 'Please fill fast.', 2, '2026-06-26 08:42:40', '2026-09-15 15:06:15'),
(16, 'INV-1-000008', 5, NULL, 1, '2026-10-11', '2026-10-13', 100.00, 0.00, 0.00, 0.00, 0.00, 'paid', 'yjhfgyjf kghuioiobjkuioobn iohiohio', 2, '2026-10-12 09:34:52', '2026-10-12 09:35:02'),
(17, 'INV-1-000009', 5, NULL, 1, '2026-10-12', '2026-10-13', 2500.00, 0.00, 0.00, 0.00, 0.00, 'draft', 'tata', 2, '2026-10-12 09:43:03', '2026-10-12 09:43:03'),
(18, 'INV-1-000010', 9, NULL, 1, '2026-10-12', '2026-10-12', 99.99, 0.00, 0.00, 0.00, 0.00, 'draft', '', 2, '2026-10-12 14:41:51', '2026-10-12 14:41:51'),
(19, 'INV-1-000011', 8, NULL, 1, '2026-10-12', '2026-10-16', 2500.00, 0.00, 0.00, 0.00, 0.00, 'draft', '', 2, '2026-10-12 17:46:41', '2026-10-12 17:46:41'),
(23, 'INV-1-000012', 5, NULL, 1, '2026-10-12', '2026-10-16', 2500.00, 0.00, 0.00, 0.00, 0.00, 'draft', '', 2, '2026-10-12 17:46:58', '2026-10-12 17:46:58'),
(25, 'INV-1-000013', 8, NULL, 1, '2026-10-12', '2026-10-16', 2500.00, 0.00, 0.00, 0.00, 0.00, 'draft', '', 2, '2026-10-12 17:47:14', '2026-10-12 17:47:14'),
(26, 'INV-1-000014', 8, NULL, 1, '2026-10-12', '2026-10-16', 2500.00, 0.00, 0.00, 0.00, 0.00, 'draft', '', 2, '2026-10-12 17:47:14', '2026-10-12 17:47:14'),
(27, 'INV-1-000015', 8, NULL, 1, '2026-10-12', '2026-10-16', 2500.00, 0.00, 0.00, 0.00, 0.00, 'draft', '', 2, '2026-10-12 17:47:14', '2026-10-12 17:47:14'),
(28, 'INV-1-000016', 2, 13, 1, '2026-10-14', '2026-10-16', 2500.00, 0.00, 0.00, 0.00, 0.00, 'pending', 'vesber regre berb eberbher brewbetbetd ebwrbhwr', 2, '2026-10-14 09:32:26', '2026-10-14 09:32:26'),
(29, 'INV-1-000017', 5, 12, 1, '2026-10-14', '2026-10-16', 2500.00, 0.00, 0.00, 0.00, 0.00, 'pending', 'vesber regre berb eberbher brewbetbetd ebwrbhwr', 2, '2026-10-14 09:32:26', '2026-10-14 09:32:26'),
(30, 'INV-1-000018', 6, 20, 1, '2026-10-14', '2026-10-16', 2500.00, 0.00, 0.00, 0.00, 0.00, 'pending', 'vesber regre berb eberbher brewbetbetd ebwrbhwr', 2, '2026-10-14 09:32:26', '2026-10-14 09:32:26'),
(31, 'INV-1-000019', 8, 2, 1, '2026-10-14', '2026-10-16', 2500.00, 0.00, 0.00, 0.00, 0.00, 'pending', 'vesber regre berb eberbher brewbetbetd ebwrbhwr', 2, '2026-10-14 09:32:26', '2026-10-14 09:32:26'),
(32, 'INV-1-000020', 5, 12, 1, '2026-10-14', '2026-10-19', 1000.00, 0.00, 0.00, 0.00, 0.00, 'pending', 'hukiuytr defsyhrdh', 2, '2026-10-14 09:33:15', '2026-10-14 09:33:15');

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

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `charge_head_id`, `description`, `quantity`, `unit_price`, `gst_rate`, `gst_amount`, `total_amount`, `created_at`) VALUES
(7, 6, 1, NULL, 1.00, 2500.00, 0.00, 0.00, 2500.00, '2026-05-26 12:44:42'),
(8, 6, 2, NULL, 1.00, 500.00, 0.00, 0.00, 500.00, '2026-05-26 12:44:42'),
(9, 6, 3, NULL, 1.00, 500.00, 0.00, 0.00, 500.00, '2026-05-26 12:44:42'),
(10, 7, 1, NULL, 1.00, 2500.00, 0.00, 0.00, 2500.00, '2026-05-26 12:44:42'),
(11, 7, 2, NULL, 1.00, 500.00, 0.00, 0.00, 500.00, '2026-05-26 12:44:42'),
(12, 7, 3, NULL, 1.00, 500.00, 0.00, 0.00, 500.00, '2026-05-26 12:44:42'),
(13, 11, 1, NULL, 1.00, 2500.00, 0.00, 0.00, 2500.00, '2026-06-24 05:48:42'),
(14, 11, 2, NULL, 1.00, 500.00, 0.00, 0.00, 500.00, '2026-06-24 05:48:42'),
(15, 11, 3, NULL, 1.00, 500.00, 0.00, 0.00, 500.00, '2026-06-24 05:48:42'),
(16, 12, 1, NULL, 1.00, 2500.00, 0.00, 0.00, 2500.00, '2026-06-24 05:48:43'),
(17, 12, 2, NULL, 1.00, 500.00, 0.00, 0.00, 500.00, '2026-06-24 05:48:43'),
(18, 12, 3, NULL, 1.00, 500.00, 0.00, 0.00, 500.00, '2026-06-24 05:48:43'),
(19, 13, 1, NULL, 1.00, 2500.00, 0.00, 0.00, 2500.00, '2026-06-24 05:59:07'),
(20, 13, 2, NULL, 1.00, 500.00, 0.00, 0.00, 500.00, '2026-06-24 05:59:07'),
(21, 13, 3, NULL, 1.00, 500.00, 0.00, 0.00, 500.00, '2026-06-24 05:59:07'),
(22, 14, 1, NULL, 1.00, 2500.00, 0.00, 0.00, 2500.00, '2026-06-24 05:59:08'),
(23, 14, 2, NULL, 1.00, 500.00, 0.00, 0.00, 500.00, '2026-06-24 05:59:08'),
(24, 14, 3, NULL, 1.00, 500.00, 0.00, 0.00, 500.00, '2026-06-24 05:59:08'),
(25, 15, 1, NULL, 1.00, 1000.00, 0.00, 0.00, 1000.00, '2026-06-26 08:42:40'),
(26, 15, 2, NULL, 1.00, 200.00, 0.00, 0.00, 200.00, '2026-06-26 08:42:40'),
(27, 15, 3, NULL, 1.00, 100.00, 0.00, 0.00, 100.00, '2026-06-26 08:42:40'),
(28, 16, 4, NULL, 1.00, 100.00, 0.00, 0.00, 100.00, '2026-10-12 09:34:52'),
(29, 17, 1, NULL, 1.00, 2500.00, 0.00, 0.00, 2500.00, '2026-10-12 09:43:03'),
(30, 18, 4, NULL, 1.00, 99.99, 0.00, 0.00, 99.99, '2026-10-12 14:41:51'),
(31, 19, 1, NULL, 1.00, 2500.00, 0.00, 0.00, 2500.00, '2026-10-12 17:46:41'),
(32, 23, 1, NULL, 1.00, 2500.00, 0.00, 0.00, 2500.00, '2026-10-12 17:46:58'),
(33, 25, 1, NULL, 1.00, 2500.00, 0.00, 0.00, 2500.00, '2026-10-12 17:47:14'),
(34, 26, 1, NULL, 1.00, 2500.00, 0.00, 0.00, 2500.00, '2026-10-12 17:47:14'),
(35, 27, 1, NULL, 1.00, 2500.00, 0.00, 0.00, 2500.00, '2026-10-12 17:47:14'),
(36, 28, 1, NULL, 1.00, 2500.00, 0.00, 0.00, 2500.00, '2026-10-14 09:32:26'),
(37, 29, 1, NULL, 1.00, 2500.00, 0.00, 0.00, 2500.00, '2026-10-14 09:32:26'),
(38, 30, 1, NULL, 1.00, 2500.00, 0.00, 0.00, 2500.00, '2026-10-14 09:32:26'),
(39, 31, 1, NULL, 1.00, 2500.00, 0.00, 0.00, 2500.00, '2026-10-14 09:32:26'),
(40, 32, 3, NULL, 2.00, 500.00, 0.00, 0.00, 1000.00, '2026-10-14 09:33:15');

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
  `type` varchar(50) DEFAULT 'general',
  `reference_id` int(11) DEFAULT NULL,
  `action_url` varchar(255) DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `reference_id`, `action_url`, `data`, `is_read`, `read_at`, `created_at`) VALUES
(1, 12, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 11, '/visitor_details', NULL, 1, '2026-06-23 05:48:54', '2026-06-17 04:11:07'),
(2, 12, 'Visitor Update', 'Your visitor has exited the premises.', 'visitor_status', 11, '/visitor_details', NULL, 1, '2026-06-17 04:59:05', '2026-06-17 04:11:31'),
(3, 12, 'New Visitor at Gate', 'Sanjay Jadhav is requesting entry for Family meet.', 'visitor_request', 12, '/visitor_details', NULL, 1, '2026-06-23 05:48:54', '2026-06-17 04:47:11'),
(4, 21, 'Expected Visitor', 'Resident has pre-approved visitor Kishori Patil.', 'visitor_pre_approval', 13, '/guard/visitor_details', NULL, 1, '2026-06-17 04:58:51', '2026-06-17 04:49:20'),
(5, 5, 'New Poll', 'my first poll', 'poll_created', 1, '/communications/polls', NULL, 0, NULL, '2026-06-22 06:05:33'),
(6, 6, 'New Poll', 'my first poll', 'poll_created', 1, '/communications/polls', NULL, 0, NULL, '2026-06-22 06:05:33'),
(7, 12, 'New Poll', 'my first poll', 'poll_created', 1, '/communications/polls', NULL, 1, '2026-06-23 05:48:52', '2026-06-22 06:05:33'),
(8, 13, 'New Poll', 'my first poll', 'poll_created', 1, '/communications/polls', NULL, 0, NULL, '2026-06-22 06:05:34'),
(9, 19, 'New Poll', 'my first poll', 'poll_created', 1, '/communications/polls', NULL, 0, NULL, '2026-06-22 06:05:34'),
(10, 20, 'New Poll', 'my first poll', 'poll_created', 1, '/communications/polls', NULL, 0, NULL, '2026-06-22 06:05:34'),
(11, 21, 'Expected Visitor', 'Resident has pre-approved visitor John Doe.', 'visitor_pre_approval', 14, '/guard/visitor_details', NULL, 0, NULL, '2026-06-23 07:58:40'),
(12, 21, 'Visitor Status Update', 'Resident has approved the visitor.', 'visitor_status', 13, '/guard/visitor_details', NULL, 1, '2026-07-03 07:12:49', '2026-06-23 07:58:51'),
(13, 21, 'Visitor Status Update', 'Resident has approved the visitor.', 'visitor_status', 14, '/guard/visitor_details', NULL, 1, '2026-07-03 07:12:49', '2026-06-23 08:40:29'),
(14, 21, 'Visitor Status Update', 'Resident has approved the visitor.', 'visitor_status', 7, '/guard/visitor_details', NULL, 1, '2026-07-03 07:12:48', '2026-06-23 08:40:51'),
(15, 21, 'Visitor Status Update', 'Resident has rejected the visitor.', 'visitor_status', 12, '/guard/visitor_details', NULL, 1, '2026-07-03 07:12:48', '2026-06-23 08:40:54'),
(16, 2, 'Payment Received', 'A payment of ₹3500 was received for Invoice #6.', 'payment_received', 2, '/admin/accounting/payments', NULL, 1, '2026-09-16 06:59:27', '2026-06-23 10:16:16'),
(17, 4, 'Payment Received', 'A payment of ₹3500 was received for Invoice #6.', 'payment_received', 2, '/admin/accounting/payments', NULL, 0, NULL, '2026-06-23 10:16:16'),
(18, 12, 'Payment Successful', 'Your payment of ₹3500 was received successfully.', 'payment_received', 2, '/accounting/receipts', NULL, 1, '2026-10-14 08:26:40', '2026-06-23 10:16:16'),
(19, 2, 'Payment Received', 'A payment of ₹3500 was received for Invoice #6.', 'payment_received', 3, '/admin/accounting/payments', NULL, 1, '2026-09-16 06:59:27', '2026-06-24 04:35:09'),
(20, 4, 'Payment Received', 'A payment of ₹3500 was received for Invoice #6.', 'payment_received', 3, '/admin/accounting/payments', NULL, 0, NULL, '2026-06-24 04:35:09'),
(21, 2, 'Payment Received', 'A payment of ₹5000 was received for Invoice #7.', 'payment_received', 4, '/admin/accounting/payments', NULL, 1, '2026-09-16 06:59:27', '2026-06-24 04:57:35'),
(22, 4, 'Payment Received', 'A payment of ₹5000 was received for Invoice #7.', 'payment_received', 4, '/admin/accounting/payments', NULL, 0, NULL, '2026-06-24 04:57:35'),
(23, 12, 'New Invoice Generated', 'Invoice INV-1-000003 for amount ₹3500 has been generated.', 'invoice_generated', 11, '/accounting/invoices', NULL, 1, '2026-07-07 12:49:03', '2026-06-24 05:48:42'),
(24, 20, 'New Invoice Generated', 'Invoice INV-1-000004 for amount ₹3500 has been generated.', 'invoice_generated', 12, '/accounting/invoices', NULL, 0, NULL, '2026-06-24 05:48:43'),
(25, 12, 'New Invoice Generated', 'Invoice INV-1-000005 for amount ₹3500 has been generated.', 'invoice_generated', 13, '/accounting/invoices', NULL, 1, '2026-10-14 08:26:40', '2026-06-24 05:59:07'),
(26, 20, 'New Invoice Generated', 'Invoice INV-1-000006 for amount ₹3500 has been generated.', 'invoice_generated', 14, '/accounting/invoices', NULL, 0, NULL, '2026-06-24 05:59:08'),
(27, 12, 'New Invoice Generated', 'Invoice INV-1-000007 for amount ₹1300 has been generated.', 'invoice_generated', 15, '/accounting/invoices', NULL, 1, '2026-06-26 08:56:45', '2026-06-26 08:42:40'),
(28, 5, 'New Poll', 'What should be date to celebrate Society Anniversary', 'poll_created', 2, '/communications/polls', NULL, 0, NULL, '2026-07-04 04:38:31'),
(29, 6, 'New Poll', 'What should be date to celebrate Society Anniversary', 'poll_created', 2, '/communications/polls', NULL, 0, NULL, '2026-07-04 04:38:31'),
(30, 12, 'New Poll', 'What should be date to celebrate Society Anniversary', 'poll_created', 2, '/communications/polls', NULL, 1, '2026-10-14 08:26:40', '2026-07-04 04:38:31'),
(31, 13, 'New Poll', 'What should be date to celebrate Society Anniversary', 'poll_created', 2, '/communications/polls', NULL, 0, NULL, '2026-07-04 04:38:31'),
(32, 19, 'New Poll', 'What should be date to celebrate Society Anniversary', 'poll_created', 2, '/communications/polls', NULL, 0, NULL, '2026-07-04 04:38:31'),
(33, 20, 'New Poll', 'What should be date to celebrate Society Anniversary', 'poll_created', 2, '/communications/polls', NULL, 0, NULL, '2026-07-04 04:38:31'),
(34, 2, 'New Helpdesk Ticket', 'A new high priority ticket has been created.', 'ticket_created', 3, '/admin/helpdesk', NULL, 1, '2026-09-16 06:59:27', '2026-07-08 04:12:45'),
(35, 4, 'New Helpdesk Ticket', 'A new high priority ticket has been created.', 'ticket_created', 3, '/admin/helpdesk', NULL, 0, NULL, '2026-07-08 04:12:45'),
(36, 21, 'Expected Visitor', 'Resident has pre-approved visitor jycr.', 'visitor_pre_approval', 15, '/guard/visitor_details', NULL, 0, NULL, '2026-07-08 04:33:41'),
(37, 21, 'Expected Visitor', 'Resident has pre-approved visitor huftbhuyvhc buugycct.', 'visitor_pre_approval', 16, '/guard/visitor_details', NULL, 0, NULL, '2026-07-08 04:35:55'),
(38, 2, 'New Helpdesk Ticket', 'A new medium priority ticket has been created.', 'ticket_created', 4, '/admin/helpdesk', NULL, 1, '2026-09-16 06:59:27', '2026-07-08 04:36:40'),
(39, 4, 'New Helpdesk Ticket', 'A new medium priority ticket has been created.', 'ticket_created', 4, '/admin/helpdesk', NULL, 0, NULL, '2026-07-08 04:36:40'),
(40, 2, 'New Helpdesk Ticket', 'A new medium priority ticket has been created.', 'ticket_created', 5, '/admin/helpdesk', NULL, 1, '2026-09-16 06:59:27', '2026-07-08 04:45:20'),
(41, 4, 'New Helpdesk Ticket', 'A new medium priority ticket has been created.', 'ticket_created', 5, '/admin/helpdesk', NULL, 0, NULL, '2026-07-08 04:45:20'),
(42, 21, 'Expected Visitor', 'Resident has pre-approved visitor jycr.', 'visitor_pre_approval', 17, '/guard/visitor_details', NULL, 0, NULL, '2026-07-08 06:22:38'),
(43, 21, 'Expected Visitor', 'Resident has pre-approved visitor xyz.', 'visitor_pre_approval', 18, '/guard/visitor_details', NULL, 0, NULL, '2026-07-18 10:44:50'),
(44, 21, 'Visitor Status Update', 'Resident has approved the visitor.', 'visitor_status', 16, '/guard/visitor_details', NULL, 0, NULL, '2026-07-21 01:55:44'),
(45, 21, 'Expected Visitor', 'Resident has pre-approved visitor abcd.', 'visitor_pre_approval', 19, '/guard/visitor_details', NULL, 0, NULL, '2026-09-13 15:29:13'),
(46, 22, 'Expected Visitor', 'Resident has pre-approved visitor abcd.', 'visitor_pre_approval', 19, '/guard/visitor_details', NULL, 0, NULL, '2026-09-13 15:29:13'),
(47, 21, 'Visitor Status Update', 'Resident has approved the visitor.', 'visitor_status', 19, '/guard/visitor_details', NULL, 0, NULL, '2026-09-15 09:25:42'),
(48, 22, 'Visitor Status Update', 'Resident has approved the visitor.', 'visitor_status', 19, '/guard/visitor_details', NULL, 0, NULL, '2026-09-15 09:25:42'),
(49, 21, 'Expected Visitor', 'Resident has pre-approved visitor hjk.', 'visitor_pre_approval', 20, '/guard/visitor_details', NULL, 0, NULL, '2026-09-15 09:26:38'),
(50, 22, 'Expected Visitor', 'Resident has pre-approved visitor hjk.', 'visitor_pre_approval', 20, '/guard/visitor_details', NULL, 0, NULL, '2026-09-15 09:26:38'),
(51, 21, 'Visitor Status Update', 'Resident has approved the visitor.', 'visitor_status', 20, '/guard/visitor_details', NULL, 0, NULL, '2026-09-15 09:32:47'),
(52, 22, 'Visitor Status Update', 'Resident has approved the visitor.', 'visitor_status', 20, '/guard/visitor_details', NULL, 0, NULL, '2026-09-15 09:32:47'),
(53, 2, 'New Helpdesk Ticket', 'A new urgent priority ticket has been created.', 'ticket_created', 6, '/admin/helpdesk', NULL, 1, '2026-09-16 06:59:27', '2026-09-15 09:34:50'),
(54, 4, 'New Helpdesk Ticket', 'A new urgent priority ticket has been created.', 'ticket_created', 6, '/admin/helpdesk', NULL, 0, NULL, '2026-09-15 09:34:50'),
(55, 2, 'Payment Received', 'A payment of ₹1300 was received for Invoice #15.', 'payment_received', 5, '/admin/accounting/payments', NULL, 1, '2026-09-16 06:59:27', '2026-09-15 09:36:15'),
(56, 4, 'Payment Received', 'A payment of ₹1300 was received for Invoice #15.', 'payment_received', 5, '/admin/accounting/payments', NULL, 0, NULL, '2026-09-15 09:36:15'),
(57, 2, 'New Amenity Booking Request', 'A resident has requested to book an amenity.', 'amenity_booking_requested', 5, '/admin/amenities/bookings', NULL, 1, '2026-09-16 06:59:27', '2026-09-15 09:37:36'),
(58, 4, 'New Amenity Booking Request', 'A resident has requested to book an amenity.', 'amenity_booking_requested', 5, '/admin/amenities/bookings', NULL, 0, NULL, '2026-09-15 09:37:36'),
(59, 2, 'New Amenity Booking Request', 'A resident has requested to book an amenity.', 'amenity_booking_requested', 6, '/admin/amenities/bookings', NULL, 1, '2026-09-16 06:59:27', '2026-09-15 09:38:03'),
(60, 4, 'New Amenity Booking Request', 'A resident has requested to book an amenity.', 'amenity_booking_requested', 6, '/admin/amenities/bookings', NULL, 0, NULL, '2026-09-15 09:38:03'),
(61, 2, 'New Amenity Booking Request', 'A resident has requested to book an amenity.', 'amenity_booking_requested', 7, '/admin/amenities/bookings', NULL, 1, '2026-09-16 06:59:27', '2026-09-15 09:38:21'),
(62, 4, 'New Amenity Booking Request', 'A resident has requested to book an amenity.', 'amenity_booking_requested', 7, '/admin/amenities/bookings', NULL, 0, NULL, '2026-09-15 09:38:21'),
(63, 12, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 10, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-15 10:01:13'),
(64, 12, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 20, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-15 10:02:14'),
(65, 2, 'Security Alert: SUSPICIOUS_ACTIVITY', 'ghao', 'security_alert', 1, '/security/alerts', NULL, 1, '2026-09-16 06:59:27', '2026-09-15 10:31:42'),
(66, 4, 'Security Alert: SUSPICIOUS_ACTIVITY', 'ghao', 'security_alert', 1, '/security/alerts', NULL, 0, NULL, '2026-09-15 10:31:42'),
(67, 5, 'Security Alert: SUSPICIOUS_ACTIVITY', 'ghao', 'security_alert', 1, '/security/alerts', NULL, 0, NULL, '2026-09-15 10:31:42'),
(68, 6, 'Security Alert: SUSPICIOUS_ACTIVITY', 'ghao', 'security_alert', 1, '/security/alerts', NULL, 0, NULL, '2026-09-15 10:31:42'),
(69, 12, 'Security Alert: SUSPICIOUS_ACTIVITY', 'ghao', 'security_alert', 1, '/security/alerts', NULL, 1, '2026-10-14 08:26:40', '2026-09-15 10:31:42'),
(70, 13, 'Security Alert: SUSPICIOUS_ACTIVITY', 'ghao', 'security_alert', 1, '/security/alerts', NULL, 0, NULL, '2026-09-15 10:31:42'),
(71, 19, 'Security Alert: SUSPICIOUS_ACTIVITY', 'ghao', 'security_alert', 1, '/security/alerts', NULL, 0, NULL, '2026-09-15 10:31:42'),
(72, 20, 'Security Alert: SUSPICIOUS_ACTIVITY', 'ghao', 'security_alert', 1, '/security/alerts', NULL, 0, NULL, '2026-09-15 10:31:42'),
(73, 21, 'Security Alert: SUSPICIOUS_ACTIVITY', 'ghao', 'security_alert', 1, '/security/alerts', NULL, 0, NULL, '2026-09-15 10:31:42'),
(74, 22, 'Security Alert: SUSPICIOUS_ACTIVITY', 'ghao', 'security_alert', 1, '/security/alerts', NULL, 0, NULL, '2026-09-15 10:31:42'),
(75, 23, 'Security Alert: SUSPICIOUS_ACTIVITY', 'ghao', 'security_alert', 1, '/security/alerts', NULL, 0, NULL, '2026-09-15 10:31:42'),
(76, 2, 'Security Alert: SUSPICIOUS_ACTIVITY', 'ghyup', 'security_alert', 2, '/security/alerts', NULL, 1, '2026-09-16 06:59:27', '2026-09-15 10:34:01'),
(77, 4, 'Security Alert: SUSPICIOUS_ACTIVITY', 'ghyup', 'security_alert', 2, '/security/alerts', NULL, 0, NULL, '2026-09-15 10:34:01'),
(78, 5, 'Security Alert: SUSPICIOUS_ACTIVITY', 'ghyup', 'security_alert', 2, '/security/alerts', NULL, 0, NULL, '2026-09-15 10:34:01'),
(79, 6, 'Security Alert: SUSPICIOUS_ACTIVITY', 'ghyup', 'security_alert', 2, '/security/alerts', NULL, 0, NULL, '2026-09-15 10:34:01'),
(80, 12, 'Security Alert: SUSPICIOUS_ACTIVITY', 'ghyup', 'security_alert', 2, '/security/alerts', NULL, 1, '2026-10-14 08:26:40', '2026-09-15 10:34:01'),
(81, 13, 'Security Alert: SUSPICIOUS_ACTIVITY', 'ghyup', 'security_alert', 2, '/security/alerts', NULL, 0, NULL, '2026-09-15 10:34:01'),
(82, 19, 'Security Alert: SUSPICIOUS_ACTIVITY', 'ghyup', 'security_alert', 2, '/security/alerts', NULL, 0, NULL, '2026-09-15 10:34:01'),
(83, 20, 'Security Alert: SUSPICIOUS_ACTIVITY', 'ghyup', 'security_alert', 2, '/security/alerts', NULL, 0, NULL, '2026-09-15 10:34:01'),
(84, 21, 'Security Alert: SUSPICIOUS_ACTIVITY', 'ghyup', 'security_alert', 2, '/security/alerts', NULL, 0, NULL, '2026-09-15 10:34:01'),
(85, 22, 'Security Alert: SUSPICIOUS_ACTIVITY', 'ghyup', 'security_alert', 2, '/security/alerts', NULL, 0, NULL, '2026-09-15 10:34:01'),
(86, 23, 'Security Alert: SUSPICIOUS_ACTIVITY', 'ghyup', 'security_alert', 2, '/security/alerts', NULL, 0, NULL, '2026-09-15 10:34:01'),
(87, 21, 'Expected Visitor', 'Resident has pre-approved visitor lasmi.', 'visitor_pre_approval', 45, '/guard/visitor_details', NULL, 0, NULL, '2026-09-15 11:43:58'),
(88, 22, 'Expected Visitor', 'Resident has pre-approved visitor lasmi.', 'visitor_pre_approval', 45, '/guard/visitor_details', NULL, 0, NULL, '2026-09-15 11:43:58'),
(89, 12, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 45, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-15 11:53:10'),
(90, 21, 'Expected Visitor', 'Resident has pre-approved visitor siriaha.', 'visitor_pre_approval', 57, '/guard/visitor_details', NULL, 0, NULL, '2026-09-15 15:35:20'),
(91, 22, 'Expected Visitor', 'Resident has pre-approved visitor siriaha.', 'visitor_pre_approval', 57, '/guard/visitor_details', NULL, 0, NULL, '2026-09-15 15:35:20'),
(92, 2, 'New Helpdesk Ticket', 'A new medium priority ticket has been created.', 'ticket_created', 7, '/admin/helpdesk', NULL, 1, '2026-09-16 06:59:27', '2026-09-15 15:49:18'),
(93, 4, 'New Helpdesk Ticket', 'A new medium priority ticket has been created.', 'ticket_created', 7, '/admin/helpdesk', NULL, 0, NULL, '2026-09-15 15:49:18'),
(94, 2, 'New Amenity Booking Request', 'A resident has requested to book an amenity.', 'amenity_booking_requested', 8, '/admin/amenities/bookings', NULL, 1, '2026-09-16 06:59:27', '2026-09-15 15:55:03'),
(95, 4, 'New Amenity Booking Request', 'A resident has requested to book an amenity.', 'amenity_booking_requested', 8, '/admin/amenities/bookings', NULL, 0, NULL, '2026-09-15 15:55:03'),
(96, 2, 'Security Alert: EMERGENCY', 'hlxnkcgg', 'security_alert', 3, '/security/alerts', NULL, 1, '2026-09-16 06:59:27', '2026-09-15 17:01:18'),
(97, 4, 'Security Alert: EMERGENCY', 'hlxnkcgg', 'security_alert', 3, '/security/alerts', NULL, 0, NULL, '2026-09-15 17:01:18'),
(98, 5, 'Security Alert: EMERGENCY', 'hlxnkcgg', 'security_alert', 3, '/security/alerts', NULL, 0, NULL, '2026-09-15 17:01:18'),
(99, 6, 'Security Alert: EMERGENCY', 'hlxnkcgg', 'security_alert', 3, '/security/alerts', NULL, 0, NULL, '2026-09-15 17:01:18'),
(100, 12, 'Security Alert: EMERGENCY', 'hlxnkcgg', 'security_alert', 3, '/security/alerts', NULL, 1, '2026-10-14 08:26:40', '2026-09-15 17:01:18'),
(101, 13, 'Security Alert: EMERGENCY', 'hlxnkcgg', 'security_alert', 3, '/security/alerts', NULL, 0, NULL, '2026-09-15 17:01:18'),
(102, 19, 'Security Alert: EMERGENCY', 'hlxnkcgg', 'security_alert', 3, '/security/alerts', NULL, 0, NULL, '2026-09-15 17:01:18'),
(103, 20, 'Security Alert: EMERGENCY', 'hlxnkcgg', 'security_alert', 3, '/security/alerts', NULL, 0, NULL, '2026-09-15 17:01:18'),
(104, 21, 'Security Alert: EMERGENCY', 'hlxnkcgg', 'security_alert', 3, '/security/alerts', NULL, 0, NULL, '2026-09-15 17:01:18'),
(105, 22, 'Security Alert: EMERGENCY', 'hlxnkcgg', 'security_alert', 3, '/security/alerts', NULL, 0, NULL, '2026-09-15 17:01:18'),
(106, 23, 'Security Alert: EMERGENCY', 'hlxnkcgg', 'security_alert', 3, '/security/alerts', NULL, 0, NULL, '2026-09-15 17:01:18'),
(107, 12, 'New Visitor at Gate', 'shuvams is requesting entry for casual.', 'visitor_request', 82, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 05:17:23'),
(108, 12, 'New Visitor at Gate', 'rao is requesting entry for delivery.', 'visitor_request', 83, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 05:38:51'),
(109, 21, 'Visitor Status Update', 'Resident has approved the visitor.', 'visitor_status', 83, '/guard/visitor_details', NULL, 0, NULL, '2026-09-16 05:39:06'),
(110, 22, 'Visitor Status Update', 'Resident has approved the visitor.', 'visitor_status', 83, '/guard/visitor_details', NULL, 0, NULL, '2026-09-16 05:39:06'),
(111, 12, 'New Visitor at Gate', 'raos is requesting entry for service.', 'visitor_request', 84, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 05:39:52'),
(112, 12, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 84, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 05:51:59'),
(113, 23, 'New Visitor at Gate', 'suaus is requesting entry for casual.', 'visitor_request', 89, '/visitor_details', NULL, 0, NULL, '2026-09-16 06:38:32'),
(114, 12, 'New Visitor at Gate', 'syui is requesting entry for casual.', 'visitor_request', 90, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:41:45'),
(115, 21, 'Visitor Status Update', 'Resident has approved the visitor.', 'visitor_status', 84, '/guard/visitor_details', NULL, 0, NULL, '2026-09-16 06:42:45'),
(116, 22, 'Visitor Status Update', 'Resident has approved the visitor.', 'visitor_status', 84, '/guard/visitor_details', NULL, 0, NULL, '2026-09-16 06:42:45'),
(117, 21, 'Visitor Status Update', 'Resident has approved the visitor.', 'visitor_status', 90, '/guard/visitor_details', NULL, 0, NULL, '2026-09-16 06:43:30'),
(118, 22, 'Visitor Status Update', 'Resident has approved the visitor.', 'visitor_status', 90, '/guard/visitor_details', NULL, 0, NULL, '2026-09-16 06:43:30'),
(119, 21, 'Expected Visitor', 'Resident has pre-approved visitor rakesh.', 'visitor_pre_approval', 91, '/guard/visitor_details', NULL, 0, NULL, '2026-09-16 06:46:09'),
(120, 22, 'Expected Visitor', 'Resident has pre-approved visitor rakesh.', 'visitor_pre_approval', 91, '/guard/visitor_details', NULL, 0, NULL, '2026-09-16 06:46:09'),
(121, 12, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 91, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:47:44'),
(122, 12, 'Visitor Update', 'Your visitor has exited the premises.', 'visitor_status', 91, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:48:38'),
(123, 12, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 90, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:48:48'),
(124, 12, 'Visitor Update', 'Your visitor has exited the premises.', 'visitor_status', 90, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:48:54'),
(125, 23, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 89, '/visitor_details', NULL, 0, NULL, '2026-09-16 06:49:00'),
(126, 23, 'Visitor Update', 'Your visitor has exited the premises.', 'visitor_status', 89, '/visitor_details', NULL, 0, NULL, '2026-09-16 06:49:07'),
(127, 12, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 84, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:49:18'),
(128, 12, 'Visitor Update', 'Your visitor has exited the premises.', 'visitor_status', 84, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:49:25'),
(129, 12, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 83, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:49:34'),
(130, 12, 'Visitor Update', 'Your visitor has exited the premises.', 'visitor_status', 83, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:49:41'),
(131, 12, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 82, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:49:47'),
(132, 12, 'Visitor Update', 'Your visitor has exited the premises.', 'visitor_status', 82, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:49:53'),
(133, 12, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 57, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:49:59'),
(134, 12, 'Visitor Update', 'Your visitor has exited the premises.', 'visitor_status', 45, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:50:05'),
(135, 12, 'Visitor Update', 'Your visitor has exited the premises.', 'visitor_status', 57, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:50:11'),
(136, 12, 'Visitor Update', 'Your visitor has exited the premises.', 'visitor_status', 20, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:50:28'),
(137, 12, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 19, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:50:38'),
(138, 12, 'Visitor Update', 'Your visitor has exited the premises.', 'visitor_status', 19, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:50:45'),
(139, 12, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 16, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:50:51'),
(140, 12, 'Visitor Update', 'Your visitor has exited the premises.', 'visitor_status', 16, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:50:58'),
(141, 12, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 15, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:51:05'),
(142, 12, 'Visitor Update', 'Your visitor has exited the premises.', 'visitor_status', 15, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:51:11'),
(143, 12, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 14, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:51:16'),
(144, 12, 'Visitor Update', 'Your visitor has exited the premises.', 'visitor_status', 14, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:51:22'),
(145, 12, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 13, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:51:29'),
(146, 12, 'Visitor Update', 'Your visitor has exited the premises.', 'visitor_status', 13, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:51:35'),
(147, 12, 'Visitor Update', 'Your visitor has exited the premises.', 'visitor_status', 10, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:51:41'),
(148, 12, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 2, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:51:46'),
(149, 12, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 7, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:51:52'),
(150, 12, 'Visitor Update', 'Your visitor has exited the premises.', 'visitor_status', 7, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:51:59'),
(151, 12, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 6, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:52:06'),
(152, 12, 'Visitor Update', 'Your visitor has exited the premises.', 'visitor_status', 6, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:52:12'),
(153, 12, 'Visitor Update', 'Your visitor has exited the premises.', 'visitor_status', 2, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-09-16 06:52:20'),
(154, 2, 'New Helpdesk Ticket', 'A new high priority ticket has been created.', 'ticket_created', 8, '/admin/helpdesk', NULL, 1, '2026-09-16 06:59:27', '2026-09-16 06:53:41'),
(155, 4, 'New Helpdesk Ticket', 'A new high priority ticket has been created.', 'ticket_created', 8, '/admin/helpdesk', NULL, 0, NULL, '2026-09-16 06:53:41'),
(156, 21, 'Expected Visitor', 'Resident has pre-approved visitor rao.', 'visitor_pre_approval', 92, '/guard/visitor_details', NULL, 0, NULL, '2026-09-21 14:03:59'),
(157, 22, 'Expected Visitor', 'Resident has pre-approved visitor rao.', 'visitor_pre_approval', 92, '/guard/visitor_details', NULL, 0, NULL, '2026-09-21 14:03:59'),
(158, 27, 'Expected Visitor', 'Resident has pre-approved visitor rao.', 'visitor_pre_approval', 92, '/guard/visitor_details', NULL, 0, NULL, '2026-09-21 14:03:59'),
(159, 28, 'Expected Visitor', 'Resident has pre-approved visitor rao.', 'visitor_pre_approval', 92, '/guard/visitor_details', NULL, 0, NULL, '2026-09-21 14:03:59'),
(160, 24, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 92, '/visitor_details', NULL, 0, NULL, '2026-09-21 14:05:03'),
(161, 24, 'Visitor Update', 'Your visitor has exited the premises.', 'visitor_status', 92, '/visitor_details', NULL, 0, NULL, '2026-09-21 14:05:26'),
(162, 24, 'New Visitor at Gate', 'kumar is requesting entry for guest.', 'visitor_request', 93, '/visitor_details', NULL, 0, NULL, '2026-09-21 14:10:38'),
(163, 24, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 93, '/visitor_details', NULL, 0, NULL, '2026-09-21 14:12:18'),
(164, 21, 'Expected Visitor', 'Resident has pre-approved visitor kumars.', 'visitor_pre_approval', 94, '/guard/visitor_details', NULL, 0, NULL, '2026-09-21 14:13:05'),
(165, 22, 'Expected Visitor', 'Resident has pre-approved visitor kumars.', 'visitor_pre_approval', 94, '/guard/visitor_details', NULL, 0, NULL, '2026-09-21 14:13:05'),
(166, 27, 'Expected Visitor', 'Resident has pre-approved visitor kumars.', 'visitor_pre_approval', 94, '/guard/visitor_details', NULL, 0, NULL, '2026-09-21 14:13:05'),
(167, 28, 'Expected Visitor', 'Resident has pre-approved visitor kumars.', 'visitor_pre_approval', 94, '/guard/visitor_details', NULL, 0, NULL, '2026-09-21 14:13:05'),
(168, 24, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 94, '/visitor_details', NULL, 0, NULL, '2026-09-21 14:16:36'),
(169, 24, 'Visitor Update', 'Your visitor has exited the premises.', 'visitor_status', 94, '/visitor_details', NULL, 0, NULL, '2026-09-21 14:16:54'),
(170, 24, 'Visitor Update', 'Your visitor has exited the premises.', 'visitor_status', 93, '/visitor_details', NULL, 0, NULL, '2026-09-21 14:17:33'),
(171, 21, 'Expected Visitor', 'Resident has pre-approved visitor khan.', 'visitor_pre_approval', 95, '/guard/visitor_details', NULL, 0, NULL, '2026-09-21 14:19:42'),
(172, 22, 'Expected Visitor', 'Resident has pre-approved visitor khan.', 'visitor_pre_approval', 95, '/guard/visitor_details', NULL, 0, NULL, '2026-09-21 14:19:42'),
(173, 27, 'Expected Visitor', 'Resident has pre-approved visitor khan.', 'visitor_pre_approval', 95, '/guard/visitor_details', NULL, 0, NULL, '2026-09-21 14:19:42'),
(174, 28, 'Expected Visitor', 'Resident has pre-approved visitor khan.', 'visitor_pre_approval', 95, '/guard/visitor_details', NULL, 0, NULL, '2026-09-21 14:19:42'),
(175, 24, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 95, '/visitor_details', NULL, 0, NULL, '2026-09-21 14:20:13'),
(176, 24, 'Visitor Update', 'Your visitor has exited the premises.', 'visitor_status', 95, '/visitor_details', NULL, 0, NULL, '2026-09-21 14:20:36'),
(177, 24, 'New Visitor at Gate', 'dad is requesting entry for guest.', 'visitor_request', 96, '/visitor_details', NULL, 0, NULL, '2026-09-21 14:24:19'),
(178, 21, 'Visitor Status Update', 'Resident has approved the visitor.', 'visitor_status', 96, '/guard/visitor_details', NULL, 0, NULL, '2026-09-21 17:32:54'),
(179, 22, 'Visitor Status Update', 'Resident has approved the visitor.', 'visitor_status', 96, '/guard/visitor_details', NULL, 0, NULL, '2026-09-21 17:32:54'),
(180, 27, 'Visitor Status Update', 'Resident has approved the visitor.', 'visitor_status', 96, '/guard/visitor_details', NULL, 0, NULL, '2026-09-21 17:32:54'),
(181, 28, 'Visitor Status Update', 'Resident has approved the visitor.', 'visitor_status', 96, '/guard/visitor_details', NULL, 0, NULL, '2026-09-21 17:32:54'),
(182, 24, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 96, '/visitor_details', NULL, 0, NULL, '2026-09-21 17:33:12'),
(183, 24, 'Visitor Update', 'Your visitor has exited the premises.', 'visitor_status', 96, '/visitor_details', NULL, 0, NULL, '2026-09-21 17:34:58'),
(184, 5, 'New Poll', 'Poll check 1', 'poll_created', 3, '/communications/polls', NULL, 0, NULL, '2026-10-07 06:16:37'),
(185, 6, 'New Poll', 'Poll check 1', 'poll_created', 3, '/communications/polls', NULL, 0, NULL, '2026-10-07 06:16:37'),
(186, 12, 'New Poll', 'Poll check 1', 'poll_created', 3, '/communications/polls', NULL, 1, '2026-10-14 08:26:40', '2026-10-07 06:16:37'),
(187, 13, 'New Poll', 'Poll check 1', 'poll_created', 3, '/communications/polls', NULL, 0, NULL, '2026-10-07 06:16:37'),
(188, 19, 'New Poll', 'Poll check 1', 'poll_created', 3, '/communications/polls', NULL, 0, NULL, '2026-10-07 06:16:37'),
(189, 20, 'New Poll', 'Poll check 1', 'poll_created', 3, '/communications/polls', NULL, 0, NULL, '2026-10-07 06:16:37'),
(190, 23, 'New Poll', 'Poll check 1', 'poll_created', 3, '/communications/polls', NULL, 0, NULL, '2026-10-07 06:16:37'),
(191, 24, 'New Poll', 'Poll check 1', 'poll_created', 3, '/communications/polls', NULL, 0, NULL, '2026-10-07 06:16:37'),
(192, 25, 'New Poll', 'Poll check 1', 'poll_created', 3, '/communications/polls', NULL, 0, NULL, '2026-10-07 06:16:37'),
(193, 26, 'New Poll', 'Poll check 1', 'poll_created', 3, '/communications/polls', NULL, 0, NULL, '2026-10-07 06:16:37'),
(194, 21, 'Expected Visitor', 'Resident has pre-approved visitor Astha Utagi.', 'visitor_pre_approval', 105, '/guard/visitor_details', NULL, 0, NULL, '2026-10-08 06:02:39'),
(195, 22, 'Expected Visitor', 'Resident has pre-approved visitor Astha Utagi.', 'visitor_pre_approval', 105, '/guard/visitor_details', NULL, 0, NULL, '2026-10-08 06:02:39'),
(196, 27, 'Expected Visitor', 'Resident has pre-approved visitor Astha Utagi.', 'visitor_pre_approval', 105, '/guard/visitor_details', NULL, 0, NULL, '2026-10-08 06:02:39'),
(197, 28, 'Expected Visitor', 'Resident has pre-approved visitor Astha Utagi.', 'visitor_pre_approval', 105, '/guard/visitor_details', NULL, 0, NULL, '2026-10-08 06:02:39'),
(198, 21, 'Expected Visitor', 'Resident has pre-approved visitor Shankar Rathod.', 'visitor_pre_approval', 106, '/guard/visitor_details', NULL, 0, NULL, '2026-10-08 07:21:52'),
(199, 22, 'Expected Visitor', 'Resident has pre-approved visitor Shankar Rathod.', 'visitor_pre_approval', 106, '/guard/visitor_details', NULL, 0, NULL, '2026-10-08 07:21:52'),
(200, 27, 'Expected Visitor', 'Resident has pre-approved visitor Shankar Rathod.', 'visitor_pre_approval', 106, '/guard/visitor_details', NULL, 0, NULL, '2026-10-08 07:21:52'),
(201, 28, 'Expected Visitor', 'Resident has pre-approved visitor Shankar Rathod.', 'visitor_pre_approval', 106, '/guard/visitor_details', NULL, 0, NULL, '2026-10-08 07:21:52'),
(202, 25, 'New Visitor at Gate', 'sushma is requesting entry for guest.', 'visitor_request', 107, '/visitor_details', NULL, 0, NULL, '2026-10-09 09:46:45'),
(203, 21, 'Visitor Status Update', 'Resident has approved the visitor.', 'visitor_status', 107, '/guard/visitor_details', NULL, 0, NULL, '2026-10-09 09:47:49'),
(204, 22, 'Visitor Status Update', 'Resident has approved the visitor.', 'visitor_status', 107, '/guard/visitor_details', NULL, 0, NULL, '2026-10-09 09:47:49'),
(205, 27, 'Visitor Status Update', 'Resident has approved the visitor.', 'visitor_status', 107, '/guard/visitor_details', NULL, 0, NULL, '2026-10-09 09:47:49'),
(206, 28, 'Visitor Status Update', 'Resident has approved the visitor.', 'visitor_status', 107, '/guard/visitor_details', NULL, 0, NULL, '2026-10-09 09:47:49'),
(207, 25, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 107, '/visitor_details', NULL, 0, NULL, '2026-10-09 09:51:55'),
(208, 25, 'Visitor Update', 'Your visitor has exited the premises.', 'visitor_status', 107, '/visitor_details', NULL, 0, NULL, '2026-10-09 09:52:04'),
(209, 6, 'New Visitor at Gate', 'sunitja is requesting entry for tution.', 'visitor_request', 108, '/visitor_details', NULL, 0, NULL, '2026-10-09 09:52:55'),
(210, 21, 'Expected Visitor', 'Resident has pre-approved visitor siri.', 'visitor_pre_approval', 109, '/guard/visitor_details', NULL, 0, NULL, '2026-10-09 10:02:47'),
(211, 22, 'Expected Visitor', 'Resident has pre-approved visitor siri.', 'visitor_pre_approval', 109, '/guard/visitor_details', NULL, 0, NULL, '2026-10-09 10:02:47'),
(212, 27, 'Expected Visitor', 'Resident has pre-approved visitor siri.', 'visitor_pre_approval', 109, '/guard/visitor_details', NULL, 0, NULL, '2026-10-09 10:02:47'),
(213, 28, 'Expected Visitor', 'Resident has pre-approved visitor siri.', 'visitor_pre_approval', 109, '/guard/visitor_details', NULL, 0, NULL, '2026-10-09 10:02:47'),
(214, 12, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 105, '/visitor_details', NULL, 1, '2026-10-14 08:26:40', '2026-10-10 09:16:42'),
(215, 21, 'Expected Visitor', 'Resident has pre-approved visitor Test visitor 1.', 'visitor_pre_approval', 110, '/guard/visitor_details', NULL, 0, NULL, '2026-10-12 08:57:50'),
(216, 22, 'Expected Visitor', 'Resident has pre-approved visitor Test visitor 1.', 'visitor_pre_approval', 110, '/guard/visitor_details', NULL, 0, NULL, '2026-10-12 08:57:50'),
(217, 27, 'Expected Visitor', 'Resident has pre-approved visitor Test visitor 1.', 'visitor_pre_approval', 110, '/guard/visitor_details', NULL, 0, NULL, '2026-10-12 08:57:50'),
(218, 28, 'Expected Visitor', 'Resident has pre-approved visitor Test visitor 1.', 'visitor_pre_approval', 110, '/guard/visitor_details', NULL, 0, NULL, '2026-10-12 08:57:50'),
(219, 12, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 110, '/visitor_details', NULL, 1, '2026-10-12 09:21:26', '2026-10-12 08:59:06'),
(220, 24, 'Visitor Update', 'Your visitor has entered the premises.', 'visitor_status', 109, '/visitor_details', NULL, 0, NULL, '2026-10-12 08:59:21'),
(221, 12, 'Visitor Update', 'Your visitor has exited the premises.', 'visitor_status', 110, '/visitor_details', NULL, 1, '2026-10-12 09:21:27', '2026-10-12 08:59:23'),
(222, 2, 'New Helpdesk Ticket', 'A new urgent priority ticket has been created.', 'ticket_created', 9, '/admin/helpdesk', NULL, 1, '2026-10-12 13:03:00', '2026-10-12 09:31:58'),
(223, 4, 'New Helpdesk Ticket', 'A new urgent priority ticket has been created.', 'ticket_created', 9, '/admin/helpdesk', NULL, 0, NULL, '2026-10-12 09:31:58'),
(224, 12, 'New Invoice Generated', 'Invoice INV-1-000008 for amount ₹100 has been generated.', 'invoice_generated', 16, '/accounting/invoices', NULL, 1, '2026-10-14 08:26:40', '2026-10-12 09:34:52'),
(225, 12, 'New Invoice Generated', 'Invoice INV-1-000009 for amount ₹2500 has been generated.', 'invoice_generated', 17, '/accounting/invoices', NULL, 1, '2026-10-14 08:26:40', '2026-10-12 09:43:03'),
(226, 5, 'New Poll', 'Dasara celebration', 'poll_created', 4, '/communications/polls', NULL, 0, NULL, '2026-10-12 09:49:38'),
(227, 6, 'New Poll', 'Dasara celebration', 'poll_created', 4, '/communications/polls', NULL, 0, NULL, '2026-10-12 09:49:38'),
(228, 12, 'New Poll', 'Dasara celebration', 'poll_created', 4, '/communications/polls', NULL, 1, '2026-10-14 08:26:40', '2026-10-12 09:49:38'),
(229, 13, 'New Poll', 'Dasara celebration', 'poll_created', 4, '/communications/polls', NULL, 0, NULL, '2026-10-12 09:49:38'),
(230, 19, 'New Poll', 'Dasara celebration', 'poll_created', 4, '/communications/polls', NULL, 0, NULL, '2026-10-12 09:49:38'),
(231, 20, 'New Poll', 'Dasara celebration', 'poll_created', 4, '/communications/polls', NULL, 0, NULL, '2026-10-12 09:49:38'),
(232, 23, 'New Poll', 'Dasara celebration', 'poll_created', 4, '/communications/polls', NULL, 0, NULL, '2026-10-12 09:49:38'),
(233, 24, 'New Poll', 'Dasara celebration', 'poll_created', 4, '/communications/polls', NULL, 0, NULL, '2026-10-12 09:49:38'),
(234, 25, 'New Poll', 'Dasara celebration', 'poll_created', 4, '/communications/polls', NULL, 0, NULL, '2026-10-12 09:49:38'),
(235, 26, 'New Poll', 'Dasara celebration', 'poll_created', 4, '/communications/polls', NULL, 0, NULL, '2026-10-12 09:49:38'),
(236, 35, 'New Poll', 'Dasara celebration', 'poll_created', 4, '/communications/polls', NULL, 0, NULL, '2026-10-12 09:49:38'),
(237, 2, 'New Invoice Generated', 'Invoice INV-1-000010 for amount ₹99.99 has been generated.', 'invoice_generated', 18, '/accounting/invoices', NULL, 0, NULL, '2026-10-12 14:41:51'),
(238, 5, 'New Poll', 'should we do the navratri celebrations or not?', 'poll_created', 5, '/communications/polls', NULL, 0, NULL, '2026-10-12 15:39:29'),
(239, 6, 'New Poll', 'should we do the navratri celebrations or not?', 'poll_created', 5, '/communications/polls', NULL, 0, NULL, '2026-10-12 15:39:29'),
(240, 12, 'New Poll', 'should we do the navratri celebrations or not?', 'poll_created', 5, '/communications/polls', NULL, 1, '2026-10-14 08:26:40', '2026-10-12 15:39:29'),
(241, 13, 'New Poll', 'should we do the navratri celebrations or not?', 'poll_created', 5, '/communications/polls', NULL, 0, NULL, '2026-10-12 15:39:29'),
(242, 19, 'New Poll', 'should we do the navratri celebrations or not?', 'poll_created', 5, '/communications/polls', NULL, 0, NULL, '2026-10-12 15:39:29'),
(243, 20, 'New Poll', 'should we do the navratri celebrations or not?', 'poll_created', 5, '/communications/polls', NULL, 0, NULL, '2026-10-12 15:39:29'),
(244, 23, 'New Poll', 'should we do the navratri celebrations or not?', 'poll_created', 5, '/communications/polls', NULL, 0, NULL, '2026-10-12 15:39:29'),
(245, 24, 'New Poll', 'should we do the navratri celebrations or not?', 'poll_created', 5, '/communications/polls', NULL, 0, NULL, '2026-10-12 15:39:29'),
(246, 25, 'New Poll', 'should we do the navratri celebrations or not?', 'poll_created', 5, '/communications/polls', NULL, 0, NULL, '2026-10-12 15:39:29'),
(247, 26, 'New Poll', 'should we do the navratri celebrations or not?', 'poll_created', 5, '/communications/polls', NULL, 0, NULL, '2026-10-12 15:39:29'),
(248, 35, 'New Poll', 'should we do the navratri celebrations or not?', 'poll_created', 5, '/communications/polls', NULL, 0, NULL, '2026-10-12 15:39:29'),
(249, 2, 'New Invoice Generated', 'Invoice INV-1-000011 for amount ₹2500 has been generated.', 'invoice_generated', 19, '/accounting/invoices', NULL, 0, NULL, '2026-10-12 17:46:41'),
(250, 12, 'New Invoice Generated', 'Invoice INV-1-000012 for amount ₹2500 has been generated.', 'invoice_generated', 23, '/accounting/invoices', NULL, 1, '2026-10-14 08:26:40', '2026-10-12 17:46:58'),
(251, 2, 'New Invoice Generated', 'Invoice INV-1-000013 for amount ₹2500 has been generated.', 'invoice_generated', 25, '/accounting/invoices', NULL, 1, '2026-10-13 08:44:35', '2026-10-12 17:47:14'),
(252, 2, 'New Invoice Generated', 'Invoice INV-1-000014 for amount ₹2500 has been generated.', 'invoice_generated', 26, '/accounting/invoices', NULL, 1, '2026-10-13 08:47:40', '2026-10-12 17:47:14'),
(253, 2, 'New Invoice Generated', 'Invoice INV-1-000015 for amount ₹2500 has been generated.', 'invoice_generated', 27, '/accounting/invoices', NULL, 1, '2026-10-13 09:08:46', '2026-10-12 17:47:14'),
(254, 12, 'Booking Status Updated', 'Your amenity booking status is now: cancelled.', 'booking_status_updated', 6, '/amenities/bookings', NULL, 1, '2026-10-14 08:26:40', '2026-10-13 12:21:56'),
(255, 12, 'Booking Status Updated', 'Your amenity booking status is now: confirmed.', 'booking_status_updated', 7, '/amenities/bookings', NULL, 1, '2026-10-14 08:26:40', '2026-10-13 12:21:58'),
(256, 12, 'Booking Status Updated', 'Your amenity booking status is now: confirmed.', 'booking_status_updated', 8, '/amenities/bookings', NULL, 1, '2026-10-14 08:26:40', '2026-10-13 12:21:59'),
(257, 12, 'Push Direct Alert / Notification test 1', 'Push Direct Alert / Notification related message.', 'notice', NULL, '/resident-notifications', NULL, 1, '2026-10-14 08:26:15', '2026-10-14 08:26:04'),
(258, 2, '🚨 Push Direct Alert / Notification test 2 building', 'Push Direct Alert / Notification test 2 building message.', 'alert', NULL, '/resident-notifications', NULL, 0, NULL, '2026-10-14 08:28:36'),
(259, 2, '🚨 Push Direct Alert / Notification test 3 building', 'Push Direct Alert / Notification test 3 building message.', 'alert', NULL, '/resident-notifications', NULL, 0, NULL, '2026-10-14 08:29:17'),
(260, 12, '🚨 Push Direct Alert / Notification test 3 building', 'Push Direct Alert / Notification test 3 building message.', 'alert', NULL, '/resident-notifications', NULL, 1, '2026-10-14 09:34:11', '2026-10-14 08:29:17'),
(261, 13, '🚨 Push Direct Alert / Notification test 3 building', 'Push Direct Alert / Notification test 3 building message.', 'alert', NULL, '/resident-notifications', NULL, 0, NULL, '2026-10-14 08:29:17'),
(262, 19, '🚨 Push Direct Alert / Notification test 3 building', 'Push Direct Alert / Notification test 3 building message.', 'alert', NULL, '/resident-notifications', NULL, 0, NULL, '2026-10-14 08:29:17'),
(263, 20, '🚨 Push Direct Alert / Notification test 3 building', 'Push Direct Alert / Notification test 3 building message.', 'alert', NULL, '/resident-notifications', NULL, 0, NULL, '2026-10-14 08:29:17'),
(264, 13, 'New Invoice Generated', 'Invoice INV-1-000016 for amount ₹2500 has been generated.', 'invoice_generated', 28, '/accounting/invoices', NULL, 0, NULL, '2026-10-14 09:32:26'),
(265, 12, 'New Invoice Generated', 'Invoice INV-1-000017 for amount ₹2500 has been generated.', 'invoice_generated', 29, '/accounting/invoices', NULL, 1, '2026-10-14 09:34:11', '2026-10-14 09:32:26'),
(266, 20, 'New Invoice Generated', 'Invoice INV-1-000018 for amount ₹2500 has been generated.', 'invoice_generated', 30, '/accounting/invoices', NULL, 0, NULL, '2026-10-14 09:32:26'),
(267, 2, 'New Invoice Generated', 'Invoice INV-1-000019 for amount ₹2500 has been generated.', 'invoice_generated', 31, '/accounting/invoices', NULL, 0, NULL, '2026-10-14 09:32:26'),
(268, 12, 'New Invoice Generated', 'Invoice INV-1-000020 for amount ₹1000 has been generated.', 'invoice_generated', 32, '/accounting/invoices', NULL, 1, '2026-10-14 09:34:11', '2026-10-14 09:33:15');

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

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `payment_reference`, `invoice_id`, `resident_id`, `society_id`, `payment_method`, `payment_gateway`, `amount`, `transaction_id`, `transaction_status`, `payment_date`, `notes`, `created_at`, `updated_at`) VALUES
(3, 'PAY-1782275709-5413', 6, 12, 1, 'cash', NULL, 3500.00, NULL, 'success', '2026-06-24 04:35:09', NULL, '2026-06-24 04:35:09', '2026-06-24 04:35:09'),
(4, 'PAY-1782277055-6054', 7, 20, 1, 'bank_transfer', NULL, 5000.00, '3457899654fvybuct', 'success', '2026-06-24 04:57:35', NULL, '2026-06-24 04:57:35', '2026-06-24 04:57:35'),
(5, 'PAY-1789464975-8605', 15, 12, 1, 'net_banking', NULL, 1300.00, NULL, 'success', '2026-09-15 09:36:15', NULL, '2026-09-15 15:06:15', '2026-09-15 15:06:15');

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
(1, 12, 1, 'Rose', 'German shepherd', 2, 24.00, 'not_vaccinated', '', 'General note', 1, 1, '2026-05-26 04:19:53', '2026-05-26 04:19:53'),
(2, 12, 1, 'bruno', 'Labrador', 3, 13.00, 'not_vaccinated', '', '', 1, 1, '2026-07-18 10:42:22', '2026-07-18 10:42:22');

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
(1, 'Dog', 'Domestic dog pet type.'),
(2, 'Cat', 'Domestic cat pet type.'),
(3, 'Bird', 'Pet bird type.'),
(4, 'Fish', 'Aquarium fish pet type.'),
(5, 'Rabbit', 'Domestic rabbit pet type.'),
(6, 'Hamster', 'Small rodent pet type.'),
(7, 'Guinea Pig', 'Domestic guinea pig pet type.'),
(8, 'Turtle', 'Pet turtle type.'),
(9, 'Parrot', 'Talking bird pet type.'),
(10, 'Snake', 'Pet snake reptile type.'),
(11, 'Lizard', 'Pet lizard reptile type.'),
(12, 'Ferret', 'Domestic ferret pet type.'),
(13, 'Horse', 'Domestic horse pet type.'),
(14, 'Goat', 'Domestic goat pet type.'),
(15, 'Chicken', 'Domestic chicken pet type.'),
(16, 'Duck', 'Domestic duck pet type.'),
(17, 'Pig', 'Domestic pig pet type.'),
(18, 'Frog', 'Pet frog amphibian type.'),
(19, 'Hedgehog', 'Pet hedgehog type.'),
(20, 'Chinchilla', 'Soft-furred rodent pet type.');

-- --------------------------------------------------------

--
-- Table structure for table `polls`
--

CREATE TABLE `polls` (
  `id` int(11) NOT NULL,
  `society_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `question` text NOT NULL,
  `poll_type` enum('public','secret') DEFAULT 'public',
  `is_active` tinyint(1) DEFAULT 1,
  `starts_at` timestamp NULL DEFAULT current_timestamp(),
  `ends_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `polls`
--

INSERT INTO `polls` (`id`, `society_id`, `created_by`, `question`, `poll_type`, `is_active`, `starts_at`, `ends_at`, `created_at`, `updated_at`) VALUES
(1, 1, 12, 'my first poll', 'public', 1, '2026-06-22 06:05:33', '2026-06-29 11:35:32', '2026-06-22 06:05:33', '2026-06-22 06:05:33'),
(2, 1, 2, 'What should be date to celebrate Society Anniversary', 'public', 1, '2026-07-04 04:38:31', '2026-07-31 10:08:00', '2026-07-04 04:38:31', '2026-07-04 04:38:31'),
(3, 1, 12, 'Poll check 1', 'public', 0, '2026-10-07 06:16:37', '2026-10-14 11:46:35', '2026-10-07 11:46:36', '2026-10-12 15:39:37'),
(4, 1, 2, 'Dasara celebration', 'public', 1, '2026-10-12 09:49:38', '2026-10-12 15:19:00', '2026-10-12 09:49:38', '2026-10-12 09:49:38'),
(5, 1, 2, 'should we do the navratri celebrations or not?', 'public', 1, '2026-10-12 15:39:29', '2026-10-13 21:09:00', '2026-10-12 15:39:29', '2026-10-12 15:39:29');

-- --------------------------------------------------------

--
-- Table structure for table `poll_options`
--

CREATE TABLE `poll_options` (
  `id` int(11) NOT NULL,
  `poll_id` int(11) NOT NULL,
  `option_text` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `poll_options`
--

INSERT INTO `poll_options` (`id`, `poll_id`, `option_text`) VALUES
(1, 1, '1'),
(2, 1, '2'),
(3, 2, '10 July'),
(4, 2, '12July'),
(5, 2, '13 July'),
(6, 2, '14 July'),
(7, 3, 'Working'),
(8, 3, 'Done'),
(9, 3, 'Partially working'),
(10, 3, 'Not Working as expected'),
(11, 4, 'Yes'),
(12, 4, 'No'),
(13, 5, 'yes'),
(14, 5, 'no');

-- --------------------------------------------------------

--
-- Table structure for table `poll_votes`
--

CREATE TABLE `poll_votes` (
  `id` int(11) NOT NULL,
  `poll_id` int(11) NOT NULL,
  `option_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `poll_votes`
--

INSERT INTO `poll_votes` (`id`, `poll_id`, `option_id`, `user_id`, `created_at`) VALUES
(1, 1, 1, 12, '2026-06-22 06:06:19'),
(2, 2, 4, 12, '2026-07-04 04:39:08'),
(3, 3, 9, 12, '2026-10-12 14:48:20'),
(4, 5, 13, 26, '2026-10-12 21:23:48');

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

--
-- Dumping data for table `receipts`
--

INSERT INTO `receipts` (`id`, `receipt_number`, `payment_id`, `invoice_id`, `resident_id`, `society_id`, `amount`, `receipt_date`, `notes`, `created_at`) VALUES
(3, 'REC-1-000002', 3, 6, 12, 1, 3500.00, '2026-06-24', NULL, '2026-06-24 04:35:09'),
(4, 'REC-1-000003', 4, 7, 20, 1, 5000.00, '2026-06-24', NULL, '2026-06-24 04:57:35'),
(5, 'REC-1-000004', 5, 15, 12, 1, 1300.00, '2026-09-15', NULL, '2026-09-15 15:06:15');

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

--
-- Dumping data for table `security_alerts`
--

INSERT INTO `security_alerts` (`id`, `alert_type`, `description`, `severity`, `reported_by`, `society_id`, `resolved_by`, `resolved_at`, `status`, `image_url`, `location`, `created_at`, `updated_at`) VALUES
(1, 'suspicious_activity', 'ghao', 'high', 21, 1, NULL, NULL, 'open', NULL, 'vizag', '2026-09-15 16:01:41', '2026-09-15 16:01:41'),
(2, 'suspicious_activity', 'ghyup', 'low', 21, 1, 2, '2026-10-13 09:14:14', 'resolved', NULL, 'near garden area', '2026-09-15 16:04:00', '2026-10-13 09:14:14'),
(3, 'emergency', 'hlxnkcgg', 'medium', 21, 1, NULL, NULL, 'in_progress', NULL, NULL, '2026-09-15 22:31:17', '2026-10-13 09:14:12');

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
  `gst` varchar(20) DEFAULT NULL,
  `pan` varchar(20) DEFAULT NULL,
  `registration_id` int(11) DEFAULT NULL COMMENT 'Source registration lead ID',
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

INSERT INTO `societies` (`id`, `name`, `code`, `address`, `city`, `state`, `country`, `pincode`, `towers`, `total_flats`, `admin_id`, `gst`, `pan`, `registration_id`, `contact_person`, `contact_phone`, `contact_email`, `plan`, `created_at`, `updated_at`, `status`) VALUES
(1, 'Horizon Universe Apartments', 'RIDD556', 'Sinhgad college campus', 'Ambegaon BK, Pune', 'Maharashtra', 'India', '411046', 1, 0, 2, NULL, NULL, NULL, 'Vikas Patil', '9383673712', 'vikaspatil@gmail.com', 'starter', '2026-05-16 04:17:41', '2026-08-10 06:07:28', 'approved');

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
  `status` enum('pending','new','under_review','approved','rejected') DEFAULT 'new',
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

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `ticket_number`, `title`, `description`, `category`, `priority`, `status`, `resident_id`, `assigned_to`, `society_id`, `created_at`, `updated_at`, `resolved_at`) VALUES
(1, 'TK-1-000001', 'Water Leakage in Bathroom', 'Water is continuously leaking from the ceiling in the master bathroom causing damp patches.', 'plumbing', 'high', 'resolved', 12, 2, 1, '2026-05-28 11:43:39', '2026-06-23 08:39:30', '2026-06-23 08:39:30'),
(2, 'TK-1-000002', 'Garbage Collection Delay', 'Garbage has not been collected from our floor since yesterday.', 'Housekeeping', 'medium', 'resolved', 12, 2, 1, '2026-06-01 10:11:26', '2026-06-04 04:02:25', '2026-06-04 04:02:25'),
(6, 'TK-1-000006', 'leak', 'dyjxhknc', 'maintenance', 'urgent', 'resolved', 12, NULL, 1, '2026-09-15 15:04:49', '2026-09-15 15:05:15', '2026-09-15 09:35:16'),
(7, 'TK-1-000007', 'leak', 'leak tap', 'plumbing', 'medium', 'resolved', 12, NULL, 1, '2026-09-15 21:19:17', '2026-09-16 11:13:07', '2026-09-16 05:43:08'),
(8, 'TK-1-000008', 'plumbing', 'water leak', 'plumbing', 'high', 'open', 12, NULL, 1, '2026-09-16 12:23:39', '2026-10-13 08:21:28', '2026-09-16 23:37:11'),
(9, 'TK-1-000009', 'Society documents related', 'I want below mentioned society documents on my WhatsApp number 7927527492', 'general', 'urgent', 'in_progress', 12, NULL, 1, '2026-10-12 15:01:57', '2026-10-13 08:19:40', NULL);

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

--
-- Dumping data for table `ticket_comments`
--

INSERT INTO `ticket_comments` (`id`, `ticket_id`, `user_id`, `comment`, `created_at`) VALUES
(1, 2, 12, 'ubvgdct', '2026-06-25 11:01:24'),
(2, 2, 12, '8010155144', '2026-07-07 12:48:03'),
(4, 2, 12, 'also', '2026-09-15 15:05:40'),
(5, 8, 12, 'taking timw', '2026-09-16 12:28:47'),
(6, 9, 2, 'This is comment reply for in progress.', '2026-10-13 08:19:50'),
(7, 9, 12, 'Comment msg reply 2.', '2026-10-14 10:20:36');

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
(1, 'UGK-835184', 'Super Admin 2', 'superadmin2@gmail.com', '1122334455', '$2y$10$YkpLGtpYW0UzfWKuOJ.MCuv5j.bW1SGirCGOaPCegqpIwOaVXjRsu', 'super_admin', NULL, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-16 04:15:54', '2026-05-16 04:15:54'),
(2, 'WIY-080057', 'Vikas Patil', 'vikaspatil@gmail.com', '9096221812', '$2y$10$Z29D9Ai9mAbmu5R.QW.kH.y/tpWxJKI7mN8/m0zNSOpf97E7sy85K', 'admin', 1, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-16 04:17:42', '2026-07-13 05:56:16'),
(3, 'DVU-197405', 'Super Admin 1', 'superadmin1@gmail.com', '1122334466', '$2y$10$IAn/L32meDYmiBEjr5Oq7.Hyf2BTWkpWIugz7zXbuWdZiAid6iHIm', 'super_admin', NULL, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-16 04:22:54', '2026-05-16 04:22:54'),
(4, 'CWO-230407', 'Radha Recidency Admin', 'radhares@gmail.com', '8908908900', '$2y$10$E6PFJv43UEMCMiVDNOJjd.BoLtXB4vLvxyWccH81t6x1wH672vjPW', 'admin', 1, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-16 05:32:05', '2026-05-18 08:26:51'),
(5, 'END-844532', 'Rahul Mehta', 'rahulmehta@gmail.com', '9087908941', '$2y$10$rsq.b.ZpoEMNX718hD35wOyWXXeXoJiT0WuHeQwX6lOtSBuaJFJV6', 'resident', 1, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-18 08:32:11', '2026-05-18 08:32:11'),
(6, 'KZL-130818', 'Manindar Singh', 'manindarsingh@gmail.com', '9046783541', '$2y$10$hote8BN82xqa4iDAL1EcDeTo2iuma/0vX.yx59Q5sV75rUNOuJEPS', 'resident', 1, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-18 11:40:43', '2026-05-18 11:40:43'),
(12, 'USR-854D07', 'Shivam Khule', 'shivamkhule27@gmail.com', '+918010155144', '$2y$10$tftqe/NXxic1ENJhmqKyt.SjTwcX1hluoAknFxI0SbiSUrjFyui0q', 'resident', 1, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRBEfARO257qVMn_zNmah764AqL_uKtq7jyz9wTQYY8Gg&s=10', 'active', 'https://amzn-s3-mygatebell.s3.us-east-1.amazonaws.com/profiles/uid12_1780488888_6a201ab88bbbf.jpg', 'owner', 'This is shivam khule\'s bio content.', 'Software Developer', 'Ahilyanagar', NULL, NULL, '2026-05-19 06:30:15', '2026-09-15 21:43:46'),
(13, 'USR-47CC19', 'Prathmesh Patil', 'ppatil@gmail.com', '+919087563142', '$2y$10$VmO1EeZQuk3xMNIxeIaSCufXr7saX5DJOvir1LNztMayt9JIdXfH2', 'resident', 1, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-19 06:32:03', '2026-05-19 06:32:03'),
(19, 'BEA-003670', 'Priya Mehta', 'priyam@gmail.com', '9876543234', '$2y$10$/aadU9VPOhkNFtnSqe2Zb.sxGlpG8lU40p4CTxV6zHK0NTXnJeZci', 'resident', 1, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-19 08:37:15', '2026-05-19 08:37:15'),
(20, 'USR-55CA6F', 'Priya Mehta 1', 'priyam1@gmail.com', '+919876543233', '$2y$10$7hXEYqyUrvXWwMMvkQIMP.KY1lahhbfweJ0W0TYTWQL8Gha9/Th86', 'resident', 1, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-19 09:52:46', '2026-05-19 09:52:46'),
(21, 'LWE-616107', 'Santosh Varma', 'santosh.varma@gmail.com', '8080808080', '$2y$10$/eeW5kMJ4VbnohxwKa3F4uiYrKrOjzPK3jsS202pFjAs.ej1fYGqm', 'guard', 1, 'https://amzn-s3-mygatebell.s3.us-east-1.amazonaws.com/profiles/uid21_1781526230_6a2feed6686d9.jpg', 'active', 'https://amzn-s3-mygatebell.s3.us-east-1.amazonaws.com/profiles/uid21_1781526232_6a2feed8654e2.jpg', NULL, 'Bio of Santosh.', 'Night Shift', 'Bihar', NULL, NULL, '2026-06-02 10:01:30', '2026-10-09 11:19:21'),
(22, 'MSC-997777', 'Vasu', '', '9393939393', '$2y$10$MfIW3LYBhOSh33ShiooOw.yAFFGg9ES1mV.aT7ZjZdhw84NiwcEkW', 'guard', 1, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-29 14:38:09', '2026-10-12 09:13:11'),
(23, 'BAT-375795', 'Kiran Sachdeva', 'abc@gmail.com', '9685741425', '$2y$10$L9SGKcq.cGiKzMg7LACvfeYIDWRIXlKe3ofVPWGzDEMfQ8nvRYh1i', 'resident', 1, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-31 08:29:42', '2026-10-12 17:24:17'),
(24, 'ZUJ-539768', 'Kiran Patil', 'kiran@gmail.com', '9685214236', '$2y$10$klshZtiJs/Q/Q5bHsaB59u9veO578lQ4dRkCJSbxrHJ.8YGTp8WQu', 'resident', 1, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-18 07:37:05', '2026-09-18 07:37:05'),
(25, 'ATF-213420', 'Ashutosh Malhotra', 'ashutosh@gmail.com', '9635214523', '$2y$10$9MgW9sDRcBVBSUgFGgPLZew45DRdNSUypj4atSzqIDtEHHxdtPPOS', 'resident', 1, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-18 07:39:17', '2026-09-18 07:39:17'),
(26, 'RYT-496101', 'Sneha Inamdar', 'sneha@gmail.com', '8541254796', '$2y$10$6W1GvGFb37vsP68GBAD/8eycYygT1CuXx.4C/.tawSF.dXOixWovO', 'resident', 1, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-18 07:40:07', '2026-09-18 07:40:07'),
(27, 'HOE-182195', 'Deva Pawar', 'deva@gmail.com', '8574123654', '$2y$10$iNKNEGDRZ98uROjg4B1TuOSrX/N34YucpwEOnJUdDWVhQ/.jzSdzC', 'guard', 1, NULL, 'active', NULL, NULL, '', '', '', NULL, NULL, '2026-09-18 07:42:35', '2026-10-09 15:15:17'),
(28, 'KPN-926451', 'Abhishek Jadhav', 'abhi@gmail.com', '7458963254', '$2y$10$5WxPkjMC50orBlSI5ha0Dujtf1nvjlY0vadm2jraldILFJT0l9/Ua', 'guard', 1, NULL, 'inactive', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-18 07:44:11', '2026-10-12 18:08:33'),
(35, 'FKX-132275', 'Sushma', 'sus@yahoo.com', '9632587412', '$2y$10$DFWsz6LsIMbDeCO7DmaRquO.kdgWjm10t/7IogfgjwSqNHyxFqGFK', 'resident', 1, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-12 08:46:57', '2026-10-12 08:46:57'),
(39, 'BQE-222740', 'rakesh', 's@yahoo.com', '9087654321', '$2y$10$vUyZZvAyaYURjNH/Uw2X6ebuvmAPIYILkYLPoz3SnKzUNU3Ro8KFm', 'guard', 1, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-12 13:54:10', '2026-10-12 13:54:10'),
(40, 'KBT-139760', 'ayyappa', 'ayyappa@gmail.com', '9096221819', '$2y$10$kfN1ptGaA6A9CQRknhG.0ecIXD2jBp.D5k1cwXTRWidEfz26wM8ti', 'guard', 1, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-12 13:58:17', '2026-10-12 13:58:17'),
(41, 'LAT-236097', 'sania ', 'dhs@gmail.com', '889239313910', '$2y$10$Ow.abXFTrOWqnl5QYoJtvuEv0x6jZZYAPuOFG.EdGbrbvF.VtHKaa', 'guard', 1, NULL, 'inactive', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-12 17:28:40', '2026-10-13 09:59:29'),
(42, 'LIZ-052145', 'Mahanti', 'r@gmail.com', '6789654321', '$2y$10$3tkQEyBfEp.oSS85VgXzC.Lg23QPNUpBlwKGPE8.NX/tTv5yQvNwG', 'guard', 1, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-12 17:49:37', '2026-10-12 17:49:37');

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
  `is_electric` tinyint(1) DEFAULT 0,
  `make` varchar(50) DEFAULT NULL,
  `model` varchar(50) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `registration_number` varchar(20) NOT NULL,
  `parking_spot` varchar(20) DEFAULT NULL,
  `is_parked` tinyint(1) DEFAULT 0,
  `society_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `resident_id`, `vehicle_type_id`, `is_electric`, `make`, `model`, `color`, `registration_number`, `parking_spot`, `is_parked`, `society_id`, `created_at`, `updated_at`, `image_url`) VALUES
(1, 4, 1, 0, 'Toyota', 'Camry', 'Black', 'MH01AB1234', 'P102', 0, 1, '2025-12-17 11:33:41', '2025-12-17 11:36:14', NULL),
(4, 12, 1, 0, 'Maruti Suzuki', 'Swift', 'Black', 'MH12JY3584', 'P-02', 0, 1, '2026-05-26 04:55:31', '2026-09-15 15:21:48', NULL),
(7, 12, 2, 1, 'Honda', 'Shine SP', 'Red-Black', 'MH12IB6926', 'P-03', 1, 1, '2026-05-26 05:07:19', '2026-06-05 05:20:25', NULL),
(8, 12, 1, 0, 'suv', 'Suv', 'white', '9000450372', 'a_13', 0, 1, '2026-07-18 10:39:35', '2026-07-18 10:39:35', NULL);

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
(1, 'Car', 'Four wheeler car', 1000.00),
(2, 'Bike', 'This is bike description.', 0.00),
(3, 'Scooty', 'This is scooty description.', 0.00);

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
-- Dumping data for table `visitors`
--

INSERT INTO `visitors` (`id`, `name`, `phone`, `email`, `purpose`, `visit_date`, `visit_time`, `expected_exit_time`, `actual_exit_time`, `status`, `visitor_type`, `resident_id`, `guard_id`, `society_id`, `image_url`, `qr_code`, `created_at`, `updated_at`) VALUES
(1, 'Visitor 1', '8936562781', NULL, 'Delivery', '2026-05-19', '15:17:31', NULL, '09:49:26', 'exited', 'delivery', 12, NULL, 1, NULL, NULL, '2026-05-19 09:47:31', '2026-05-25 08:55:54'),
(2, 'Visitor Name', '9876543210', 'visitor@example.com', 'Meeting', '2023-06-15', '10:00:00', '12:00:00', '06:52:20', 'exited', 'guest', 12, NULL, 1, 'https://example.com/image.jpg', NULL, '2026-05-25 07:39:00', '2026-09-16 12:22:19'),
(5, 'uhgdt jtf', '9685253685', NULL, 'Normal visit', '2026-05-30', '18:08:00', NULL, NULL, 'rejected', 'guest', 12, NULL, 1, NULL, NULL, '2026-05-29 12:38:31', '2026-06-17 04:11:54'),
(6, 'jhbrc heexvr', '9682147528', NULL, 'Casual visit', '2026-05-30', '01:00:00', NULL, '06:52:12', 'exited', 'delivery', 12, NULL, 1, NULL, NULL, '2026-05-30 05:22:24', '2026-09-16 12:22:11'),
(7, 'jhdvygjj vfwgiyg', '8574142536', NULL, 'Casual visit', '2026-05-31', '12:00:00', NULL, '06:51:59', 'exited', 'guest', 12, NULL, 1, NULL, NULL, '2026-05-30 06:51:55', '2026-09-16 12:21:58'),
(8, 'Ketan Patil', '96852', NULL, 'Casual meet', '2026-06-01', '12:39:00', NULL, '05:59:07', 'exited', 'service', 12, NULL, 1, NULL, NULL, '2026-05-30 07:09:48', '2026-09-15 15:16:36'),
(9, 'kjt ubvyct', '9636587415', NULL, 'jiutf hiijvycy onuhcy', '2026-05-30', '06:00:00', NULL, NULL, 'rejected', 'delivery', 12, NULL, 1, NULL, NULL, '2026-05-30 08:41:14', '2026-06-03 06:00:01'),
(10, 'Jsihes ksiehev', '9654254316', NULL, 'Attend Family function', '2026-06-04', '18:00:00', NULL, '06:51:41', 'exited', 'guest', 12, NULL, 1, 'https://amzn-s3-mygatebell.s3.us-east-1.amazonaws.com/visitors/uid12_1780490086_6a201f668eabe.jpg', NULL, '2026-06-03 12:36:03', '2026-09-16 12:21:40'),
(11, 'Ashok Kumar', '9087654567', NULL, 'Personal Visit', '2026-06-16', '15:15:46', NULL, '04:11:31', 'exited', 'guest', 12, NULL, 1, NULL, NULL, '2026-06-16 09:45:45', '2026-06-17 04:11:31'),
(12, 'Sanjay Jadhav', '9685741436', NULL, 'Family meet', '2026-06-17', '01:00:00', NULL, NULL, 'rejected', 'guest', 12, 21, 1, 'https://amzn-s3-mygatebell.s3.us-east-1.amazonaws.com/visitors/uid21_1781671584_6a3226a0a26e3.jpg', NULL, '2026-06-17 04:47:11', '2026-06-23 08:40:54'),
(13, 'Kishori Patil', '8052365741', NULL, 'Demo visitor', '2026-06-17', '00:00:00', NULL, '06:51:35', 'exited', 'delivery', 12, NULL, 1, 'https://amzn-s3-mygatebell.s3.us-east-1.amazonaws.com/visitors/uid12_1781671716_6a3227241cfd8.jpg', NULL, '2026-06-17 04:49:20', '2026-09-16 12:21:35'),
(14, 'John Doe', '1234567890', NULL, 'Meeting', '2026-06-23', '13:28:00', NULL, '06:51:22', 'exited', 'guest', 12, NULL, 1, NULL, NULL, '2026-06-23 07:58:40', '2026-09-16 12:21:21'),
(15, 'Jayesh Kadam', '8010155144', NULL, 'casual visit', '2026-07-08', '11:00:00', '15:00:00', '06:51:11', 'exited', 'delivery', 12, NULL, 1, 'https://amzn-s3-mygatebell.s3.us-east-1.amazonaws.com/visitors/uid12_1783485185_6a4dd30134629.jpg', NULL, '2026-07-08 04:33:41', '2026-09-16 12:21:10'),
(16, 'huftbhuyvhc buugycct', '8525362547', NULL, 'hbc hubyvct nyg', '2026-07-09', '10:05:00', NULL, '06:50:58', 'exited', 'delivery', 12, NULL, 1, 'https://amzn-s3-mygatebell.s3.us-east-1.amazonaws.com/visitors/uid12_1783485325_6a4dd38d7db4f.jpg', NULL, '2026-07-08 04:35:55', '2026-09-16 12:20:57'),
(19, 'abcd', '8564940598', NULL, 'homely visit', '2026-09-13', '15:29:13', NULL, '06:50:45', 'exited', 'guest', 12, NULL, 1, NULL, NULL, '2026-09-13 20:59:12', '2026-09-16 12:20:45'),
(20, 'hjk', '80085236125', NULL, 'delivery', '2026-09-15', '16:00:00', NULL, '06:50:28', 'exited', 'delivery', 12, NULL, 1, NULL, NULL, '2026-09-15 14:56:37', '2026-09-16 12:20:27'),
(45, 'lasmi', '2809431978', NULL, 'Maid', '2026-09-15', '11:43:58', NULL, '06:50:05', 'exited', 'service', 12, NULL, 1, NULL, NULL, '2026-09-15 17:13:57', '2026-09-16 12:20:04'),
(57, 'siriaha', '96325874168', NULL, 'casual', '2026-09-16', '10:05:00', NULL, '06:50:11', 'exited', 'guest', 12, NULL, 1, NULL, NULL, '2026-09-15 21:05:19', '2026-09-16 12:20:10'),
(82, 'shuvams', '9632587412', NULL, 'casual', '2026-09-16', '10:46:00', '11:12:00', '06:49:53', 'exited', 'guest', 12, 21, 1, NULL, NULL, '2026-09-16 10:47:22', '2026-09-16 12:19:52'),
(83, 'rao', '8523691478', NULL, 'delivery', '2026-09-16', '11:08:00', NULL, '06:49:41', 'exited', 'delivery', 12, 21, 1, NULL, NULL, '2026-09-16 11:08:51', '2026-09-16 12:19:40'),
(84, 'raos', '8523691470', NULL, 'service', '2026-09-16', '11:09:00', NULL, '06:49:25', 'exited', 'service', 12, 21, 1, NULL, NULL, '2026-09-16 11:09:51', '2026-09-16 12:19:25'),
(89, 'suaus', '8523691472', NULL, 'casual', '2026-09-16', '12:08:00', NULL, '06:49:07', 'exited', 'guest', 23, 21, 1, NULL, NULL, '2026-09-16 12:08:31', '2026-09-16 12:19:07'),
(90, 'syui', '8523691470', NULL, 'casual', '2026-09-16', '12:11:00', NULL, '06:48:54', 'exited', 'guest', 12, 21, 1, NULL, NULL, '2026-09-16 12:11:44', '2026-09-16 12:18:53'),
(91, 'rakesh', '9696968585', NULL, 'meet', '2026-09-16', '12:15:00', NULL, '06:48:38', 'exited', 'guest', 12, NULL, 1, NULL, NULL, '2026-09-16 12:16:07', '2026-09-16 12:18:38'),
(92, 'rao', '9632580741', NULL, 'guest', '2026-09-21', '19:33:00', NULL, '14:05:26', 'exited', 'guest', 24, NULL, 1, NULL, NULL, '2026-09-21 19:33:59', '2026-09-21 19:35:25'),
(93, 'kumar', '96325808521', NULL, 'guest', '2026-09-21', '19:40:00', NULL, '14:17:33', 'exited', 'guest', 24, 21, 1, NULL, NULL, '2026-09-21 19:40:37', '2026-09-21 19:47:32'),
(94, 'kumars', '98523614408', NULL, 'guest', '2026-09-21', '19:43:00', '19:45:00', '14:16:54', 'exited', 'guest', 24, NULL, 1, 'https://amzn-s3-mygatebell.s3.us-east-1.amazonaws.com/visitors/uid24_1789999964_6ab13b5cd4dbe.jpg', NULL, '2026-09-21 19:43:05', '2026-09-21 19:46:53'),
(95, 'khan', '90852325866', NULL, 'guest', '2026-09-21', '19:49:00', NULL, '14:20:36', 'exited', 'guest', 24, NULL, 1, NULL, NULL, '2026-09-21 19:49:42', '2026-09-21 19:50:35'),
(96, 'dad', '9632580741', NULL, 'guest', '2026-09-21', '19:54:00', NULL, '17:34:58', 'exited', 'guest', 24, 21, 1, NULL, NULL, '2026-09-21 19:54:19', '2026-09-21 23:04:58'),
(105, 'Astha Utagi', '9085697458', NULL, 'Casual visit', '2026-10-09', '11:00:00', NULL, NULL, 'entered', 'guest', 12, NULL, 1, 'https://amzn-s3-mygatebell.s3.us-east-1.amazonaws.com/visitors/uid12_1791439298_6ac731c2218ec.jpg', NULL, '2026-10-08 11:32:38', '2026-10-10 14:46:40'),
(106, 'Shankar Rathod', '8070587412', NULL, 'Normal visit', '2026-10-08', '01:30:00', NULL, NULL, 'approved', 'service', 12, NULL, 1, 'https://amzn-s3-mygatebell.s3.us-east-1.amazonaws.com/visitors/uid12_1791443718_6ac743064cabc.jpg', NULL, '2026-10-08 12:51:51', '2026-10-08 14:25:29'),
(107, 'sushma', '963258096336', NULL, 'guest', '2026-10-09', '15:16:00', NULL, '09:52:04', 'exited', 'guest', 25, 27, 1, NULL, NULL, '2026-10-09 15:16:44', '2026-10-09 15:22:04'),
(108, 'sunitja', '9469464926', NULL, 'tution', '2026-10-09', '15:22:00', NULL, NULL, 'approved', 'service', 6, 27, 1, NULL, NULL, '2026-10-09 15:22:54', '2026-10-09 15:23:01'),
(109, 'siri', '8521346796', NULL, 'guest', '2026-10-09', '10:02:47', NULL, NULL, 'entered', 'guest', 24, NULL, 1, NULL, NULL, '2026-10-09 15:32:47', '2026-10-12 14:29:20'),
(110, 'Test visitor 1', '9685254785', NULL, 'Casual meet', '2026-10-12', '15:00:00', NULL, '08:59:23', 'exited', 'guest', 12, NULL, 1, 'https://amzn-s3-mygatebell.s3.us-east-1.amazonaws.com/visitors/uid12_1791795387_6acca0bb155ab.jpg', NULL, '2026-10-12 14:27:49', '2026-10-12 14:29:22');

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
-- Indexes for table `community_posts`
--
ALTER TABLE `community_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `society_id` (`society_id`);

--
-- Indexes for table `community_post_comments`
--
ALTER TABLE `community_post_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `community_post_likes`
--
ALTER TABLE `community_post_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`post_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

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
-- Indexes for table `device_tokens`
--
ALTER TABLE `device_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_device` (`user_id`,`device_token`);

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
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `society_id` (`society_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `event_attendees`
--
ALTER TABLE `event_attendees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendee` (`event_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

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
-- Indexes for table `guard_attendance`
--
ALTER TABLE `guard_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_guard_date` (`guard_id`,`date`),
  ADD KEY `idx_society_date` (`society_id`,`date`);

--
-- Indexes for table `guard_vehicle_entries`
--
ALTER TABLE `guard_vehicle_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_society_status` (`society_id`,`status`),
  ADD KEY `idx_guard_id` (`guard_id`),
  ADD KEY `idx_vehicle_number` (`vehicle_number`),
  ADD KEY `fk_gve_resident` (`resident_id`);

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
  ADD UNIQUE KEY `unique_vote` (`poll_id`,`user_id`),
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
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_societies_registration_id` (`registration_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `amenity_bookings`
--
ALTER TABLE `amenity_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `charge_heads`
--
ALTER TABLE `charge_heads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `community_posts`
--
ALTER TABLE `community_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `community_post_comments`
--
ALTER TABLE `community_post_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `community_post_likes`
--
ALTER TABLE `community_post_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
-- AUTO_INCREMENT for table `device_tokens`
--
ALTER TABLE `device_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=449;

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
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `event_attendees`
--
ALTER TABLE `event_attendees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `family_members`
--
ALTER TABLE `family_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `financial_years`
--
ALTER TABLE `financial_years`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `flats`
--
ALTER TABLE `flats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
-- AUTO_INCREMENT for table `guard_attendance`
--
ALTER TABLE `guard_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `guard_vehicle_entries`
--
ALTER TABLE `guard_vehicle_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `marketplace_categories`
--
ALTER TABLE `marketplace_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=269;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `polls`
--
ALTER TABLE `polls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `poll_options`
--
ALTER TABLE `poll_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `poll_votes`
--
ALTER TABLE `poll_votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `receipts`
--
ALTER TABLE `receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `society_registrations`
--
ALTER TABLE `society_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `ticket_comments`
--
ALTER TABLE `ticket_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `vehicle_types`
--
ALTER TABLE `vehicle_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `visitors`
--
ALTER TABLE `visitors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

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
-- Constraints for table `community_posts`
--
ALTER TABLE `community_posts`
  ADD CONSTRAINT `community_posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_posts_ibfk_2` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `community_post_comments`
--
ALTER TABLE `community_post_comments`
  ADD CONSTRAINT `community_post_comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `community_posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_post_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `community_post_likes`
--
ALTER TABLE `community_post_likes`
  ADD CONSTRAINT `community_post_likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `community_posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_post_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_attendees`
--
ALTER TABLE `event_attendees`
  ADD CONSTRAINT `event_attendees_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_attendees_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `guard_attendance`
--
ALTER TABLE `guard_attendance`
  ADD CONSTRAINT `fk_ga_guard` FOREIGN KEY (`guard_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ga_society` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `guard_vehicle_entries`
--
ALTER TABLE `guard_vehicle_entries`
  ADD CONSTRAINT `fk_gve_guard` FOREIGN KEY (`guard_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gve_resident` FOREIGN KEY (`resident_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_gve_society` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `polls_ibfk_1` FOREIGN KEY (`society_id`) REFERENCES `societies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `polls_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `poll_votes_ibfk_2` FOREIGN KEY (`option_id`) REFERENCES `poll_options` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `poll_votes_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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

--
-- Table structure for table `daily_helpers`
--

CREATE TABLE IF NOT EXISTS `daily_helpers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `society_id` int(11) NOT NULL,
  `flat_id` int(11) NOT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `service_type` varchar(50) NOT NULL,
  `qr_code` varchar(100) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `id_proof_url` varchar(255) DEFAULT NULL,
  `vehicle_number` varchar(50) DEFAULT NULL,
  `expected_time_in` time DEFAULT NULL,
  `expected_time_out` time DEFAULT NULL,
  `working_days` varchar(50) DEFAULT 'All Days',
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_dh_qr_code` (`qr_code`),
  KEY `idx_dh_society` (`society_id`),
  KEY `idx_dh_flat` (`flat_id`),
  KEY `idx_dh_resident` (`resident_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `daily_helper_logs`
--

CREATE TABLE IF NOT EXISTS `daily_helper_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `daily_helper_id` int(11) NOT NULL,
  `society_id` int(11) NOT NULL,
  `flat_id` int(11) NOT NULL,
  `entry_time` datetime NOT NULL,
  `exit_time` datetime DEFAULT NULL,
  `guard_in_id` int(11) DEFAULT NULL,
  `guard_out_id` int(11) DEFAULT NULL,
  `status` enum('inside','exited') NOT NULL DEFAULT 'inside',
  `entry_method` enum('qr_scan','manual') DEFAULT 'qr_scan',
  `exit_method` enum('qr_scan','manual') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_dhl_helper` (`daily_helper_id`),
  KEY `idx_dhl_society` (`society_id`),
  KEY `idx_dhl_flat` (`flat_id`),
  KEY `idx_dhl_status` (`status`),
  KEY `idx_dhl_entry` (`entry_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

