CREATE TABLE `polls` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `society_id` INT NOT NULL,
  `created_by` INT NOT NULL,
  `question` TEXT NOT NULL,
  `poll_type` ENUM('public', 'secret') DEFAULT 'public',
  `is_active` TINYINT(1) DEFAULT 1,
  `starts_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `ends_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`society_id`) REFERENCES `societies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE `poll_options` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `poll_id` INT NOT NULL,
  `option_text` VARCHAR(255) NOT NULL,
  FOREIGN KEY (`poll_id`) REFERENCES `polls`(`id`) ON DELETE CASCADE
);

CREATE TABLE `poll_votes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `poll_id` INT NOT NULL,
  `option_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`poll_id`) REFERENCES `polls`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`option_id`) REFERENCES `poll_options`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_vote` (`poll_id`, `user_id`)
);
