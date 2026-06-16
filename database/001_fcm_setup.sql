CREATE TABLE IF NOT EXISTS `device_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `device_token` VARCHAR(255) NOT NULL,
    `device_type` ENUM('android', 'ios', 'web') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_device` (`user_id`, `device_token`)
);

ALTER TABLE `notifications` ADD COLUMN `type` VARCHAR(50) DEFAULT 'general' AFTER `message`;
ALTER TABLE `notifications` ADD COLUMN `reference_id` INT DEFAULT NULL AFTER `type`;
ALTER TABLE `notifications` ADD COLUMN `action_url` VARCHAR(255) DEFAULT NULL AFTER `reference_id`;
