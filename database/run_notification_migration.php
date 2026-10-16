<?php
/**
 * Migration runner for Notifications & Device Tokens
 * Ensures tables exist in MySQL
 *
 * Usage via CLI: php database/run_notification_migration.php
 */

require_once __DIR__ . '/../app/core/Database.php';
$dbConfig = require __DIR__ . '/../app/config/database.php';

try {
    echo "Connecting to database [{$dbConfig['db']}] on [{$dbConfig['host']}]...\n";
    $db = Database::connect($dbConfig);

    echo "Running Notification & Device Token Migrations...\n";

    // 1. Create device_tokens table
    $sqlDeviceTokens = "
    CREATE TABLE IF NOT EXISTS `device_tokens` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `user_id` INT(11) NOT NULL,
      `device_token` VARCHAR(500) NOT NULL,
      `device_type` ENUM('android', 'ios', 'web') NOT NULL DEFAULT 'android',
      `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uq_user_device_token` (`user_id`, `device_token`(191)),
      KEY `idx_dt_user` (`user_id`),
      KEY `idx_dt_device_type` (`device_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $db->exec($sqlDeviceTokens);
    echo "✓ Table `device_tokens` verified / created successfully.\n";

    // 2. Create notifications table
    $sqlNotifications = "
    CREATE TABLE IF NOT EXISTS `notifications` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `user_id` INT(11) NOT NULL,
      `title` VARCHAR(255) NOT NULL,
      `message` TEXT NOT NULL,
      `type` VARCHAR(50) DEFAULT 'general',
      `reference_id` VARCHAR(100) DEFAULT NULL,
      `action_url` VARCHAR(255) DEFAULT NULL,
      `is_read` TINYINT(1) NOT NULL DEFAULT 0,
      `read_at` DATETIME DEFAULT NULL,
      `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_notif_user` (`user_id`),
      KEY `idx_notif_user_read` (`user_id`, `is_read`),
      KEY `idx_notif_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $db->exec($sqlNotifications);
    echo "✓ Table `notifications` verified / created successfully.\n";

    // 3. Ensure any missing columns if notifications table existed previously
    $columnsToCheck = [
        'type' => "VARCHAR(50) DEFAULT 'general' AFTER `message`",
        'reference_id' => "VARCHAR(100) DEFAULT NULL AFTER `type`",
        'action_url' => "VARCHAR(255) DEFAULT NULL AFTER `reference_id`",
        'is_read' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER `action_url`",
        'read_at' => "DATETIME DEFAULT NULL AFTER `is_read`",
    ];

    foreach ($columnsToCheck as $colName => $colDef) {
        try {
            $stmt = $db->query("SHOW COLUMNS FROM `notifications` LIKE '$colName'");
            if (!$stmt->fetch()) {
                $db->exec("ALTER TABLE `notifications` ADD COLUMN `$colName` $colDef");
                echo "  + Added missing column `$colName` to `notifications`.\n";
            }
        } catch (Exception $e) {
            // ignore if exists
        }
    }

    echo "\nNotification migration completed successfully!\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
