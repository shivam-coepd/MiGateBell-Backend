<?php
// Migration runner to ensure daily_helpers and daily_helper_logs tables exist

require_once __DIR__ . '/../app/core/Database.php';
$dbConfig = require __DIR__ . '/../app/config/database.php';

try {
    $db = Database::connect($dbConfig);

    echo "Running Daily Helper Migrations...\n";

    // 1. Create daily_helpers table
    $sqlHelpers = "
    CREATE TABLE IF NOT EXISTS `daily_helpers` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `society_id` int(11) NOT NULL,
        `flat_id` int(11) NOT NULL,
        `resident_id` int(11) NULL,
        `name` varchar(100) NOT NULL,
        `phone` varchar(15) NOT NULL,
        `service_type` varchar(50) NOT NULL COMMENT 'Maid, Cook, Cleaner, Driver, Caretaker, Laundry, etc.',
        `qr_code` varchar(100) NOT NULL UNIQUE COMMENT 'Unique static QR token e.g. DHP-SOC1-FLAT10-XYZ',
        `image_url` varchar(255) NULL,
        `id_proof_url` varchar(255) NULL,
        `vehicle_number` varchar(50) NULL,
        `expected_time_in` time NULL,
        `expected_time_out` time NULL,
        `working_days` varchar(50) DEFAULT 'All Days',
        `is_active` tinyint(1) DEFAULT 1,
        `created_by` int(11) NULL,
        `created_at` timestamp NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_dh_society` (`society_id`),
        KEY `idx_dh_flat` (`flat_id`),
        KEY `idx_dh_resident` (`resident_id`),
        KEY `idx_dh_qr_code` (`qr_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $db->exec($sqlHelpers);
    echo "✓ Table `daily_helpers` verified/created successfully.\n";

    // 2. Create daily_helper_logs table
    $sqlLogs = "
    CREATE TABLE IF NOT EXISTS `daily_helper_logs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `daily_helper_id` int(11) NOT NULL,
        `society_id` int(11) NOT NULL,
        `flat_id` int(11) NOT NULL,
        `entry_time` datetime NOT NULL,
        `exit_time` datetime NULL,
        `guard_in_id` int(11) NULL,
        `guard_out_id` int(11) NULL,
        `status` enum('inside','exited') NOT NULL DEFAULT 'inside',
        `entry_method` enum('qr_scan','manual') DEFAULT 'qr_scan',
        `exit_method` enum('qr_scan','manual') DEFAULT NULL,
        `notes` text NULL,
        `created_at` timestamp NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_dhl_helper` (`daily_helper_id`),
        KEY `idx_dhl_society` (`society_id`),
        KEY `idx_dhl_flat` (`flat_id`),
        KEY `idx_dhl_status` (`status`),
        KEY `idx_dhl_entry` (`entry_time`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $db->exec($sqlLogs);
    echo "✓ Table `daily_helper_logs` verified/created successfully.\n";

    echo "Migration completed successfully!\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
