-- Migration 007: Create Notifications and Device Tokens tables for Push Notifications (FCM)
-- Project: MyGateBell

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

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `type` VARCHAR(50) DEFAULT 'general' COMMENT 'visitor_request, notice_created, alert, ticket_created, etc.',
  `reference_id` VARCHAR(100) DEFAULT NULL COMMENT 'ID of referenced visitor, notice, ticket, bill',
  `action_url` VARCHAR(255) DEFAULT NULL COMMENT 'Deep link or UI route e.g. /visitors, /resident-notifications',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `read_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`),
  KEY `idx_notif_user_read` (`user_id`, `is_read`),
  KEY `idx_notif_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
